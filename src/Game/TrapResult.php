<?php

declare(strict_types=1);

namespace VampirKoylu\Game;

final readonly class TrapResult
{
    public function __construct(
        public int $trapperPlayerId,
        public int $housePlayerId,
        public ?int $caughtPlayerId,
    ) {
    }
}
