<?php

declare(strict_types=1);

namespace VampirKoylu\Services;

use VampirKoylu\Exceptions\ForbiddenException;
use VampirKoylu\Game\GameRules;
use VampirKoylu\Game\TargetRules\TargetRules;
use VampirKoylu\Models\ActionType;
use VampirKoylu\Models\Game;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Models\NightAction;
use VampirKoylu\Models\Phase;
use VampirKoylu\Models\PhaseType;
use VampirKoylu\Models\Player;
use VampirKoylu\Models\RoleType;
use VampirKoylu\Models\User;
use VampirKoylu\Repositories\ActionRepositoryInterface;
use VampirKoylu\Repositories\GameLogRepositoryInterface;
use VampirKoylu\Repositories\PhaseRepositoryInterface;
use VampirKoylu\Repositories\PlayerRepositoryInterface;
use VampirKoylu\Repositories\VoteRepositoryInterface;
use VampirKoylu\Support\Clock;

// Gizlilik kuralı yalnızca bu sınıfta uygulanır
final class GameStateBuilder
{
    public function __construct(
        private readonly PlayerRepositoryInterface $players,
        private readonly PhaseRepositoryInterface $phases,
        private readonly ActionRepositoryInterface $actions,
        private readonly VoteRepositoryInterface $votes,
        private readonly GameLogRepositoryInterface $logs,
        private readonly NightHistory $history,
        private readonly Clock $clock,
        private readonly GameRules $rules,
    ) {
    }

    public function build(Game $game, User $viewer): array
    {
        $players = $this->players->listByGame($game->id);
        $me = $this->findByUser($players, $viewer->id)
            ?? throw new ForbiddenException('Bu oyunun oyuncusu değilsiniz.');

        $finished = $game->status === GameStatus::Finished;
        $phase = $game->status === GameStatus::InProgress ? $this->phases->current($game->id) : null;
        $stage = $this->stageOf($game, $phase);
        $now = $this->clock->now();

        $selectionOpen = $phase !== null
            && $phase->type === PhaseType::Night
            && $now < $this->rules->nightSelectionEndsAt($phase);

        $state = [
            'game' => [
                'code' => $game->roomCode,
                'status' => $game->status->value,
                'stage' => $stage,
                'stage_label' => $this->stageLabel($stage, $phase),
                'day' => $phase?->dayNumber,
                'remaining_seconds' => $phase === null ? null : max(0, $phase->endsAt->getTimestamp() - $now->getTimestamp()),
                'selection_open' => $selectionOpen,
                'winner' => $game->winnerTeam === null ? null : [
                    'key' => $game->winnerTeam->value,
                    'label' => $game->winnerTeam->label(),
                    'emoji' => $game->winnerTeam->emoji(),
                ],
                'player_count' => count($players),
                'min_players' => $this->rules->minPlayers,
                'max_players' => $this->rules->maxPlayers,
                'vampire_count' => $game->status === GameStatus::Lobby ? null : $game->vampireCount,
            ],
            'me' => [
                'player_id' => $me->id,
                'name' => $me->name,
                'is_host' => $game->isHost($viewer),
                'alive' => $me->alive,
                'is_ghost' => !$me->alive && $game->status === GameStatus::InProgress,
                'role' => $this->roleView($me->role),
                'role_description' => $me->role?->description(),
            ],
            'players' => array_map(
                fn (Player $p): array => $this->playerView($p, $me, $game, $finished),
                $players,
            ),
            'settings' => $stage === 'lobby' ? $this->settingsPanel($game, count($players)) : null,
            'night' => $stage === 'night' ? $this->nightPanel($me, $players, $phase, $selectionOpen) : null,
            'vote' => $stage === 'vote' ? $this->votePanel($me, $players, $phase) : null,
            'seer_results' => $this->seerResults($me, $players, $phase, $finished),
            'trap_notes' => $this->trapNotes($me, $phase, $finished),
            'announcements' => $this->announcements($game),
            'report' => $finished ? $this->report($game) : null,
        ];

        $state['signature'] = $this->signature($state);

        return $state;
    }

    private function stageOf(Game $game, ?Phase $phase): string
    {
        return match ($game->status) {
            GameStatus::Lobby => 'lobby',
            GameStatus::Finished => 'finished',
            GameStatus::InProgress => $phase?->type->value ?? 'lobby',
        };
    }

    private function stageLabel(string $stage, ?Phase $phase): string
    {
        return match ($stage) {
            'lobby' => 'Lobi',
            'finished' => 'Oyun bitti',
            default => $phase?->type->label() . ' · ' . $phase?->dayNumber . '. gün',
        };
    }

    private function playerView(Player $player, Player $me, Game $game, bool $finished): array
    {
        $isMe = $player->id === $me->id;
        $roleVisible = $isMe || $player->isRoleRevealed() || $finished;

        return [
            'id' => $player->id,
            'name' => $player->name,
            'alive' => $player->alive,
            'is_me' => $isMe,
            'is_host' => $player->userId === $game->hostUserId,
            'status' => $player->alive ? 'Canlı' : ucfirst($player->deathCause?->label() ?? 'oyunda değil'),
            'role' => $roleVisible ? $this->roleView($player->role) : null,
        ];
    }

    private function roleView(?RoleType $role): ?array
    {
        return $role === null ? null : ['key' => $role->value, 'label' => $role->label(), 'emoji' => $role->emoji()];
    }

    private function settingsPanel(Game $game, int $playerCount): array
    {
        return [
            'vampire_options' => $this->rules->vampireOptions($playerCount),
            'trapper_options' => $this->rules->trapperOptions($playerCount),
            'vampires' => $this->rules->resolveVampireCount($game->vampireCount, $playerCount),
            'trappers' => $this->rules->resolveTrapperCount($game->trapperCount, $playerCount),
        ];
    }

    private function nightPanel(Player $me, array $players, Phase $phase, bool $selectionOpen): ?array
    {
        $actionType = $me->role?->nightAction();
        if (!$me->alive || $actionType === null) {
            return null;
        }

        $chosenId = null;
        foreach ($this->actions->forPhase($phase->id) as $action) {
            if ($action->actorPlayerId === $me->id) {
                $chosenId = $action->targetPlayerId;
            }
        }

        $rule = TargetRules::forRole($me->role);
        $lastTarget = $this->history->lastNightTarget($me, $phase);
        $targets = [];
        $chosenName = null;
        foreach ($players as $player) {
            if (!$player->alive || $rule?->violation($me, $player, $lastTarget) !== null) {
                continue;
            }
            $targets[] = ['id' => $player->id, 'name' => $player->name, 'is_me' => $player->id === $me->id];
        }
        foreach ($players as $player) {
            if ($player->id === $chosenId) {
                $chosenName = $player->name;
            }
        }

        return [
            'action' => $actionType->value,
            'action_label' => $actionType->label(),
            'selection_open' => $selectionOpen,
            'targets' => $targets,
            'chosen_id' => $chosenId,
            'chosen_name' => $chosenName,
        ];
    }

    private function votePanel(Player $me, array $players, Phase $phase): array
    {
        $votes = $this->votes->forPhase($phase->id);
        $myVote = null;
        $counts = [];
        $passCount = 0;

        foreach ($votes as $vote) {
            if ($vote->voterPlayerId === $me->id) {
                $myVote = $vote;
            }
            if ($vote->isPass()) {
                $passCount++;
            } else {
                $counts[$vote->targetPlayerId] = ($counts[$vote->targetPlayerId] ?? 0) + 1;
            }
        }

        $tally = [];
        $targets = [];
        $aliveCount = 0;
        foreach ($players as $player) {
            if (!$player->alive) {
                continue;
            }
            $aliveCount++;
            $tally[] = ['id' => $player->id, 'name' => $player->name, 'votes' => $counts[$player->id] ?? 0];
            if ($player->id !== $me->id) {
                $targets[] = ['id' => $player->id, 'name' => $player->name];
            }
        }

        return [
            'can_vote' => $me->alive && $myVote === null,
            'has_voted' => $myVote !== null,
            'my_vote_was_pass' => $myVote?->isPass() ?? false,
            'my_target_id' => $myVote?->targetPlayerId,
            'targets' => $targets,
            'tally' => $tally,
            'pass_count' => $passCount,
            'voted_count' => count($votes),
            'voter_count' => $aliveCount,
        ];
    }

    private function seerResults(Player $me, array $players, ?Phase $currentPhase, bool $finished): array
    {
        if ($me->role !== RoleType::Seer) {
            return [];
        }

        $byId = [];
        foreach ($players as $player) {
            $byId[$player->id] = $player;
        }

        $results = [];
        foreach ($this->actions->forActor($me->id) as $action) {
            if ($action->type !== ActionType::Inspect || $this->isUnresolved($action, $currentPhase, $finished)) {
                continue;
            }
            $target = $byId[$action->targetPlayerId] ?? null;
            if ($target !== null) {
                $results[] = ['name' => $target->name, 'role' => $this->roleView($target->role)];
            }
        }

        return $results;
    }

    private function trapNotes(Player $me, ?Phase $currentPhase, bool $finished): array
    {
        $notes = [];
        foreach ($this->actions->forActor($me->id) as $action) {
            if ($this->isUnresolved($action, $currentPhase, $finished)) {
                continue;
            }
            $day = $this->phases->findById($action->phaseId)?->dayNumber;

            if ($action->cancelled) {
                $notes[] = "{$day}. gece: Bir tuzağa takıldın, eylemin iptal oldu.";
            }
            if ($action->type === ActionType::Trap) {
                $caught = $this->someoneWasCaught($action);
                $notes[] = "{$day}. gece: Tuzağına " . ($caught ? 'biri takıldı.' : 'kimse takılmadı.');
            }
        }

        return $notes;
    }

    private function someoneWasCaught(NightAction $trap): bool
    {
        foreach ($this->actions->forPhase($trap->phaseId) as $other) {
            if ($other->cancelled && $other->type !== ActionType::Trap && $other->targetPlayerId === $trap->targetPlayerId) {
                return true;
            }
        }

        return false;
    }

    // gece sonuçları sabaha kadar gizli
    private function isUnresolved(NightAction $action, ?Phase $currentPhase, bool $finished): bool
    {
        return !$finished && $currentPhase !== null && $action->phaseId === $currentPhase->id;
    }

    private function announcements(Game $game): array
    {
        $entries = array_map(
            static fn ($log): array => [
                'time' => $log->createdAt->format('H:i:s'),
                'type' => $log->eventType,
                'message' => $log->message,
            ],
            $this->logs->listByGame($game->id, false),
        );

        return array_reverse($entries);
    }

    private function report(Game $game): array
    {
        return array_map(
            static fn ($log): array => [
                'time' => $log->createdAt->format('H:i:s'),
                'type' => $log->eventType,
                'message' => $log->message,
                'secret' => $log->secret,
            ],
            $this->logs->listByGame($game->id, true),
        );
    }

    private function signature(array $state): string
    {
        $game = $state['game'];
        $lobby = $game['stage'] === 'lobby';

        return implode('|', [
            $game['status'],
            $game['stage'],
            $game['day'] ?? 0,
            (int) $game['selection_open'],
            (int) $state['me']['alive'],
            $state['night']['chosen_id'] ?? 0,
            (int) ($state['vote']['has_voted'] ?? false),
            $lobby ? $game['player_count'] : 0,
            $lobby ? $state['settings']['vampires'] . '-' . $state['settings']['trappers'] : '',
        ]);
    }

    private function findByUser(array $players, int $userId): ?Player
    {
        foreach ($players as $player) {
            if ($player->userId === $userId) {
                return $player;
            }
        }

        return null;
    }
}
