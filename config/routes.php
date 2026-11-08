<?php

declare(strict_types=1);

use VampirKoylu\Controllers\AdminController;
use VampirKoylu\Controllers\AuthController;
use VampirKoylu\Controllers\GameController;
use VampirKoylu\Controllers\HistoryController;
use VampirKoylu\Controllers\HomeController;
use VampirKoylu\Controllers\RoomController;
use VampirKoylu\Http\Router;

return static function (Router $router): void {
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/kurallar', [HomeController::class, 'rules']);

    // Hesap
    $router->get('/kayit', [AuthController::class, 'showRegister'], ['guest']);
    $router->post('/kayit', [AuthController::class, 'register'], ['guest']);
    $router->get('/giris', [AuthController::class, 'showLogin'], ['guest']);
    $router->post('/giris', [AuthController::class, 'login'], ['guest']);
    $router->post('/cikis', [AuthController::class, 'logout'], ['auth']);
    $router->get('/profil', [AuthController::class, 'showProfile'], ['auth']);
    $router->post('/profil/sifre', [AuthController::class, 'changePassword'], ['auth']);

    // Oda
    $router->get('/odalar', [RoomController::class, 'index'], ['auth']);
    $router->post('/oda', [RoomController::class, 'create'], ['auth']);
    $router->post('/oda/katil', [RoomController::class, 'join'], ['auth']);
    $router->post('/oyun/{code}/ayril', [RoomController::class, 'leave'], ['auth']);
    $router->post('/oyun/{code}/at', [RoomController::class, 'kick'], ['auth']);
    $router->post('/oyun/{code}/ayar', [RoomController::class, 'settings'], ['auth']);
    $router->post('/oyun/{code}/sil', [RoomController::class, 'delete'], ['auth']);

    // Oyun
    $router->get('/oyun/{code}', [GameController::class, 'show'], ['auth']);
    $router->get('/oyun/{code}/parca', [GameController::class, 'fragment'], ['auth']);
    $router->get('/oyun/{code}/durum', [GameController::class, 'state'], ['auth']);
    $router->post('/oyun/{code}/baslat', [GameController::class, 'start'], ['auth']);
    $router->post('/oyun/{code}/eylem', [GameController::class, 'action'], ['auth']);
    $router->post('/oyun/{code}/oy', [GameController::class, 'vote'], ['auth']);
    $router->get('/oyunlarim', [HistoryController::class, 'index'], ['auth']);

    // Yönetici
    $router->get('/yonetim', [AdminController::class, 'dashboard'], ['admin']);
    $router->get('/yonetim/kullanicilar', [AdminController::class, 'users'], ['admin']);
    $router->post('/yonetim/kullanicilar/{id}/yetki', [AdminController::class, 'changeRole'], ['admin']);
    $router->get('/yonetim/oyunlar', [AdminController::class, 'games'], ['admin']);
    $router->post('/yonetim/oyunlar/{id}/sil', [AdminController::class, 'deleteGame'], ['admin']);
};
