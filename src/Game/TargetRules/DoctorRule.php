<?php

declare(strict_types=1);

namespace VampirKoylu\Game\TargetRules;

use VampirKoylu\Models\Player;

final class DoctorRule implements TargetRule
{
    public function violation(Player $actor, Player $target, ?int $lastNightTargetId): ?string
    {
        $selfAgain = $target->id === $actor->id && $lastNightTargetId === $actor->id;

        return $selfAgain ? 'Art arda iki gece kendini koruyamazsın.' : null;
    }
}
