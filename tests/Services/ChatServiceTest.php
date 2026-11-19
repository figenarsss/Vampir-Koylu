<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Services;

use VampirKoylu\Exceptions\ForbiddenException;
use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Models\DeathCause;
use VampirKoylu\Models\Game;
use VampirKoylu\Models\PhaseType;
use VampirKoylu\Models\Team;
use VampirKoylu\Models\User;
use VampirKoylu\Services\ChatService;
use VampirKoylu\Tests\Support\AppTestCase;

// Oda sohbeti kuralları: gece kapalı, elenen yalnızca okur, mesaj sınırları.
final class ChatServiceTest extends AppTestCase
{
    private Game $game;
    private array $users;

    protected function setUp(): void
    {
        parent::setUp();
        // 6 oyunculu oyun başlatılır; ilk faz geceyi başlatır.
        [$this->game, $this->users] = $this->app->createGame(6);
    }

    private function chat(): ChatService
    {
        return $this->app->container->get(ChatService::class);
    }

    private function goToDay(): void
    {
        $this->app->finishCurrentPhase($this->game);
        $this->assertSame(PhaseType::Day, $this->app->currentPhaseType($this->game));
    }

    private function bodies(?Game $game = null): array
    {
        return array_map(
            static fn ($message): string => $message->body,
            $this->chat()->recent($game ?? $this->game),
        );
    }

    public function testPlayerCanChatDuringTheDay(): void
    {
        $this->goToDay();

        $this->chat()->send($this->game, $this->users[1], 'Merhaba köylüler');

        $messages = $this->chat()->recent($this->game);
        $this->assertCount(1, $messages);
        $this->assertSame('Merhaba köylüler', $messages[0]->body);
        $this->assertSame($this->users[1]->id, $messages[0]->userId);
    }

    public function testChatIsClosedAtNight(): void
    {
        $this->assertSame(PhaseType::Night, $this->app->currentPhaseType($this->game));

        try {
            $this->chat()->send($this->game, $this->users[0], 'Vampirler gizlice konuşuyor');
            $this->fail('Gece sohbet reddedilmeliydi');
        } catch (RuleViolationException $error) {
            $this->assertStringContainsString('Gece sohbet kapalı', $error->getMessage());
        }

        $this->assertSame([], $this->bodies(), 'gece hiçbir mesaj kaydedilmemeli');
    }

    public function testChatIsOpenDuringTheVote(): void
    {
        $this->goToDay();
        $this->app->finishCurrentPhase($this->game);
        $this->assertSame(PhaseType::Vote, $this->app->currentPhaseType($this->game));

        $this->chat()->send($this->game, $this->users[2], 'Bence o şüpheli');

        $this->assertSame(['Bence o şüpheli'], $this->bodies());
    }

    public function testEliminatedPlayerCanReadButCannotWrite(): void
    {
        $this->goToDay();
        $this->chat()->send($this->game, $this->users[1], 'Canlı oyuncu yazdı');

        $deadPlayer = $this->app->players($this->game)[2];
        $deadUser = $this->app->userOf($deadPlayer, $this->users);
        $this->app->repositories->players->markDead($deadPlayer->id, DeathCause::Vote, $this->app->currentPhase($this->game)->id);

        $reason = $this->chat()->blockReason($this->game, $this->app->repositories->players->findById($deadPlayer->id));
        $this->assertNotNull($reason);

        try {
            $this->chat()->send($this->game, $deadUser, 'Ben ölüyüm ama yazıyorum');
            $this->fail('Elenen oyuncunun mesajı reddedilmeliydi');
        } catch (RuleViolationException $error) {
            $this->assertStringContainsString('yalnızca okuyabilirsin', $error->getMessage());
        }

        $this->assertSame(['Canlı oyuncu yazdı'], $this->bodies(), 'elenen oyuncu okuyabilmeli, kendi mesajı eklenmemeli');
    }

    public function testPlayerOutsideTheRoomCannotWrite(): void
    {
        $this->goToDay();
        $stranger = $this->app->registerUser('Yabanci');

        $this->expectException(ForbiddenException::class);
        $this->chat()->send($this->game, $stranger, 'İçeri sızıyorum');
    }

    public function testEmptyAndBlankMessagesAreRejected(): void
    {
        $this->goToDay();

        foreach (['', '   ', "\n\t  "] as $text) {
            try {
                $this->chat()->send($this->game, $this->users[0], $text);
                $this->fail('Boş mesaj reddedilmeliydi');
            } catch (RuleViolationException $error) {
                $this->assertStringContainsString('Boş mesaj', $error->getMessage());
            }
        }

        $this->assertSame([], $this->bodies());
    }

    public function testMessageLengthLimitIsThreeHundredCharacters(): void
    {
        $this->goToDay();

        // Türkçe karakterler de bir karakter sayılır (mb_strlen)
        $exactly = str_repeat('ş', ChatService::MAX_LENGTH);
        $this->chat()->send($this->game, $this->users[0], $exactly);
        $this->assertSame([$exactly], $this->bodies());

        try {
            $this->chat()->send($this->game, $this->users[0], $exactly . 'x');
            $this->fail('301 karakterlik mesaj reddedilmeliydi');
        } catch (RuleViolationException $error) {
            $this->assertStringContainsString('en fazla 300 karakter', $error->getMessage());
        }

        $this->assertCount(1, $this->bodies());
    }

    public function testControlCharactersAreReplacedAndTextIsTrimmed(): void
    {
        $this->goToDay();

        $this->chat()->send($this->game, $this->users[0], "  satir1\nsatir2\x00sonu  ");

        $this->assertSame(['satir1 satir2 sonu'], $this->bodies());
    }

    public function testHtmlIsStoredAsPlainText(): void
    {
        $this->goToDay();
        $html = '<script>alert(1)</script>';

        $this->chat()->send($this->game, $this->users[0], $html);

        // Kayıt değişmez; kaçırma görünümde yapılır (fragment.php içinde $e()).
        $this->assertSame([$html], $this->bodies());
    }

    public function testOnlyTheLatestSixtyMessagesAreVisibleInOrder(): void
    {
        $this->goToDay();

        for ($i = 1; $i <= 65; $i++) {
            $this->app->clock->advance(1);
            $this->chat()->send($this->game, $this->users[$i % 6], "mesaj{$i}");
        }

        $bodies = $this->bodies();
        $this->assertCount(ChatService::VISIBLE_MESSAGES, $bodies);
        $this->assertSame('mesaj6', $bodies[0], 'en eski 5 mesaj görünmemeli');
        $this->assertSame('mesaj65', $bodies[59], 'sıralama eskiden yeniye olmalı');
    }

    public function testMessagesBelongToTheirOwnRoom(): void
    {
        $this->goToDay();
        $this->chat()->send($this->game, $this->users[0], 'Birinci oda');

        [$otherGame, $otherUsers] = $this->app->createGame(6);
        $this->app->finishCurrentPhase($otherGame);
        $this->chat()->send($otherGame, $otherUsers[0], 'İkinci oda');

        $this->assertSame(['Birinci oda'], $this->bodies($this->game));
        $this->assertSame(['İkinci oda'], $this->bodies($otherGame));
    }

    public function testChatIsOpenInTheLobbyEvenForAFreshRoom(): void
    {
        [$lobby, $users] = $this->app->createGame(5, false);

        $this->chat()->send($lobby, $users[3], 'Herkes hazır mı?');

        $this->assertSame(['Herkes hazır mı?'], $this->bodies($lobby));
    }

    public function testChatReopensForEveryoneAfterTheGameEnds(): void
    {
        $this->goToDay();
        $deadPlayer = $this->app->players($this->game)[2];
        $deadUser = $this->app->userOf($deadPlayer, $this->users);
        $this->app->repositories->players->markDead($deadPlayer->id, DeathCause::Night, $this->app->currentPhase($this->game)->id);
        $this->app->repositories->games->finish($this->game->id, Team::Villagers);
        $finished = $this->app->repositories->games->findById($this->game->id);

        $this->chat()->send($finished, $deadUser, 'İyi oyundu');

        $this->assertSame(['İyi oyundu'], $this->bodies($finished));
    }
}
