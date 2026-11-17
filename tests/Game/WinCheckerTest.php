<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Game;

use PHPUnit\Framework\TestCase;
use VampirKoylu\Game\WinChecker;
use VampirKoylu\Models\RoleType;
use VampirKoylu\Models\Team;
use VampirKoylu\Tests\Support\Make;

final class WinCheckerTest extends TestCase
{
    private WinChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new WinChecker();
    }

    public function testVillagersWinWhenNoVampireIsAlive(): void
    {
        $players = [
            Make::player(1, RoleType::Vampire, alive: false),
            Make::player(2, RoleType::Villager),
            Make::player(3, RoleType::Doctor),
            Make::player(4, RoleType::Seer, alive: false),
        ];

        $this->assertSame(Team::Villagers, $this->checker->check($players));
    }

    public function testVampiresWinWhenTheyEqualTheVillagerSide(): void
    {
        $players = [
            Make::player(1, RoleType::Vampire),
            Make::player(2, RoleType::Vampire),
            Make::player(3, RoleType::Villager),
            Make::player(4, RoleType::Seer),
            Make::player(5, RoleType::Doctor, alive: false),
            Make::player(6, RoleType::Villager, alive: false),
        ];

        $this->assertSame(Team::Vampires, $this->checker->check($players));
    }

    public function testVampiresWinWhenOutnumberingTheVillagers(): void
    {
        $players = [
            Make::player(1, RoleType::Vampire),
            Make::player(2, RoleType::Vampire),
            Make::player(3, RoleType::Villager),
        ];

        $this->assertSame(Team::Vampires, $this->checker->check($players));
    }

    public function testTrapperCountsAsVillagerSide(): void
    {
        $players = [
            Make::player(1, RoleType::Vampire),
            Make::player(2, RoleType::Trapper),
            Make::player(3, RoleType::Seer, alive: false),
            Make::player(4, RoleType::Villager, alive: false),
        ];

        $this->assertSame(Team::Vampires, $this->checker->check($players));
        $this->assertNull($this->checker->check([...$players, Make::player(5, RoleType::Doctor)]));
    }

    public function testGameContinuesWhileVillagerSideIsLarger(): void
    {
        $players = [
            Make::player(1, RoleType::Vampire),
            Make::player(2, RoleType::Doctor),
            Make::player(3, RoleType::Seer),
        ];

        $this->assertNull($this->checker->check($players));
    }
}
