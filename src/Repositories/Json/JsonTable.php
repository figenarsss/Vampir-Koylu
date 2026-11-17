<?php

declare(strict_types=1);

namespace VampirKoylu\Repositories\Json;

use RuntimeException;

// Okuma ve yazma ayrı bir kilit dosyası (.lock) üzerinden flock ile korunur.
// Yazma doğrudan asıl dosyaya yapılmaz: veri önce geçici dosyaya yazılır, sonra rename ile
// asıl dosyanın yerine geçirilir. Böylece yazma yarıda kesilse bile asıl dosya bozulmaz.
final class JsonTable
{
    public function __construct(private readonly string $path)
    {
    }

    public function all(): array
    {
        $handle = $this->open(LOCK_SH);
        try {
            return array_values($this->read()['rows']);
        } finally {
            $this->close($handle);
        }
    }

    public function find(int $id): ?array
    {
        $handle = $this->open(LOCK_SH);
        try {
            return $this->read()['rows'][$id] ?? null;
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
            $data = $this->read();
            $result = $change($data);
            $this->write($data);

            return $result;
        } finally {
            $this->close($handle);
        }
    }

    /**
     * Kilit dosyasını açar ve kilidi alır. Asıl veri dosyası yazma sırasında
     * yer değiştirdiği için kilit asıl dosyada değil, ayrı bir dosyada tutulur.
     *
     * @return resource
     */
    private function open(int $lockType)
    {
        $handle = fopen($this->path . '.lock', 'c');
        if ($handle === false) {
            throw new RuntimeException("Kilit dosyası açılamadı: {$this->path}.lock");
        }
        if (!flock($handle, $lockType)) {
            fclose($handle);
            throw new RuntimeException("Kilit alınamadı: {$this->path}.lock");
        }

        return $handle;
    }

    /** @param resource $handle */
    private function close($handle): void
    {
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    private function read(): array
    {
        if (!is_file($this->path)) {
            return ['next_id' => 1, 'rows' => []];
        }

        $raw = file_get_contents($this->path);
        if ($raw === false) {
            throw new RuntimeException("Veri dosyası okunamadı: {$this->path}");
        }
        if (trim($raw) === '') {
            return ['next_id' => 1, 'rows' => []];
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['next_id'], $data['rows']) || !is_array($data['rows'])) {
            // Bozuk dosya sessizce boş sayılmaz; aksi halde sonraki yazma tüm veriyi silerdi.
            throw new RuntimeException("Veri dosyası bozuk veya geçersiz: {$this->path}");
        }

        return $data;
    }

    private function write(array $data): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $temp = $this->path . '.' . bin2hex(random_bytes(6)) . '.tmp';

        if (file_put_contents($temp, $json, LOCK_EX) === false) {
            @unlink($temp);
            throw new RuntimeException("Geçici veri dosyası yazılamadı: {$temp}");
        }
        if (!rename($temp, $this->path)) {
            @unlink($temp);
            throw new RuntimeException("Veri dosyası değiştirilemedi: {$this->path}");
        }
    }
}
