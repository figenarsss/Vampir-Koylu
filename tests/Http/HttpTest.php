<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Http;

use Random\Engine\Mt19937;
use Random\Randomizer;
use VampirKoylu\Dev\GameSimulator;
use VampirKoylu\Models\AccountRole;
use VampirKoylu\Models\GameStatus;
use VampirKoylu\Models\User;
use VampirKoylu\Tests\Support\AppTestCase;
use VampirKoylu\Tests\Support\Browser;
use VampirKoylu\Tests\Support\TestApp;

final class HttpTest extends AppTestCase
{
    private function browserFor(User $user): Browser
    {
        $browser = new Browser($this->app);
        $response = $browser->login($user->email);
        $this->assertSame(302, $response->status, 'giriş başarılı olmalı');

        return $browser;
    }

    private function admin(): User
    {
        return $this->app->repositories->users->create(
            'Yönetici',
            'admin@test.com',
            password_hash(TestApp::PASSWORD, PASSWORD_DEFAULT),
            AccountRole::Admin,
            $this->app->clock->now(),
        );
    }

    private function startedRoomOverHttp(int $count): array
    {
        $users = $this->app->registerUsers($count);
        $browsers = array_map(fn (User $u): Browser => $this->browserFor($u), $users);

        $created = $browsers[0]->post('/oda');
        $this->assertSame(302, $created->status);
        preg_match('#/oyun/([A-Z0-9]{6})$#', $created->headers['Location'], $match);
        $code = $match[1];

        foreach (array_slice($browsers, 1) as $browser) {
            $browser->post('/oda/katil', ['code' => strtolower($code)]);
        }
        $browsers[0]->post("/oyun/{$code}/baslat");

        return [$code, $browsers, $users];
    }

    public function testGuestsAreRedirectedToLogin(): void
    {
        $response = (new Browser($this->app))->get('/oyunlarim');

        $this->assertSame(302, $response->status);
        $this->assertSame('/giris', $response->headers['Location']);
    }

    public function testPostWithoutCsrfTokenIsRejected(): void
    {
        $user = $this->app->registerUser('Ali');

        $response = (new Browser($this->app))->postWithToken('/giris', ['email' => $user->email, 'password' => TestApp::PASSWORD]);

        $this->assertSame(419, $response->status);
    }

    public function testRegisterLoginAndLogoutFlow(): void
    {
        $browser = new Browser($this->app);

        $register = $browser->post('/kayit', ['name' => 'Yeni Oyuncu', 'email' => 'yeni@test.com', 'password' => 'Parola123', 'password_repeat' => 'Parola123']);
        $this->assertSame(302, $register->status);

        $home = $browser->get('/');
        $this->assertStringContainsString('Oda oluştur', $home->body);
        $this->assertStringContainsString('Yeni Oyuncu', $home->body);

        $browser->post('/cikis');
        $this->assertSame(302, $browser->get('/oyunlarim')->status, 'çıkıştan sonra korunan sayfa açılmaz');
    }

    public function testSessionIdIsRegeneratedOnLogin(): void
    {
        $user = $this->app->registerUser('Ali');
        $browser = new Browser($this->app);

        $this->assertSame(0, $browser->sessionIdRegenerations());
        $this->assertSame(422, $browser->login($user->email, 'YanlisSifre1')->status);
        $this->assertSame(0, $browser->sessionIdRegenerations());

        $browser->login($user->email);
        $this->assertSame(1, $browser->sessionIdRegenerations());
    }

    public function testJoiningAFullRoomIsRejectedWithinThreeSeconds(): void
    {
        $users = $this->app->registerUsers(13);
        $game = $this->app->lobby()->createRoom($users[0]);
        foreach (array_slice($users, 1, 11) as $user) {
            $this->app->lobby()->join($game->roomCode, $user);
        }
        $browser = $this->browserFor($users[12]);

        $started = microtime(true);
        $response = $browser->post('/oda/katil', ['code' => $game->roomCode]);
        $page = $browser->get('/');
        $elapsed = microtime(true) - $started;

        $this->assertSame(302, $response->status);
        $this->assertStringContainsString('Oda dolu', $page->body);
        $this->assertLessThan(3.0, $elapsed);
        $this->assertCount(12, $this->app->players($game));
    }

    public function testOpenRoomsCanBeListedAndSearchedByCode(): void
    {
        [$first, $second, $third] = $this->app->registerUsers(3);
        $open = $this->app->lobby()->createRoom($first);
        $other = $this->app->lobby()->createRoom($second);
        [$running] = $this->app->createGame(5);
        $browser = $this->browserFor($third);

        $all = $browser->get('/odalar')->body;
        $this->assertStringContainsString($open->roomCode, $all);
        $this->assertStringContainsString($other->roomCode, $all);
        $this->assertStringNotContainsString($running->roomCode, $all);

        $filtered = $browser->get('/odalar', ['q' => strtolower($open->roomCode)])->body;
        $this->assertStringContainsString($open->roomCode, $filtered);
        $this->assertStringNotContainsString($other->roomCode, $filtered);

        $browser->post('/oda/katil', ['code' => $open->roomCode]);
        $this->assertCount(2, $this->app->players($open));
    }

    public function testHostChangesTheRoleSettingsOverHttp(): void
    {
        [$game, $users] = $this->app->createGame(9, start: false);
        $host = $this->browserFor($users[0]);
        $guest = $this->browserFor($users[1]);

        $host->post("/oyun/{$game->roomCode}/ayar", ['vampires' => '3', 'trappers' => '2']);
        $saved = $this->app->repositories->games->findById($game->id);
        $this->assertSame([3, 2], [$saved->vampireCount, $saved->trapperCount]);

        $this->assertSame(403, $guest->post("/oyun/{$game->roomCode}/ayar", ['vampires' => '1', 'trappers' => '0'])->status);

        $host->post("/oyun/{$game->roomCode}/ayar", ['vampires' => '9', 'trappers' => '0']);
        $this->assertSame(3, $this->app->repositories->games->findById($game->id)->vampireCount);

        $page = $host->get("/oyun/{$game->roomCode}/parca", ajax: true)->body;
        $this->assertStringContainsString('Vampir sayısı', $page);
        $this->assertStringContainsString('Tuzakçı sayısı', $page);
    }

    public function testRegisterShowsValidationErrorsInTheForm(): void
    {
        $response = (new Browser($this->app))->post('/kayit', ['name' => 'Ali', 'email' => 'ali@test.com', 'password' => '123', 'password_repeat' => '123']);

        $this->assertSame(422, $response->status);
        $this->assertStringContainsString('en az 8 karakter', $response->body);
        $this->assertStringContainsString('value="Ali"', $response->body, 'girilen ad forma geri yazılmalı');
    }

    public function testFragmentEndpointReturnsOnlyTheSectionAndIgnoresClientState(): void
    {
        [$code, $browsers] = $this->startedRoomOverHttp(5);

        $fragment = $browsers[0]->get("/oyun/{$code}/parca", ['faz' => 'oylama', 'kalan' => '0'], ajax: true);

        $this->assertSame(200, $fragment->status);
        $this->assertStringNotContainsString('<html', $fragment->body, 'tam sayfa değil, yalnızca bölüm olmalı');
        $this->assertStringContainsString('data-imza', $fragment->body);
        $this->assertStringContainsString('id="sayac"', $fragment->body);
        $this->assertStringContainsString('stage-night', $fragment->body, 'istemcinin gönderdiği faz bilgisine güvenilmemeli');

        $anonymous = (new Browser($this->app))->get("/oyun/{$code}/parca", [], ajax: true);
        $this->assertSame(401, $anonymous->status);
    }

    public function testGamePageEmbedsTheRefreshScript(): void
    {
        [$code, $browsers] = $this->startedRoomOverHttp(5);

        $page = $browsers[1]->get("/oyun/{$code}");

        $this->assertStringContainsString('<html', $page->body);
        $this->assertStringContainsString('id="bolum"', $page->body);
        $this->assertStringContainsString("/oyun/{$code}/parca", $page->body);
        $this->assertStringContainsString('oyun.js', $page->body);
    }

    public function testStateEndpointReturnsDifferentDataForEachPlayer(): void
    {
        [$code, $browsers] = $this->startedRoomOverHttp(8);

        $myRoles = [];
        foreach ($browsers as $browser) {
            $response = $browser->get("/oyun/{$code}/durum", ajax: true);
            $this->assertSame(200, $response->status);
            $state = json_decode($response->body, true, flags: JSON_THROW_ON_ERROR);
            $myRoles[] = $state['me']['role']['key'];
            $shown = array_filter($state['players'], static fn (array $p): bool => $p['role'] !== null);
            $this->assertCount(1, $shown);
        }

        $this->assertGreaterThan(1, count(array_unique($myRoles)), 'oyuncuların rolleri farklı olmalı');
    }

    public function testOutsiderCannotOpenTheGame(): void
    {
        [$code] = $this->startedRoomOverHttp(5);
        $outsider = $this->browserFor($this->app->registerUser('Yabanci'));

        $page = $outsider->get("/oyun/{$code}");
        $this->assertSame(302, $page->status, 'ana sayfaya yönlendirilir');

        $this->assertSame(403, $outsider->get("/oyun/{$code}/durum", ajax: true)->status);
    }

    public function testPlayerNamesAreEscaped(): void
    {
        $evil = $this->app->auth()->register('<script>alert(1)</script>', 'evil@test.com', TestApp::PASSWORD, TestApp::PASSWORD);
        $browser = $this->browserFor($evil);
        $browser->post('/oda');
        $code = $this->app->repositories->games->search(null, null, $evil->id)[0]['room_code'];

        $fragment = $browser->get("/oyun/{$code}/parca", ajax: true)->body;

        $this->assertStringNotContainsString('<script>alert(1)</script>', $fragment);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $fragment);
    }

    public function testOnlyTheHostCanDeleteARoomOverHttp(): void
    {
        [$code, $browsers] = $this->startedRoomOverHttp(5);

        $this->assertSame(403, $browsers[1]->post("/oyun/{$code}/sil")->status);
        $this->assertNotNull($this->app->repositories->games->findByCode($code));
    }

    public function testFinishedGameShowsWinnerRolesAndFullReport(): void
    {
        $users = $this->app->registerUsers(7);
        $game = (new GameSimulator($this->app->container, $this->app->clock, new Randomizer(new Mt19937(11))))->playToEnd($users);
        $this->assertSame(GameStatus::Finished, $this->app->repositories->games->findById($game->id)->status);

        $browser = $this->browserFor($users[3]);
        $fragment = $browser->get("/oyun/{$game->roomCode}/parca", ajax: true)->body;

        $this->assertStringContainsString('Oyun bitti', $fragment);
        $this->assertMatchesRegularExpression('/(Köylüler|Vampirler) kazandı/u', $fragment);
        $this->assertStringContainsString('Olay özeti', $fragment);
        $this->assertStringContainsString('🔒', $fragment, 'gizli olaylar oyun bitince gösterilmeli');
        $this->assertStringContainsString('Vampir', $fragment, 'tüm roller açıklanmalı');
    }

    public function testHistorySearchAndFilter(): void
    {
        $users = $this->app->registerUsers(5);
        $finished = (new GameSimulator($this->app->container, $this->app->clock, new Randomizer(new Mt19937(3))))->playToEnd($users);
        $lobby = $this->app->lobby()->createRoom($users[0]);
        $browser = $this->browserFor($users[0]);

        $all = $browser->get('/oyunlarim')->body;
        $this->assertStringContainsString($finished->roomCode, $all);
        $this->assertStringContainsString($lobby->roomCode, $all);

        $onlyFinished = $browser->get('/oyunlarim', ['status' => 'finished'])->body;
        $this->assertStringContainsString($finished->roomCode, $onlyFinished);
        $this->assertStringNotContainsString($lobby->roomCode, $onlyFinished);

        $byCode = $browser->get('/oyunlarim', ['q' => strtolower($lobby->roomCode)])->body;
        $this->assertStringContainsString($lobby->roomCode, $byCode);
        $this->assertStringNotContainsString($finished->roomCode, $byCode);

        $nothing = $browser->get('/oyunlarim', ['q' => 'YOKBOYLEBIR'])->body;
        $this->assertStringContainsString('bulunamadı', $nothing);
    }

    public function testAdminPagesRequireTheAdminRole(): void
    {
        $this->app->registerUsers(5);
        $player = $this->browserFor($this->app->registerUser('Sade'));
        $admin = $this->browserFor($this->admin());

        $this->assertSame('/giris', (new Browser($this->app))->get('/yonetim')->headers['Location']);
        $this->assertSame(403, $player->get('/yonetim')->status);
        $this->assertSame(403, $player->get('/yonetim/kullanicilar')->status);
        $this->assertSame(403, $player->post('/yonetim/oyunlar/1/sil')->status);

        $dashboard = $admin->get('/yonetim');
        $this->assertSame(200, $dashboard->status);
        $this->assertStringContainsString('Özet rapor', $dashboard->body);
    }

    public function testAdminCanSearchUsersChangeRolesAndDeleteGames(): void
    {
        $users = $this->app->registerUsers(5);
        $adminUser = $this->admin();
        $admin = $this->browserFor($adminUser);
        $game = $this->app->lobby()->createRoom($users[0]);

        $search = $admin->get('/yonetim/kullanicilar', ['q' => $users[2]->name])->body;
        $this->assertStringContainsString($users[2]->name, $search);
        $this->assertStringNotContainsString($users[3]->name, $search);

        $admin->post("/yonetim/kullanicilar/{$users[2]->id}/yetki", ['role' => 'admin']);
        $this->assertTrue($this->app->repositories->users->findById($users[2]->id)->isAdmin());

        $admin->post("/yonetim/kullanicilar/{$adminUser->id}/yetki", ['role' => 'player']);
        $this->assertTrue($this->app->repositories->users->findById($adminUser->id)->isAdmin(), 'yönetici kendi yetkisini düşüremez');

        $admin->post("/yonetim/oyunlar/{$game->id}/sil");
        $this->assertNull($this->app->repositories->games->findById($game->id));
    }

    public function testUnknownPageGives404(): void
    {
        $this->assertSame(404, (new Browser($this->app))->get('/yok-boyle-bir-sayfa')->status);
    }
}
