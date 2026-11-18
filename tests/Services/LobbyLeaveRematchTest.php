<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Services;

use VampirKoylu\Exceptions\ForbiddenException;
use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Models\Game;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Models\Team;
use VampirKoylu\Tests\Support\AppTestCase;

// Lobiden ayrılma / kurucu devri ve biten oyundan sonra "yeni lobi" (tekrar oyna) kuralları.
final class LobbyLeaveRematchTest extends AppTestCase
{
    private function reload(Game $game): ?Game
    {
        return $this->app->repositories->games->findById($game->id);
    }

    private function finish(Game $game): Game
    {
        $this->app->repositories->games->finish($game->id, Team::Villagers);

        return $this->reload($game);
    }

    // ---- lobiden ayrılma ve kurucu devri ----

    public function testNormalPlayerLeavesAndHostStays(): void
    {
        [$game, $users] = $this->app->createGame(5, false);

        $message = $this->app->lobby()->leave($game, $users[2]);

        $this->assertSame('Odadan ayrıldınız.', $message);
        $this->assertCount(4, $this->app->players($game));
        $this->assertNull($this->app->repositories->players->findByGameAndUser($game->id, $users[2]->id));
        $this->assertSame($users[0]->id, $this->reload($game)->hostUserId, 'kurucu değişmemeli');
    }

    public function testHostLeavingPassesHostToTheEarliestJoiner(): void
    {
        [$game, $users] = $this->app->createGame(5, false);

        $message = $this->app->lobby()->leave($game, $users[0]);

        $this->assertSame($users[1]->id, $this->reload($game)->hostUserId, 'en erken katılan oyuncu kurucu olmalı');
        $this->assertStringContainsString($users[1]->name, $message);
        $this->assertCount(4, $this->app->players($game));
        $this->assertNull($this->app->repositories->players->findByGameAndUser($game->id, $users[0]->id));
    }

    public function testNewHostCanStartTheGameAndOldHostCannot(): void
    {
        [$game, $users] = $this->app->createGame(6, false);
        $this->app->lobby()->leave($game, $users[0]);
        $game = $this->reload($game);

        try {
            $this->app->games()->start($game, $users[0]);
            $this->fail('Eski kurucu oyunu başlatamamalı');
        } catch (ForbiddenException $error) {
            $this->assertStringContainsString('yalnızca oda kurucusu', $error->getMessage());
        }

        $this->app->games()->start($game, $users[1]);
        $this->assertSame(GameStatus::InProgress, $this->reload($game)->status);
    }

    public function testRoomIsClosedWhenTheLastPlayerLeaves(): void
    {
        $host = $this->app->registerUser('Yalniz');
        $game = $this->app->lobby()->createRoom($host);

        $message = $this->app->lobby()->leave($game, $host);

        $this->assertStringContainsString('oda kapandı', $message);
        $this->assertNull($this->reload($game));
    }

    public function testCannotLeaveAfterTheGameStarted(): void
    {
        [$game, $users] = $this->app->createGame(5);

        try {
            $this->app->lobby()->leave($game, $users[1]);
            $this->fail('Oyun başladıktan sonra ayrılma reddedilmeliydi');
        } catch (RuleViolationException $error) {
            $this->assertStringContainsString('Oyun başladıktan sonra', $error->getMessage());
        }

        $this->assertCount(5, $this->app->players($game));
    }

    public function testLeavingARoomYouAreNotInChangesNothing(): void
    {
        [$game] = $this->app->createGame(5, false);
        $stranger = $this->app->registerUser('Yabanci');

        $message = $this->app->lobby()->leave($game, $stranger);

        $this->assertSame('Odadan ayrıldınız.', $message);
        $this->assertCount(5, $this->app->players($game));
    }

    public function testPlayerWhoLeftCanJoinAgain(): void
    {
        [$game, $users] = $this->app->createGame(5, false);
        $this->app->lobby()->leave($game, $users[3]);

        $this->app->lobby()->join($game->roomCode, $users[3]);

        $this->assertCount(5, $this->app->players($game));
    }

    // ---- yeni lobi (tekrar oyna) ----

    public function testRematchIsRejectedBeforeTheGameEnds(): void
    {
        [$game, $users] = $this->app->createGame(5);

        try {
            $this->app->lobby()->rematch($game, $users[0]);
            $this->fail('Oyun bitmeden yeni lobi reddedilmeliydi');
        } catch (RuleViolationException $error) {
            $this->assertStringContainsString('yalnızca oyun bittikten sonra', $error->getMessage());
        }
    }

    public function testFirstPlayerOpensTheNewLobbyAndBecomesItsHost(): void
    {
        [$game, $users] = $this->app->createGame(5);
        $old = $this->finish($game);

        $next = $this->app->lobby()->rematch($old, $users[2]);

        $this->assertNotSame($old->id, $next->id);
        $this->assertSame(GameStatus::Lobby, $next->status);
        $this->assertSame($users[2]->id, $next->hostUserId, 'ilk tıklayan kurucu olur');
        $this->assertSame($next->roomCode, $this->reload($old)->rematchCode, 'eski oyun yeni odanın kodunu tutar');
        $this->assertCount(1, $this->app->players($next));
    }

    public function testOtherPlayersJoinTheSameNewLobby(): void
    {
        [$game, $users] = $this->app->createGame(5);
        $old = $this->finish($game);

        $first = $this->app->lobby()->rematch($old, $users[0]);
        $second = $this->app->lobby()->rematch($this->reload($old), $users[3]);

        $this->assertSame($first->id, $second->id, 'herkes aynı yeni odaya gitmeli');
        $this->assertCount(2, $this->app->players($first));
        $this->assertSame($users[0]->id, $this->reload($first)->hostUserId);
    }

    public function testPressingRematchTwiceDoesNotDuplicateThePlayer(): void
    {
        [$game, $users] = $this->app->createGame(5);
        $old = $this->finish($game);

        $first = $this->app->lobby()->rematch($old, $users[0]);
        $again = $this->app->lobby()->rematch($this->reload($old), $users[0]);

        $this->assertSame($first->id, $again->id);
        $this->assertCount(1, $this->app->players($first));
    }

    public function testStrangerCannotUseRematch(): void
    {
        [$game] = $this->app->createGame(5);
        $old = $this->finish($game);
        $stranger = $this->app->registerUser('Yabanci');

        $this->expectException(ForbiddenException::class);
        $this->app->lobby()->rematch($old, $stranger);
    }

    public function testFinishedGameStaysInHistoryAfterRematch(): void
    {
        [$game, $users] = $this->app->createGame(5);
        $old = $this->finish($game);

        $this->app->lobby()->rematch($old, $users[0]);

        $kept = $this->reload($old);
        $this->assertSame(GameStatus::Finished, $kept->status);
        $this->assertSame(Team::Villagers, $kept->winnerTeam);
        $this->assertCount(5, $this->app->players($kept), 'eski oyunun oyuncuları korunmalı');
    }

    public function testLatePlayerCannotJoinNewLobbyAfterItStarted(): void
    {
        [$game, $users] = $this->app->createGame(6);
        $old = $this->finish($game);

        $next = $this->app->lobby()->rematch($old, $users[0]);
        foreach (array_slice($users, 1, 4) as $user) {
            $this->app->lobby()->rematch($this->reload($old), $user);
        }
        $this->app->games()->start($this->reload($next), $users[0]);

        try {
            $this->app->lobby()->rematch($this->reload($old), $users[5]);
            $this->fail('Başlamış yeni oyuna katılım reddedilmeliydi');
        } catch (RuleViolationException $error) {
            $this->assertStringContainsString('Yeni oyun başlamış', $error->getMessage());
        }
    }
}
