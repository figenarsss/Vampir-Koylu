<?php

declare(strict_types=1);

namespace VampirKoylu\Models;

enum RoleType: string
{
    case Villager = 'villager';
    case Vampire = 'vampire';
    case Doctor = 'doctor';
    case Seer = 'seer';
    case Trapper = 'trapper';

    public function id(): int
    {
        return match ($this) {
            self::Villager => 1,
            self::Vampire => 2,
            self::Doctor => 3,
            self::Seer => 4,
            self::Trapper => 5,
        };
    }

    public static function fromId(int $id): self
    {
        foreach (self::cases() as $role) {
            if ($role->id() === $id) {
                return $role;
            }
        }
        throw new \InvalidArgumentException("Bilinmeyen rol id: {$id}");
    }

    public function label(): string
    {
        return match ($this) {
            self::Villager => 'Köylü',
            self::Vampire => 'Vampir',
            self::Doctor => 'Doktor',
            self::Seer => 'Kâhin',
            self::Trapper => 'Tuzakçı',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Villager => '🧑‍🌾',
            self::Vampire => '🧛',
            self::Doctor => '🩺',
            self::Seer => '🔮',
            self::Trapper => '🪤',
        };
    }

    public function team(): Team
    {
        return $this === self::Vampire ? Team::Vampires : Team::Villagers;
    }

    public function nightAction(): ?ActionType
    {
        return match ($this) {
            self::Vampire => ActionType::Kill,
            self::Doctor => ActionType::Protect,
            self::Seer => ActionType::Inspect,
            self::Trapper => ActionType::Trap,
            self::Villager => null,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Villager => 'Gece yeteneği yoktur. Gündüz oylamada vampirleri bulmaya çalışır.',
            self::Vampire => 'Her gece bir kurban seçer. Diğer vampirlerin kim olduğunu bilmez.',
            self::Doctor => 'Her gece kendisi dahil bir oyuncuyu korur. Art arda iki gece kendini koruyamaz.',
            self::Seer => 'Her gece bir oyuncunun rolünü öğrenir. Sonucu yalnızca kendisi görür.',
            self::Trapper => 'Her gece bir oyuncunun evine tuzak kurar. Eve eylem yapan ilk oyuncunun eylemi iptal olur.',
        };
    }
}
