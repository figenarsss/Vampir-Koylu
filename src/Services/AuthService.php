<?php

declare(strict_types=1);

namespace VampirKoylu\Services;

use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Models\AccountRole;
use VampirKoylu\Models\User;
use VampirKoylu\Repositories\UserRepositoryInterface;
use VampirKoylu\Support\Clock;

final class AuthService
{
    public const MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly Clock $clock,
    ) {
    }

    public function register(string $name, string $email, string $password, string $passwordRepeat): User
    {
        $name = trim($name);
        $email = mb_strtolower(trim($email));

        $length = mb_strlen($name);
        if ($length < 2 || $length > 40) {
            throw new RuleViolationException('Ad 2 ile 40 karakter arasında olmalıdır.');
        }
        if (mb_strlen($email) > 150 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuleViolationException('Geçerli bir e-posta adresi girin.');
        }
        $this->assertValidPassword($password);
        if ($password !== $passwordRepeat) {
            throw new RuleViolationException('Şifreler birbiriyle aynı değil.');
        }
        if ($this->users->findByEmail($email) !== null) {
            throw new RuleViolationException('Bu e-posta adresiyle zaten bir hesap var.');
        }

        return $this->users->create(
            $name,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            AccountRole::Player,
            $this->clock->now(),
        );
    }

    public function login(string $email, string $password): User
    {
        $user = $this->users->findByEmail(mb_strtolower(trim($email)));

        if ($user === null || !password_verify($password, $user->passwordHash)) {
            throw new RuleViolationException('E-posta veya şifre hatalı.');
        }
        if (password_needs_rehash($user->passwordHash, PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash($user->id, password_hash($password, PASSWORD_DEFAULT));
        }

        return $user;
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword, string $newPasswordRepeat): void
    {
        if (!password_verify($currentPassword, $user->passwordHash)) {
            throw new RuleViolationException('Mevcut şifre hatalı.');
        }
        $this->assertValidPassword($newPassword);
        if ($newPassword !== $newPasswordRepeat) {
            throw new RuleViolationException('Yeni şifreler birbiriyle aynı değil.');
        }

        $this->users->updatePasswordHash($user->id, password_hash($newPassword, PASSWORD_DEFAULT));
    }

    private function assertValidPassword(string $password): void
    {
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new RuleViolationException('Şifre en az ' . self::MIN_PASSWORD_LENGTH . ' karakter olmalıdır.');
        }
        if (!preg_match('/\p{L}/u', $password) || !preg_match('/\d/', $password)) {
            throw new RuleViolationException('Şifre en az bir harf ve bir rakam içermelidir.');
        }
    }
}
