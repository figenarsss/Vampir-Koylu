<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories;

use DateTimeImmutable;
use VampirKoylu\Models\Vote;

interface VoteRepositoryInterface
{
    public function cast(int $phaseId, int $voterPlayerId, ?int $targetPlayerId, DateTimeImmutable $createdAt): bool;

    public function forPhase(int $phaseId): array;
}
