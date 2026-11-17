<?php

declare(strict_types=1);

namespace VampirKoylu\Game;

final readonly class VoteResult
{
    public function __construct(
        public ?int $eliminatedPlayerId,
        public array $counts,
        public int $passCount,
    ) {
    }
}
