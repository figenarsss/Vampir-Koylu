<?php

declare(strict_types=1);

namespace VampirKoylu\Tests\Support;

use VampirKoylu\Http\Application;
use VampirKoylu\Http\Csrf;
use VampirKoylu\Http\Request;
use VampirKoylu\Http\Response;
use VampirKoylu\Http\Router;
use VampirKoylu\Http\Session;

final class Browser
{
    private readonly Application $application;
    private readonly Session $session;

    public function __construct(private readonly TestApp $app)
    {
        $router = new Router();
        (require dirname(__DIR__, 2) . '/config/routes.php')($router);

        $this->application = new Application($app->container, $router, true);
        $this->session = Session::memory();
    }

    public function get(string $path, array $query = [], bool $ajax = false): Response
    {
        return $this->application->handle(new Request('GET', $path, $query, [], $this->session, '', $ajax));
    }

    public function post(string $path, array $data = []): Response
    {
        return $this->postWithToken($path, $data + [Csrf::FIELD => $this->token()]);
    }

    public function postWithToken(string $path, array $data): Response
    {
        return $this->application->handle(new Request('POST', $path, [], $data, $this->session));
    }

    public function login(string $email, string $password = TestApp::PASSWORD): Response
    {
        return $this->post('/giris', ['email' => $email, 'password' => $password]);
    }

    public function sessionIdRegenerations(): int
    {
        return $this->session->idRegenerations();
    }

    private function token(): string
    {
        return $this->app->container->get(Csrf::class)->token($this->session);
    }
}
