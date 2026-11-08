<?php

declare(strict_types=1);

namespace VampirKoylu\Controllers;

use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Game\GameRules;
use VampirKoylu\Http\Request;
use VampirKoylu\Http\Response;
use VampirKoylu\Http\View;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Repositories\GameRepositoryInterface;
use VampirKoylu\Services\LobbyService;

final class RoomController extends GameAwareController
{
    public function __construct(
        View $view,
        GameRepositoryInterface $games,
        private readonly LobbyService $lobby,
        private readonly GameRules $rules,
    ) {
        parent::__construct($view, $games);
    }

    public function index(Request $request): Response
    {
        $term = $request->string('q');

        return $this->render($request, 'rooms/index', [
            'title' => 'Açık odalar',
            'rooms' => $this->games->search($term, GameStatus::Lobby, null),
            'term' => $term,
            'maxPlayers' => $this->rules->maxPlayers,
        ]);
    }

    public function settings(Request $request): Response
    {
        $game = $this->findGame($request);
        $vampires = $request->int('vampires') ?? 0;
        $trappers = $request->int('trappers') ?? 0;

        return $this->attempt(
            $request,
            "/oyun/{$game->roomCode}",
            'Rol ayarları kaydedildi.',
            fn () => $this->lobby->updateRoleSettings($game, $request->user, $vampires, $trappers),
        );
    }

    public function create(Request $request): Response
    {
        $game = $this->lobby->createRoom($request->user);
        $request->session->flash('success', "Oda açıldı. Arkadaşların bu kodla katılabilir: {$game->roomCode}");

        return $this->redirect($request, "/oyun/{$game->roomCode}");
    }

    public function join(Request $request): Response
    {
        try {
            $game = $this->lobby->join($request->string('code'), $request->user);
        } catch (RuleViolationException $error) {
            $request->session->flash('error', $error->getMessage());

            return $this->redirect($request, '/');
        }

        return $this->redirect($request, "/oyun/{$game->roomCode}");
    }

    public function leave(Request $request): Response
    {
        $game = $this->findGame($request);

        return $this->attempt($request, '/', 'Odadan ayrıldınız.', fn () => $this->lobby->leave($game, $request->user));
    }

    public function kick(Request $request): Response
    {
        $game = $this->findGame($request);
        $playerId = $request->int('player_id') ?? 0;

        return $this->attempt($request, "/oyun/{$game->roomCode}", 'Oyuncu odadan atıldı.', fn () => $this->lobby->kick($game, $request->user, $playerId));
    }

    public function delete(Request $request): Response
    {
        $game = $this->findGame($request);

        try {
            $this->lobby->deleteRoom($game, $request->user);
        } catch (RuleViolationException $error) {
            $request->session->flash('error', $error->getMessage());

            return $this->redirect($request, "/oyun/{$game->roomCode}");
        }
        $request->session->flash('success', 'Oda silindi.');

        return $this->redirect($request, '/');
    }
}
