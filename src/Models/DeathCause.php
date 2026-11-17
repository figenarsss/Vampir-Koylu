<?php

declare(strict_types=1);

namespace VampirKoylu\Models;

enum DeathCause: string
{
    case Night = 'night';
    case Vote = 'vote';

    public function label(): string
    {
        return match ($this) {
            self::Night => 'gece öldürüldü',
            self::Vote => 'oylamayla elendi',
        };
    }
}
