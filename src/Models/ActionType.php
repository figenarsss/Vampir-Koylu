<?php

declare(strict_types=1);

namespace VampirKoylu\Models;

enum ActionType: string
{
    case Kill = 'kill';
    case Protect = 'protect';
    case Inspect = 'inspect';
    case Trap = 'trap';

    public function label(): string
    {
        return match ($this) {
            self::Kill => 'Kurban seç',
            self::Protect => 'Koru',
            self::Inspect => 'Rolünü öğren',
            self::Trap => 'Tuzak kur',
        };
    }
}
