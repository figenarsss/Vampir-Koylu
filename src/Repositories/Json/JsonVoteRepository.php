<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Json;

use DateTimeImmutable;
use VampirKoylu\Models\Vote;
use VampirKoylu\Repositories\VoteRepositoryInterface;
use VampirKoylu\Support\DbDate;

final class JsonVoteRepository implements VoteRepositoryInterface
{
    private readonly JsonTable $votes;

    public function __construct(JsonStorage $storage)
    {
        $this->votes = $storage->table('votes');
    }

    public function cast(int $phaseId, int $voterPlayerId, ?int $targetPlayerId, DateTimeImmutable $createdAt): bool
    {
        return $this->votes->mutate(static function (array &$data) use ($phaseId, $voterPlayerId, $targetPlayerId, $createdAt): bool {
            foreach ($data['rows'] as $row) {
                if ((int) $row['phase_id'] === $phaseId && (int) $row['voter_player_id'] === $voterPlayerId) {
                    return false;
                }
            }

            $id = $data['next_id']++;
            $data['rows'][$id] = [
                'id' => $id,
                'phase_id' => $phaseId,
                'voter_player_id' => $voterPlayerId,
                'target_player_id' => $targetPlayerId,
                'created_at' => DbDate::format($createdAt),
            ];

            return true;
        });
    }

    public function forPhase(int $phaseId): array
    {
        $rows = $this->votes->where(static fn (array $row): bool => (int) $row['phase_id'] === $phaseId);

        return array_map(Vote::fromRow(...), $rows);
    }
}
