<?php

declare(strict_types=1);

namespace VampirKoylu\Models;

final readonly class Vote
{
    public function __construct(
        public int $id,
        public int $phaseId,
        public int $voterPlayerId,
        public ?int $targetPlayerId,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['phase_id'],
            (int) $row['voter_player_id'],
            isset($row['target_player_id']) ? (int) $row['target_player_id'] : null,
        );
    }

    public function isPass(): bool
    {
        return $this->targetPlayerId === null;
    }
}
