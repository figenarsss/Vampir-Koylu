<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories;

use DateTimeImmutable;
use VampirKoylu\Models\Game;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Models\Team;

interface GameRepositoryInterface
{
    public function create(string $roomCode, int $hostUserId, DateTimeImmutable $createdAt): Game;

    public function findById(int $id): ?Game;

    public function findByCode(string $roomCode): ?Game;

    public function codeExists(string $roomCode): bool;

    public function updateRoleSettings(int $id, int $vampireCount, int $trapperCount): bool;

    public function markStarted(int $id, int $vampireCount, int $trapperCount): bool;

    public function bumpVersion(int $id, int $expectedVersion): bool;

    public function finish(int $id, Team $winner): void;

    public function setHost(int $id, int $hostUserId): void;

    // Biten oyunun "yeni lobi" kodunu yalnızca beklenen değer hâlâ geçerliyse yazar (iki kişi aynı anda tıklarsa biri kazanır)
    public function claimRematchCode(int $id, ?string $expected, string $newCode): bool;

    public function delete(int $id): void;

    public function search(?string $term, ?GameStatus $status, ?int $userId): array;
}
