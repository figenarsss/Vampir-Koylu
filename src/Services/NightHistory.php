<?php

declare(strict_types=1);

namespace VampirKoylu\Services;

use VampirKoylu\Models\Phase;
use VampirKoylu\Models\Player;
use VampirKoylu\Repositories\ActionRepositoryInterface;
use VampirKoylu\Repositories\PhaseRepositoryInterface;

final class NightHistory
{
    public function __construct(
        private readonly ActionRepositoryInterface $actions,
        private readonly PhaseRepositoryInterface $phases,
    ) {
    }

    public function lastNightTarget(Player $actor, Phase $currentNight): ?int
    {
        foreach (array_reverse($this->actions->forActor($actor->id)) as $action) {
            if ($action->phaseId === $currentNight->id) {
                continue;
            }
            $previous = $this->phases->findById($action->phaseId);

            return $previous?->dayNumber === $currentNight->dayNumber - 1 ? $action->targetPlayerId : null;
        }

        return null;
    }
}
