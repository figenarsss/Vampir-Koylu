<?php

declare(strict_types=1);

namespace VampirKoylu\Models;

enum PhaseType: string
{
    case Night = 'night';
    case Day = 'day';
    case Vote = 'vote';

    public function label(): string
    {
        return match ($this) {
            self::Night => 'Gece',
            self::Day => 'Gündüz',
            self::Vote => 'Oylama',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Night => '🌙',
            self::Day => '☀️',
            self::Vote => '🗳️',
        };
    }
}
