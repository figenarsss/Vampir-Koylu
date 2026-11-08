<?php

declare(strict_types=1);

namespace VampirKoylu\Controllers;

use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Http\Request;
use VampirKoylu\Http\Response;
use VampirKoylu\Http\View;
use VampirKoylu\Services\AuthService;

final class AuthController extends Controller
{
    public function __construct(View $view, private readonly AuthService $auth)
    {
        parent::__construct($view);
    }

    public function showRegister(Request $request): Response
    {
        return $this->render($request, 'auth/register', ['title' => 'Kayıt ol', 'error' => null, 'old' => []]);
    }

    public function register(Request $request): Response
    {
        $old = ['name' => $request->string('name'), 'email' => $request->string('email')];

        try {
            $user = $this->auth->register(
                $old['name'],
                $old['email'],
                (string) ($request->post['password'] ?? ''),
                (string) ($request->post['password_repeat'] ?? ''),
            );
        } catch (RuleViolationException $error) {
            return $this->render($request, 'auth/register', ['title' => 'Kayıt ol', 'error' => $error->getMessage(), 'old' => $old], 422);
        }

        $request->session->login($user->id);
        $request->session->flash('success', "Hoş geldin {$user->name}! Bir oda aç ya da bir kodla katıl.");

        return $this->redirect($request, '/');
    }

    public function showLogin(Request $request): Response
    {
        return $this->render($request, 'auth/login', ['title' => 'Giriş yap', 'error' => null, 'old' => []]);
    }

    public function login(Request $request): Response
    {
        $email = $request->string('email');

        try {
            $user = $this->auth->login($email, (string) ($request->post['password'] ?? ''));
        } catch (RuleViolationException $error) {
            return $this->render($request, 'auth/login', ['title' => 'Giriş yap', 'error' => $error->getMessage(), 'old' => ['email' => $email]], 422);
        }

        $request->session->login($user->id);

        return $this->redirect($request, '/');
    }

    public function logout(Request $request): Response
    {
        $request->session->logout();
        $request->session->flash('success', 'Çıkış yaptınız.');

        return $this->redirect($request, '/giris');
    }

    public function showProfile(Request $request): Response
    {
        return $this->render($request, 'auth/profile', ['title' => 'Profilim']);
    }

    public function changePassword(Request $request): Response
    {
        return $this->attempt($request, '/profil', 'Şifreniz değiştirildi.', fn () => $this->auth->changePassword(
            $request->user,
            (string) ($request->post['current_password'] ?? ''),
            (string) ($request->post['new_password'] ?? ''),
            (string) ($request->post['new_password_repeat'] ?? ''),
        ));
    }
}
