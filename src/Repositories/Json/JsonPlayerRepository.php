<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Json;

use VampirKoylu\Models\DeathCause;
use VampirKoylu\Models\Player;
use VampirKoylu\Repositories\PlayerRepositoryInterface;

final class JsonPlayerRepository implements PlayerRepositoryInterface
{
    private readonly JsonTable $players;
    private readonly JsonTable $users;

    public function __construct(JsonStorage $storage)
    {
        $this->players = $storage->table('players');
        $this->users = $storage->table('users');
    }

    public function add(int $gameId, int $userId): Player
    {
        $row = $this->players->insert([
            'game_id' => $gameId,
            'user_id' => $userId,
            'role_id' => null,
            'is_alive' => 1,
            'death_cause' => null,
            'death_phase_id' => null,
        ]);

        return Player::fromRow($this->withName($row));
    }

    public function remove(int $playerId): void
    {
        $this->players->deleteWhere(static fn (array $row): bool => (int) $row['id'] === $playerId);
    }

    public function findById(int $playerId): ?Player
    {
        $row = $this->players->find($playerId);

        return $row === null ? null : Player::fromRow($this->withName($row));
    }

    public function findByGameAndUser(int $gameId, int $userId): ?Player
    {
        $rows = $this->players->where(
            static fn (array $row): bool => (int) $row['game_id'] === $gameId && (int) $row['user_id'] === $userId,
        );

        return $rows === [] ? null : Player::fromRow($this->withName($rows[0]));
    }

    public function listByGame(int $gameId): array
    {
        $rows = $this->players->where(static fn (array $row): bool => (int) $row['game_id'] === $gameId);

        return array_map(fn (array $row): Player => Player::fromRow($this->withName($row)), $rows);
    }

    public function countByGame(int $gameId): int
    {
        return count($this->players->where(static fn (array $row): bool => (int) $row['game_id'] === $gameId));
    }

    public function assignRoles(array $roles): void
    {
        foreach ($roles as $playerId => $role) {
            $this->players->update($playerId, ['role_id' => $role->id()]);
        }
    }

    public function markDead(int $playerId, DeathCause $cause, int $phaseId): void
    {
        $this->players->update($playerId, [
            'is_alive' => 0,
            'death_cause' => $cause->value,
            'death_phase_id' => $phaseId,
        ]);
    }

    private function withName(array $row): array
    {
        $user = $this->users->find((int) $row['user_id']);
        $row['name'] = $user['name'] ?? '?';

        return $row;
    }
}
