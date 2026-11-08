<?php

declare(strict_types=1);

namespace VampirKoylu\Http;

use VampirKoylu\Models\User;

final class Request
{
    public ?User $user = null;

    public array $params = [];

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $post,
        public readonly Session $session,
        public readonly string $basePath = '',
        public readonly bool $ajax = false,
    ) {
    }

    public static function fromGlobals(Session $session): self
    {
        $uri = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        $path = '/' . trim($uri, '/');

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $path,
            $_GET,
            $_POST,
            $session,
            $basePath,
            ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch',
        );
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->post[$key] ?? $this->query[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    public function int(string $key): ?int
    {
        $value = $this->post[$key] ?? $this->query[$key] ?? null;

        return is_string($value) && preg_match('/^\d{1,9}$/', $value) === 1 ? (int) $value : null;
    }

    public function param(string $name): string
    {
        return $this->params[$name] ?? '';
    }

    public function url(string $path = '/'): string
    {
        return $this->basePath . $path;
    }
}
