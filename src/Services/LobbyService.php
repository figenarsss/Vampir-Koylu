<?php

declare(strict_types=1);

namespace VampirKoylu\Services;

use VampirKoylu\Exceptions\ForbiddenException;
use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Game\GameRules;
use VampirKoylu\Models\Game;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Models\Player;
use VampirKoylu\Models\User;
use VampirKoylu\Repositories\GameRepositoryInterface;
use VampirKoylu\Repositories\PlayerRepositoryInterface;
use VampirKoylu\Repositories\TransactionManagerInterface;
use VampirKoylu\Support\Clock;

final class LobbyService
{
    public const CODE_LENGTH = 6;

    // 0/O ve 1/I karışmasın diye kodda yok
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(
        private readonly GameRepositoryInterface $games,
        private readonly PlayerRepositoryInterface $players,
        private readonly TransactionManagerInterface $transactions,
        private readonly Clock $clock,
        private readonly GameRules $rules,
    ) {
    }

    public function createRoom(User $host): Game
    {
        return $this->transactions->run(function () use ($host): Game {
            $game = $this->games->create($this->generateUniqueCode(), $host->id, $this->clock->now());
            $this->players->add($game->id, $host->id);

            return $game;
        });
    }

    public function join(string $code, User $user): Game
    {
        $game = $this->games->findByCode($this->normalizeCode($code))
            ?? throw new RuleViolationException('Bu kodla bir oda bulunamadı.');

        if ($this->players->findByGameAndUser($game->id, $user->id) !== null) {
            return $game;
        }
        if ($game->status !== GameStatus::Lobby) {
            throw new RuleViolationException('Bu oyun başlamış; yeni oyuncu katılamaz.');
        }
        if ($this->players->countByGame($game->id) >= $this->rules->maxPlayers) {
            throw new RuleViolationException("Oda dolu (en fazla {$this->rules->maxPlayers} oyuncu).");
        }

        $player = $this->players->add($game->id, $user->id);

        // aynı anda gelen iki katılım son yere birlikte girebilir
        if ($this->players->countByGame($game->id) > $this->rules->maxPlayers) {
            $this->players->remove($player->id);
            throw new RuleViolationException("Oda dolu (en fazla {$this->rules->maxPlayers} oyuncu).");
        }

        return $game;
    }

    /**
     * Lobiden ayrılır. Kurucu ayrılırsa kurucu yetkisi en erken katılan diğer oyuncuya geçer;
     * odada kimse kalmazsa oda kapanır (silinir). Kullanıcıya gösterilecek mesajı döndürür.
     */
    public function leave(Game $game, User $user): string
    {
        return $this->transactions->run(function () use ($game, $user): string {
            $this->requireLobby($game);

            $player = $this->players->findByGameAndUser($game->id, $user->id);
            if ($player === null) {
                return 'Odadan ayrıldınız.';
            }
            if (!$game->isHost($user)) {
                $this->players->remove($player->id);

                return 'Odadan ayrıldınız.';
            }

            $others = array_values(array_filter(
                $this->players->listByGame($game->id),
                static fn (Player $p): bool => $p->id !== $player->id,
            ));
            if ($others === []) {
                $this->games->delete($game->id);

                return 'Odadan ayrıldınız. Odada kimse kalmadığı için oda kapandı.';
            }

            $this->games->setHost($game->id, $others[0]->userId);
            $this->players->remove($player->id);

            return "Odadan ayrıldınız. Oda kurucusu {$others[0]->name} oldu.";
        });
    }

    /**
     * Biten oyundan sonra herkesi yeni bir lobide toplar. İlk tıklayan yeni odayı açar ve kurucusu olur;
     * sonrakiler aynı odaya katılır. Biten oyun, kayıtlarıyla birlikte geçmişte kalır.
     */
    public function rematch(Game $old, User $user): Game
    {
        return $this->transactions->run(function () use ($old, $user): Game {
            $old = $this->games->findById($old->id) ?? throw new RuleViolationException('Oda bulunamadı.');
            if ($old->status !== GameStatus::Finished) {
                throw new RuleViolationException('Yeni lobiye yalnızca oyun bittikten sonra geçilebilir.');
            }
            if ($this->players->findByGameAndUser($old->id, $user->id) === null) {
                throw new ForbiddenException('Bu oyunun oyuncusu değilsiniz.');
            }

            if ($old->rematchCode !== null) {
                $next = $this->games->findByCode($old->rematchCode);
                if ($next !== null) {
                    if ($next->status !== GameStatus::Lobby && $this->players->findByGameAndUser($next->id, $user->id) === null) {
                        throw new RuleViolationException('Yeni oyun başlamış; katılamazsın.');
                    }

                    return $this->join($next->roomCode, $user);
                }
            }

            $created = $this->createRoom($user);
            if (!$this->games->claimRematchCode($old->id, $old->rematchCode, $created->roomCode)) {
                // aynı anda başka biri yeni lobiyi açtı: onunkine katıl, kendi odamızı sil
                $this->games->delete($created->id);
                $fresh = $this->games->findById($old->id);
                if ($fresh?->rematchCode === null) {
                    throw new RuleViolationException('Yeni lobi açılamadı; tekrar deneyin.');
                }

                return $this->join($fresh->rematchCode, $user);
            }

            return $created;
        });
    }

    public function kick(Game $game, User $host, int $playerId): void
    {
        $this->requireLobby($game);
        if (!$game->isHost($host)) {
            throw new ForbiddenException('Oyuncuyu yalnızca oda kurucusu atabilir.');
        }

        $player = $this->players->findById($playerId);
        if ($player === null || $player->gameId !== $game->id) {
            throw new RuleViolationException('Oyuncu bulunamadı.');
        }
        if ($player->userId === $host->id) {
            throw new RuleViolationException('Kurucu kendini atamaz.');
        }

        $this->players->remove($player->id);
    }

    public function updateRoleSettings(Game $game, User $host, int $vampires, int $trappers): void
    {
        $this->requireLobby($game);
        if (!$game->isHost($host)) {
            throw new ForbiddenException('Rol ayarlarını yalnızca oda kurucusu değiştirebilir.');
        }

        $this->rules->assertValidSettings($this->players->countByGame($game->id), $vampires, $trappers);
        $this->games->updateRoleSettings($game->id, $vampires, $trappers);
    }

    public function deleteRoom(Game $game, User $actor): void
    {
        if (!$game->isHost($actor) && !$actor->isAdmin()) {
            throw new ForbiddenException('Odayı yalnızca kurucusu ya da yönetici silebilir.');
        }
        if ($game->status === GameStatus::InProgress && !$actor->isAdmin()) {
            throw new RuleViolationException('Oyun sürerken oda silinemez.');
        }

        $this->games->delete($game->id);
    }

    private function requireLobby(Game $game): void
    {
        $current = $this->games->findById($game->id);
        if ($current === null || $current->status !== GameStatus::Lobby) {
            throw new RuleViolationException('Oyun başladıktan sonra lobi değiştirilemez.');
        }
    }

    private function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
        } while ($this->games->codeExists($code));

        return $code;
    }
}
