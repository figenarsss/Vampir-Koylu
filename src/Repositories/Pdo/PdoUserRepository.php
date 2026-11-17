<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Pdo;

use DateTimeImmutable;
use VampirKoylu\Models\AccountRole;
use VampirKoylu\Models\User;
use VampirKoylu\Repositories\UserRepositoryInterface;
use VampirKoylu\Support\DbDate;

final class PdoUserRepository extends PdoRepository implements UserRepositoryInterface
{
    public function create(
        string $name,
        string $email,
        string $passwordHash,
        AccountRole $accountRole,
        DateTimeImmutable $createdAt,
    ): User {
        $id = $this->insert(
            'INSERT INTO users (name, email, password_hash, account_role, created_at) VALUES (?, ?, ?, ?, ?)',
            [$name, $email, $passwordHash, $accountRole->value, DbDate::format($createdAt)],
        );

        return $this->findById($id) ?? throw new \LogicException('Kullanıcı kaydı okunamadı.');
    }

    public function findById(int $id): ?User
    {
        $row = $this->fetchOne('SELECT * FROM users WHERE id = ?', [$id]);

        return $row === null ? null : User::fromRow($row);
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->fetchOne('SELECT * FROM users WHERE email = ?', [mb_strtolower($email)]);

        return $row === null ? null : User::fromRow($row);
    }

    public function updatePasswordHash(int $id, string $passwordHash): void
    {
        $this->execute('UPDATE users SET password_hash = ? WHERE id = ?', [$passwordHash, $id]);
    }

    public function updateAccountRole(int $id, AccountRole $role): void
    {
        $this->execute('UPDATE users SET account_role = ? WHERE id = ?', [$role->value, $id]);
    }

    public function search(?string $term, ?AccountRole $role): array
    {
        $sql = 'SELECT * FROM users WHERE 1 = 1';
        $params = [];

        if ($term !== null && $term !== '') {
            $sql .= ' AND (name LIKE ? OR email LIKE ?)';
            $params[] = "%{$term}%";
            $params[] = "%{$term}%";
        }
        if ($role !== null) {
            $sql .= ' AND account_role = ?';
            $params[] = $role->value;
        }

        return array_map(User::fromRow(...), $this->fetchAll($sql . ' ORDER BY id', $params));
    }
}
