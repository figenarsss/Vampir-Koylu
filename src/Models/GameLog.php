<?php

declare(strict_types=1);

namespace VampirKoylu\Models;

use DateTimeImmutable;

final readonly class GameLog
{
    public function __construct(
        public int $id,
        public int $gameId,
        public string $eventType,
        public string $message,
        public bool $secret,
        public DateTimeImmutable $createdAt,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['game_id'],
            (string) $row['event_type'],
            (string) $row['message'],
            (bool) $row['is_secret'],
            new DateTimeImmutable((string) $row['created_at']),
        );
    }
}
