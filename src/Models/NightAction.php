<?php

declare(strict_types=1);

namespace VampirKoylu\Models;

final readonly class NightAction
{
    public function __construct(
        public int $id,
        public int $phaseId,
        public int $actorPlayerId,
        public int $targetPlayerId,
        public ActionType $type,
        public bool $cancelled,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['phase_id'],
            (int) $row['actor_player_id'],
            (int) $row['target_player_id'],
            ActionType::from((string) $row['action_type']),
            (bool) $row['is_cancelled'],
        );
    }
}
