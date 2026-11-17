<?php

declare(strict_types=1);

namespace VampirKoylu\Models;

use DateTimeImmutable;

final readonly class Phase
{
    public function __construct(
        public int $id,
        public int $gameId,
        public int $sequenceNo,
        public PhaseType $type,
        public int $dayNumber,
        public DateTimeImmutable $startedAt,
        public DateTimeImmutable $endsAt,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['game_id'],
            (int) $row['sequence_no'],
            PhaseType::from((string) $row['phase_type']),
            (int) $row['day_number'],
            new DateTimeImmutable((string) $row['started_at']),
            new DateTimeImmutable((string) $row['ends_at']),
        );
    }
}
