<?php

declare(strict_types=1);

namespace VampirKoylu\Controllers;

use VampirKoylu\Http\Request;
use VampirKoylu\Http\Response;
use VampirKoylu\Http\View;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Models\RoleType;
use VampirKoylu\Repositories\GameRepositoryInterface;

final class HomeController extends Controller
{
    private const RECENT_FINISHED_LIMIT = 5;

    public function __construct(View $view, private readonly GameRepositoryInterface $games)
    {
        parent::__construct($view);
    }

    public function index(Request $request): Response
    {
        if ($request->user === null) {
            return $this->render($request, 'home/welcome', ['title' => 'Vampir Köylü']);
        }

        $mine = $this->games->search(null, null, $request->user->id);
        $active = array_values(array_filter($mine, static fn (array $g): bool => $g['status'] !== GameStatus::Finished->value));
        $finished = array_slice(
            array_values(array_filter($mine, static fn (array $g): bool => $g['status'] === GameStatus::Finished->value)),
            0,
            self::RECENT_FINISHED_LIMIT,
        );

        return $this->render($request, 'home/dashboard', [
            'title' => 'Ana sayfa',
            'activeGames' => $active,
            'recentGames' => $finished,
        ]);
    }

    public function rules(Request $request): Response
    {
        return $this->render($request, 'home/rules', ['title' => 'Oyun kuralları', 'roles' => RoleType::cases()]);
    }
}
