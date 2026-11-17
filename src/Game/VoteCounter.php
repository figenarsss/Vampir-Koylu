<?php

declare(strict_types=1);

namespace VampirKoylu\Game;

use VampirKoylu\Models\Vote;

final class VoteCounter
{
    public function count(array $votes, array $aliveVoterIds): VoteResult
    {
        $counts = [];
        $passCount = 0;
        $voted = [];

        foreach ($votes as $vote) {
            $voted[$vote->voterPlayerId] = true;
            if ($vote->isPass()) {
                $passCount++;
            } else {
                $counts[$vote->targetPlayerId] = ($counts[$vote->targetPlayerId] ?? 0) + 1;
            }
        }

        foreach ($aliveVoterIds as $voterId) {
            if (!isset($voted[$voterId])) {
                $passCount++;
            }
        }

        return new VoteResult($this->findEliminated($counts, $passCount), $counts, $passCount);
    }

    private function findEliminated(array $counts, int $passCount): ?int
    {
        if ($counts === []) {
            return null;
        }

        $highest = max($counts);
        if ($passCount >= $highest) {
            return null;
        }

        $leaders = array_keys($counts, $highest, true);

        return count($leaders) === 1 ? $leaders[0] : null;
    }
}
