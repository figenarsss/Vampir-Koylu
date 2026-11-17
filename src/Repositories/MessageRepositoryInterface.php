<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories;

use DateTimeImmutable;

interface MessageRepositoryInterface
{
    public function add(int $gameId, int $userId, string $body, DateTimeImmutable $createdAt): void;

    /** Odanın son $limit mesajı, eskiden yeniye sıralı. @return list<\VampirKoylu\Models\ChatMessage> */
    public function latestByGame(int $gameId, int $limit): array;
}
