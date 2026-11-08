<?php

declare(strict_types=1);

namespace VampirKoylu\Models;

enum GameStatus: string
{
    case Lobby = 'lobby';
    case InProgress = 'in_progress';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Lobby => 'Lobi',
            self::InProgress => 'Sürüyor',
            self::Finished => 'Bitti',
        };
    }
}
