<?php

declare(strict_types=1);

namespace VampirKoylu\Dev;

use DateTimeImmutable;
use Random\Randomizer;
use VampirKoylu\Core\Container;
use VampirKoylu\Models\AccountRole;
use VampirKoylu\Models\User;
use VampirKoylu\Repositories\UserRepositoryInterface;
use VampirKoylu\Services\AuthService;
use VampirKoylu\Support\FakeClock;

final class SampleData
{
    public const ADMIN_EMAIL = 'admin@vampirkoylu.test';
    public const ADMIN_PASSWORD = 'Admin1234';
    public const PLAYER_PASSWORD = 'Parola123';

    private const PLAYERS = [
        'figen@vampirkoylu.test' => 'Figen Arslan',
        'melek@vampirkoylu.test' => 'Melek Efe',
        'zeynep.t@vampirkoylu.test' => 'Zeynep Tunç',
        'zeynep.c@vampirkoylu.test' => 'Zeynep Çakmak',
        'ahmet@vampirkoylu.test' => 'Ahmet Kaya',
        'selin@vampirkoylu.test' => 'Selin Demir',
        'burak@vampirkoylu.test' => 'Burak Yıldız',
        'derya@vampirkoylu.test' => 'Derya Acar',
        'mert@vampirkoylu.test' => 'Mert Şahin',
    ];

    public static function populate(Container $container, FakeClock $clock, Randomizer $random): void
    {
        $auth = $container->get(AuthService::class);
        $users = $container->get(UserRepositoryInterface::class);
        $simulator = new GameSimulator($container, $clock, $random);

        $clock->set(new DateTimeImmutable('2026-10-15 10:00:00'));
        $admin = $auth->register('Yönetici', self::ADMIN_EMAIL, self::ADMIN_PASSWORD, self::ADMIN_PASSWORD);
        $users->updateAccountRole($admin->id, AccountRole::Admin);

        $players = [];
        foreach (self::PLAYERS as $email => $name) {
            $clock->advance(600);
            $players[] = $auth->register($name, $email, self::PLAYER_PASSWORD, self::PLAYER_PASSWORD);
        }

        $finishedGames = [
            [[0, 1, 2, 3, 4, 5, 6, 7], '2026-10-20 19:00:00', 2, 1],
            [[1, 2, 3, 4, 5, 6], '2026-10-22 21:30:00', 0, 0],
            [[2, 0, 4, 6, 7, 8, 5], '2026-10-25 20:15:00', 0, 0],
        ];
        foreach ($finishedGames as [$indexes, $startTime, $vampires, $trappers]) {
            $clock->set(new DateTimeImmutable($startTime));
            $gamePlayers = array_map(static fn (int $i): User => $players[$i], $indexes);
            $simulator->playToEnd($gamePlayers, null, $vampires, $trappers);
        }

        $clock->set(new DateTimeImmutable('2026-11-07 18:00:00'));
        $simulator->createRoom([$players[0], $players[1], $players[4]]);
    }
}
