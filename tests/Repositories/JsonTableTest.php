<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Repositories;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use VampirKoylu\Repositories\Json\JsonTable;

/**
 * JSON depolamanın dosya düzeyi güvencelerini sınar (Aşama 2, bölüm 2):
 * kilit dosyası, geçici dosyaya yazıp rename ile değiştirme ve bozuk dosyanın korunması.
 * Her test kendi geçici klasörünü kullanır; gerçek storage/ dosyalarına dokunmaz.
 */
final class JsonTableTest extends TestCase
{
    private string $directory;

    private string $path;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/vk_jsontable_' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0777, true);
        $this->path = $this->directory . '/items.json';
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/{,.}*', GLOB_BRACE) ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->directory);
    }

    public function testInsertedRowsSurviveANewTableInstance(): void
    {
        $table = new JsonTable($this->path);
        $first = $table->insert(['name' => 'Ayşe']);
        $second = $table->insert(['name' => 'Mehmet']);

        $this->assertSame(1, $first['id']);
        $this->assertSame(2, $second['id']);

        $reopened = new JsonTable($this->path);
        $this->assertCount(2, $reopened->all());
        $this->assertSame('Mehmet', $reopened->find(2)['name']);
    }

    public function testWriteLeavesNoTemporaryFilesBehind(): void
    {
        $table = new JsonTable($this->path);
        for ($i = 0; $i < 5; $i++) {
            $table->insert(['n' => $i]);
        }

        $temporary = glob($this->directory . '/*.tmp') ?: [];
        $this->assertSame([], $temporary, 'rename sonrası geçici dosya kalmamalı');
        $this->assertJson((string) file_get_contents($this->path));
    }

    public function testFileIsReplacedAtomicallyInsteadOfBeingRewrittenInPlace(): void
    {
        $table = new JsonTable($this->path);
        $table->insert(['n' => 1]);
        $inodeBefore = fileinode($this->path);

        $table->insert(['n' => 2]);
        clearstatcache();

        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Windows üzerinde inode numarası güvenilir değildir.');
        }
        $this->assertNotSame($inodeBefore, fileinode($this->path), 'dosya yerinde değil, rename ile değiştirilmeli');
    }

    public function testLockFileIsSeparateFromTheDataFile(): void
    {
        $table = new JsonTable($this->path);
        $table->insert(['n' => 1]);

        $this->assertFileExists($this->path . '.lock');
        $this->assertJson((string) file_get_contents($this->path));
    }

    public function testFailingChangeLeavesTheStoredDataUntouched(): void
    {
        $table = new JsonTable($this->path);
        $table->insert(['n' => 1]);
        $before = file_get_contents($this->path);

        try {
            $table->mutate(static function (array &$data): void {
                $data['rows'] = [];
                throw new RuntimeException('işlem yarıda kesildi');
            });
            $this->fail('Özel durum bekleniyordu.');
        } catch (RuntimeException $exception) {
            $this->assertSame('işlem yarıda kesildi', $exception->getMessage());
        }

        $this->assertSame($before, file_get_contents($this->path));
        $this->assertCount(1, (new JsonTable($this->path))->all());
    }

    public function testCorruptFileIsReportedAndNotSilentlyOverwritten(): void
    {
        file_put_contents($this->path, '{ bozuk json');
        $table = new JsonTable($this->path);

        try {
            $table->insert(['n' => 1]);
            $this->fail('Bozuk dosya için özel durum bekleniyordu.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('bozuk', $exception->getMessage());
        }

        $this->assertSame('{ bozuk json', file_get_contents($this->path), 'bozuk dosya ezilmemeli');
    }

    public function testMissingOrEmptyFileIsTreatedAsAnEmptyTable(): void
    {
        $table = new JsonTable($this->path);
        $this->assertSame([], $table->all());
        $this->assertNull($table->find(1));

        file_put_contents($this->path, '');
        $this->assertSame([], (new JsonTable($this->path))->all());
    }

    public function testUpdateAndDeleteArePersisted(): void
    {
        $table = new JsonTable($this->path);
        $row = $table->insert(['name' => 'Ali', 'score' => 1]);
        $table->insert(['name' => 'Veli', 'score' => 2]);

        $table->update($row['id'], ['score' => 10]);
        $this->assertSame(10, (new JsonTable($this->path))->find($row['id'])['score']);

        $table->deleteWhere(static fn (array $r): bool => $r['name'] === 'Veli');
        $remaining = (new JsonTable($this->path))->all();
        $this->assertCount(1, $remaining);
        $this->assertSame('Ali', $remaining[0]['name']);
    }
}
