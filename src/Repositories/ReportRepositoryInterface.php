<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories;

interface ReportRepositoryInterface
{
    public function overview(): array;

    public function leaderboard(int $limit): array;
}
