<?php

declare(strict_types=1);

namespace VampirKoylu\Models;

enum Team: string
{
    case Villagers = 'villagers';
    case Vampires = 'vampires';

    public function label(): string
    {
        return match ($this) {
            self::Villagers => 'Köylüler',
            self::Vampires => 'Vampirler',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Villagers => '🏡',
            self::Vampires => '🧛',
        };
    }
}
