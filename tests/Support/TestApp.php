<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Support;

use Random\Engine\Mt19937;
use Random\Randomizer;
use VampirKoylu\Core\Bootstrap;
use VampirKoylu\Core\Container;
use VampirKoylu\Game\GameRules;
use VampirKoylu\Game\PhaseManager;
use VampirKoylu\Models\Game;
use VampirKoylu\Models\Phase;
use VampirKoylu\Models\PhaseType;
use VampirKoylu\Models\Player;
use VampirKoylu\Models\RoleType;
use VampirKoylu\Models\User;
use VampirKoylu\Repositories\Json\JsonRepositoryFactory;
use VampirKoylu\Repositories\Pdo\PdoRepositoryFactory;
use VampirKoylu\Repositories\RepositoryBundle;
use VampirKoylu\Services\AuthService;
use VampirKoylu\Services\GameService;
use VampirKoylu\Services\LobbyService;
use VampirKoylu\Support\FakeClock;

final class TestApp
{
    public const PASSWORD = 'Parola123';

    private ?string $jsonDirectory = null;

    private $dropDatabase = null;

    private int $userCounter = 0;

    private function __construct(
        public readonly Container $container,
        public readonly FakeClock $clock,
        public readonly RepositoryBundle $repositories,
        public readonly GameRules $rules,
    ) {
    }

    public static function create(?GameRules $rules = null, int $seed = 7): self
    {
        $clock = new FakeClock();
        $rules ??= new GameRules();
        $backend = getenv('TEST_BACKEND') ?: 'sqlite';

        $jsonDirectory = null;
        $dropDatabase = null;
        if ($backend === 'json') {
            $jsonDirectory = sys_get_temp_dir() . '/vampir-koylu-test-' . bin2hex(random_bytes(6));
            $repositories = JsonRepositoryFactory::create($jsonDirectory);
        } elseif ($backend === 'mysql') {
            ['pdo' => $pdo, 'drop' => $dropDatabase] = MysqlSchema::createDatabase();
            $repositories = PdoRepositoryFactory::create($pdo);
        } else {
            $repositories = PdoRepositoryFactory::create(SqliteSchema::createDatabase());
        }

        $container = Bootstrap::createContainer(
            ['uygulama' => ['ad' => 'Vampir Köylü Test']],
            $clock,
            new Randomizer(new Mt19937($seed)),
            $repositories,
            $rules,
        );

        $app = new self($container, $clock, $repositories, $rules);
        $app->jsonDirectory = $jsonDirectory;
        $app->dropDatabase = $dropDatabase;

        return $app;
    }

    public function cleanup(): void
    {
        if ($this->dropDatabase !== null) {
            ($this->dropDatabase)();
        }
        if ($this->jsonDirectory === null || !is_dir($this->jsonDirectory)) {
            return;
        }
        foreach (glob($this->jsonDirectory . '/{,.}*', GLOB_BRACE) ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->jsonDirectory);
    }

    public function auth(): AuthService
    {
        return $this->container->get(AuthService::class);
    }

    public function lobby(): LobbyService
    {
        return $this->container->get(LobbyService::class);
    }

    public function games(): GameService
    {
        return $this->container->get(GameService::class);
    }

    public function registerUser(string $name): User
    {
        return $this->auth()->register($name, mb_strtolower($name) . '@test.com', self::PASSWORD, self::PASSWORD);
    }

    public function registerUsers(int $count): array
    {
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $users[] = $this->registerUser('Oyuncu' . ++$this->userCounter);
        }

        return $users;
    }

    public function createGame(int $count, bool $start = true, int $vampires = 0, int $trappers = 0): array
    {
        $users = $this->registerUsers($count);
        $game = $this->lobby()->createRoom($users[0]);
        foreach (array_slice($users, 1) as $user) {
            $this->lobby()->join($game->roomCode, $user);
        }
        if ($vampires > 0) {
            $this->lobby()->updateRoleSettings($game, $users[0], $vampires, $trappers);
        }
        if ($start) {
            $this->games()->start($game, $users[0]);
        }

        return [$this->repositories->games->findById($game->id), $users];
    }

    public function players(Game $game): array
    {
        return $this->repositories->players->listByGame($game->id);
    }

    public function playerWithRole(Game $game, RoleType $role, int $index = 0): Player
    {
        $matches = array_values(array_filter($this->players($game), static fn (Player $p): bool => $p->role === $role));

        return $matches[$index] ?? throw new \LogicException("{$role->value} rolünde {$index}. oyuncu yok.");
    }

    public function userOf(Player $player, array $users): User
    {
        foreach ($users as $user) {
            if ($user->id === $player->userId) {
                return $user;
            }
        }
        throw new \LogicException('Oyuncunun kullanıcısı bulunamadı.');
    }

    public function act(Game $game, array $users, Player $actor, Player $target): void
    {
        $this->games()->submitNightAction($game, $this->userOf($actor, $users), $target->id);
    }

    public function currentPhase(Game $game): Phase
    {
        return $this->repositories->phases->current($game->id) ?? throw new \LogicException('Faz yok.');
    }

    public function currentPhaseType(Game $game): PhaseType
    {
        return $this->currentPhase($game)->type;
    }

    public function finishCurrentPhase(Game $game): void
    {
        $this->clock->set($this->currentPhase($game)->endsAt);
        $this->container->get(PhaseManager::class)->tick($game->id);
    }


    public function skipToNextNight(Game $game): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->finishCurrentPhase($game);
        }
    }
}
