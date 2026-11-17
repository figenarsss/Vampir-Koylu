<?php

declare(strict_types=1);

namespace VampirKoylu\Game;

use Random\Randomizer;
use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Models\RoleType;

final class RoleDistributor
{
    public function __construct(
        private readonly Randomizer $random,
        private readonly GameRules $rules,
    ) {
    }

    public function distribute(array $playerIds, int $vampires, int $trappers): array
    {
        $count = count($playerIds);
        if ($count < $this->rules->minPlayers || $count > $this->rules->maxPlayers) {
            throw new RuleViolationException(
                "Oyun {$this->rules->minPlayers} ile {$this->rules->maxPlayers} oyuncu arasında başlatılabilir.",
            );
        }
        $this->rules->assertValidSettings($count, $vampires, $trappers);

        $villagers = $count - $vampires - $trappers - 2;
        $roles = [
            ...array_fill(0, $vampires, RoleType::Vampire),
            ...array_fill(0, $trappers, RoleType::Trapper),
            RoleType::Doctor,
            RoleType::Seer,
            ...array_fill(0, $villagers, RoleType::Villager),
        ];

        return array_combine($playerIds, $this->random->shuffleArray($roles));
    }
}
