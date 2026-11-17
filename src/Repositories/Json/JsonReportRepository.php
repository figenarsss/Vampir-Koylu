<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Json;

use VampirKoylu\Models\GameStatus;
use VampirKoylu\Models\RoleType;
use VampirKoylu\Models\Team;
use VampirKoylu\Repositories\ReportRepositoryInterface;

final class JsonReportRepository implements ReportRepositoryInterface
{
    private readonly JsonTable $users;
    private readonly JsonTable $games;
    private readonly JsonTable $players;

    public function __construct(JsonStorage $storage)
    {
        $this->users = $storage->table('users');
        $this->games = $storage->table('games');
        $this->players = $storage->table('players');
    }

    public function overview(): array
    {
        $games = $this->games->all();
        $countBy = static fn (callable $filter): int => count(array_filter($games, $filter));

        $perGame = array_count_values(array_map('intval', array_column($this->players->all(), 'game_id')));
        $average = $perGame === [] ? 0.0 : array_sum($perGame) / count($perGame);

        $finished = static fn (array $g): bool => $g['status'] === GameStatus::Finished->value;

        return [
            'users' => count($this->users->all()),
            'games' => count($games),
            'lobby' => $countBy(static fn (array $g): bool => $g['status'] === GameStatus::Lobby->value),
            'in_progress' => $countBy(static fn (array $g): bool => $g['status'] === GameStatus::InProgress->value),
            'finished' => $countBy($finished),
            'villager_wins' => $countBy(static fn (array $g): bool => $finished($g) && $g['winner_team'] === Team::Villagers->value),
            'vampire_wins' => $countBy(static fn (array $g): bool => $finished($g) && $g['winner_team'] === Team::Vampires->value),
            'average_players' => round($average, 1),
        ];
    }

    public function leaderboard(int $limit): array
    {
        $names = array_column($this->users->all(), 'name', 'id');
        $winners = [];
        foreach ($this->games->all() as $game) {
            if ($game['status'] === GameStatus::Finished->value) {
                $winners[(int) $game['id']] = $game['winner_team'];
            }
        }

        $stats = [];
        foreach ($this->players->all() as $player) {
            $gameId = (int) $player['game_id'];
            if (!isset($winners[$gameId]) || $player['role_id'] === null) {
                continue;
            }
            $userId = (int) $player['user_id'];
            $stats[$userId] ??= ['name' => (string) ($names[$userId] ?? '?'), 'games' => 0, 'wins' => 0];
            $stats[$userId]['games']++;
            if (RoleType::fromId((int) $player['role_id'])->team()->value === $winners[$gameId]) {
                $stats[$userId]['wins']++;
            }
        }

        $rows = array_values($stats);
        usort($rows, static fn (array $a, array $b): int => [$b['wins'], $b['games'], $a['name']] <=> [$a['wins'], $a['games'], $b['name']]);

        return array_slice($rows, 0, max(1, $limit));
    }
}
