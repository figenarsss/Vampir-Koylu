<?php

declare(strict_types=1);

namespace VampirKoylu\Http;

final class Session
{
    private const KEY_USER = 'user_id';
    private const KEY_FLASH = '_flash';
    private const KEY_LAST_ACTIVITY = '_last_activity';

    private array $data;

    private bool $native;

    private int $idRegenerations = 0;

    private function __construct(array $data, bool $native)
    {
        $this->data = $data;
        $this->native = $native;
    }

    public static function memory(): self
    {
        return new self([], false);
    }

    public static function start(int $timeoutSeconds): self
    {
        session_name('vampirkoylu');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        $session = new self($_SESSION, true);
        $last = $session->data[self::KEY_LAST_ACTIVITY] ?? null;

        if (is_int($last) && time() - $last > $timeoutSeconds) {
            $session->data = [];
            $session->flash('error', 'Oturumunuz uzun süre işlem yapılmadığı için kapatıldı. Lütfen tekrar giriş yapın.');
        }
        $session->data[self::KEY_LAST_ACTIVITY] = time();
        $session->save();

        return $session;
    }

    public function userId(): ?int
    {
        $id = $this->data[self::KEY_USER] ?? null;

        return is_int($id) ? $id : null;
    }

    public function login(int $userId): void
    {
        $this->regenerateId();
        $this->data[self::KEY_USER] = $userId;
        $this->save();
    }

    public function logout(): void
    {
        unset($this->data[self::KEY_USER]);
        $this->regenerateId();
        $this->save();
    }

    public function idRegenerations(): int
    {
        return $this->idRegenerations;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
        $this->save();
    }

    public function flash(string $type, string $message): void
    {
        $this->data[self::KEY_FLASH][] = ['type' => $type, 'message' => $message];
        $this->save();
    }

    public function pullFlashes(): array
    {
        $flashes = $this->data[self::KEY_FLASH] ?? [];
        unset($this->data[self::KEY_FLASH]);
        $this->save();

        return $flashes;
    }

    // kilit bırakılmazsa yenileme istekleri sıraya girer
    public function release(): void
    {
        if ($this->native && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $this->native = false;
    }

    private function regenerateId(): void
    {
        $this->idRegenerations++;
        if ($this->native) {
            session_regenerate_id(true);
        }
    }

    private function save(): void
    {
        if ($this->native) {
            $_SESSION = $this->data;
        }
    }
}
