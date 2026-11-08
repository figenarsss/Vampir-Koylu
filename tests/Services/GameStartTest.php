<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use VampirKoylu\Exceptions\ForbiddenException;
use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Models\PhaseType;
use VampirKoylu\Models\RoleType;
use VampirKoylu\Tests\Support\AppTestCase;

final class GameStartTest extends AppTestCase
{
    public function testOnlyTheHostCanStartTheGame(): void
    {
        [$game, $users] = $this->app->createGame(5, start: false);

        try {
            $this->app->games()->start($game, $users[1]);
            $this->fail('Kurucu olmayan oyuncu başlatamamalı');
        } catch (ForbiddenException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(GameStatus::Lobby, $this->app->repositories->games->findById($game->id)->status);
    }

    public static function tooFewPlayers(): array
    {
        return ['1 oyuncu' => [1], '2 oyuncu' => [2], '3 oyuncu' => [3], '4 oyuncu' => [4]];
    }

    #[DataProvider('tooFewPlayers')]
    public function testCannotStartWithFewerThanFivePlayers(int $count): void
    {
        [$game, $users] = $this->app->createGame($count, start: false);

        $this->expectException(RuleViolationException::class);
        $this->expectExceptionMessage('en az 5 oyuncu');
        $this->app->games()->start($game, $users[0]);
    }

    public function testCannotStartWithMoreThanTwelvePlayers(): void
    {
        [$game, $users] = $this->app->createGame(12, start: false);
        $this->app->repositories->players->add($game->id, $this->app->registerUser('Fazla')->id);

        $this->expectException(RuleViolationException::class);
        $this->expectExceptionMessage('en fazla 12 oyuncu');
        $this->app->games()->start($game, $users[0]);
    }

    public static function validPlayerCounts(): array
    {
        return ['5 oyuncu' => [5], '12 oyuncu' => [12]];
    }

    #[DataProvider('validPlayerCounts')]
    public function testHostCanStartWithinTheLimits(int $count): void
    {
        [$game] = $this->app->createGame($count);

        $this->assertSame(GameStatus::InProgress, $game->status);
    }

    public function testStartingAssignsRolesAndOpensTheFirstNight(): void
    {
        [$game] = $this->app->createGame(8);

        $this->assertSame(GameStatus::InProgress, $game->status);
        $this->assertSame(2, $game->vampireCount);
        foreach ($this->app->players($game) as $player) {
            $this->assertNotNull($player->role);
            $this->assertTrue($player->alive);
        }

        $phase = $this->app->currentPhase($game);
        $this->assertSame(PhaseType::Night, $phase->type);
        $this->assertSame(1, $phase->dayNumber);
        $this->assertSame(25, $phase->endsAt->getTimestamp() - $phase->startedAt->getTimestamp());
    }

    public function testChosenRoleCountsAreUsed(): void
    {
        [$game] = $this->app->createGame(10, vampires: 3, trappers: 2);

        $roles = array_map(static fn ($p): string => $p->role->value, $this->app->players($game));
        $counts = array_count_values($roles);

        $this->assertSame(3, $counts['vampire']);
        $this->assertSame(2, $counts['trapper']);
        $this->assertSame(1, $counts['doctor']);
        $this->assertSame(1, $counts['seer']);
        $this->assertSame(3, $counts['villager']);
    }

    public function testSettingsThatNoLongerFitAreReplacedByDefaults(): void
    {
        [$game, $users] = $this->app->createGame(10, start: false, vampires: 3, trappers: 2);
        foreach (array_slice($this->app->players($game), 5) as $player) {
            $this->app->lobby()->kick($game, $users[0], $player->id);
        }

        $this->app->games()->start($game, $users[0]);

        $started = $this->app->repositories->games->findById($game->id);
        $this->assertSame(1, $started->vampireCount);
        $this->assertSame(0, $started->trapperCount);
    }

    public function testStartedGameCannotBeStartedTwice(): void
    {
        [$game, $users] = $this->app->createGame(5);

        $this->expectException(RuleViolationException::class);
        $this->app->games()->start($game, $users[0]);
    }

    public function testRolesAreNotChangedByASecondStartAttempt(): void
    {
        [$game, $users] = $this->app->createGame(5);
        $before = array_map(static fn ($p): ?RoleType => $p->role, $this->app->players($game));

        try {
            $this->app->games()->start($game, $users[0]);
        } catch (RuleViolationException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame($before, array_map(static fn ($p): ?RoleType => $p->role, $this->app->players($game)));
    }
}
