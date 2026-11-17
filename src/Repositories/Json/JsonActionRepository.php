<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Json;

use DateTimeImmutable;
use VampirKoylu\Models\ActionType;
use VampirKoylu\Models\NightAction;
use VampirKoylu\Repositories\ActionRepositoryInterface;
use VampirKoylu\Support\DbDate;

final class JsonActionRepository implements ActionRepositoryInterface
{
    private readonly JsonTable $actions;

    public function __construct(JsonStorage $storage)
    {
        $this->actions = $storage->table('actions');
    }

    public function add(
        int $phaseId,
        int $actorPlayerId,
        int $targetPlayerId,
        ActionType $type,
        DateTimeImmutable $createdAt,
    ): bool {
        return $this->actions->mutate(static function (array &$data) use ($phaseId, $actorPlayerId, $targetPlayerId, $type, $createdAt): bool {
            foreach ($data['rows'] as $row) {
                if ((int) $row['phase_id'] === $phaseId && (int) $row['actor_player_id'] === $actorPlayerId) {
                    return false;
                }
            }

            $id = $data['next_id']++;
            $data['rows'][$id] = [
                'id' => $id,
                'phase_id' => $phaseId,
                'actor_player_id' => $actorPlayerId,
                'target_player_id' => $targetPlayerId,
                'action_type' => $type->value,
                'is_cancelled' => 0,
                'created_at' => DbDate::format($createdAt),
            ];

            return true;
        });
    }

    public function markCancelled(array $actionIds): void
    {
        foreach ($actionIds as $actionId) {
            $this->actions->update($actionId, ['is_cancelled' => 1]);
        }
    }

    public function forPhase(int $phaseId): array
    {
        $rows = $this->actions->where(static fn (array $row): bool => (int) $row['phase_id'] === $phaseId);

        return array_map(NightAction::fromRow(...), $rows);
    }

    public function forActor(int $actorPlayerId): array
    {
        $rows = $this->actions->where(static fn (array $row): bool => (int) $row['actor_player_id'] === $actorPlayerId);

        return array_map(NightAction::fromRow(...), $rows);
    }
}
