<?php

declare(strict_types=1);

namespace VampirKoylu\Game;

use DateTimeImmutable;
use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Models\Phase;
use VampirKoylu\Models\PhaseType;

final readonly class GameRules
{
    public const TRAPPER_MIN_PLAYERS = 8;

    public function __construct(
        public int $minPlayers = 5,
        public int $maxPlayers = 12,
        public int $nightSeconds = 25,
        public int $nightSelectionSeconds = 15,
        public int $daySeconds = 75,
        public int $voteSeconds = 30,
    ) {
    }

    public static function fromConfig(array $sureler): self
    {
        $defaults = new self();

        return new self(
            nightSeconds: $sureler['gece'] ?? $defaults->nightSeconds,
            nightSelectionSeconds: $sureler['gece_secim'] ?? $defaults->nightSelectionSeconds,
            daySeconds: $sureler['gunduz'] ?? $defaults->daySeconds,
            voteSeconds: $sureler['oylama'] ?? $defaults->voteSeconds,
        );
    }

    public function defaultVampireCount(int $playerCount): int
    {
        return match (true) {
            $playerCount >= 11 => 3,
            $playerCount >= 8 => 2,
            default => 1,
        };
    }

    public function vampireOptions(int $playerCount): array
    {
        return $playerCount >= self::TRAPPER_MIN_PLAYERS ? [1, 2, 3] : [1];
    }

    public function trapperOptions(int $playerCount): array
    {
        return $playerCount >= self::TRAPPER_MIN_PLAYERS ? [0, 1, 2] : [0];
    }

    public function resolveVampireCount(int $stored, int $playerCount): int
    {
        return in_array($stored, $this->vampireOptions($playerCount), true) ? $stored : $this->defaultVampireCount($playerCount);
    }

    public function resolveTrapperCount(int $stored, int $playerCount): int
    {
        return in_array($stored, $this->trapperOptions($playerCount), true) ? $stored : 0;
    }

    public function assertValidSettings(int $playerCount, int $vampires, int $trappers): void
    {
        $vampireOptions = $this->vampireOptions($playerCount);
        if (!in_array($vampires, $vampireOptions, true)) {
            throw new RuleViolationException("{$playerCount} oyunculu odada vampir sayısı şunlardan biri olabilir: " . implode(', ', $vampireOptions) . '.');
        }

        $trapperOptions = $this->trapperOptions($playerCount);
        if (!in_array($trappers, $trapperOptions, true)) {
            throw new RuleViolationException("{$playerCount} oyunculu odada tuzakçı sayısı şunlardan biri olabilir: " . implode(', ', $trapperOptions) . '.');
        }
    }

    public function durationOf(PhaseType $type): int
    {
        return match ($type) {
            PhaseType::Night => $this->nightSeconds,
            PhaseType::Day => $this->daySeconds,
            PhaseType::Vote => $this->voteSeconds,
        };
    }

    public function nightSelectionEndsAt(Phase $nightPhase): DateTimeImmutable
    {
        return $nightPhase->startedAt->modify("+{$this->nightSelectionSeconds} seconds");
    }
}
