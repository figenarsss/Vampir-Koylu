<?php

declare(strict_types=1);

namespace VampirKoylu\Services;

use VampirKoylu\Exceptions\ForbiddenException;
use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Game\GameRules;
use VampirKoylu\Game\PhaseManager;
use VampirKoylu\Game\RoleDistributor;
use VampirKoylu\Game\TargetRules\TargetRules;
use VampirKoylu\Models\Game;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Models\PhaseType;
use VampirKoylu\Models\Player;
use VampirKoylu\Models\User;
use VampirKoylu\Repositories\ActionRepositoryInterface;
use VampirKoylu\Repositories\GameLogRepositoryInterface;
use VampirKoylu\Repositories\GameRepositoryInterface;
use VampirKoylu\Repositories\PhaseRepositoryInterface;
use VampirKoylu\Repositories\PlayerRepositoryInterface;
use VampirKoylu\Repositories\TransactionManagerInterface;
use VampirKoylu\Repositories\VoteRepositoryInterface;
use VampirKoylu\Support\Clock;

final class GameService
{
    public function __construct(
        private readonly GameRepositoryInterface $games,
        private readonly PlayerRepositoryInterface $players,
        private readonly PhaseRepositoryInterface $phases,
        private readonly ActionRepositoryInterface $actions,
        private readonly VoteRepositoryInterface $votes,
        private readonly GameLogRepositoryInterface $logs,
        private readonly TransactionManagerInterface $transactions,
        private readonly Clock $clock,
        private readonly GameRules $rules,
        private readonly RoleDistributor $roleDistributor,
        private readonly PhaseManager $phaseManager,
        private readonly NightHistory $history,
    ) {
    }

    public function start(Game $game, User $host): void
    {
        if (!$game->isHost($host)) {
            throw new ForbiddenException('Oyunu yalnızca oda kurucusu başlatabilir.');
        }

        $this->transactions->run(function () use ($game): void {
            $game = $this->games->findById($game->id) ?? throw new RuleViolationException('Oyun bulunamadı.');
            $players = $this->players->listByGame($game->id);
            $count = count($players);

            if ($count < $this->rules->minPlayers) {
                throw new RuleViolationException("Oyunu başlatmak için en az {$this->rules->minPlayers} oyuncu gerekir (şu an {$count}).");
            }
            if ($count > $this->rules->maxPlayers) {
                throw new RuleViolationException("Oyun en fazla {$this->rules->maxPlayers} oyuncuyla başlatılabilir (şu an {$count}).");
            }

            $vampires = $this->rules->resolveVampireCount($game->vampireCount, $count);
            $trappers = $this->rules->resolveTrapperCount($game->trapperCount, $count);
            $roles = $this->roleDistributor->distribute(array_map(static fn (Player $p): int => $p->id, $players), $vampires, $trappers);

            // koşullu UPDATE: oyun hâlâ lobideyse başlar
            if (!$this->games->markStarted($game->id, $vampires, $trappers)) {
                throw new RuleViolationException('Bu oyun zaten başlatılmış.');
            }

            $this->players->assignRoles($roles);
            $this->logs->add($game->id, 'game_start', "Oyun başladı! {$count} oyuncu, {$vampires} vampir var.", false, $this->clock->now());
            $this->phaseManager->beginPhase($game, PhaseType::Night, 1);
        });
    }

    public function submitNightAction(Game $game, User $user, int $targetPlayerId): void
    {
        [$game, $me] = $this->requireActivePlayer($game, $user);

        $phase = $this->phases->current($game->id);
        if ($phase === null || $phase->type !== PhaseType::Night) {
            throw new RuleViolationException('Gece eylemleri yalnızca gece fazında seçilebilir.');
        }
        if ($this->clock->now() >= $this->rules->nightSelectionEndsAt($phase)) {
            throw new RuleViolationException("Eylem seçme süresi doldu (gecenin ilk {$this->rules->nightSelectionSeconds} saniyesi).");
        }

        $actionType = $me->role?->nightAction();
        if ($actionType === null) {
            throw new RuleViolationException('Rolünün gece yeteneği yok.');
        }

        $target = $this->requireAliveTarget($game, $targetPlayerId);
        $violation = TargetRules::forRole($me->role)?->violation($me, $target, $this->history->lastNightTarget($me, $phase));
        if ($violation !== null) {
            throw new RuleViolationException($violation);
        }

        // vampirin başka bir vampiri seçmesi reddedilmez, rol sızmasın
        if (!$this->actions->add($phase->id, $me->id, $target->id, $actionType, $this->clock->now())) {
            throw new RuleViolationException('Bu gece seçimini yaptın; değiştiremezsin.');
        }
    }

    public function submitVote(Game $game, User $user, ?int $targetPlayerId): void
    {
        [$game, $me] = $this->requireActivePlayer($game, $user);

        $phase = $this->phases->current($game->id);
        if ($phase === null || $phase->type !== PhaseType::Vote || $this->clock->now() >= $phase->endsAt) {
            throw new RuleViolationException('Şu anda oylama yapılmıyor.');
        }

        if ($targetPlayerId !== null) {
            $target = $this->requireAliveTarget($game, $targetPlayerId);
            if ($target->id === $me->id) {
                throw new RuleViolationException('Kendine oy veremezsin.');
            }
        }

        if (!$this->votes->cast($phase->id, $me->id, $targetPlayerId, $this->clock->now())) {
            throw new RuleViolationException('Bu oylamada zaten oy kullandın; oyunu değiştiremezsin.');
        }

        $this->phaseManager->tick($game->id);
    }

    private function requireActivePlayer(Game $game, User $user): array
    {
        $game = $this->games->findById($game->id) ?? throw new RuleViolationException('Oyun bulunamadı.');
        if ($game->status !== GameStatus::InProgress) {
            throw new RuleViolationException('Oyun şu anda oynanmıyor.');
        }

        $me = $this->players->findByGameAndUser($game->id, $user->id)
            ?? throw new ForbiddenException('Bu oyunun oyuncusu değilsiniz.');
        if (!$me->alive) {
            throw new RuleViolationException('Ölü ve elenen oyuncular eylem yapamaz, yalnızca izleyebilir.');
        }

        return [$game, $me];
    }

    private function requireAliveTarget(Game $game, int $targetPlayerId): Player
    {
        $target = $this->players->findById($targetPlayerId);
        if ($target === null || $target->gameId !== $game->id) {
            throw new RuleViolationException('Geçersiz hedef.');
        }
        if (!$target->alive) {
            throw new RuleViolationException('Seçilen oyuncu artık oyunda değil.');
        }

        return $target;
    }
}
