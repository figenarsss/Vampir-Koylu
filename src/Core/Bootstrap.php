<?php

declare(strict_types=1);

namespace VampirKoylu\Core;

use Random\Randomizer;
use VampirKoylu\Controllers\AdminController;
use VampirKoylu\Controllers\AuthController;
use VampirKoylu\Controllers\GameController;
use VampirKoylu\Controllers\HistoryController;
use VampirKoylu\Controllers\HomeController;
use VampirKoylu\Controllers\RoomController;
use VampirKoylu\Game\GameRules;
use VampirKoylu\Game\NightResolver;
use VampirKoylu\Game\PhaseManager;
use VampirKoylu\Game\RoleDistributor;
use VampirKoylu\Game\VoteCounter;
use VampirKoylu\Game\WinChecker;
use VampirKoylu\Http\Csrf;
use VampirKoylu\Http\View;
use VampirKoylu\Repositories\ActionRepositoryInterface;
use VampirKoylu\Repositories\GameLogRepositoryInterface;
use VampirKoylu\Repositories\GameRepositoryInterface;
use VampirKoylu\Repositories\Json\JsonRepositoryFactory;
use VampirKoylu\Repositories\Pdo\PdoRepositoryFactory;
use VampirKoylu\Repositories\PhaseRepositoryInterface;
use VampirKoylu\Repositories\PlayerRepositoryInterface;
use VampirKoylu\Repositories\ReportRepositoryInterface;
use VampirKoylu\Repositories\RepositoryBundle;
use VampirKoylu\Repositories\TransactionManagerInterface;
use VampirKoylu\Repositories\UserRepositoryInterface;
use VampirKoylu\Repositories\VoteRepositoryInterface;
use VampirKoylu\Services\AdminService;
use VampirKoylu\Services\AuthService;
use VampirKoylu\Services\GameService;
use VampirKoylu\Services\GameStateBuilder;
use VampirKoylu\Services\LobbyService;
use VampirKoylu\Services\NightHistory;
use VampirKoylu\Support\Clock;
use VampirKoylu\Support\SystemClock;

final class Bootstrap
{
    public static function loadConfig(string $projectRoot): array
    {
        $config = require "{$projectRoot}/config/config.php";

        $localFile = "{$projectRoot}/config/config.local.php";
        if (is_file($localFile)) {
            $config = array_replace_recursive($config, require $localFile);
        }

        return $config;
    }

    public static function createContainer(
        array $config,
        ?Clock $clock = null,
        ?Randomizer $random = null,
        ?RepositoryBundle $repositories = null,
        ?GameRules $rules = null,
    ): Container {
        $clock ??= new SystemClock();
        $random ??= new Randomizer();
        $rules ??= GameRules::fromConfig($config['sureler'] ?? []);
        $repos = $repositories ?? self::createRepositories($config);
        $csrf = new Csrf();
        $view = new View(
            dirname(__DIR__) . '/Views',
            (string) ($config['uygulama']['ad'] ?? 'Vampir Köylü'),
            $csrf,
        );

        $c = new Container();

        $c->set(Clock::class, fn () => $clock);
        $c->set(Csrf::class, fn () => $csrf);
        $c->set(View::class, fn () => $view);

        $c->set(UserRepositoryInterface::class, fn () => $repos->users);
        $c->set(GameRepositoryInterface::class, fn () => $repos->games);
        $c->set(PlayerRepositoryInterface::class, fn () => $repos->players);
        $c->set(PhaseRepositoryInterface::class, fn () => $repos->phases);
        $c->set(ActionRepositoryInterface::class, fn () => $repos->actions);
        $c->set(VoteRepositoryInterface::class, fn () => $repos->votes);
        $c->set(GameLogRepositoryInterface::class, fn () => $repos->logs);
        $c->set(ReportRepositoryInterface::class, fn () => $repos->reports);
        $c->set(TransactionManagerInterface::class, fn () => $repos->transactions);

        $c->set(NightResolver::class, fn () => new NightResolver());
        $c->set(VoteCounter::class, fn () => new VoteCounter());
        $c->set(WinChecker::class, fn () => new WinChecker());
        $c->set(RoleDistributor::class, fn () => new RoleDistributor($random, $rules));
        $c->set(NightHistory::class, fn () => new NightHistory($repos->actions, $repos->phases));

        $c->set(PhaseManager::class, fn () => new PhaseManager(
            $repos->games,
            $repos->players,
            $repos->phases,
            $repos->actions,
            $repos->votes,
            $repos->logs,
            $repos->transactions,
            $clock,
            $rules,
            $c->get(NightResolver::class),
            $c->get(VoteCounter::class),
            $c->get(WinChecker::class),
        ));

        $c->set(AuthService::class, fn () => new AuthService($repos->users, $clock));
        $c->set(AdminService::class, fn () => new AdminService($repos->users, $repos->reports));
        $c->set(LobbyService::class, fn () => new LobbyService($repos->games, $repos->players, $repos->transactions, $clock, $rules));
        $c->set(GameService::class, fn () => new GameService(
            $repos->games,
            $repos->players,
            $repos->phases,
            $repos->actions,
            $repos->votes,
            $repos->logs,
            $repos->transactions,
            $clock,
            $rules,
            $c->get(RoleDistributor::class),
            $c->get(PhaseManager::class),
            $c->get(NightHistory::class),
        ));
        $c->set(GameStateBuilder::class, fn () => new GameStateBuilder(
            $repos->players,
            $repos->phases,
            $repos->actions,
            $repos->votes,
            $repos->logs,
            $c->get(NightHistory::class),
            $clock,
            $rules,
        ));

        $c->set(HomeController::class, fn () => new HomeController($view, $repos->games));
        $c->set(AuthController::class, fn () => new AuthController($view, $c->get(AuthService::class)));
        $c->set(RoomController::class, fn () => new RoomController($view, $repos->games, $c->get(LobbyService::class), $rules));
        $c->set(HistoryController::class, fn () => new HistoryController($view, $repos->games));
        $c->set(GameController::class, fn () => new GameController(
            $view,
            $repos->games,
            $c->get(GameService::class),
            $c->get(PhaseManager::class),
            $c->get(GameStateBuilder::class),
        ));
        $c->set(AdminController::class, fn () => new AdminController(
            $view,
            $c->get(AdminService::class),
            $repos->games,
            $c->get(LobbyService::class),
        ));

        return $c;
    }

    private static function createRepositories(array $config): RepositoryBundle
    {
        return match ($config['depolama'] ?? 'json') {
            'mysql' => PdoRepositoryFactory::create(PdoRepositoryFactory::connectMysql($config['mysql'])),
            'json' => JsonRepositoryFactory::create((string) $config['json']['klasor']),
            default => throw new \InvalidArgumentException("Geçersiz depolama ayarı: {$config['depolama']} (json veya mysql olmalı)"),
        };
    }
}
