<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Pdo;

use DateTimeImmutable;
use VampirKoylu\Models\GameLog;
use VampirKoylu\Repositories\GameLogRepositoryInterface;
use VampirKoylu\Support\DbDate;

final class PdoGameLogRepository extends PdoRepository implements GameLogRepositoryInterface
{
    public function add(int $gameId, string $eventType, string $message, bool $secret, DateTimeImmutable $createdAt): void
    {
        $this->execute(
            'INSERT INTO game_logs (game_id, event_type, message, is_secret, created_at) VALUES (?, ?, ?, ?, ?)',
            [$gameId, $eventType, $message, $secret ? 1 : 0, DbDate::format($createdAt)],
        );
    }

    public function listByGame(int $gameId, bool $includeSecret): array
    {
        $sql = 'SELECT * FROM game_logs WHERE game_id = ?';
        if (!$includeSecret) {
            $sql .= ' AND is_secret = 0';
        }

        return array_map(GameLog::fromRow(...), $this->fetchAll($sql . ' ORDER BY id', [$gameId]));
    }
}
