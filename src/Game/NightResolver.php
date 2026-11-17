<?php

declare(strict_types=1);

namespace VampirKoylu\Game;

use VampirKoylu\Models\ActionType;
use VampirKoylu\Models\NightAction;
use VampirKoylu\Models\Player;

final class NightResolver
{
    public function resolve(array $actions, array $players): NightOutcome
    {
        $valid = $this->validActions($actions, $players);

        // sıra: tuzaklar, saldırı ve koruma, sorgu
        [$cancelledIds, $trapResults] = $this->resolveTraps($valid);
        $active = array_values(array_filter(
            $valid,
            static fn (NightAction $action): bool => !in_array($action->id, $cancelledIds, true),
        ));

        return new NightOutcome(
            $this->vampireTargets($active),
            $this->doctorTarget($active),
            $this->inspections($active),
            $cancelledIds,
            $trapResults,
        );
    }

    private function validActions(array $actions, array $players): array
    {
        $byId = [];
        foreach ($players as $player) {
            $byId[$player->id] = $player;
        }

        return array_values(array_filter(
            $actions,
            static function (NightAction $action) use ($byId): bool {
                $actor = $byId[$action->actorPlayerId] ?? null;
                $target = $byId[$action->targetPlayerId] ?? null;

                return $actor !== null
                    && $target !== null
                    && $actor->alive
                    && $target->alive
                    && $actor->role?->nightAction() === $action->type;
            },
        ));
    }

    private function resolveTraps(array $actions): array
    {
        $cancelledIds = [];
        $results = [];

        foreach ($actions as $trap) {
            if ($trap->type !== ActionType::Trap) {
                continue;
            }

            $caught = null;
            foreach ($actions as $visit) {
                $isVisitor = $visit->type !== ActionType::Trap
                    && $visit->targetPlayerId === $trap->targetPlayerId
                    && !in_array($visit->id, $cancelledIds, true);
                if ($isVisitor) {
                    $caught = $visit;
                    break;
                }
            }

            if ($caught !== null) {
                $cancelledIds[] = $caught->id;
            }
            $results[] = new TrapResult($trap->actorPlayerId, $trap->targetPlayerId, $caught?->actorPlayerId);
        }

        return [$cancelledIds, $results];
    }

    private function vampireTargets(array $actions): array
    {
        $targets = [];
        foreach ($actions as $action) {
            if ($action->type === ActionType::Kill) {
                $targets[$action->targetPlayerId] = $action->targetPlayerId;
            }
        }

        return array_values($targets);
    }

    private function doctorTarget(array $actions): ?int
    {
        foreach ($actions as $action) {
            if ($action->type === ActionType::Protect) {
                return $action->targetPlayerId;
            }
        }

        return null;
    }

    private function inspections(array $actions): array
    {
        $result = [];
        foreach ($actions as $action) {
            if ($action->type === ActionType::Inspect) {
                $result[$action->actorPlayerId] = $action->targetPlayerId;
            }
        }

        return $result;
    }
}
