<?php

declare(strict_types=1);

namespace VampirKoylu\Game\TargetRules;

use VampirKoylu\Models\Player;

final class VampireRule implements TargetRule
{
    public function violation(Player $actor, Player $target, ?int $lastNightTargetId): ?string
    {
        return $actor->id === $target->id ? 'Kendini kurban seçemezsin.' : null;
    }
}
