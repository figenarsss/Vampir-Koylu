<?php

declare(strict_types=1);

use Random\Engine\Mt19937;
use Random\Randomizer;
use VampirKoylu\Core\Bootstrap;
use VampirKoylu\Dev\SampleData;
use VampirKoylu\Repositories\UserRepositoryInterface;
use VampirKoylu\Support\FakeClock;

// Kullanım: php bin/ornek-veri.php
require dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('Europe/Istanbul');

$config = Bootstrap::loadConfig(dirname(__DIR__));
$clock = new FakeClock();
$container = Bootstrap::createContainer($config, $clock, new Randomizer(new Mt19937(2026)));

if ($container->get(UserRepositoryInterface::class)->search(null, null) !== []) {
    fwrite(STDERR, "Depolama boş değil; örnek veri yüklenmedi. (JSON için storage/veri klasörünü silebilirsiniz.)\n");
    exit(1);
}

SampleData::populate($container, $clock, new Randomizer(new Mt19937(2026)));

echo "Örnek veri yüklendi ({$config['depolama']}).\n";
echo "Yönetici : " . SampleData::ADMIN_EMAIL . ' / ' . SampleData::ADMIN_PASSWORD . "\n";
echo 'Oyuncular: figen@vampirkoylu.test ... / ' . SampleData::PLAYER_PASSWORD . "\n";
