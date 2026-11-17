<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Repositories;

use Random\Engine\Mt19937;
use Random\Randomizer;
use VampirKoylu\Dev\GameSimulator;
use VampirKoylu\Models\AccountRole;
use VampirKoylu\Models\ActionType;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Models\Team;
use VampirKoylu\Tests\Support\AppTestCase;

final class RepositoryTest extends AppTestCase
{
    public function testVoteCanBeCastOnlyOncePerVoterAndPhase(): void
    {
        [$game] = $this->app->createGame(5);
        $phase = $this->app->currentPhase($game);
        [$a, $b, $c] = $this->app->players($game);
        $votes = $this->app->repositories->votes;
        $now = $this->app->clock->now();

        $this->assertTrue($votes->cast($phase->id, $a->id, $b->id, $now));
        $this->assertFalse($votes->cast($phase->id, $a->id, $c->id, $now), 'aynı oy veren ikinci kez oy kullanamaz');
        $this->assertFalse($votes->cast($phase->id, $a->id, null, $now), 'pas da ikinci oy sayılır');
        $this->assertTrue($votes->cast($phase->id, $b->id, null, $now));

        $stored = $votes->forPhase($phase->id);
        $this->assertCount(2, $stored);
        $this->assertSame($b->id, $stored[0]->targetPlayerId);
        $this->assertTrue($stored[1]->isPass());
    }

    public function testAnActionCanBeAddedOnlyOncePerActorAndPhase(): void
    {
        [$game] = $this->app->createGame(5);
        $phase = $this->app->currentPhase($game);
        [$a, $b, $c] = $this->app->players($game);
        $actions = $this->app->repositories->actions;
        $now = $this->app->clock->now();

        $this->assertTrue($actions->add($phase->id, $a->id, $b->id, ActionType::Kill, $now));
        $this->assertFalse($actions->add($phase->id, $a->id, $c->id, ActionType::Kill, $now));

        $stored = $actions->forPhase($phase->id);
        $this->assertCount(1, $stored);
        $this->assertSame($b->id, $stored[0]->targetPlayerId);
        $this->assertCount(1, $actions->forActor($a->id));
    }

    public function testActionsCanBeMarkedAsCancelled(): void
    {
        [$game] = $this->app->createGame(5);
        $phase = $this->app->currentPhase($game);
        [$a, $b, $c] = $this->app->players($game);
        $actions = $this->app->repositories->actions;
        $actions->add($phase->id, $a->id, $c->id, ActionType::Kill, $this->app->clock->now());
        $actions->add($phase->id, $b->id, $c->id, ActionType::Trap, $this->app->clock->now());
        $stored = $actions->forPhase($phase->id);

        $actions->markCancelled([$stored[0]->id]);

        $after = $actions->forPhase($phase->id);
        $this->assertTrue($after[0]->cancelled);
        $this->assertFalse($after[1]->cancelled);
    }

    public function testRoleSettingsCanOnlyBeChangedInTheLobby(): void
    {
        [$game] = $this->app->createGame(9, start: false);
        $games = $this->app->repositories->games;

        $this->assertTrue($games->updateRoleSettings($game->id, 3, 2));
        $this->assertTrue($games->updateRoleSettings($game->id, 3, 2));
        $this->assertSame(3, $games->findById($game->id)->vampireCount);
        $this->assertSame(2, $games->findById($game->id)->trapperCount);

        $games->markStarted($game->id, 3, 2);
        $this->assertFalse($games->updateRoleSettings($game->id, 1, 0));
        $this->assertSame(3, $games->findById($game->id)->vampireCount);
    }

    public function testGameStateTransitionsAreConditional(): void
    {
        [$game] = $this->app->createGame(5, start: false);
        $games = $this->app->repositories->games;

        $this->assertTrue($games->markStarted($game->id, 1, 0));
        $this->assertFalse($games->markStarted($game->id, 1, 0), 'ikinci başlatma etkisiz olmalı');

        $this->assertFalse($games->bumpVersion($game->id, 5), 'yanlış sürümle artırılamaz');
        $this->assertTrue($games->bumpVersion($game->id, 0));
        $this->assertSame(1, $games->findById($game->id)->version);

        $games->finish($game->id, Team::Villagers);
        $this->assertSame(Team::Villagers, $games->findById($game->id)->winnerTeam);
        $this->assertSame(GameStatus::Finished, $games->findById($game->id)->status);
    }

    public function testPlayersAreListedInJoinOrderWithTheirNames(): void
    {
        [$game, $users] = $this->app->createGame(5, start: false);

        $players = $this->app->players($game);

        $this->assertSame(array_map(static fn ($u): string => $u->name, $users), array_map(static fn ($p): string => $p->name, $players));
        $this->assertNull($players[0]->role);
        $this->assertSame($players[2]->id, $this->app->repositories->players->findByGameAndUser($game->id, $users[2]->id)->id);
        $this->assertSame(5, $this->app->repositories->players->countByGame($game->id));
    }

    public function testUserSearchByTermAndRole(): void
    {
        $this->app->registerUsers(3);
        $admin = $this->app->registerUser('Yonetici');
        $this->app->repositories->users->updateAccountRole($admin->id, AccountRole::Admin);
        $users = $this->app->repositories->users;

        $this->assertCount(4, $users->search(null, null));
        $this->assertCount(1, $users->search('yonetici', null), 'arama büyük/küçük harf duyarsız');
        $this->assertCount(1, $users->search(null, AccountRole::Admin));
        $this->assertCount(0, $users->search('yonetici', AccountRole::Player));
        $this->assertSame($admin->id, $users->findByEmail('YONETICI@test.com')?->id);
    }

    public function testGameSearchFilters(): void
    {
        [$running, $users] = $this->app->createGame(5);
        $lobby = $this->app->lobby()->createRoom($users[1]);
        $games = $this->app->repositories->games;

        $all = $games->search(null, null, null);
        $this->assertCount(2, $all);
        $this->assertSame($lobby->roomCode, $all[0]['room_code'], 'en yeni oyun üstte');
        $this->assertSame($users[1]->name, $all[0]['host_name']);
        $this->assertEquals(1, $all[0]['player_count']);

        $this->assertCount(1, $games->search($running->roomCode, null, null));
        $this->assertCount(1, $games->search($users[1]->name, null, null));
        $this->assertCount(1, $games->search(null, GameStatus::Lobby, null));
        $this->assertCount(2, $games->search(null, null, $users[1]->id));
        $this->assertCount(1, $games->search(null, null, $users[3]->id), 'oyuncu yalnızca katıldığı oyunları görür');
        $this->assertCount(0, $games->search('ZZZZZZ', null, null));
    }

    public function testSecretLogsAreFilteredOut(): void
    {
        [$game] = $this->app->createGame(5);
        $logs = $this->app->repositories->logs;
        $logs->add($game->id, 'night_action', 'gizli olay', true, $this->app->clock->now());
        $logs->add($game->id, 'night_result', 'herkese açık olay', false, $this->app->clock->now());

        $public = array_map(static fn ($l): string => $l->message, $logs->listByGame($game->id, false));
        $all = array_map(static fn ($l): string => $l->message, $logs->listByGame($game->id, true));

        $this->assertNotContains('gizli olay', $public);
        $this->assertContains('herkese açık olay', $public);
        $this->assertContains('gizli olay', $all);
    }

    public function testReportsCountFinishedGames(): void
    {
        $users = $this->app->registerUsers(6);
        (new GameSimulator($this->app->container, $this->app->clock, new Randomizer(new Mt19937(5))))->playToEnd($users);
        $this->app->lobby()->createRoom($users[0]);

        $overview = $this->app->repositories->reports->overview();

        $this->assertSame(6, $overview['users']);
        $this->assertSame(2, $overview['games']);
        $this->assertSame(1, $overview['finished']);
        $this->assertSame(1, $overview['lobby']);
        $this->assertSame(1, $overview['villager_wins'] + $overview['vampire_wins']);
        $this->assertEqualsWithDelta(3.5, $overview['average_players'], 0.01, '(6 + 1) / 2 oyun');

        $board = $this->app->repositories->reports->leaderboard(10);
        $this->assertCount(6, $board);
        $this->assertGreaterThan(0, array_sum(array_column($board, 'wins')), 'kazanan takımda en az bir oyuncu var');
        $this->assertGreaterThanOrEqual($board[count($board) - 1]['wins'], $board[0]['wins'], 'kazanma sayısına göre azalan sıra');
    }
}
