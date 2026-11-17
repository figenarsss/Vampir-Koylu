<?php

declare(strict_types=1);

namespace VampirKoylu\Dev;

use Random\Randomizer;
use VampirKoylu\Core\Container;
use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Game\PhaseManager;
use VampirKoylu\Models\Game;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Models\PhaseType;
use VampirKoylu\Models\Player;
use VampirKoylu\Models\User;
use VampirKoylu\Repositories\GameRepositoryInterface;
use VampirKoylu\Repositories\PhaseRepositoryInterface;
use VampirKoylu\Repositories\PlayerRepositoryInterface;
use VampirKoylu\Services\GameService;
use VampirKoylu\Services\LobbyService;
use VampirKoylu\Support\FakeClock;

// Yalnızca örnek veri ve testler için
final class GameSimulator
{
    private const MOVE_DELAY_SECONDS = 3;

    private readonly LobbyService $lobby;
    private readonly GameService $gameService;
    private readonly PhaseManager $phaseManager;
    private readonly GameRepositoryInterface $games;
    private readonly PhaseRepositoryInterface $phases;
    private readonly PlayerRepositoryInterface $players;

    public function __construct(
        Container $container,
        private readonly FakeClock $clock,
        private readonly Randomizer $random,
    ) {
        $this->lobby = $container->get(LobbyService::class);
        $this->gameService = $container->get(GameService::class);
        $this->phaseManager = $container->get(PhaseManager::class);
        $this->games = $container->get(GameRepositoryInterface::class);
        $this->phases = $container->get(PhaseRepositoryInterface::class);
        $this->players = $container->get(PlayerRepositoryInterface::class);
    }

    public function createRoom(array $users): Game
    {
        $game = $this->lobby->createRoom($users[0]);
        foreach (array_slice($users, 1) as $user) {
            $this->lobby->join($game->roomCode, $user);
        }

        return $game;
    }

    public function playToEnd(array $users, ?callable $afterEachPhase = null, int $vampires = 0, int $trappers = 0): Game
    {
        $game = $this->createRoom($users);
        if ($vampires > 0) {
            $this->lobby->updateRoleSettings($game, $users[0], $vampires, $trappers);
        }
        $this->clock->advance(10);
        $this->gameService->start($game, $users[0]);

        for ($guard = 0; $guard < 200; $guard++) {
            $game = $this->games->findById($game->id);
            if ($game->status === GameStatus::Finished) {
                return $game;
            }

            $phase = $this->phases->current($game->id);
            $this->clock->set($phase->startedAt->modify('+' . self::MOVE_DELAY_SECONDS . ' seconds'));
            match ($phase->type) {
                PhaseType::Night => $this->playNight($game, $users),
                PhaseType::Vote => $this->playVote($game, $users),
                PhaseType::Day => null,
            };

            if ($this->phases->current($game->id)->id === $phase->id) {
                $this->clock->set($phase->endsAt);
                $this->phaseManager->tick($game->id);
            }
            if ($afterEachPhase !== null) {
                $afterEachPhase($this->games->findById($game->id));
            }
        }

        throw new \LogicException('Simülasyon sınırı aşıldı; oyun bitmedi.');
    }

    private function playNight(Game $game, array $users): void
    {
        $alive = $this->alivePlayers($game);
        foreach ($alive as $player) {
            if ($player->role?->nightAction() === null) {
                continue;
            }
            foreach ($this->random->shuffleArray($alive) as $target) {
                try {
                    $this->gameService->submitNightAction($game, $this->userOf($player, $users), $target->id);
                    break;
                } catch (RuleViolationException) {
                    continue;
                }
            }
        }
    }

    private function playVote(Game $game, array $users): void
    {
        $alive = $this->alivePlayers($game);
        foreach ($alive as $player) {
            $others = array_values(array_filter($alive, static fn (Player $p): bool => $p->id !== $player->id));
            $pass = $this->random->getInt(1, 100) <= 20;
            $target = $pass ? null : $others[$this->random->getInt(0, count($others) - 1)]->id;

            try {
                $this->gameService->submitVote($game, $this->userOf($player, $users), $target);
            } catch (RuleViolationException) {
                return;
            }
        }
    }

    private function alivePlayers(Game $game): array
    {
        return array_values(array_filter($this->players->listByGame($game->id), static fn (Player $p): bool => $p->alive));
    }

    private function userOf(Player $player, array $users): User
    {
        foreach ($users as $user) {
            if ($user->id === $player->userId) {
                return $user;
            }
        }
        throw new \LogicException('Oyuncunun kullanıcısı bulunamadı.');
    }
}
