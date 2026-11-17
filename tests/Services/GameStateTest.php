<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Services;

use VampirKoylu\Dev\GameSimulator;
use VampirKoylu\Exceptions\ForbiddenException;
use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Models\DeathCause;
use VampirKoylu\Models\Game;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Models\RoleType;
use VampirKoylu\Models\User;
use VampirKoylu\Services\GameStateBuilder;
use VampirKoylu\Tests\Support\AppTestCase;

final class GameStateTest extends AppTestCase
{
    private function state(Game $game, User $viewer): array
    {
        return $this->app->container->get(GameStateBuilder::class)
            ->build($this->app->repositories->games->findById($game->id), $viewer);
    }

    private function countLeakedRoles(array $state): int
    {
        $leaks = 0;
        $finished = $state['game']['status'] === GameStatus::Finished->value;

        foreach ($state['players'] as $player) {
            if ($player['role'] === null) {
                continue;
            }
            $deadSeer = !$player['alive'] && $player['role']['key'] === 'seer';
            $allowed = $player['is_me'] || $finished || $deadSeer || str_contains($player['status'], 'Oylamayla');
            if (!$allowed) {
                $leaks++;
            }
        }

        return $leaks;
    }

    public function testPlayerSeesOnlyTheirOwnRole(): void
    {
        [$game, $users] = $this->app->createGame(8);
        $villager = $this->app->playerWithRole($game, RoleType::Villager);

        $state = $this->state($game, $this->app->userOf($villager, $users));

        $this->assertSame('villager', $state['me']['role']['key']);
        $rolesShown = array_filter($state['players'], static fn (array $p): bool => $p['role'] !== null);
        $this->assertCount(1, $rolesShown);
        $this->assertTrue(array_values($rolesShown)[0]['is_me']);
        $this->assertSame(0, $this->countLeakedRoles($state));
    }

    public function testVampireDoesNotSeeTheOtherVampires(): void
    {
        [$game, $users] = $this->app->createGame(8, vampires: 2);
        $vampire = $this->app->playerWithRole($game, RoleType::Vampire, 0);

        $state = $this->state($game, $this->app->userOf($vampire, $users));

        $shown = array_filter($state['players'], static fn (array $p): bool => $p['role'] !== null);
        $this->assertCount(1, $shown);
        $this->assertSame($vampire->id, array_values($shown)[0]['id']);
    }

    public function testNightChoicesStayPrivate(): void
    {
        [$game, $users] = $this->app->createGame(8, vampires: 2);
        $v1 = $this->app->playerWithRole($game, RoleType::Vampire, 0);
        $v2 = $this->app->playerWithRole($game, RoleType::Vampire, 1);
        $villager = $this->app->playerWithRole($game, RoleType::Villager);
        $this->app->act($game, $users, $v1, $villager);

        $partner = $this->state($game, $this->app->userOf($v2, $users));
        $this->assertNull($partner['night']['chosen_id']);
        $this->assertNull($this->state($game, $this->app->userOf($villager, $users))['night']);
    }

    public function testNightPanelOffersOnlyAllowedTargets(): void
    {
        [$game, $users] = $this->app->createGame(5);
        $vampire = $this->app->playerWithRole($game, RoleType::Vampire);
        $doctor = $this->app->playerWithRole($game, RoleType::Doctor);

        $vampireTargets = array_column($this->state($game, $this->app->userOf($vampire, $users))['night']['targets'], 'id');
        $doctorTargets = array_column($this->state($game, $this->app->userOf($doctor, $users))['night']['targets'], 'id');

        $this->assertNotContains($vampire->id, $vampireTargets);
        $this->assertContains($doctor->id, $doctorTargets);
    }

    public function testSeerLearnsTheResultOnlyInTheMorning(): void
    {
        [$game, $users] = $this->app->createGame(8);
        $seer = $this->app->playerWithRole($game, RoleType::Seer);
        $vampire = $this->app->playerWithRole($game, RoleType::Vampire);
        $seerUser = $this->app->userOf($seer, $users);

        $this->app->act($game, $users, $seer, $vampire);
        $this->assertSame([], $this->state($game, $seerUser)['seer_results']);

        $this->app->finishCurrentPhase($game);

        $results = $this->state($game, $seerUser)['seer_results'];
        $this->assertCount(1, $results);
        $this->assertSame($vampire->name, $results[0]['name']);
        $this->assertSame('vampire', $results[0]['role']['key']);

        $other = $this->state($game, $this->app->userOf($this->app->playerWithRole($game, RoleType::Villager), $users));
        $this->assertSame([], $other['seer_results']);
        $this->assertSame(0, $this->countLeakedRoles($other));
    }

    public function testGhostSeesNoSecretsAndCannotAct(): void
    {
        [$game, $users] = $this->app->createGame(8);
        $ghost = $this->app->playerWithRole($game, RoleType::Villager);
        $ghostUser = $this->app->userOf($ghost, $users);
        $this->app->repositories->players->markDead($ghost->id, DeathCause::Night, $this->app->currentPhase($game)->id);

        $state = $this->state($game, $ghostUser);

        $this->assertTrue($state['me']['is_ghost']);
        $this->assertSame(0, $this->countLeakedRoles($state));
        $this->assertNull($state['night']);

        $this->expectException(RuleViolationException::class);
        $this->app->games()->submitNightAction($game, $ghostUser, $this->app->playerWithRole($game, RoleType::Vampire)->id);
    }

    public function testGhostKeepsWhatTheSeerLearnedBeforeDying(): void
    {
        [$game, $users] = $this->app->createGame(8);
        $seer = $this->app->playerWithRole($game, RoleType::Seer);
        $this->app->act($game, $users, $seer, $this->app->playerWithRole($game, RoleType::Vampire));
        $this->app->finishCurrentPhase($game);
        $this->app->repositories->players->markDead($seer->id, DeathCause::Night, $this->app->currentPhase($game)->id);

        $this->assertCount(1, $this->state($game, $this->app->userOf($seer, $users))['seer_results']);
    }

    public function testOutsiderCannotViewTheGame(): void
    {
        [$game] = $this->app->createGame(5);
        $outsider = $this->app->registerUser('Yabanci');

        $this->expectException(ForbiddenException::class);
        $this->state($game, $outsider);
    }

    public function testReportIsNotAvailableBeforeTheGameEnds(): void
    {
        [$game, $users] = $this->app->createGame(5);

        $this->assertNull($this->state($game, $users[0])['report']);
    }

    public function testLobbyStateHasNoRolesAndSignatureTracksPlayersAndSettings(): void
    {
        [$game, $users] = $this->app->createGame(8, start: false);
        $before = $this->state($game, $users[0]);
        $this->assertCount(0, array_filter($before['players'], static fn (array $p): bool => $p['role'] !== null));

        $this->app->lobby()->updateRoleSettings($game, $users[0], 3, 1);
        $afterSettings = $this->state($game, $users[0]);
        $this->assertNotSame($before['signature'], $afterSettings['signature']);

        $this->app->lobby()->join($game->roomCode, $this->app->registerUser('Yeni'));
        $this->assertNotSame($afterSettings['signature'], $this->state($game, $users[0])['signature']);
    }

    public function testLobbyPanelListsTheAllowedSettings(): void
    {
        [$small, $smallUsers] = $this->app->createGame(6, start: false);
        [$large, $largeUsers] = $this->app->createGame(9, start: false);

        $smallSettings = $this->state($small, $smallUsers[0])['settings'];
        $largeSettings = $this->state($large, $largeUsers[0])['settings'];

        $this->assertSame([1], $smallSettings['vampire_options']);
        $this->assertSame([0], $smallSettings['trapper_options']);
        $this->assertSame([1, 2, 3], $largeSettings['vampire_options']);
        $this->assertSame([0, 1, 2], $largeSettings['trapper_options']);
        $this->assertSame(2, $largeSettings['vampires']);
    }

    public function testNoRoleLeaksDuringAFullGame(): void
    {
        $users = $this->app->registerUsers(10);
        $simulator = new GameSimulator($this->app->container, $this->app->clock, new \Random\Randomizer(new \Random\Engine\Mt19937(42)));

        $checks = 0;
        $leaks = 0;
        $simulator->playToEnd($users, function (Game $game) use ($users, &$checks, &$leaks): void {
            foreach ($users as $user) {
                $state = $this->state($game, $user);
                $leaks += $this->countLeakedRoles($state);
                $checks++;

                if (($state['me']['role']['key'] ?? null) !== 'seer') {
                    $this->assertSame([], $state['seer_results']);
                }
            }
        }, vampires: 2, trappers: 2);

        $this->assertGreaterThan(20, $checks);
        $this->assertSame(0, $leaks);
    }
}
