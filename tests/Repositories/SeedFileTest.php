<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Repositories;

use PHPUnit\Framework\TestCase;
use VampirKoylu\Dev\SampleData;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Repositories\Pdo\PdoRepositoryFactory;
use VampirKoylu\Tests\Support\SqliteSchema;

final class SeedFileTest extends TestCase
{
    public function testSeedLoadsIntoTheSchemaAndLoginsWork(): void
    {
        $pdo = SqliteSchema::createDatabase();
        SqliteSchema::loadSeed($pdo);
        $repositories = PdoRepositoryFactory::create($pdo);

        $admin = $repositories->users->findByEmail(SampleData::ADMIN_EMAIL);
        $this->assertNotNull($admin);
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(password_verify(SampleData::ADMIN_PASSWORD, $admin->passwordHash));

        $player = $repositories->users->findByEmail('figen@vampirkoylu.test');
        $this->assertTrue(password_verify(SampleData::PLAYER_PASSWORD, $player->passwordHash));
    }

    public function testSeedContainsFinishedGamesAndAWaitingRoom(): void
    {
        $pdo = SqliteSchema::createDatabase();
        SqliteSchema::loadSeed($pdo);
        $repositories = PdoRepositoryFactory::create($pdo);

        $overview = $repositories->reports->overview();

        $this->assertGreaterThanOrEqual(8, $overview['users']);
        $this->assertGreaterThanOrEqual(3, $overview['finished']);
        $this->assertGreaterThan(0, $overview['villager_wins']);
        $this->assertGreaterThan(0, $overview['vampire_wins']);
        $this->assertSame(1, $overview['lobby']);

        $finished = $repositories->games->search(null, GameStatus::Finished, null);
        $this->assertNotEmpty($finished);
        $this->assertNotEmpty($repositories->logs->listByGame((int) $finished[0]['id'], true));
    }
}
