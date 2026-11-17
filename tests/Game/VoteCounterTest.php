<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Game;

use PHPUnit\Framework\TestCase;
use VampirKoylu\Game\VoteCounter;
use VampirKoylu\Tests\Support\Make;

final class VoteCounterTest extends TestCase
{
    private VoteCounter $counter;

    protected function setUp(): void
    {
        $this->counter = new VoteCounter();
    }

    public function testPlayerWithMostVotesIsEliminated(): void
    {
        $votes = [Make::vote(1, 3), Make::vote(2, 3), Make::vote(3, 1), Make::vote(4, 2)];

        $result = $this->counter->count($votes, [1, 2, 3, 4]);

        $this->assertSame(3, $result->eliminatedPlayerId);
        $this->assertSame([3 => 2, 1 => 1, 2 => 1], $result->counts);
    }

    public function testTieMeansNobodyIsEliminated(): void
    {
        $votes = [Make::vote(1, 3), Make::vote(2, 3), Make::vote(3, 1), Make::vote(4, 1)];

        $this->assertNull($this->counter->count($votes, [1, 2, 3, 4])->eliminatedPlayerId);
    }

    public function testNobodyIsEliminatedWhenPassHasTheMostVotes(): void
    {
        $votes = [Make::vote(1, null), Make::vote(2, null), Make::vote(3, null), Make::vote(4, 1)];

        $result = $this->counter->count($votes, [1, 2, 3, 4]);

        $this->assertNull($result->eliminatedPlayerId);
        $this->assertSame(3, $result->passCount);
    }

    public function testPassTyingWithTheLeaderMeansNobodyIsEliminated(): void
    {
        $votes = [Make::vote(1, 2), Make::vote(3, 2), Make::vote(2, null), Make::vote(4, null)];

        $this->assertNull($this->counter->count($votes, [1, 2, 3, 4])->eliminatedPlayerId);
    }

    public function testPlayersWhoDidNotVoteCountAsPass(): void
    {
        $votes = [Make::vote(1, 3), Make::vote(2, 3)];

        $result = $this->counter->count($votes, [1, 2, 3, 4, 5]);

        $this->assertSame(3, $result->passCount);
        $this->assertNull($result->eliminatedPlayerId);
    }

    public function testNoVotesAtAllEliminatesNobody(): void
    {
        $this->assertNull($this->counter->count([], [1, 2, 3])->eliminatedPlayerId);
    }
}
