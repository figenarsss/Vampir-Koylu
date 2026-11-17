<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Pdo;

use DateTimeImmutable;
use VampirKoylu\Models\ChatMessage;
use VampirKoylu\Repositories\MessageRepositoryInterface;
use VampirKoylu\Support\DbDate;

final class PdoMessageRepository extends PdoRepository implements MessageRepositoryInterface
{
    public function add(int $gameId, int $userId, string $body, DateTimeImmutable $createdAt): void
    {
        $this->execute(
            'INSERT INTO messages (game_id, user_id, body, created_at) VALUES (?, ?, ?, ?)',
            [$gameId, $userId, $body, DbDate::format($createdAt)],
        );
    }

    public function latestByGame(int $gameId, int $limit): array
    {
        // LIMIT parametresi tamsayıya zorlanır; son mesajlar alınıp eskiden yeniye çevrilir
        $limit = max(1, $limit);
        $rows = $this->fetchAll(
            "SELECT m.*, u.name AS user_name FROM messages m
             INNER JOIN users u ON u.id = m.user_id
             WHERE m.game_id = ? ORDER BY m.id DESC LIMIT {$limit}",
            [$gameId],
        );

        return array_map(ChatMessage::fromRow(...), array_reverse($rows));
    }
}
