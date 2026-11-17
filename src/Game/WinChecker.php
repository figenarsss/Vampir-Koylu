<?php

declare(strict_types=1);

namespace VampirKoylu\Game;

use VampirKoylu\Models\Player;
use VampirKoylu\Models\Team;

final class WinChecker
{
    public function check(array $players): ?Team
    {
        $aliveVampires = 0;
        $aliveVillagers = 0;

        foreach ($players as $player) {
            if (!$player->alive) {
                continue;
            }
            $player->isVampire() ? $aliveVampires++ : $aliveVillagers++;
        }

        if ($aliveVampires === 0) {
            return Team::Villagers;
        }
        if ($aliveVampires >= $aliveVillagers) {
            return Team::Vampires;
        }

        return null;
    }
}
