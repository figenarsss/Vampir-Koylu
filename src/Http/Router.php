<?php

declare(strict_types=1);

namespace VampirKoylu\Http;

final class Router
{
    private array $routes = [];

    public function get(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    public function match(string $method, string $path): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['regex'], $path, $found) === 1) {
                $params = array_filter($found, 'is_string', ARRAY_FILTER_USE_KEY);

                return ['handler' => $route['handler'], 'params' => $params, 'middleware' => $route['middleware']];
            }
        }

        return null;
    }

    public function pathExists(string $path): bool
    {
        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path) === 1) {
                return true;
            }
        }

        return false;
    }

    private function add(string $method, string $pattern, array $handler, array $middleware): void
    {
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[A-Za-z0-9]+)', $pattern);
        $this->routes[] = [
            'method' => $method,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }
}
