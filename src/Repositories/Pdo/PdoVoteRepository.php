<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Pdo;

use DateTimeImmutable;
use PDOException;
use VampirKoylu\Models\Vote;
use VampirKoylu\Repositories\VoteRepositoryInterface;
use VampirKoylu\Support\DbDate;

final class PdoVoteRepository extends PdoRepository implements VoteRepositoryInterface
{
    private const INTEGRITY_VIOLATION = '23000';

    public function cast(int $phaseId, int $voterPlayerId, ?int $targetPlayerId, DateTimeImmutable $createdAt): bool
    {
        try {
            $this->execute(
                'INSERT INTO votes (phase_id, voter_player_id, target_player_id, created_at) VALUES (?, ?, ?, ?)',
                [$phaseId, $voterPlayerId, $targetPlayerId, DbDate::format($createdAt)],
            );

            return true;
        } catch (PDOException $error) {
            // (faz, oy veren) benzersiz: ikinci oy reddedilir
            if ((string) $error->getCode() === self::INTEGRITY_VIOLATION) {
                return false;
            }
            throw $error;
        }
    }

    public function forPhase(int $phaseId): array
    {
        $rows = $this->fetchAll('SELECT * FROM votes WHERE phase_id = ? ORDER BY id', [$phaseId]);

        return array_map(Vote::fromRow(...), $rows);
    }
}
