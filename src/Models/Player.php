<?php

declare(strict_types=1);

namespace VampirKoylu\Models;

final readonly class Player
{
    public function __construct(
        public int $id,
        public int $gameId,
        public int $userId,
        public string $name,
        public ?RoleType $role,
        public bool $alive,
        public ?DeathCause $deathCause,
        public ?int $deathPhaseId,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['game_id'],
            (int) $row['user_id'],
            (string) $row['name'],
            isset($row['role_id']) ? RoleType::fromId((int) $row['role_id']) : null,
            (bool) $row['is_alive'],
            isset($row['death_cause']) ? DeathCause::from((string) $row['death_cause']) : null,
            isset($row['death_phase_id']) ? (int) $row['death_phase_id'] : null,
        );
    }

    public function isVampire(): bool
    {
        return $this->role === RoleType::Vampire;
    }

    public function isRoleRevealed(): bool
    {
        // Kâhinin rolü, nasıl ölürse ölsün herkese açıklanır
        return $this->deathCause === DeathCause::Vote || (!$this->alive && $this->role === RoleType::Seer);
    }
}
