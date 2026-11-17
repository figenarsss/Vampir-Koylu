<?php

declare(strict_types=1);

namespace VampirKoylu\Controllers;

use VampirKoylu\Http\Request;
use VampirKoylu\Http\Response;
use VampirKoylu\Http\View;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Repositories\GameRepositoryInterface;

final class HistoryController extends Controller
{
    public function __construct(View $view, private readonly GameRepositoryInterface $games)
    {
        parent::__construct($view);
    }

    public function index(Request $request): Response
    {
        $term = $request->string('q');
        $status = GameStatus::tryFrom($request->string('status'));

        return $this->render($request, 'history/index', [
            'title' => 'Oyunlarım',
            'games' => $this->games->search($term, $status, $request->user->id),
            'term' => $term,
            'status' => $status?->value ?? '',
            'statuses' => GameStatus::cases(),
        ]);
    }
}
