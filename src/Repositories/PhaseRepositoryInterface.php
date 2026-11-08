<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories;

use DateTimeImmutable;
use VampirKoylu\Models\Phase;
use VampirKoylu\Models\PhaseType;

interface PhaseRepositoryInterface
{
    public function create(
        int $gameId,
        PhaseType $type,
        int $dayNumber,
        DateTimeImmutable $startedAt,
        DateTimeImmutable $endsAt,
    ): Phase;

    public function current(int $gameId): ?Phase;

    public function findById(int $phaseId): ?Phase;
}
