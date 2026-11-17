<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Services;

use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Models\Game;
use VampirKoylu\Models\PhaseType;
use VampirKoylu\Models\Player;
use VampirKoylu\Models\RoleType;
use VampirKoylu\Models\User;
use VampirKoylu\Tests\Support\AppTestCase;

final class VotingTest extends AppTestCase
{
    private array $users;
    private Game $game;

    protected function setUp(): void
    {
        parent::setUp();
        [$this->game, $this->users] = $this->app->createGame(6);
        $this->app->finishCurrentPhase($this->game);
        $this->app->finishCurrentPhase($this->game);
        $this->assertSame(PhaseType::Vote, $this->app->currentPhaseType($this->game));
        $this->app->clock->advance(1);
    }

    private function vote(Player $voter, ?Player $target): void
    {
        $this->app->games()->submitVote($this->game, $this->app->userOf($voter, $this->users), $target?->id);
    }

    private function villager(int $index): Player
    {
        return $this->app->playerWithRole($this->game, RoleType::Villager, $index);
    }

    public function testDeadPlayerCannotVote(): void
    {
        $dead = $this->villager(0);
        $this->app->repositories->players->markDead($dead->id, \VampirKoylu\Models\DeathCause::Night, $this->app->currentPhase($this->game)->id);

        $this->expectException(RuleViolationException::class);
        $this->expectExceptionMessage('Ölü ve elenen');
        $this->vote($dead, $this->villager(1));
    }

    public function testCannotVoteForADeadPlayer(): void
    {
        $dead = $this->villager(0);
        $this->app->repositories->players->markDead($dead->id, \VampirKoylu\Models\DeathCause::Night, $this->app->currentPhase($this->game)->id);

        $this->expectException(RuleViolationException::class);
        $this->vote($this->villager(1), $dead);
    }

    public function testSecondVoteFromTheSamePlayerIsRejected(): void
    {
        $voter = $this->villager(0);
        $this->vote($voter, $this->villager(1));

        try {
            $this->vote($voter, $this->app->playerWithRole($this->game, RoleType::Doctor));
            $this->fail('İkinci oy reddedilmeliydi');
        } catch (RuleViolationException $error) {
            $this->assertStringContainsString('zaten oy kullandın', $error->getMessage());
        }

        $votes = $this->app->repositories->votes->forPhase($this->app->currentPhase($this->game)->id);
        $this->assertCount(1, $votes);
        $this->assertSame($this->villager(1)->id, $votes[0]->targetPlayerId, 'ilk oy korunmalı');
    }

    public function testPassCannotBeChangedIntoAVote(): void
    {
        $voter = $this->villager(0);
        $this->vote($voter, null);

        $this->expectException(RuleViolationException::class);
        $this->vote($voter, $this->villager(1));
    }

    public function testCannotVoteForYourself(): void
    {
        $voter = $this->villager(0);

        $this->expectException(RuleViolationException::class);
        $this->expectExceptionMessage('Kendine oy veremezsin');
        $this->vote($voter, $voter);
    }

    public function testVotingOutsideTheVotePhaseIsRejected(): void
    {
        $this->app->finishCurrentPhase($this->game);

        $this->expectException(RuleViolationException::class);
        $this->vote($this->villager(0), $this->villager(1));
    }

    public function testMostVotedPlayerIsEliminatedAndNightFollows(): void
    {
        $target = $this->villager(0);
        $others = array_filter($this->app->players($this->game), static fn (Player $p): bool => $p->id !== $target->id);
        foreach ($others as $voter) {
            $this->vote($voter, $target);
        }
        $this->vote($target, null);

        $this->assertFalse($this->app->repositories->players->findById($target->id)->alive);
        $this->assertSame(PhaseType::Night, $this->app->currentPhaseType($this->game));
        $this->assertSame(2, $this->app->currentPhase($this->game)->dayNumber);
    }

    public function testTieEliminatesNobody(): void
    {
        $players = $this->app->players($this->game);
        [$a, $b, $c, $d, $e, $f] = $players;
        $this->vote($a, $c);
        $this->vote($b, $c);
        $this->vote($c, $a);
        $this->vote($d, $a);
        $this->vote($e, null);
        $this->vote($f, null);

        $this->assertCount(6, array_filter($this->app->players($this->game), static fn (Player $p): bool => $p->alive));
        $this->assertSame(PhaseType::Night, $this->app->currentPhaseType($this->game));
    }

    public function testEliminatedPlayersRoleIsAnnouncedToEveryone(): void
    {
        $target = $this->app->playerWithRole($this->game, RoleType::Seer);
        foreach ($this->app->players($this->game) as $voter) {
            $this->vote($voter, $voter->id === $target->id ? null : $target);
        }

        $messages = array_map(static fn ($log): string => $log->message, $this->app->repositories->logs->listByGame($this->game->id, false));
        $this->assertContains("{$target->name} elendi. Rolü: Kâhin.", $messages);


        $viewer = $this->app->userOf($this->villager(0), $this->users);
        $state = $this->app->container->get(\VampirKoylu\Services\GameStateBuilder::class)
            ->build($this->app->repositories->games->findById($this->game->id), $viewer);
        $entry = array_values(array_filter($state['players'], static fn (array $p): bool => $p['id'] === $target->id))[0];
        $this->assertSame('seer', $entry['role']['key']);
    }

    public function testNonVotersCountAsPassWhenTimeRunsOut(): void
    {
        $this->vote($this->villager(0), $this->villager(1));

        $this->app->finishCurrentPhase($this->game);

        $this->assertCount(6, array_filter($this->app->players($this->game), static fn (Player $p): bool => $p->alive));
        $this->assertSame(PhaseType::Night, $this->app->currentPhaseType($this->game));
    }
}
