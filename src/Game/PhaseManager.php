<?php

declare(strict_types=1);

namespace VampirKoylu\Game;

use VampirKoylu\Models\DeathCause;
use VampirKoylu\Models\Game;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Models\Phase;
use VampirKoylu\Models\PhaseType;
use VampirKoylu\Models\Player;
use VampirKoylu\Models\RoleType;
use VampirKoylu\Models\Team;
use VampirKoylu\Repositories\ActionRepositoryInterface;
use VampirKoylu\Repositories\GameLogRepositoryInterface;
use VampirKoylu\Repositories\GameRepositoryInterface;
use VampirKoylu\Repositories\PhaseRepositoryInterface;
use VampirKoylu\Repositories\PlayerRepositoryInterface;
use VampirKoylu\Repositories\TransactionManagerInterface;
use VampirKoylu\Repositories\VoteRepositoryInterface;
use VampirKoylu\Support\Clock;

final class PhaseManager
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
        private readonly NightResolver $nightResolver,
        private readonly VoteCounter $voteCounter,
        private readonly WinChecker $winChecker,
    ) {
    }

    public function tick(int $gameId): bool
    {
        $game = $this->games->findById($gameId);
        if ($game === null || $game->status !== GameStatus::InProgress) {
            return false;
        }

        $phase = $this->phases->current($gameId);
        if ($phase === null || !$this->isOver($phase)) {
            return false;
        }

        return $this->transactions->run(function () use ($game, $phase): bool {
            // aynı anda gelen diğer istekler burada false alır
            if (!$this->games->bumpVersion($game->id, $game->version)) {
                return false;
            }
            $this->advance($game, $phase);

            return true;
        });
    }

    public function beginPhase(Game $game, PhaseType $type, int $dayNumber): Phase
    {
        $now = $this->clock->now();
        $endsAt = $now->modify('+' . $this->rules->durationOf($type) . ' seconds');
        $phase = $this->phases->create($game->id, $type, $dayNumber, $now, $endsAt);

        $message = match ($type) {
            PhaseType::Night => "{$dayNumber}. gece başladı. Özel roller gizlice eylemini seçiyor.",
            PhaseType::Day => "{$dayNumber}. gün başladı. Tartışma zamanı.",
            PhaseType::Vote => 'Oylama başladı. Şüphelendiğiniz oyuncuya oy verin ya da pas geçin.',
        };
        $this->log($game, 'phase_change', $message);

        return $phase;
    }

    private function isOver(Phase $phase): bool
    {
        if ($this->clock->now() >= $phase->endsAt) {
            return true;
        }

        return $phase->type === PhaseType::Vote && $this->everyoneVoted($phase);
    }

    private function everyoneVoted(Phase $phase): bool
    {
        $alive = array_filter($this->players->listByGame($phase->gameId), static fn (Player $p): bool => $p->alive);

        return count($this->votes->forPhase($phase->id)) >= count($alive);
    }

    private function advance(Game $game, Phase $phase): void
    {
        match ($phase->type) {
            PhaseType::Night => $this->finishNight($game, $phase),
            PhaseType::Day => $this->beginPhase($game, PhaseType::Vote, $phase->dayNumber),
            PhaseType::Vote => $this->finishVote($game, $phase),
        };
    }

    private function finishNight(Game $game, Phase $phase): void
    {
        $players = $this->players->listByGame($game->id);
        $names = $this->namesById($players);
        $outcome = $this->nightResolver->resolve($this->actions->forPhase($phase->id), $players);

        $this->actions->markCancelled($outcome->cancelledActionIds);
        $this->logSecretNightEvents($game, $outcome, $players, $names);

        $victimIds = $outcome->victimPlayerIds();
        foreach ($victimIds as $victimId) {
            $this->players->markDead($victimId, DeathCause::Night, $phase->id);
        }
        $this->announceMorning($game, $victimIds, $players, $names);

        $this->continueOrFinish($game, PhaseType::Day, $phase->dayNumber);
    }

    private function announceMorning(Game $game, array $victimIds, array $players, array $names): void
    {
        if ($victimIds === []) {
            $this->log($game, 'night_result', 'Bu gece kimse ölmedi.');

            return;
        }

        $victimNames = array_map(static fn (int $id): string => $names[$id], $victimIds);
        $this->log($game, 'night_result', 'Bu gece ' . $this->joinNames($victimNames) . ' öldü.');

        foreach ($victimIds as $victimId) {
            if ($this->findPlayer($players, $victimId)->role === RoleType::Seer) {
                $this->log($game, 'role_reveal', "{$names[$victimId]} Kâhin'di.");
            }
        }
    }

    private function finishVote(Game $game, Phase $phase): void
    {
        $players = $this->players->listByGame($game->id);
        $names = $this->namesById($players);
        $aliveIds = array_map(
            static fn (Player $p): int => $p->id,
            array_filter($players, static fn (Player $p): bool => $p->alive),
        );

        $result = $this->voteCounter->count($this->votes->forPhase($phase->id), array_values($aliveIds));
        $this->log($game, 'vote_result', $this->describeVoteCounts($result, $names));

        if ($result->eliminatedPlayerId !== null) {
            $eliminated = $result->eliminatedPlayerId;
            $this->players->markDead($eliminated, DeathCause::Vote, $phase->id);
            $role = $this->findPlayer($players, $eliminated)->role;
            $this->log($game, 'elimination', "{$names[$eliminated]} elendi. Rolü: {$role?->label()}.");
        } else {
            $this->log($game, 'elimination', 'Oylamada kimse elenmedi.');
        }

        $this->continueOrFinish($game, PhaseType::Night, $phase->dayNumber + 1);
    }

    private function continueOrFinish(Game $game, PhaseType $next, int $dayNumber): void
    {
        $winner = $this->winChecker->check($this->players->listByGame($game->id));

        if ($winner === null) {
            $this->beginPhase($game, $next, $dayNumber);

            return;
        }

        $this->finishGame($game, $winner);
    }

    private function finishGame(Game $game, Team $winner): void
    {
        $this->games->finish($game->id, $winner);
        $this->log($game, 'game_over', "Oyun bitti. Kazanan: {$winner->label()}.");
    }

    private function logSecretNightEvents(Game $game, NightOutcome $outcome, array $players, array $names): void
    {
        foreach ($outcome->trapResults as $trap) {
            $result = $trap->caughtPlayerId === null ? 'kimse takılmadı' : "{$names[$trap->caughtPlayerId]} takıldı";
            $this->logSecret($game, "Tuzakçı {$names[$trap->trapperPlayerId]}, {$names[$trap->housePlayerId]} adlı oyuncunun evine tuzak kurdu: {$result}.");
        }
        if ($outcome->targetedPlayerIds !== []) {
            $targets = array_map(static fn (int $id): string => $names[$id], $outcome->targetedPlayerIds);
            $this->logSecret($game, 'Vampirlerin hedefleri: ' . $this->joinNames($targets) . '.');
        }
        if ($outcome->protectedPlayerId !== null) {
            $verb = $outcome->wasSaved() ? 'kurtardı' : 'korudu';
            $this->logSecret($game, "Doktor {$names[$outcome->protectedPlayerId]} adlı oyuncuyu {$verb}.");
        }
        foreach ($outcome->inspections as $seerId => $targetId) {
            $role = $this->findPlayer($players, $targetId)->role;
            $this->logSecret($game, "Kâhin {$names[$seerId]}, {$names[$targetId]} adlı oyuncunun rolünü öğrendi: {$role?->label()}.");
        }
    }

    private function describeVoteCounts(VoteResult $result, array $names): string
    {
        $counts = $result->counts;
        arsort($counts);
        $parts = [];
        foreach ($counts as $playerId => $votes) {
            $parts[] = "{$names[$playerId]} {$votes} oy";
        }
        $parts[] = "Pas {$result->passCount}";

        return 'Oylama sonucu: ' . implode(', ', $parts) . '.';
    }

    private function joinNames(array $names): string
    {
        if (count($names) === 1) {
            return $names[0];
        }
        $last = array_pop($names);

        return implode(', ', $names) . " ve {$last}";
    }

    private function namesById(array $players): array
    {
        $names = [];
        foreach ($players as $player) {
            $names[$player->id] = $player->name;
        }

        return $names;
    }

    private function findPlayer(array $players, int $playerId): Player
    {
        foreach ($players as $player) {
            if ($player->id === $playerId) {
                return $player;
            }
        }
        throw new \LogicException("Oyuncu bulunamadı: {$playerId}");
    }

    private function log(Game $game, string $type, string $message): void
    {
        $this->logs->add($game->id, $type, $message, false, $this->clock->now());
    }

    private function logSecret(Game $game, string $message): void
    {
        $this->logs->add($game->id, 'night_action', $message, true, $this->clock->now());
    }
}
