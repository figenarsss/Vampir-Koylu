<?php

declare(strict_types=1);

use VampirKoylu\Core\Bootstrap;
use VampirKoylu\Http\Application;
use VampirKoylu\Http\Request;
use VampirKoylu\Http\Response;
use VampirKoylu\Http\Router;
use VampirKoylu\Http\Session;

// Ön denetleyici: tüm istekler buradan girer
$projectRoot = dirname(__DIR__);

if (!is_file("{$projectRoot}/vendor/autoload.php")) {
    http_response_code(500);
    exit('Önce proje klasöründe "composer install" komutunu çalıştırın.');
}
require "{$projectRoot}/vendor/autoload.php";

date_default_timezone_set('Europe/Istanbul');

$config = Bootstrap::loadConfig($projectRoot);

try {
    $session = Session::start((int) $config['uygulama']['oturum_zaman_asimi']);
    $container = Bootstrap::createContainer($config);
} catch (Throwable $error) {
    $detail = $config['uygulama']['hata_ayrintisi'] ? ' (' . $error->getMessage() . ')' : '';
    Response::text('Uygulama başlatılamadı. Depolama ayarlarını (config/config.local.php) kontrol edin.' . $detail, 500)->send();
    exit;
}

$router = new Router();
(require "{$projectRoot}/config/routes.php")($router);

$application = new Application($container, $router, (bool) $config['uygulama']['hata_ayrintisi']);
$application->handle(Request::fromGlobals($session))->send();
