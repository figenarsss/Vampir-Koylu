<?php

declare(strict_types=1);

namespace VampirKoylu\Controllers;

use VampirKoylu\Exceptions\NotFoundException;
use VampirKoylu\Http\Request;
use VampirKoylu\Http\View;
use VampirKoylu\Models\Game;
use VampirKoylu\Repositories\GameRepositoryInterface;

abstract class GameAwareController extends Controller
{
    public function __construct(View $view, protected readonly GameRepositoryInterface $games)
    {
        parent::__construct($view);
    }

    protected function findGame(Request $request): Game
    {
        return $this->games->findByCode(strtoupper($request->param('code')))
            ?? throw new NotFoundException('Bu kodla bir oda bulunamadı.');
    }
}
