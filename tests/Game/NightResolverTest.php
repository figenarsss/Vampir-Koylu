<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Game;

use PHPUnit\Framework\TestCase;
use VampirKoylu\Game\NightResolver;
use VampirKoylu\Models\ActionType;
use VampirKoylu\Models\RoleType;
use VampirKoylu\Tests\Support\Make;

final class NightResolverTest extends TestCase
{
    private NightResolver $resolver;

    private array $players;

    protected function setUp(): void
    {
        $this->resolver = new NightResolver();
        $this->players = Make::players([
            RoleType::Vampire, RoleType::Vampire, RoleType::Doctor, RoleType::Seer, RoleType::Trapper,
            RoleType::Villager, RoleType::Villager, RoleType::Villager,
        ]);
    }

    public function testVampireTargetBecomesTheVictim(): void
    {
        $outcome = $this->resolver->resolve([Make::action(1, 6, ActionType::Kill)], $this->players);

        $this->assertSame([6], $outcome->victimPlayerIds());
    }

    public function testVampiresWithDifferentTargetsKillBoth(): void
    {
        $actions = [Make::action(1, 6, ActionType::Kill), Make::action(2, 7, ActionType::Kill)];

        $this->assertSame([6, 7], $this->resolver->resolve($actions, $this->players)->victimPlayerIds());
    }

    public function testSameTargetIsCountedOnce(): void
    {
        $actions = [Make::action(1, 6, ActionType::Kill), Make::action(2, 6, ActionType::Kill)];

        $this->assertSame([6], $this->resolver->resolve($actions, $this->players)->victimPlayerIds());
    }

    public function testDoctorSavesOnlyTheProtectedVictim(): void
    {
        $actions = [
            Make::action(1, 6, ActionType::Kill),
            Make::action(2, 7, ActionType::Kill),
            Make::action(3, 6, ActionType::Protect),
        ];

        $outcome = $this->resolver->resolve($actions, $this->players);

        $this->assertSame([7], $outcome->victimPlayerIds());
        $this->assertTrue($outcome->wasSaved());
    }

    public function testDoctorCanProtectThemselves(): void
    {
        $actions = [Make::action(1, 3, ActionType::Kill), Make::action(3, 3, ActionType::Protect)];

        $this->assertSame([], $this->resolver->resolve($actions, $this->players)->victimPlayerIds());
    }

    public function testProtectingSomeoneElseDoesNotSaveTheVictim(): void
    {
        $actions = [Make::action(1, 6, ActionType::Kill), Make::action(3, 7, ActionType::Protect)];

        $this->assertSame([6], $this->resolver->resolve($actions, $this->players)->victimPlayerIds());
    }

    public function testSeerInspectionIsRecorded(): void
    {
        $outcome = $this->resolver->resolve([Make::action(4, 1, ActionType::Inspect)], $this->players);

        $this->assertSame([4 => 1], $outcome->inspections);
    }

    public function testInvalidActionsAreIgnored(): void
    {
        $players = $this->players;
        $players[0] = Make::player(1, RoleType::Vampire, alive: false);

        $actions = [
            Make::action(1, 6, ActionType::Kill),
            Make::action(6, 7, ActionType::Kill),
            Make::action(2, 8, ActionType::Protect),
        ];

        $outcome = $this->resolver->resolve($actions, $players);

        $this->assertSame([], $outcome->targetedPlayerIds);
        $this->assertNull($outcome->protectedPlayerId);
    }

    public function testVampireMayTargetAnotherVampire(): void
    {
        $outcome = $this->resolver->resolve([Make::action(1, 2, ActionType::Kill)], $this->players);

        $this->assertSame([2], $outcome->victimPlayerIds());
    }

    public function testEmptyNightHasNoVictim(): void
    {
        $outcome = $this->resolver->resolve([], $this->players);

        $this->assertSame([], $outcome->victimPlayerIds());
        $this->assertSame([], $outcome->inspections);
    }

    public function testTrapCancelsTheActionOfTheVisitor(): void
    {
        $actions = [Make::action(5, 6, ActionType::Trap), Make::action(1, 6, ActionType::Kill)];

        $outcome = $this->resolver->resolve($actions, $this->players);

        $this->assertSame([10], $outcome->cancelledActionIds);
        $this->assertSame([], $outcome->victimPlayerIds());
        $this->assertSame(1, $outcome->trapResults[0]->caughtPlayerId);
    }

    public function testOnlyTheFirstVisitorIsTrapped(): void
    {
        $actions = [
            Make::action(5, 6, ActionType::Trap),
            Make::action(1, 6, ActionType::Kill),
            Make::action(3, 6, ActionType::Protect),
        ];

        $outcome = $this->resolver->resolve($actions, $this->players);

        $this->assertSame([10], $outcome->cancelledActionIds);
        $this->assertSame(6, $outcome->protectedPlayerId);
    }

    public function testTrappedDoctorCannotSaveTheVictim(): void
    {
        $actions = [
            Make::action(5, 3, ActionType::Trap),
            Make::action(3, 3, ActionType::Protect),
            Make::action(1, 3, ActionType::Kill),
        ];

        $outcome = $this->resolver->resolve($actions, $this->players);

        $this->assertNull($outcome->protectedPlayerId);
        $this->assertSame([3], $outcome->victimPlayerIds());
    }

    public function testTrappedSeerLearnsNothing(): void
    {
        $actions = [Make::action(5, 1, ActionType::Trap), Make::action(4, 1, ActionType::Inspect)];

        $this->assertSame([], $this->resolver->resolve($actions, $this->players)->inspections);
    }

    public function testTrapWithoutVisitorCatchesNobody(): void
    {
        $outcome = $this->resolver->resolve([Make::action(5, 6, ActionType::Trap)], $this->players);

        $this->assertNull($outcome->trapResults[0]->caughtPlayerId);
        $this->assertSame([], $outcome->cancelledActionIds);
    }
}
