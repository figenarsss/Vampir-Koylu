<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories;

use VampirKoylu\Models\DeathCause;
use VampirKoylu\Models\Player;
use VampirKoylu\Models\RoleType;

interface PlayerRepositoryInterface
{
    public function add(int $gameId, int $userId): Player;

    public function remove(int $playerId): void;

    public function findById(int $playerId): ?Player;

    public function findByGameAndUser(int $gameId, int $userId): ?Player;

    public function listByGame(int $gameId): array;

    public function countByGame(int $gameId): int;

    public function assignRoles(array $roles): void;

    public function markDead(int $playerId, DeathCause $cause, int $phaseId): void;
}
