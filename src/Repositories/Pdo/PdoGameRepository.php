<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Pdo;

use DateTimeImmutable;
use VampirKoylu\Models\Game;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Models\Team;
use VampirKoylu\Repositories\GameRepositoryInterface;
use VampirKoylu\Support\DbDate;

final class PdoGameRepository extends PdoRepository implements GameRepositoryInterface
{
    public function create(string $roomCode, int $hostUserId, DateTimeImmutable $createdAt): Game
    {
        $id = $this->insert(
            'INSERT INTO games (room_code, host_user_id, status, vampire_count, trapper_count, version, created_at)
             VALUES (?, ?, ?, 0, 0, 0, ?)',
            [$roomCode, $hostUserId, GameStatus::Lobby->value, DbDate::format($createdAt)],
        );

        return $this->findById($id) ?? throw new \LogicException('Oyun kaydı okunamadı.');
    }

    public function findById(int $id): ?Game
    {
        $row = $this->fetchOne('SELECT * FROM games WHERE id = ?', [$id]);

        return $row === null ? null : Game::fromRow($row);
    }

    public function findByCode(string $roomCode): ?Game
    {
        $row = $this->fetchOne('SELECT * FROM games WHERE room_code = ?', [$roomCode]);

        return $row === null ? null : Game::fromRow($row);
    }

    public function codeExists(string $roomCode): bool
    {
        return $this->fetchOne('SELECT id FROM games WHERE room_code = ?', [$roomCode]) !== null;
    }

    public function updateRoleSettings(int $id, int $vampireCount, int $trapperCount): bool
    {
        $changed = $this->execute(
            'UPDATE games SET vampire_count = ?, trapper_count = ? WHERE id = ? AND status = ?',
            [$vampireCount, $trapperCount, $id, GameStatus::Lobby->value],
        );

        return $changed === 1 || $this->findById($id)?->status === GameStatus::Lobby;
    }

    public function markStarted(int $id, int $vampireCount, int $trapperCount): bool
    {
        $changed = $this->execute(
            'UPDATE games SET status = ?, vampire_count = ?, trapper_count = ? WHERE id = ? AND status = ?',
            [GameStatus::InProgress->value, $vampireCount, $trapperCount, $id, GameStatus::Lobby->value],
        );

        return $changed === 1;
    }

    // iyimser kilit: yalnızca bir istek başarılı olur
    public function bumpVersion(int $id, int $expectedVersion): bool
    {
        $changed = $this->execute(
            'UPDATE games SET version = version + 1 WHERE id = ? AND version = ?',
            [$id, $expectedVersion],
        );

        return $changed === 1;
    }

    public function finish(int $id, Team $winner): void
    {
        $this->execute(
            'UPDATE games SET status = ?, winner_team = ? WHERE id = ?',
            [GameStatus::Finished->value, $winner->value, $id],
        );
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM games WHERE id = ?', [$id]);
    }

    public function search(?string $term, ?GameStatus $status, ?int $userId): array
    {
        // JOIN: oyunun kurucusunun adı users tablosundan gelir.
        $sql = 'SELECT g.id, g.room_code, g.status, g.winner_team, g.created_at, u.name AS host_name,
                       (SELECT COUNT(*) FROM players p WHERE p.game_id = g.id) AS player_count
                FROM games g
                INNER JOIN users u ON u.id = g.host_user_id
                WHERE 1 = 1';
        $params = [];

        if ($term !== null && $term !== '') {
            $sql .= ' AND (g.room_code LIKE ? OR u.name LIKE ?)';
            $params[] = "%{$term}%";
            $params[] = "%{$term}%";
        }
        if ($status !== null) {
            $sql .= ' AND g.status = ?';
            $params[] = $status->value;
        }
        if ($userId !== null) {
            $sql .= ' AND EXISTS (SELECT 1 FROM players pp WHERE pp.game_id = g.id AND pp.user_id = ?)';
            $params[] = $userId;
        }

        return $this->fetchAll($sql . ' ORDER BY g.id DESC', $params);
    }
}
