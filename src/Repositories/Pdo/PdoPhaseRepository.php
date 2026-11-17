<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Pdo;

use DateTimeImmutable;
use VampirKoylu\Models\Phase;
use VampirKoylu\Models\PhaseType;
use VampirKoylu\Repositories\PhaseRepositoryInterface;
use VampirKoylu\Support\DbDate;

final class PdoPhaseRepository extends PdoRepository implements PhaseRepositoryInterface
{
    public function create(
        int $gameId,
        PhaseType $type,
        int $dayNumber,
        DateTimeImmutable $startedAt,
        DateTimeImmutable $endsAt,
    ): Phase {
        $last = $this->fetchOne('SELECT MAX(sequence_no) AS last_no FROM phases WHERE game_id = ?', [$gameId]);
        $sequenceNo = (int) ($last['last_no'] ?? 0) + 1;

        $id = $this->insert(
            'INSERT INTO phases (game_id, sequence_no, phase_type, day_number, started_at, ends_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$gameId, $sequenceNo, $type->value, $dayNumber, DbDate::format($startedAt), DbDate::format($endsAt)],
        );

        return $this->findById($id) ?? throw new \LogicException('Faz kaydı okunamadı.');
    }

    public function current(int $gameId): ?Phase
    {
        $row = $this->fetchOne(
            'SELECT * FROM phases WHERE game_id = ? ORDER BY sequence_no DESC LIMIT 1',
            [$gameId],
        );

        return $row === null ? null : Phase::fromRow($row);
    }

    public function findById(int $phaseId): ?Phase
    {
        $row = $this->fetchOne('SELECT * FROM phases WHERE id = ?', [$phaseId]);

        return $row === null ? null : Phase::fromRow($row);
    }
}
