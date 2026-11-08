<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Json;

use RuntimeException;

// flock ile okuma ve yazma
final class JsonTable
{
    public function __construct(private readonly string $path)
    {
    }

    public function all(): array
    {
        $handle = $this->open(LOCK_SH);
        try {
            return array_values($this->read($handle)['rows']);
        } finally {
            $this->close($handle);
        }
    }

    public function find(int $id): ?array
    {
        $handle = $this->open(LOCK_SH);
        try {
            return $this->read($handle)['rows'][$id] ?? null;
        } finally {
            $this->close($handle);
        }
    }

    public function where(callable $predicate): array
    {
        return array_values(array_filter($this->all(), $predicate));
    }

    public function insert(array $row): array
    {
        return $this->mutate(static function (array &$data) use ($row): array {
            $row['id'] = $data['next_id']++;
            $data['rows'][$row['id']] = $row;

            return $row;
        });
    }

    public function update(int $id, array $changes): void
    {
        $this->mutate(static function (array &$data) use ($id, $changes): void {
            if (isset($data['rows'][$id])) {
                $data['rows'][$id] = $changes + $data['rows'][$id];
            }
        });
    }

    public function deleteWhere(callable $predicate): void
    {
        $this->mutate(static function (array &$data) use ($predicate): void {
            $data['rows'] = array_filter($data['rows'], static fn (array $row): bool => !$predicate($row));
        });
    }

    public function mutate(callable $change): mixed
    {
        $handle = $this->open(LOCK_EX);
        try {
            $data = $this->read($handle);
            $result = $change($data);

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            fflush($handle);

            return $result;
        } finally {
            $this->close($handle);
        }
    }

    private function open(int $lockType)
    {
        $handle = fopen($this->path, 'c+');
        if ($handle === false) {
            throw new RuntimeException("Veri dosyası açılamadı: {$this->path}");
        }
        flock($handle, $lockType);

        return $handle;
    }

    private function close($handle): void
    {
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    private function read($handle): array
    {
        rewind($handle);
        $raw = stream_get_contents($handle);
        $data = ($raw === false || $raw === '') ? null : json_decode($raw, true);

        return is_array($data) ? $data : ['next_id' => 1, 'rows' => []];
    }
}
