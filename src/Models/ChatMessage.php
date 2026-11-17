<?php

declare(strict_types=1);

namespace VampirKoylu\Models;

use DateTimeImmutable;

final readonly class ChatMessage
{
    public function __construct(
        public int $id,
        public int $gameId,
        public int $userId,
        public string $userName,
        public string $body,
        public DateTimeImmutable $createdAt,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['game_id'],
            (int) $row['user_id'],
            (string) ($row['user_name'] ?? ''),
            (string) $row['body'],
            new DateTimeImmutable((string) $row['created_at']),
        );
    }
}
