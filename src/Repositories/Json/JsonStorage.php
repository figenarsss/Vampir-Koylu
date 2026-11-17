<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Json;

use RuntimeException;

final class JsonStorage
{
    private array $tables = [];

    private $lockHandle = null;

    private int $lockDepth = 0;

    public function __construct(private readonly string $directory)
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Veri klasörü oluşturulamadı: {$directory}");
        }
    }

    public function table(string $name): JsonTable
    {
        return $this->tables[$name] ??= new JsonTable("{$this->directory}/{$name}.json");
    }

    public function transaction(callable $work): mixed
    {
        if ($this->lockDepth === 0) {
            $handle = fopen("{$this->directory}/.kilit", 'c');
            if ($handle === false) {
                throw new RuntimeException('Kilit dosyası açılamadı.');
            }
            flock($handle, LOCK_EX);
            $this->lockHandle = $handle;
        }
        $this->lockDepth++;

        try {
            return $work();
        } finally {
            $this->lockDepth--;
            if ($this->lockDepth === 0 && $this->lockHandle !== null) {
                flock($this->lockHandle, LOCK_UN);
                fclose($this->lockHandle);
                $this->lockHandle = null;
            }
        }
    }
}
