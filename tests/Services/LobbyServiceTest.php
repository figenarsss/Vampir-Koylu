<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use VampirKoylu\Exceptions\ForbiddenException;
use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Models\AccountRole;
use VampirKoylu\Tests\Support\AppTestCase;

final class LobbyServiceTest extends AppTestCase
{
    public function testCreateRoomGeneratesUniqueCodesAndAddsTheHost(): void
    {
        $host = $this->app->registerUser('Kurucu');
        $codes = [];

        for ($i = 0; $i < 25; $i++) {
            $game = $this->app->lobby()->createRoom($host);
            $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{6}$/', $game->roomCode);
            $codes[] = $game->roomCode;
        }

        $this->assertCount(25, array_unique($codes));
        $players = $this->app->players($game);
        $this->assertCount(1, $players);
        $this->assertSame($host->id, $players[0]->userId);
    }

    public function testThousandRoomsNeverShareACode(): void
    {
        $host = $this->app->registerUser('Kurucu');
        $codes = [];

        for ($i = 0; $i < 1000; $i++) {
            $codes[] = $this->app->lobby()->createRoom($host)->roomCode;
        }

        $this->assertCount(1000, array_unique($codes));
        $this->assertGreaterThanOrEqual(6, min(array_map('strlen', $codes)));
    }

    public function testJoinWithRoomCode(): void
    {
        [$host, $guest] = $this->app->registerUsers(2);
        $game = $this->app->lobby()->createRoom($host);

        $this->app->lobby()->join(strtolower($game->roomCode), $guest);

        $this->assertCount(2, $this->app->players($game));
    }

    public function testJoinWithUnknownCodeIsRejected(): void
    {
        $user = $this->app->registerUser('Ali');

        $this->expectException(RuleViolationException::class);
        $this->app->lobby()->join('ZZZZZZ', $user);
    }

    public function testJoiningTwiceDoesNotDuplicateThePlayer(): void
    {
        [$host, $guest] = $this->app->registerUsers(2);
        $game = $this->app->lobby()->createRoom($host);

        $this->app->lobby()->join($game->roomCode, $guest);
        $this->app->lobby()->join($game->roomCode, $guest);

        $this->assertCount(2, $this->app->players($game));
    }

    public function testThirteenthPlayerIsRejected(): void
    {
        $users = $this->app->registerUsers(13);
        $game = $this->app->lobby()->createRoom($users[0]);
        foreach (array_slice($users, 1, 11) as $user) {
            $this->app->lobby()->join($game->roomCode, $user);
        }
        $this->assertCount(12, $this->app->players($game));

        $this->expectException(RuleViolationException::class);
        $this->expectExceptionMessage('Oda dolu');
        $this->app->lobby()->join($game->roomCode, $users[12]);
    }

    public function testCannotJoinAfterTheGameHasStarted(): void
    {
        [$game] = $this->app->createGame(5);
        $latecomer = $this->app->registerUser('Gecikmis');

        $this->expectException(RuleViolationException::class);
        $this->expectExceptionMessage('başlamış');
        $this->app->lobby()->join($game->roomCode, $latecomer);
    }

    public function testHostCanKickAPlayerButOthersCannot(): void
    {
        [$host, $guest, $other] = $this->app->registerUsers(3);
        $game = $this->app->lobby()->createRoom($host);
        $this->app->lobby()->join($game->roomCode, $guest);
        $this->app->lobby()->join($game->roomCode, $other);
        $guestPlayer = $this->app->repositories->players->findByGameAndUser($game->id, $guest->id);

        try {
            $this->app->lobby()->kick($game, $other, $guestPlayer->id);
            $this->fail('Kurucu olmayan oyuncu atamamalı');
        } catch (ForbiddenException) {
            $this->addToAssertionCount(1);
        }

        $this->app->lobby()->kick($game, $host, $guestPlayer->id);
        $this->assertCount(2, $this->app->players($game));
    }

    public function testOnlyTheHostCanDeleteTheRoomAndNotWhileTheGameRuns(): void
    {
        [$game, $users] = $this->app->createGame(5);

        try {
            $this->app->lobby()->deleteRoom($game, $users[1]);
            $this->fail('Kurucu olmayan silememeli');
        } catch (ForbiddenException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(RuleViolationException::class);
        $this->app->lobby()->deleteRoom($game, $users[0]);
    }

    public function testDeletingARoomRemovesItsRecords(): void
    {
        [$game] = $this->app->createGame(5);
        $this->app->finishCurrentPhase($game);
        $admin = $this->app->repositories->users->create('Yonetici', 'yonetici@test.com', 'x', AccountRole::Admin, $this->app->clock->now());

        $this->app->lobby()->deleteRoom($game, $admin);

        $this->assertNull($this->app->repositories->games->findById($game->id));
        $this->assertSame([], $this->app->players($game));
        $this->assertNull($this->app->repositories->phases->current($game->id));
        $this->assertSame([], $this->app->repositories->logs->listByGame($game->id, true));
    }

    public function testHostCanChangeTheRoleSettings(): void
    {
        [$game, $users] = $this->app->createGame(9, start: false);

        $this->app->lobby()->updateRoleSettings($game, $users[0], 3, 2);

        $saved = $this->app->repositories->games->findById($game->id);
        $this->assertSame(3, $saved->vampireCount);
        $this->assertSame(2, $saved->trapperCount);
    }

    public function testOnlyTheHostCanChangeTheRoleSettings(): void
    {
        [$game, $users] = $this->app->createGame(9, start: false);

        $this->expectException(ForbiddenException::class);
        $this->app->lobby()->updateRoleSettings($game, $users[1], 2, 0);
    }

    public static function invalidSettings(): array
    {
        return [
            '7 oyuncuda 2 vampir' => [7, 2, 0],
            '7 oyuncuda 1 tuzakçı' => [7, 1, 1],
            '8 oyuncuda 4 vampir' => [8, 4, 0],
            '8 oyuncuda 3 tuzakçı' => [8, 1, 3],
        ];
    }

    #[DataProvider('invalidSettings')]
    public function testInvalidRoleSettingsAreRejected(int $playerCount, int $vampires, int $trappers): void
    {
        [$game, $users] = $this->app->createGame($playerCount, start: false);

        $this->expectException(RuleViolationException::class);
        $this->app->lobby()->updateRoleSettings($game, $users[0], $vampires, $trappers);
    }

    public function testRoleSettingsCannotChangeAfterTheGameStarted(): void
    {
        [$game, $users] = $this->app->createGame(9);

        $this->expectException(RuleViolationException::class);
        $this->app->lobby()->updateRoleSettings($game, $users[0], 3, 0);
    }
}
