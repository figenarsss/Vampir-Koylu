<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories;

use DateTimeImmutable;
use VampirKoylu\Models\GameLog;

interface GameLogRepositoryInterface
{
    public function add(int $gameId, string $eventType, string $message, bool $secret, DateTimeImmutable $createdAt): void;

    public function listByGame(int $gameId, bool $includeSecret): array;
}
