<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Support;

use PDO;

final class SqliteSchema
{
    public static function createDatabase(): PDO
    {
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec(self::toSqlite((string) file_get_contents(self::projectPath('database/schema.sql'))));

        return $pdo;
    }

    public static function loadSeed(PDO $pdo): void
    {
        $sql = (string) file_get_contents(self::projectPath('database/seed.sql'));
        $pdo->exec(self::stripDatabaseStatements($sql));
    }

    private static function toSqlite(string $sql): string
    {
        $sql = self::stripDatabaseStatements($sql);
        $sql = preg_replace('/INT NOT NULL AUTO_INCREMENT PRIMARY KEY/', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);

        return (string) preg_replace('/\)\s*ENGINE=[^;]*;/', ');', (string) $sql);
    }

    private static function stripDatabaseStatements(string $sql): string
    {
        return (string) preg_replace('/^(CREATE DATABASE|USE)\b[^;]*;/mi', '', $sql);
    }

    private static function projectPath(string $relative): string
    {
        return dirname(__DIR__, 2) . '/' . $relative;
    }
}
