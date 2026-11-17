<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Json;

use DateTimeImmutable;
use VampirKoylu\Models\ChatMessage;
use VampirKoylu\Repositories\MessageRepositoryInterface;
use VampirKoylu\Support\DbDate;

final class JsonMessageRepository implements MessageRepositoryInterface
{
    private readonly JsonTable $messages;
    private readonly JsonTable $users;

    public function __construct(JsonStorage $storage)
    {
        $this->messages = $storage->table('messages');
        $this->users = $storage->table('users');
    }

    public function add(int $gameId, int $userId, string $body, DateTimeImmutable $createdAt): void
    {
        $this->messages->insert([
            'game_id' => $gameId,
            'user_id' => $userId,
            'body' => $body,
            'created_at' => DbDate::format($createdAt),
        ]);
    }

    public function latestByGame(int $gameId, int $limit): array
    {
        $rows = $this->messages->where(static fn (array $row): bool => (int) $row['game_id'] === $gameId);
        $rows = array_slice($rows, -$limit);
        $names = array_column($this->users->all(), 'name', 'id');

        return array_map(
            static fn (array $row): ChatMessage => ChatMessage::fromRow($row + ['user_name' => $names[$row['user_id']] ?? '']),
            $rows,
        );
    }
}
