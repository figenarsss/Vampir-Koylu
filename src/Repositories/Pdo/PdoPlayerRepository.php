<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Pdo;

use VampirKoylu\Models\DeathCause;
use VampirKoylu\Models\Player;
use VampirKoylu\Repositories\PlayerRepositoryInterface;

final class PdoPlayerRepository extends PdoRepository implements PlayerRepositoryInterface
{
    private const SELECT = 'SELECT p.id, p.game_id, p.user_id, u.name, p.role_id, p.is_alive, p.death_cause, p.death_phase_id
                            FROM players p
                            INNER JOIN users u ON u.id = p.user_id';

    public function add(int $gameId, int $userId): Player
    {
        $id = $this->insert('INSERT INTO players (game_id, user_id, is_alive) VALUES (?, ?, 1)', [$gameId, $userId]);

        return $this->findById($id) ?? throw new \LogicException('Oyuncu kaydı okunamadı.');
    }

    public function remove(int $playerId): void
    {
        $this->execute('DELETE FROM players WHERE id = ?', [$playerId]);
    }

    public function findById(int $playerId): ?Player
    {
        $row = $this->fetchOne(self::SELECT . ' WHERE p.id = ?', [$playerId]);

        return $row === null ? null : Player::fromRow($row);
    }

    public function findByGameAndUser(int $gameId, int $userId): ?Player
    {
        $row = $this->fetchOne(self::SELECT . ' WHERE p.game_id = ? AND p.user_id = ?', [$gameId, $userId]);

        return $row === null ? null : Player::fromRow($row);
    }

    public function listByGame(int $gameId): array
    {
        $rows = $this->fetchAll(self::SELECT . ' WHERE p.game_id = ? ORDER BY p.id', [$gameId]);

        return array_map(Player::fromRow(...), $rows);
    }

    public function countByGame(int $gameId): int
    {
        $row = $this->fetchOne('SELECT COUNT(*) AS total FROM players WHERE game_id = ?', [$gameId]);

        return (int) ($row['total'] ?? 0);
    }

    public function assignRoles(array $roles): void
    {
        foreach ($roles as $playerId => $role) {
            $this->execute('UPDATE players SET role_id = ? WHERE id = ?', [$role->id(), $playerId]);
        }
    }

    public function markDead(int $playerId, DeathCause $cause, int $phaseId): void
    {
        $this->execute(
            'UPDATE players SET is_alive = 0, death_cause = ?, death_phase_id = ? WHERE id = ?',
            [$cause->value, $phaseId, $playerId],
        );
    }
}
