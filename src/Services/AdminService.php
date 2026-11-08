<?php

declare(strict_types=1);

namespace VampirKoylu\Services;

use VampirKoylu\Exceptions\ForbiddenException;
use VampirKoylu\Exceptions\NotFoundException;
use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Models\AccountRole;
use VampirKoylu\Models\User;
use VampirKoylu\Repositories\ReportRepositoryInterface;
use VampirKoylu\Repositories\UserRepositoryInterface;

final class AdminService
{
    private const LEADERBOARD_SIZE = 5;

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly ReportRepositoryInterface $reports,
    ) {
    }

    public function searchUsers(?string $term, ?AccountRole $role): array
    {
        return $this->users->search($term, $role);
    }

    public function changeAccountRole(User $actor, int $userId, AccountRole $role): void
    {
        if (!$actor->isAdmin()) {
            throw new ForbiddenException('Bu işlem için yönetici olmalısınız.');
        }
        if ($actor->id === $userId) {
            throw new RuleViolationException('Kendi yetkinizi değiştiremezsiniz.');
        }
        $this->users->findById($userId) ?? throw new NotFoundException('Kullanıcı bulunamadı.');

        $this->users->updateAccountRole($userId, $role);
    }

    public function report(): array
    {
        $overview = $this->reports->overview();
        $finished = $overview['finished'];

        return [
            'overview' => $overview,
            'villager_rate' => $finished > 0 ? (int) round($overview['villager_wins'] * 100 / $finished) : 0,
            'vampire_rate' => $finished > 0 ? (int) round($overview['vampire_wins'] * 100 / $finished) : 0,
            'leaderboard' => $this->reports->leaderboard(self::LEADERBOARD_SIZE),
        ];
    }
}
