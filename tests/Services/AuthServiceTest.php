<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Services;

use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Models\AccountRole;
use VampirKoylu\Tests\Support\AppTestCase;
use VampirKoylu\Tests\Support\TestApp;

final class AuthServiceTest extends AppTestCase
{
    public function testRegisterStoresAHashedPassword(): void
    {
        $user = $this->app->auth()->register('Ayşe', 'Ayse@Test.com ', TestApp::PASSWORD, TestApp::PASSWORD);

        $this->assertSame('ayse@test.com', $user->email, 'e-posta küçük harfe çevrilip kırpılmalı');
        $this->assertSame(AccountRole::Player, $user->accountRole);
        $this->assertNotSame(TestApp::PASSWORD, $user->passwordHash);
        $this->assertTrue(password_verify(TestApp::PASSWORD, $user->passwordHash));
    }

    public function testRegisterRejectsInvalidInput(): void
    {
        $this->app->registerUser('Ali');
        $auth = $this->app->auth();

        $cases = [
            'aynı e-posta' => ['Başka', 'ali@test.com', TestApp::PASSWORD, TestApp::PASSWORD],
            'kısa şifre' => ['Veli', 'veli@test.com', 'a1', 'a1'],
            'rakamsız şifre' => ['Veli', 'veli@test.com', 'sadeceharfler', 'sadeceharfler'],
            'farklı tekrar' => ['Veli', 'veli@test.com', TestApp::PASSWORD, 'Baska123456'],
            'geçersiz e-posta' => ['Veli', 'bu-bir-eposta-degil', TestApp::PASSWORD, TestApp::PASSWORD],
            'çok kısa ad' => ['V', 'veli@test.com', TestApp::PASSWORD, TestApp::PASSWORD],
        ];

        foreach ($cases as $label => $input) {
            try {
                $auth->register(...$input);
                $this->fail("{$label} reddedilmeliydi");
            } catch (RuleViolationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testLoginWithCorrectPassword(): void
    {
        $registered = $this->app->registerUser('Ali');

        $user = $this->app->auth()->login(' ALI@test.com', TestApp::PASSWORD);

        $this->assertSame($registered->id, $user->id);
    }

    public function testLoginRejectsWrongCredentialsWithTheSameMessage(): void
    {
        $this->app->registerUser('Ali');
        $messages = [];

        foreach ([['ali@test.com', 'YanlisSifre1'], ['yok@test.com', TestApp::PASSWORD]] as [$email, $password]) {
            try {
                $this->app->auth()->login($email, $password);
                $this->fail('Giriş reddedilmeliydi');
            } catch (RuleViolationException $error) {
                $messages[] = $error->getMessage();
            }
        }

        $this->assertSame($messages[0], $messages[1]);
    }

    public function testChangePasswordRequiresTheCurrentPassword(): void
    {
        $user = $this->app->registerUser('Ali');
        $auth = $this->app->auth();

        $auth->changePassword($user, TestApp::PASSWORD, 'YeniSifre456', 'YeniSifre456');
        $this->assertSame($user->id, $auth->login('ali@test.com', 'YeniSifre456')->id);

        $this->expectException(RuleViolationException::class);
        $auth->changePassword($user, 'yanlisMevcut1', 'BaskaSifre789', 'BaskaSifre789');
    }
}
