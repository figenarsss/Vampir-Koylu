<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Support;

use PDO;
use VampirKoylu\Repositories\Pdo\PdoRepositoryFactory;

final class MysqlSchema
{
    private const DATABASE_PREFIX = 'vk_test_';

    public static function createDatabase(): array
    {
        $settings = [
            'sunucu' => getenv('TEST_MYSQL_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('TEST_MYSQL_PORT') ?: 3306),
            'veritabani' => self::DATABASE_PREFIX . bin2hex(random_bytes(4)),
            'kullanici' => getenv('TEST_MYSQL_USER') ?: 'root',
            'sifre' => getenv('TEST_MYSQL_PASSWORD') ?: '',
        ];

        $admin = new PDO(
            "mysql:host={$settings['sunucu']};port={$settings['port']};charset=utf8mb4",
            $settings['kullanici'],
            $settings['sifre'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => true],
        );
        $admin->exec("CREATE DATABASE `{$settings['veritabani']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $admin->exec("USE `{$settings['veritabani']}`");

        $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');
        $admin->exec((string) preg_replace('/^(CREATE DATABASE|USE)\b[^;]*;/mi', '', $schema));

        return [
            'pdo' => PdoRepositoryFactory::connectMysql($settings),
            'drop' => static fn () => $admin->exec("DROP DATABASE IF EXISTS `{$settings['veritabani']}`"),
        ];
    }
}
