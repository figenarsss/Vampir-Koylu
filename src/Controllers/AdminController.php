<?php

declare(strict_types=1);

namespace VampirKoylu\Controllers;

use VampirKoylu\Exceptions\NotFoundException;
use VampirKoylu\Http\Request;
use VampirKoylu\Http\Response;
use VampirKoylu\Http\View;
use VampirKoylu\Models\AccountRole;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Repositories\GameRepositoryInterface;
use VampirKoylu\Services\AdminService;
use VampirKoylu\Services\LobbyService;

final class AdminController extends Controller
{
    public function __construct(
        View $view,
        private readonly AdminService $admin,
        private readonly GameRepositoryInterface $games,
        private readonly LobbyService $lobby,
    ) {
        parent::__construct($view);
    }

    public function dashboard(Request $request): Response
    {
        return $this->render($request, 'admin/dashboard', ['title' => 'Yönetim paneli'] + $this->admin->report());
    }

    public function users(Request $request): Response
    {
        $term = $request->string('q');
        $role = AccountRole::tryFrom($request->string('role'));

        return $this->render($request, 'admin/users', [
            'title' => 'Kullanıcılar',
            'users' => $this->admin->searchUsers($term, $role),
            'term' => $term,
            'role' => $role?->value ?? '',
            'roles' => AccountRole::cases(),
        ]);
    }

    public function changeRole(Request $request): Response
    {
        $role = AccountRole::tryFrom($request->string('role'));
        $userId = (int) $request->param('id');

        return $this->attempt($request, '/yonetim/kullanicilar', 'Kullanıcının yetkisi güncellendi.', function () use ($request, $role, $userId): void {
            $this->admin->changeAccountRole($request->user, $userId, $role ?? throw new NotFoundException('Geçersiz yetki.'));
        });
    }

    public function games(Request $request): Response
    {
        $term = $request->string('q');
        $status = GameStatus::tryFrom($request->string('status'));

        return $this->render($request, 'admin/games', [
            'title' => 'Tüm oyunlar',
            'games' => $this->games->search($term, $status, null),
            'term' => $term,
            'status' => $status?->value ?? '',
            'statuses' => GameStatus::cases(),
        ]);
    }

    public function deleteGame(Request $request): Response
    {
        $game = $this->games->findById((int) $request->param('id')) ?? throw new NotFoundException('Oyun bulunamadı.');

        return $this->attempt($request, '/yonetim/oyunlar', "{$game->roomCode} odası silindi.", fn () => $this->lobby->deleteRoom($game, $request->user));
    }
}
