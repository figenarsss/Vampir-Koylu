<?php

declare(strict_types=1);

namespace VampirKoylu\Game;

final readonly class NightOutcome
{
    public function __construct(
        public array $targetedPlayerIds,
        public ?int $protectedPlayerId,
        public array $inspections,
        public array $cancelledActionIds,
        public array $trapResults,
    ) {
    }

    public function victimPlayerIds(): array
    {
        return array_values(array_diff($this->targetedPlayerIds, [$this->protectedPlayerId]));
    }

    public function wasSaved(): bool
    {
        return $this->protectedPlayerId !== null && in_array($this->protectedPlayerId, $this->targetedPlayerIds, true);
    }
}
