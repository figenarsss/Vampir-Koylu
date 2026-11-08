<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories;

use DateTimeImmutable;
use VampirKoylu\Models\AccountRole;
use VampirKoylu\Models\User;

interface UserRepositoryInterface
{
    public function create(
        string $name,
        string $email,
        string $passwordHash,
        AccountRole $accountRole,
        DateTimeImmutable $createdAt,
    ): User;

    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function updatePasswordHash(int $id, string $passwordHash): void;

    public function updateAccountRole(int $id, AccountRole $role): void;

    public function search(?string $term, ?AccountRole $role): array;
}
