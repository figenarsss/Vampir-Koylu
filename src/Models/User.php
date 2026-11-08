<?php

declare(strict_types=1);

namespace VampirKoylu\Models;

use DateTimeImmutable;

final readonly class User
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $passwordHash,
        public AccountRole $accountRole,
        public DateTimeImmutable $createdAt,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['email'],
            (string) $row['password_hash'],
            AccountRole::from((string) $row['account_role']),
            new DateTimeImmutable((string) $row['created_at']),
        );
    }

    public function isAdmin(): bool
    {
        return $this->accountRole === AccountRole::Admin;
    }
}
