<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Json;

use DateTimeImmutable;
use VampirKoylu\Models\GameLog;
use VampirKoylu\Repositories\GameLogRepositoryInterface;
use VampirKoylu\Support\DbDate;

final class JsonGameLogRepository implements GameLogRepositoryInterface
{
    private readonly JsonTable $logs;

    public function __construct(JsonStorage $storage)
    {
        $this->logs = $storage->table('game_logs');
    }

    public function add(int $gameId, string $eventType, string $message, bool $secret, DateTimeImmutable $createdAt): void
    {
        $this->logs->insert([
            'game_id' => $gameId,
            'event_type' => $eventType,
            'message' => $message,
            'is_secret' => $secret ? 1 : 0,
            'created_at' => DbDate::format($createdAt),
        ]);
    }

    public function listByGame(int $gameId, bool $includeSecret): array
    {
        $rows = $this->logs->where(
            static fn (array $row): bool => (int) $row['game_id'] === $gameId && ($includeSecret || !(int) $row['is_secret']),
        );

        return array_map(GameLog::fromRow(...), $rows);
    }
}
