<?php

declare(strict_types=1);

namespace VampirKoylu\Models;

use DateTimeImmutable;

final readonly class Game
{
    public function __construct(
        public int $id,
        public string $roomCode,
        public int $hostUserId,
        public GameStatus $status,
        public int $vampireCount,
        public int $trapperCount,
        public ?Team $winnerTeam,
        public int $version,
        public DateTimeImmutable $createdAt,
        public ?string $rematchCode = null,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['room_code'],
            (int) $row['host_user_id'],
            GameStatus::from((string) $row['status']),
            (int) $row['vampire_count'],
            (int) $row['trapper_count'],
            isset($row['winner_team']) ? Team::from((string) $row['winner_team']) : null,
            (int) $row['version'],
            new DateTimeImmutable((string) $row['created_at']),
            isset($row['rematch_code']) && $row['rematch_code'] !== '' ? (string) $row['rematch_code'] : null,
        );
    }

    public function isHost(User $user): bool
    {
        return $this->hostUserId === $user->id;
    }
}
