<?php

declare(strict_types=1);

namespace VampirKoylu\Services;

use VampirKoylu\Exceptions\ForbiddenException;
use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Models\Game;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Models\PhaseType;
use VampirKoylu\Models\Player;
use VampirKoylu\Models\User;
use VampirKoylu\Repositories\GameRepositoryInterface;
use VampirKoylu\Repositories\MessageRepositoryInterface;
use VampirKoylu\Repositories\PhaseRepositoryInterface;
use VampirKoylu\Repositories\PlayerRepositoryInterface;
use VampirKoylu\Support\Clock;

// Oda sohbeti. Lobide, gündüz, oylamada ve oyun bittikten sonra açıktır.
// Gece kapalıdır (vampirler gizlice konuşmasın); elenen oyuncular yalnızca okuyabilir.
final class ChatService
{
    public const MAX_LENGTH = 300;
    public const VISIBLE_MESSAGES = 60;

    public function __construct(
        private readonly GameRepositoryInterface $games,
        private readonly PlayerRepositoryInterface $players,
        private readonly PhaseRepositoryInterface $phases,
        private readonly MessageRepositoryInterface $messages,
        private readonly Clock $clock,
    ) {
    }

    /** Sohbete yazılabiliyorsa null, yazılamıyorsa nedenini döndürür. */
    public function blockReason(Game $game, ?Player $player): ?string
    {
        if ($player === null) {
            return 'Bu odanın oyuncusu değilsiniz.';
        }
        if ($game->status !== GameStatus::InProgress) {
            return null;
        }
        if (!$player->alive) {
            return 'Oyundan elendin; sohbeti yalnızca okuyabilirsin.';
        }
        if ($this->phases->current($game->id)?->type === PhaseType::Night) {
            return 'Gece sohbet kapalı. Sabah açılacak.';
        }

        return null;
    }

    public function send(Game $game, User $user, string $text): void
    {
        $game = $this->games->findById($game->id) ?? throw new RuleViolationException('Oda bulunamadı.');
        $player = $this->players->findByGameAndUser($game->id, $user->id)
            ?? throw new ForbiddenException('Bu odanın oyuncusu değilsiniz.');

        $reason = $this->blockReason($game, $player);
        if ($reason !== null) {
            throw new RuleViolationException($reason);
        }

        $text = trim((string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $text));
        if ($text === '') {
            throw new RuleViolationException('Boş mesaj gönderilemez.');
        }
        if (mb_strlen($text) > self::MAX_LENGTH) {
            throw new RuleViolationException('Mesaj en fazla ' . self::MAX_LENGTH . ' karakter olabilir.');
        }

        $this->messages->add($game->id, $user->id, $text, $this->clock->now());
    }

    public function recent(Game $game): array
    {
        return $this->messages->latestByGame($game->id, self::VISIBLE_MESSAGES);
    }
}
