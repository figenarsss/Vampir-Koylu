<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Support;

use VampirKoylu\Models\ActionType;
use VampirKoylu\Models\DeathCause;
use VampirKoylu\Models\NightAction;
use VampirKoylu\Models\Player;
use VampirKoylu\Models\RoleType;
use VampirKoylu\Models\Vote;

final class Make
{
    public static function player(int $id, RoleType $role, bool $alive = true): Player
    {
        return new Player($id, 1, $id, "Oyuncu{$id}", $role, $alive, $alive ? null : DeathCause::Night, null);
    }

    public static function players(array $roles): array
    {
        $players = [];
        foreach ($roles as $i => $role) {
            $players[] = self::player($i + 1, $role);
        }

        return $players;
    }

    public static function vote(int $voterId, ?int $targetId): Vote
    {
        return new Vote($voterId * 10, 1, $voterId, $targetId);
    }

    public static function action(int $actorId, int $targetId, ActionType $type): NightAction
    {
        return new NightAction($actorId * 10, 1, $actorId, $targetId, $type, false);
    }
}
