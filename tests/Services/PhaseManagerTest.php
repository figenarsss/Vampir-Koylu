<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Services;

use VampirKoylu\Game\PhaseManager;
use VampirKoylu\Models\DeathCause;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Models\PhaseType;
use VampirKoylu\Models\RoleType;
use VampirKoylu\Models\Team;
use VampirKoylu\Tests\Support\AppTestCase;

final class PhaseManagerTest extends AppTestCase
{
    private function manager(): PhaseManager
    {
        return $this->app->container->get(PhaseManager::class);
    }

    public function testPhaseDoesNotAdvanceBeforeItsTimeIsUp(): void
    {
        [$game] = $this->app->createGame(5);
        $phase = $this->app->currentPhase($game);

        $this->app->clock->set($phase->endsAt->modify('-1 second'));

        $this->assertFalse($this->manager()->tick($game->id));
        $this->assertSame($phase->id, $this->app->currentPhase($game)->id);
    }

    public function testPhasesAdvanceInOrderWhenTimeIsUp(): void
    {
        [$game] = $this->app->createGame(5);

        $expected = [
            [PhaseType::Night, 1, 25],
            [PhaseType::Day, 1, 75],
            [PhaseType::Vote, 1, 30],
            [PhaseType::Night, 2, 25],
        ];

        foreach ($expected as $i => [$type, $day, $seconds]) {
            $phase = $this->app->currentPhase($game);
            $this->assertSame($type, $phase->type, "{$i}. faz türü");
            $this->assertSame($day, $phase->dayNumber, "{$i}. faz gün numarası");
            $this->assertSame($seconds, $phase->endsAt->getTimestamp() - $phase->startedAt->getTimestamp(), "{$i}. faz süresi");
            $this->assertSame($i + 1, $phase->sequenceNo);

            if ($i < count($expected) - 1) {
                $this->app->finishCurrentPhase($game);
            }
        }
    }

    public function testVotingEndsEarlyWhenEveryoneHasVoted(): void
    {
        [$game, $users] = $this->app->createGame(5);
        $this->app->finishCurrentPhase($game);
        $this->app->finishCurrentPhase($game);
        $phase = $this->app->currentPhase($game);
        $this->assertSame(PhaseType::Vote, $phase->type);

        $this->app->clock->advance(5);
        foreach ($users as $user) {
            $this->app->games()->submitVote($game, $user, null);
        }

        $now = $this->app->currentPhase($game);
        $this->assertSame(PhaseType::Night, $now->type);
        $this->assertSame(2, $now->dayNumber);
    }

    public function testOnlyOneOfTenConcurrentRequestsWinsTheVersionLock(): void
    {
        [$game] = $this->app->createGame(5);

        $winners = 0;
        for ($i = 0; $i < 10; $i++) {
            if ($this->app->repositories->games->bumpVersion($game->id, $game->version)) {
                $winners++;
            }
        }

        $this->assertSame(1, $winners);
    }

    public function testPhaseAdvancesOnlyOnceEvenIfManyRequestsSeeItExpired(): void
    {
        [$game] = $this->app->createGame(5);
        $this->app->clock->set($this->app->currentPhase($game)->endsAt);

        $advanced = 0;
        for ($i = 0; $i < 10; $i++) {
            $advanced += $this->manager()->tick($game->id) ? 1 : 0;
        }

        $this->assertSame(1, $advanced);
        $this->assertSame(2, $this->app->currentPhase($game)->sequenceNo, 'gece → gündüz: yalnızca bir adım');
    }

    public function testNothingHappensToAGameThatIsNotRunning(): void
    {
        [$game] = $this->app->createGame(5, start: false);

        $this->assertFalse($this->manager()->tick($game->id));
        $this->assertFalse($this->manager()->tick(9999));
    }

    public function testGameEndsAfterTheNightWhenVampiresReachTheVillagerCount(): void
    {
        [$game, $users] = $this->app->createGame(5);
        $phaseId = $this->app->currentPhase($game)->id;
        foreach ([0, 1] as $index) {
            $this->app->repositories->players->markDead($this->app->playerWithRole($game, RoleType::Villager, $index)->id, DeathCause::Night, $phaseId);
        }
        $this->app->act($game, $users, $this->app->playerWithRole($game, RoleType::Vampire), $this->app->playerWithRole($game, RoleType::Doctor));

        $this->app->finishCurrentPhase($game);

        $finished = $this->app->repositories->games->findById($game->id);
        $this->assertSame(GameStatus::Finished, $finished->status);
        $this->assertSame(Team::Vampires, $finished->winnerTeam);
    }

    public function testGameEndsAfterTheVoteWhenTheLastVampireIsEliminated(): void
    {
        [$game, $users] = $this->app->createGame(5);
        $this->app->finishCurrentPhase($game);
        $this->app->finishCurrentPhase($game);
        $vampire = $this->app->playerWithRole($game, RoleType::Vampire);

        foreach ($this->app->players($game) as $player) {
            $target = $player->id === $vampire->id ? null : $vampire->id;
            $this->app->games()->submitVote($game, $this->app->userOf($player, $users), $target);
        }

        $finished = $this->app->repositories->games->findById($game->id);
        $this->assertSame(GameStatus::Finished, $finished->status);
        $this->assertSame(Team::Villagers, $finished->winnerTeam);
    }

    public function testFinishedGameIsNeverAdvanced(): void
    {
        [$game] = $this->app->createGame(5);
        $this->app->repositories->games->finish($game->id, Team::Vampires);
        $this->app->clock->advance(3600);

        $this->assertFalse($this->manager()->tick($game->id));
        $this->assertSame(GameStatus::Finished, $this->app->repositories->games->findById($game->id)->status);
    }
}
