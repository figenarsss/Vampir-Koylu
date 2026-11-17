<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Pdo;

use DateTimeImmutable;
use PDOException;
use VampirKoylu\Models\ActionType;
use VampirKoylu\Models\NightAction;
use VampirKoylu\Repositories\ActionRepositoryInterface;
use VampirKoylu\Support\DbDate;

final class PdoActionRepository extends PdoRepository implements ActionRepositoryInterface
{
    private const INTEGRITY_VIOLATION = '23000';

    public function add(
        int $phaseId,
        int $actorPlayerId,
        int $targetPlayerId,
        ActionType $type,
        DateTimeImmutable $createdAt,
    ): bool {
        try {
            $this->execute(
                'INSERT INTO actions (phase_id, actor_player_id, target_player_id, action_type, is_cancelled, created_at)
                 VALUES (?, ?, ?, ?, 0, ?)',
                [$phaseId, $actorPlayerId, $targetPlayerId, $type->value, DbDate::format($createdAt)],
            );

            return true;
        } catch (PDOException $error) {
            if ((string) $error->getCode() === self::INTEGRITY_VIOLATION) {
                return false;
            }
            throw $error;
        }
    }

    public function markCancelled(array $actionIds): void
    {
        foreach ($actionIds as $actionId) {
            $this->execute('UPDATE actions SET is_cancelled = 1 WHERE id = ?', [$actionId]);
        }
    }

    public function forPhase(int $phaseId): array
    {
        $rows = $this->fetchAll('SELECT * FROM actions WHERE phase_id = ? ORDER BY id', [$phaseId]);

        return array_map(NightAction::fromRow(...), $rows);
    }

    public function forActor(int $actorPlayerId): array
    {
        $rows = $this->fetchAll('SELECT * FROM actions WHERE actor_player_id = ? ORDER BY id', [$actorPlayerId]);

        return array_map(NightAction::fromRow(...), $rows);
    }
}
