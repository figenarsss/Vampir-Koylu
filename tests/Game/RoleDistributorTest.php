<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Game;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;
use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Game\GameRules;
use VampirKoylu\Game\RoleDistributor;
use VampirKoylu\Models\RoleType;

final class RoleDistributorTest extends TestCase
{
    private function distributor(int $seed = 1): RoleDistributor
    {
        return new RoleDistributor(new Randomizer(new Mt19937($seed)), new GameRules());
    }

    public static function settings(): array
    {
        return [
            '5 oyuncu, 1 vampir' => [5, 1, 0],
            '7 oyuncu, 1 vampir' => [7, 1, 0],
            '8 oyuncu, 2 vampir' => [8, 2, 0],
            '8 oyuncu, 1 vampir, 1 tuzakçı' => [8, 1, 1],
            '10 oyuncu, 2 vampir, 2 tuzakçı' => [10, 2, 2],
            '12 oyuncu, 3 vampir' => [12, 3, 0],
            '12 oyuncu, 3 vampir, 2 tuzakçı' => [12, 3, 2],
        ];
    }

    #[DataProvider('settings')]
    public function testRoleCountsFollowTheSettings(int $playerCount, int $vampires, int $trappers): void
    {
        $ids = range(1, $playerCount);

        $roles = $this->distributor()->distribute($ids, $vampires, $trappers);
        $counts = array_count_values(array_map(static fn (RoleType $r): string => $r->value, $roles));

        $this->assertSame($ids, array_keys($roles));
        $this->assertSame($vampires, $counts['vampire']);
        $this->assertSame($trappers, $counts['trapper'] ?? 0);
        $this->assertSame(1, $counts['doctor']);
        $this->assertSame(1, $counts['seer']);
        $this->assertSame($playerCount - $vampires - $trappers - 2, $counts['villager']);
    }

    public function testAtLeastFourDifferentRolesAreUsed(): void
    {
        $roles = $this->distributor()->distribute(range(1, 5), 1, 0);

        $this->assertGreaterThanOrEqual(4, count(array_unique(array_map(static fn (RoleType $r): string => $r->value, $roles))));
    }

    public function testTrapperTakesThePlaceOfAVillager(): void
    {
        $without = array_count_values(array_map(static fn (RoleType $r): string => $r->value, $this->distributor()->distribute(range(1, 8), 1, 0)));
        $with = array_count_values(array_map(static fn (RoleType $r): string => $r->value, $this->distributor()->distribute(range(1, 8), 1, 1)));

        $this->assertSame($without['villager'] - 1, $with['villager']);
    }

    public function testRolesAreShuffledRandomly(): void
    {
        $distributions = [];
        foreach ([1, 2, 3, 4, 5, 6] as $seed) {
            $roles = $this->distributor($seed)->distribute(range(1, 8), 2, 0);
            $distributions[] = array_map(static fn (RoleType $r): string => $r->value, $roles);
        }

        $this->assertGreaterThan(1, count(array_unique(array_map('serialize', $distributions))));
    }

    public function testRejectsPlayerCountsOutsideTheLimits(): void
    {
        $this->expectException(RuleViolationException::class);
        $this->distributor()->distribute([1, 2, 3, 4], 1, 0);
    }

    public function testRejectsMoreThanTwelvePlayers(): void
    {
        $this->expectException(RuleViolationException::class);
        $this->distributor()->distribute(range(1, 13), 3, 0);
    }

    public static function invalidSettings(): array
    {
        return [
            '5-7 oyuncuda 2 vampir' => [7, 2, 0],
            '7 oyuncuda tuzakçı' => [7, 1, 1],
            '8 oyuncuda 4 vampir' => [8, 4, 0],
            '8 oyuncuda 3 tuzakçı' => [8, 1, 3],
            '0 vampir' => [8, 0, 0],
        ];
    }

    #[DataProvider('invalidSettings')]
    public function testRejectsInvalidRoleCounts(int $playerCount, int $vampires, int $trappers): void
    {
        $this->expectException(RuleViolationException::class);
        $this->distributor()->distribute(range(1, $playerCount), $vampires, $trappers);
    }
}
