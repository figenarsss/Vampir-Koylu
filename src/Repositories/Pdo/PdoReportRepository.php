<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Pdo;

use VampirKoylu\Models\GameStatus;
use VampirKoylu\Models\Team;
use VampirKoylu\Repositories\ReportRepositoryInterface;

final class PdoReportRepository extends PdoRepository implements ReportRepositoryInterface
{
    public function overview(): array
    {
        $statusCounts = [];
        foreach ($this->fetchAll('SELECT status, COUNT(*) AS total FROM games GROUP BY status') as $row) {
            $statusCounts[(string) $row['status']] = (int) $row['total'];
        }

        $winCounts = [];
        $wins = $this->fetchAll(
            'SELECT winner_team, COUNT(*) AS total FROM games WHERE status = ? GROUP BY winner_team',
            [GameStatus::Finished->value],
        );
        foreach ($wins as $row) {
            $winCounts[(string) $row['winner_team']] = (int) $row['total'];
        }

        $users = $this->fetchOne('SELECT COUNT(*) AS total FROM users');
        $average = $this->fetchOne(
            'SELECT AVG(player_total) AS average_players
             FROM (SELECT COUNT(*) AS player_total FROM players GROUP BY game_id) AS per_game',
        );

        return [
            'users' => (int) ($users['total'] ?? 0),
            'games' => array_sum($statusCounts),
            'lobby' => $statusCounts[GameStatus::Lobby->value] ?? 0,
            'in_progress' => $statusCounts[GameStatus::InProgress->value] ?? 0,
            'finished' => $statusCounts[GameStatus::Finished->value] ?? 0,
            'villager_wins' => $winCounts[Team::Villagers->value] ?? 0,
            'vampire_wins' => $winCounts[Team::Vampires->value] ?? 0,
            'average_players' => round((float) ($average['average_players'] ?? 0), 1),
        ];
    }

    public function leaderboard(int $limit): array
    {
        // 4 tablo JOIN: oyuncunun takımı kazanan takımsa galibiyet
        $rows = $this->fetchAll(
            'SELECT u.name AS name,
                    COUNT(*) AS games,
                    SUM(CASE WHEN r.team = g.winner_team THEN 1 ELSE 0 END) AS wins
             FROM players p
             INNER JOIN users u ON u.id = p.user_id
             INNER JOIN games g ON g.id = p.game_id
             INNER JOIN roles r ON r.id = p.role_id
             WHERE g.status = ?
             GROUP BY u.id, u.name
             ORDER BY wins DESC, games DESC, u.name
             LIMIT ' . max(1, $limit),
            [GameStatus::Finished->value],
        );

        return array_map(
            static fn (array $row): array => [
                'name' => (string) $row['name'],
                'games' => (int) $row['games'],
                'wins' => (int) $row['wins'],
            ],
            $rows,
        );
    }
}
