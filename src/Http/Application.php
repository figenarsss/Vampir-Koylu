<?php

declare(strict_types=1);

namespace VampirKoylu\Http;

use Throwable;
use VampirKoylu\Core\Container;
use VampirKoylu\Exceptions\ForbiddenException;
use VampirKoylu\Exceptions\NotFoundException;
use VampirKoylu\Repositories\UserRepositoryInterface;

final class Application
{
    public function __construct(
        private readonly Container $container,
        private readonly Router $router,
        private readonly bool $showErrorDetails = false,
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            // her POST isteği CSRF belirteci ister
            if ($request->method === 'POST' && !$this->container->get(Csrf::class)->isValid($request->session, $request->post[Csrf::FIELD] ?? null)) {
                return $this->errorPage($request, 419, 'Oturum doğrulaması başarısız oldu. Sayfayı yenileyip tekrar deneyin.');
            }

            $route = $this->router->match($request->method, $request->path);
            if ($route === null) {
                return $this->router->pathExists($request->path)
                    ? $this->errorPage($request, 405, 'Bu adres bu yöntemle kullanılamaz.')
                    : $this->errorPage($request, 404, 'Aradığınız sayfa bulunamadı.');
            }

            $request->params = $route['params'];
            $this->loadUser($request);

            foreach ($route['middleware'] as $guard) {
                $blocked = $this->applyGuard($guard, $request);
                if ($blocked !== null) {
                    return $blocked;
                }
            }

            [$class, $method] = $route['handler'];

            return $this->container->get($class)->$method($request);
        } catch (NotFoundException $error) {
            return $this->errorPage($request, 404, $error->getMessage());
        } catch (ForbiddenException $error) {
            return $this->errorPage($request, 403, $error->getMessage());
        } catch (Throwable $error) {
            $message = $this->showErrorDetails ? $error->getMessage() : 'Beklenmeyen bir hata oluştu. Lütfen tekrar deneyin.';

            return $this->errorPage($request, 500, $message);
        }
    }

    private function loadUser(Request $request): void
    {
        $userId = $request->session->userId();
        if ($userId === null) {
            return;
        }

        $request->user = $this->container->get(UserRepositoryInterface::class)->findById($userId);
        if ($request->user === null) {
            $request->session->logout(); // hesap silinmişse oturumu da kapat
        }
    }

    private function applyGuard(string $guard, Request $request): ?Response
    {
        return match ($guard) {
            'auth' => $request->user !== null ? null : $this->loginRequired($request),
            'guest' => $request->user === null ? null : Response::redirect($request->url('/')),
            'admin' => $request->user === null
                ? $this->loginRequired($request)
                : ($request->user->isAdmin() ? null : $this->errorPage($request, 403, 'Bu sayfa yalnızca yöneticiler içindir.')),
            default => throw new \LogicException("Bilinmeyen ara katman: {$guard}"),
        };
    }

    private function loginRequired(Request $request): Response
    {
        if ($request->ajax) {
            return Response::text('Oturum kapalı.', 401);
        }
        $request->session->flash('error', 'Bu sayfayı görmek için giriş yapmalısınız.');

        return Response::redirect($request->url('/giris'));
    }

    private function errorPage(Request $request, int $status, string $message): Response
    {
        if ($request->ajax) {
            return Response::text($message, $status);
        }

        $html = $this->container->get(View::class)->render('error', [
            'title' => "Hata {$status}",
            'status' => $status,
            'message' => $message,
        ], $request);

        return Response::html($html, $status);
    }
}
