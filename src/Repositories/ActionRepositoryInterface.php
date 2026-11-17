<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories;

use DateTimeImmutable;
use VampirKoylu\Models\ActionType;
use VampirKoylu\Models\NightAction;

interface ActionRepositoryInterface
{
    public function add(
        int $phaseId,
        int $actorPlayerId,
        int $targetPlayerId,
        ActionType $type,
        DateTimeImmutable $createdAt,
    ): bool;

    public function markCancelled(array $actionIds): void;

    public function forPhase(int $phaseId): array;

    public function forActor(int $actorPlayerId): array;
}
