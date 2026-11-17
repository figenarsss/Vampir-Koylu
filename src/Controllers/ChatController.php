<?php

declare(strict_types=1);

namespace VampirKoylu\Controllers;

use VampirKoylu\Exceptions\ForbiddenException;
use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Http\Request;
use VampirKoylu\Http\Response;
use VampirKoylu\Http\View;
use VampirKoylu\Repositories\GameRepositoryInterface;
use VampirKoylu\Services\ChatService;

final class ChatController extends GameAwareController
{
    public function __construct(
        View $view,
        GameRepositoryInterface $games,
        private readonly ChatService $chat,
    ) {
        parent::__construct($view, $games);
    }

    public function send(Request $request): Response
    {
        $game = $this->findGame($request);

        try {
            $this->chat->send($game, $request->user, $request->string('mesaj'));
        } catch (RuleViolationException|ForbiddenException $error) {
            if ($request->ajax) {
                return Response::json(['ok' => false, 'error' => $error->getMessage()], 422);
            }
            $request->session->flash('error', $error->getMessage());
        }

        if ($request->ajax) {
            return Response::json(['ok' => true]);
        }

        return $this->redirect($request, "/oyun/{$game->roomCode}");
    }
}
