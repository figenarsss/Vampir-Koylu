<?php

declare(strict_types=1);

namespace VampirKoylu\Game\TargetRules;

use VampirKoylu\Models\Player;

interface TargetRule
{
    public function violation(Player $actor, Player $target, ?int $lastNightTargetId): ?string;
}
