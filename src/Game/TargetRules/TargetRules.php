<?php

declare(strict_types=1);

namespace VampirKoylu\Game\TargetRules;

use VampirKoylu\Models\RoleType;

final class TargetRules
{
    public static function forRole(RoleType $role): ?TargetRule
    {
        return match ($role) {
            RoleType::Vampire => new VampireRule(),
            RoleType::Doctor => new DoctorRule(),
            RoleType::Seer => new SeerRule(),
            RoleType::Trapper => new TrapperRule(),
            RoleType::Villager => null,
        };
    }
}
