<?php

declare(strict_types=1);

namespace VampirKoylu\Models;

enum AccountRole: string
{
    case Player = 'player';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Player => 'Oyuncu',
            self::Admin => 'Yönetici',
        };
    }
}
