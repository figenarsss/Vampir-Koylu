<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Json;

use DateTimeImmutable;
use VampirKoylu\Models\Game;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Models\Team;
use VampirKoylu\Repositories\GameRepositoryInterface;
use VampirKoylu\Support\DbDate;

final class JsonGameRepository implements GameRepositoryInterface
{
    private readonly JsonTable $games;
    private readonly JsonTable $users;
    private readonly JsonTable $players;
    private readonly JsonTable $phases;
    private readonly JsonTable $actions;
    private readonly JsonTable $votes;
    private readonly JsonTable $logs;

    public function __construct(JsonStorage $storage)
    {
        $this->games = $storage->table('games');
        $this->users = $storage->table('users');
        $this->players = $storage->table('players');
        $this->phases = $storage->table('phases');
        $this->actions = $storage->table('actions');
        $this->votes = $storage->table('votes');
        $this->logs = $storage->table('game_logs');
    }

    public function create(string $roomCode, int $hostUserId, DateTimeImmutable $createdAt): Game
    {
        return Game::fromRow($this->games->insert([
            'room_code' => $roomCode,
            'host_user_id' => $hostUserId,
            'status' => GameStatus::Lobby->value,
            'vampire_count' => 0,
            'trapper_count' => 0,
            'winner_team' => null,
            'version' => 0,
            'created_at' => DbDate::format($createdAt),
        ]));
    }

    public function findById(int $id): ?Game
    {
        $row = $this->games->find($id);

        return $row === null ? null : Game::fromRow($row);
    }

    public function findByCode(string $roomCode): ?Game
    {
        $rows = $this->games->where(static fn (array $row): bool => $row['room_code'] === $roomCode);

        return $rows === [] ? null : Game::fromRow($rows[0]);
    }

    public function codeExists(string $roomCode): bool
    {
        return $this->findByCode($roomCode) !== null;
    }

    public function updateRoleSettings(int $id, int $vampireCount, int $trapperCount): bool
    {
        return $this->games->mutate(static function (array &$data) use ($id, $vampireCount, $trapperCount): bool {
            if (($data['rows'][$id]['status'] ?? null) !== GameStatus::Lobby->value) {
                return false;
            }
            $data['rows'][$id]['vampire_count'] = $vampireCount;
            $data['rows'][$id]['trapper_count'] = $trapperCount;

            return true;
        });
    }

    public function markStarted(int $id, int $vampireCount, int $trapperCount): bool
    {
        return $this->games->mutate(static function (array &$data) use ($id, $vampireCount, $trapperCount): bool {
            if (($data['rows'][$id]['status'] ?? null) !== GameStatus::Lobby->value) {
                return false;
            }
            $data['rows'][$id]['status'] = GameStatus::InProgress->value;
            $data['rows'][$id]['vampire_count'] = $vampireCount;
            $data['rows'][$id]['trapper_count'] = $trapperCount;

            return true;
        });
    }

    public function bumpVersion(int $id, int $expectedVersion): bool
    {
        return $this->games->mutate(static function (array &$data) use ($id, $expectedVersion): bool {
            $row = $data['rows'][$id] ?? null;
            if ($row === null || (int) $row['version'] !== $expectedVersion) {
                return false;
            }
            $data['rows'][$id]['version'] = $expectedVersion + 1;

            return true;
        });
    }

    public function finish(int $id, Team $winner): void
    {
        $this->games->update($id, ['status' => GameStatus::Finished->value, 'winner_team' => $winner->value]);
    }

    public function delete(int $id): void
    {
        $phaseIds = array_column($this->phases->where(static fn (array $row): bool => (int) $row['game_id'] === $id), 'id');

        $this->votes->deleteWhere(static fn (array $row): bool => in_array((int) $row['phase_id'], $phaseIds, true));
        $this->actions->deleteWhere(static fn (array $row): bool => in_array((int) $row['phase_id'], $phaseIds, true));
        $this->phases->deleteWhere(static fn (array $row): bool => (int) $row['game_id'] === $id);
        $this->players->deleteWhere(static fn (array $row): bool => (int) $row['game_id'] === $id);
        $this->logs->deleteWhere(static fn (array $row): bool => (int) $row['game_id'] === $id);
        $this->games->deleteWhere(static fn (array $row): bool => (int) $row['id'] === $id);
    }

    public function search(?string $term, ?GameStatus $status, ?int $userId): array
    {
        $hostNames = array_column($this->users->all(), 'name', 'id');
        $players = $this->players->all();

        $result = [];
        foreach ($this->games->all() as $game) {
            $hostName = (string) ($hostNames[$game['host_user_id']] ?? '');

            if ($status !== null && $game['status'] !== $status->value) {
                continue;
            }
            if ($term !== null && $term !== ''
                && mb_stripos((string) $game['room_code'], $term) === false
                && mb_stripos($hostName, $term) === false) {
                continue;
            }

            $gamePlayers = array_filter($players, static fn (array $p): bool => (int) $p['game_id'] === (int) $game['id']);
            if ($userId !== null && !in_array($userId, array_map('intval', array_column($gamePlayers, 'user_id')), true)) {
                continue;
            }

            $result[] = [
                'id' => $game['id'],
                'room_code' => $game['room_code'],
                'status' => $game['status'],
                'winner_team' => $game['winner_team'],
                'created_at' => $game['created_at'],
                'host_name' => $hostName,
                'player_count' => count($gamePlayers),
            ];
        }

        return array_reverse($result);
    }
}
