<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Pdo;

use PDO;
use VampirKoylu\Repositories\RepositoryBundle;

final class PdoRepositoryFactory
{
    public static function create(PDO $pdo): RepositoryBundle
    {
        return new RepositoryBundle(
            new PdoUserRepository($pdo),
            new PdoGameRepository($pdo),
            new PdoPlayerRepository($pdo),
            new PdoPhaseRepository($pdo),
            new PdoActionRepository($pdo),
            new PdoVoteRepository($pdo),
            new PdoGameLogRepository($pdo),
            new PdoReportRepository($pdo),
            new PdoTransactionManager($pdo),
        );
    }

    public static function connectMysql(array $ayar): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $ayar['sunucu'],
            $ayar['port'],
            $ayar['veritabani'],
        );

        return new PDO($dsn, $ayar['kullanici'], $ayar['sifre'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
