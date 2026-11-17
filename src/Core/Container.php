<?php

declare(strict_types=1);

namespace VampirKoylu\Core;

use RuntimeException;

final class Container
{
    private array $factories = [];

    private array $instances = [];

    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    public function get(string $id): object
    {
        if (!isset($this->instances[$id])) {
            $factory = $this->factories[$id] ?? throw new RuntimeException("Tanımsız servis: {$id}");
            $this->instances[$id] = $factory();
        }

        return $this->instances[$id];
    }
}
