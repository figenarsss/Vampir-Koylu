<?php

declare(strict_types=1);

namespace VampirKoylu\Controllers;

use VampirKoylu\Exceptions\ForbiddenException;
use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Game\PhaseManager;
use VampirKoylu\Http\Request;
use VampirKoylu\Http\Response;
use VampirKoylu\Http\View;
use VampirKoylu\Models\Game;
use VampirKoylu\Repositories\GameRepositoryInterface;
use VampirKoylu\Services\GameService;
use VampirKoylu\Services\GameStateBuilder;

final class GameController extends GameAwareController
{
    public function __construct(
        View $view,
        GameRepositoryInterface $games,
        private readonly GameService $gameService,
        private readonly PhaseManager $phaseManager,
        private readonly GameStateBuilder $stateBuilder,
    ) {
        parent::__construct($view, $games);
    }

    public function show(Request $request): Response
    {
        try {
            [$game, $state] = $this->loadState($request);
        } catch (ForbiddenException $error) {
            $request->session->flash('error', $error->getMessage());

            return $this->redirect($request, '/');
        }

        return $this->render($request, 'game/show', [
            'title' => "Oda {$game->roomCode}",
            'state' => $state,
            'code' => $game->roomCode,
            'fragmentUrl' => $request->url("/oyun/{$game->roomCode}/parca"),
        ]);
    }

    public function fragment(Request $request): Response
    {
        $request->session->release(); // oturum kilidini hemen bırak: yenileme istekleri birbirini beklemesin
        [$game, $state] = $this->loadState($request);

        return Response::html($this->view->partial('game/fragment', ['state' => $state, 'code' => $game->roomCode], $request));
    }

    public function state(Request $request): Response
    {
        $request->session->release();
        [, $state] = $this->loadState($request);

        return Response::json($state);
    }

    public function start(Request $request): Response
    {
        $game = $this->findGame($request);

        return $this->attempt($request, "/oyun/{$game->roomCode}", 'Oyun başladı! Rolünü ekranda göreceksin.', fn () => $this->gameService->start($game, $request->user));
    }

    public function action(Request $request): Response
    {
        $game = $this->findGame($request);
        $targetId = $request->int('target_id');

        return $this->attempt($request, "/oyun/{$game->roomCode}", 'Seçimin kaydedildi.', function () use ($game, $request, $targetId): void {
            $this->gameService->submitNightAction($game, $request->user, $targetId ?? throw new RuleViolationException('Önce bir oyuncu seçin.'));
        });
    }

    public function vote(Request $request): Response
    {
        $game = $this->findGame($request);
        $choice = $request->string('target_id');

        return $this->attempt($request, "/oyun/{$game->roomCode}", 'Oyun kaydedildi.', function () use ($game, $request, $choice): void {
            $targetId = $choice === 'pas' ? null : ($request->int('target_id') ?? throw new RuleViolationException('Bir oyuncu seçin ya da pas geçin.'));
            $this->gameService->submitVote($game, $request->user, $targetId);
        });
    }

    private function loadState(Request $request): array
    {
        $game = $this->findGame($request);
        $this->phaseManager->tick($game->id);
        $game = $this->games->findById($game->id) ?? $game; // faz ilerlemiş olabilir

        return [$game, $this->stateBuilder->build($game, $request->user)];
    }
}
