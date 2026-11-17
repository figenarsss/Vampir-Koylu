<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Services;

use VampirKoylu\Exceptions\ForbiddenException;
use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Models\ActionType;
use VampirKoylu\Models\Game;
use VampirKoylu\Models\RoleType;
use VampirKoylu\Models\User;
use VampirKoylu\Services\GameStateBuilder;
use VampirKoylu\Tests\Support\AppTestCase;

final class NightPhaseTest extends AppTestCase
{
    private array $users;
    private Game $game;

    protected function setUp(): void
    {
        parent::setUp();
        [$this->game, $this->users] = $this->app->createGame(5);
    }

    private function role(RoleType $role, int $index = 0): \VampirKoylu\Models\Player
    {
        return $this->app->playerWithRole($this->game, $role, $index);
    }

    private function publicMessages(Game $game): array
    {
        return array_map(
            static fn ($log): string => $log->message,
            $this->app->repositories->logs->listByGame($game->id, false),
        );
    }

    private function stateOf(Game $game, User $user): array
    {
        return $this->app->container->get(GameStateBuilder::class)
            ->build($this->app->repositories->games->findById($game->id), $user);
    }

    public function testActionIsAcceptedOnlyDuringTheFirstFifteenSeconds(): void
    {
        $this->app->clock->advance(14);
        $this->app->act($this->game, $this->users, $this->role(RoleType::Vampire), $this->role(RoleType::Villager));
        $this->assertCount(1, $this->app->repositories->actions->forPhase($this->app->currentPhase($this->game)->id));

        $this->app->clock->advance(1);
        $this->expectException(RuleViolationException::class);
        $this->expectExceptionMessage('süresi doldu');
        $this->app->act($this->game, $this->users, $this->role(RoleType::Doctor), $this->role(RoleType::Doctor));
    }

    public function testChoiceCannotBeChangedAfterItIsMade(): void
    {
        $vampire = $this->role(RoleType::Vampire);
        $first = $this->role(RoleType::Villager, 0);
        $second = $this->role(RoleType::Villager, 1);
        $this->app->act($this->game, $this->users, $vampire, $first);

        try {
            $this->app->act($this->game, $this->users, $vampire, $second);
            $this->fail('Seçim değiştirilememeli');
        } catch (RuleViolationException $error) {
            $this->assertStringContainsString('değiştiremezsin', $error->getMessage());
        }

        $actions = $this->app->repositories->actions->forPhase($this->app->currentPhase($this->game)->id);
        $this->assertCount(1, $actions);
        $this->assertSame($first->id, $actions[0]->targetPlayerId);
        $this->assertSame(ActionType::Kill, $actions[0]->type);
    }

    public function testVillagerCannotPerformANightAction(): void
    {
        $this->expectException(RuleViolationException::class);
        $this->app->act($this->game, $this->users, $this->role(RoleType::Villager), $this->role(RoleType::Vampire));
    }

    public function testOutsiderCannotAct(): void
    {
        $outsider = $this->app->registerUser('Yabanci');

        $this->expectException(ForbiddenException::class);
        $this->app->games()->submitNightAction($this->game, $outsider, $this->role(RoleType::Villager)->id);
    }

    public function testTargetFromAnotherGameIsRejected(): void
    {
        [$otherGame] = $this->app->createGame(5);

        $this->expectException(RuleViolationException::class);
        $this->expectExceptionMessage('Geçersiz hedef');
        $this->app->act($this->game, $this->users, $this->role(RoleType::Vampire), $this->app->players($otherGame)[0]);
    }

    public function testVampireCannotTargetThemselves(): void
    {
        $vampire = $this->role(RoleType::Vampire);

        $this->expectException(RuleViolationException::class);
        $this->expectExceptionMessage('Kendini kurban seçemezsin');
        $this->app->act($this->game, $this->users, $vampire, $vampire);
    }

    public function testVampireMayTargetAnotherVampire(): void
    {
        [$game, $users] = $this->app->createGame(8, vampires: 2);

        $this->app->act($game, $users, $this->app->playerWithRole($game, RoleType::Vampire, 0), $this->app->playerWithRole($game, RoleType::Vampire, 1));

        $this->addToAssertionCount(1);
    }

    public function testSeerCannotInspectThemselves(): void
    {
        $seer = $this->role(RoleType::Seer);

        $this->expectException(RuleViolationException::class);
        $this->app->act($this->game, $this->users, $seer, $seer);
    }

    public function testDoctorCannotProtectThemselvesTwoNightsInARow(): void
    {
        $doctor = $this->role(RoleType::Doctor);
        $other = $this->role(RoleType::Villager);

        $this->app->act($this->game, $this->users, $doctor, $doctor);
        $this->app->skipToNextNight($this->game);

        try {
            $this->app->act($this->game, $this->users, $doctor, $doctor);
            $this->fail('Art arda kendini koruma reddedilmeli');
        } catch (RuleViolationException $error) {
            $this->assertStringContainsString('Art arda', $error->getMessage());
        }

        $this->app->act($this->game, $this->users, $doctor, $other);
        $this->app->skipToNextNight($this->game);
        $this->app->act($this->game, $this->users, $doctor, $doctor);
        $this->addToAssertionCount(1);
    }

    public function testTrapperCannotTrapTheirOwnHouse(): void
    {
        [$game, $users] = $this->app->createGame(8, vampires: 1, trappers: 1);
        $trapper = $this->app->playerWithRole($game, RoleType::Trapper);

        $this->expectException(RuleViolationException::class);
        $this->expectExceptionMessage('Kendi evine tuzak kuramazsın');
        $this->app->act($game, $users, $trapper, $trapper);
    }

    public function testMorningAnnouncesTheVictimWithoutRevealingTheDoctor(): void
    {
        $victim = $this->role(RoleType::Villager);
        $this->app->act($this->game, $this->users, $this->role(RoleType::Vampire), $victim);

        $this->app->finishCurrentPhase($this->game);

        $public = $this->publicMessages($this->game);
        $this->assertContains("Bu gece {$victim->name} öldü.", $public);
        $this->assertFalse($this->app->repositories->players->findById($victim->id)->alive);
        $this->assertStringNotContainsString('Doktor', implode(' ', $public));
    }

    public function testMorningSaysNobodyDiedWhenTheDoctorSavesTheTarget(): void
    {
        $victim = $this->role(RoleType::Villager);
        $this->app->act($this->game, $this->users, $this->role(RoleType::Vampire), $victim);
        $this->app->act($this->game, $this->users, $this->role(RoleType::Doctor), $victim);

        $this->app->finishCurrentPhase($this->game);

        $public = $this->publicMessages($this->game);
        $this->assertContains('Bu gece kimse ölmedi.', $public);
        $this->assertTrue($this->app->repositories->players->findById($victim->id)->alive);
        $this->assertStringNotContainsString('kurtar', implode(' ', $public));

        $secret = array_map(static fn ($log): string => $log->message, $this->app->repositories->logs->listByGame($this->game->id, true));
        $this->assertNotEmpty(array_filter($secret, static fn (string $m): bool => str_contains($m, 'kurtardı')));
    }

    public function testNightWithoutActionsKillsNobody(): void
    {
        $this->app->finishCurrentPhase($this->game);

        $this->assertContains('Bu gece kimse ölmedi.', $this->publicMessages($this->game));
    }

    public function testVampiresWithDifferentTargetsKillTwoPlayers(): void
    {
        [$game, $users] = $this->app->createGame(8, vampires: 2);
        $first = $this->app->playerWithRole($game, RoleType::Villager, 0);
        $second = $this->app->playerWithRole($game, RoleType::Villager, 1);
        $this->app->act($game, $users, $this->app->playerWithRole($game, RoleType::Vampire, 0), $first);
        $this->app->act($game, $users, $this->app->playerWithRole($game, RoleType::Vampire, 1), $second);

        $this->app->finishCurrentPhase($game);

        $this->assertContains("Bu gece {$first->name} ve {$second->name} öldü.", $this->publicMessages($game));
        $this->assertFalse($this->app->repositories->players->findById($first->id)->alive);
        $this->assertFalse($this->app->repositories->players->findById($second->id)->alive);
    }

    public function testSeerKilledAtNightHasTheirRoleRevealed(): void
    {
        $seer = $this->role(RoleType::Seer);
        $this->app->act($this->game, $this->users, $this->role(RoleType::Vampire), $seer);
        $this->app->finishCurrentPhase($this->game);

        $viewer = $this->app->userOf($this->role(RoleType::Villager), $this->users);
        $entry = array_values(array_filter($this->stateOf($this->game, $viewer)['players'], static fn (array $p): bool => $p['id'] === $seer->id))[0];

        $this->assertSame('seer', $entry['role']['key']);
        $this->assertContains("{$seer->name} Kâhin'di.", $this->publicMessages($this->game));
    }

    public function testNightVictimsOtherThanTheSeerStayHidden(): void
    {
        $victim = $this->role(RoleType::Villager);
        $this->app->act($this->game, $this->users, $this->role(RoleType::Vampire), $victim);
        $this->app->finishCurrentPhase($this->game);

        $viewer = $this->app->userOf($this->role(RoleType::Doctor), $this->users);
        $entry = array_values(array_filter($this->stateOf($this->game, $viewer)['players'], static fn (array $p): bool => $p['id'] === $victim->id))[0];

        $this->assertNull($entry['role']);
    }

    public function testTrapCancelsTheVisitorAndNotifiesBothPlayers(): void
    {
        [$game, $users] = $this->app->createGame(8, vampires: 1, trappers: 1);
        $trapper = $this->app->playerWithRole($game, RoleType::Trapper);
        $vampire = $this->app->playerWithRole($game, RoleType::Vampire);
        $house = $this->app->playerWithRole($game, RoleType::Villager);
        $trapperUser = $this->app->userOf($trapper, $users);
        $vampireUser = $this->app->userOf($vampire, $users);

        $this->app->act($game, $users, $trapper, $house);
        $this->app->act($game, $users, $vampire, $house);
        $this->assertSame([], $this->stateOf($game, $trapperUser)['trap_notes'], 'sonuç sabaha kadar gizli');

        $this->app->finishCurrentPhase($game);

        $this->assertTrue($this->app->repositories->players->findById($house->id)->alive);
        $this->assertContains('Bu gece kimse ölmedi.', $this->publicMessages($game));
        $this->assertSame(['1. gece: Bir tuzağa takıldın, eylemin iptal oldu.'], $this->stateOf($game, $vampireUser)['trap_notes']);
        $this->assertSame(['1. gece: Tuzağına biri takıldı.'], $this->stateOf($game, $trapperUser)['trap_notes']);

        $bystander = $this->app->userOf($this->app->playerWithRole($game, RoleType::Doctor), $users);
        $this->assertSame([], $this->stateOf($game, $bystander)['trap_notes']);
    }

    public function testTrapperIsToldWhenNobodyWasTrapped(): void
    {
        [$game, $users] = $this->app->createGame(8, vampires: 1, trappers: 1);
        $trapper = $this->app->playerWithRole($game, RoleType::Trapper);
        $this->app->act($game, $users, $trapper, $this->app->playerWithRole($game, RoleType::Villager));

        $this->app->finishCurrentPhase($game);

        $this->assertSame(['1. gece: Tuzağına kimse takılmadı.'], $this->stateOf($game, $this->app->userOf($trapper, $users))['trap_notes']);
    }
}
