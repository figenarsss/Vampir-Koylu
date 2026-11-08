<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Json;

use DateTimeImmutable;
use VampirKoylu\Models\AccountRole;
use VampirKoylu\Models\User;
use VampirKoylu\Repositories\UserRepositoryInterface;
use VampirKoylu\Support\DbDate;

final class JsonUserRepository implements UserRepositoryInterface
{
    private readonly JsonTable $users;

    public function __construct(JsonStorage $storage)
    {
        $this->users = $storage->table('users');
    }

    public function create(
        string $name,
        string $email,
        string $passwordHash,
        AccountRole $accountRole,
        DateTimeImmutable $createdAt,
    ): User {
        return User::fromRow($this->users->insert([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'account_role' => $accountRole->value,
            'created_at' => DbDate::format($createdAt),
        ]));
    }

    public function findById(int $id): ?User
    {
        $row = $this->users->find($id);

        return $row === null ? null : User::fromRow($row);
    }

    public function findByEmail(string $email): ?User
    {
        $wanted = mb_strtolower($email);
        $rows = $this->users->where(static fn (array $row): bool => mb_strtolower((string) $row['email']) === $wanted);

        return $rows === [] ? null : User::fromRow($rows[0]);
    }

    public function updatePasswordHash(int $id, string $passwordHash): void
    {
        $this->users->update($id, ['password_hash' => $passwordHash]);
    }

    public function updateAccountRole(int $id, AccountRole $role): void
    {
        $this->users->update($id, ['account_role' => $role->value]);
    }

    public function search(?string $term, ?AccountRole $role): array
    {
        $rows = $this->users->where(static function (array $row) use ($term, $role): bool {
            if ($role !== null && $row['account_role'] !== $role->value) {
                return false;
            }
            if ($term === null || $term === '') {
                return true;
            }

            return mb_stripos((string) $row['name'], $term) !== false
                || mb_stripos((string) $row['email'], $term) !== false;
        });

        return array_map(User::fromRow(...), $rows);
    }
}
