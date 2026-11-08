<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Json;

use DateTimeImmutable;
use VampirKoylu\Models\Phase;
use VampirKoylu\Models\PhaseType;
use VampirKoylu\Repositories\PhaseRepositoryInterface;
use VampirKoylu\Support\DbDate;

final class JsonPhaseRepository implements PhaseRepositoryInterface
{
    private readonly JsonTable $phases;

    public function __construct(JsonStorage $storage)
    {
        $this->phases = $storage->table('phases');
    }

    public function create(
        int $gameId,
        PhaseType $type,
        int $dayNumber,
        DateTimeImmutable $startedAt,
        DateTimeImmutable $endsAt,
    ): Phase {
        $current = $this->current($gameId);

        return Phase::fromRow($this->phases->insert([
            'game_id' => $gameId,
            'sequence_no' => ($current?->sequenceNo ?? 0) + 1,
            'phase_type' => $type->value,
            'day_number' => $dayNumber,
            'started_at' => DbDate::format($startedAt),
            'ends_at' => DbDate::format($endsAt),
        ]));
    }

    public function current(int $gameId): ?Phase
    {
        $rows = $this->phases->where(static fn (array $row): bool => (int) $row['game_id'] === $gameId);
        if ($rows === []) {
            return null;
        }
        usort($rows, static fn (array $a, array $b): int => (int) $b['sequence_no'] <=> (int) $a['sequence_no']);

        return Phase::fromRow($rows[0]);
    }

    public function findById(int $phaseId): ?Phase
    {
        $row = $this->phases->find($phaseId);

        return $row === null ? null : Phase::fromRow($row);
    }
}
