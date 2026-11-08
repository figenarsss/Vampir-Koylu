<?php

declare(strict_types=1);

namespace VampirKoylu\Http;

final class Csrf
{
    public const FIELD = '_csrf';
    private const KEY = '_csrf_token';

    public function token(Session $session): string
    {
        $token = $session->get(self::KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $session->set(self::KEY, $token);
        }

        return $token;
    }

    public function isValid(Session $session, mixed $submitted): bool
    {
        $expected = $session->get(self::KEY);

        return is_string($expected) && is_string($submitted) && hash_equals($expected, $submitted);
    }
}
