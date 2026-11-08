<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Json;

use VampirKoylu\Repositories\RepositoryBundle;

final class JsonRepositoryFactory
{
    public static function create(string $directory): RepositoryBundle
    {
        $storage = new JsonStorage($directory);

        return new RepositoryBundle(
            new JsonUserRepository($storage),
            new JsonGameRepository($storage),
            new JsonPlayerRepository($storage),
            new JsonPhaseRepository($storage),
            new JsonActionRepository($storage),
            new JsonVoteRepository($storage),
            new JsonGameLogRepository($storage),
            new JsonReportRepository($storage),
            new JsonTransactionManager($storage),
        );
    }
}
