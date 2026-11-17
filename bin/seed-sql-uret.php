<?php

declare(strict_types=1);

use Random\Engine\Mt19937;
use Random\Randomizer;
use VampirKoylu\Core\Bootstrap;
use VampirKoylu\Dev\SampleData;
use VampirKoylu\Repositories\Pdo\PdoRepositoryFactory;
use VampirKoylu\Support\FakeClock;
use VampirKoylu\Tests\Support\SqliteSchema;

// Kullanım: php bin/seed-sql-uret.php
require dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('Europe/Istanbul');

$pdo = SqliteSchema::createDatabase();
$clock = new FakeClock();
$container = Bootstrap::createContainer(
    ['uygulama' => ['ad' => 'Vampir Köylü']],
    $clock,
    new Randomizer(new Mt19937(2026)),
    PdoRepositoryFactory::create($pdo),
);
SampleData::populate($container, $clock, new Randomizer(new Mt19937(2026)));

$tables = ['users', 'games', 'phases', 'players', 'actions', 'votes', 'game_logs'];

$sql = "-- Vampir Köylü - örnek veri\n"
    . "-- Bu dosya bin/seed-sql-uret.php ile otomatik üretilir; elle düzenlemeyin.\n"
    . "-- Giriş: admin@vampirkoylu.test / Admin1234 · oyuncular (figen@vampirkoylu.test ...) / Parola123\n\n"
    . "USE vampir_koylu;\n\n";

foreach ($tables as $table) {
    $rows = $pdo->query("SELECT * FROM {$table} ORDER BY id")->fetchAll();
    if ($rows === []) {
        continue;
    }
    $columns = implode(', ', array_keys($rows[0]));
    $values = array_map(
        static fn (array $row): string => '(' . implode(', ', array_map(
            static fn (mixed $value): string => $value === null ? 'NULL' : (is_int($value) ? (string) $value : $pdo->quote((string) $value)),
            $row,
        )) . ')',
        $rows,
    );
    $sql .= "-- {$table}\nINSERT INTO {$table} ({$columns}) VALUES\n    " . implode(",\n    ", $values) . ";\n\n";
}

file_put_contents(dirname(__DIR__) . '/database/seed.sql', $sql);
echo 'database/seed.sql yazıldı (' . strlen($sql) . " bayt).\n";
