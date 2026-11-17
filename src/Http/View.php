<?php

declare(strict_types=1);

namespace VampirKoylu\Http;

use RuntimeException;

final class View
{
    public function __construct(
        private readonly string $viewPath,
        private readonly string $appName,
        private readonly Csrf $csrf,
    ) {
    }

    public function render(string $template, array $data, Request $request): string
    {
        // Her şablonda giriş yapmış kullanıcı $user olarak hazırdır.
        $content = $this->partial($template, $data + ['user' => $request->user], $request);

        return $this->partial('layout', [
            'content' => $content,
            'appName' => $this->appName,
            'flashes' => $request->session->pullFlashes(),
            'user' => $request->user,
            'title' => $data['title'] ?? $this->appName,
        ], $request);
    }

    public function partial(string $template, array $data, Request $request): string
    {
        $file = "{$this->viewPath}/{$template}.php";
        if (!is_file($file)) {
            throw new RuntimeException("Şablon bulunamadı: {$template}");
        }

        // şablonlarda hazır: $e, $url, $csrf, $partial
        $helpers = [
            'e' => static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'url' => static fn (string $path = '/'): string => $request->url($path),
            'csrf' => fn (): string => sprintf(
                '<input type="hidden" name="%s" value="%s">',
                Csrf::FIELD,
                htmlspecialchars($this->csrf->token($request->session), ENT_QUOTES, 'UTF-8'),
            ),
            'partial' => fn (string $name, array $partialData = []): string => $this->partial($name, $partialData + $data, $request),
        ];

        return (static function () use ($file, $data, $helpers): string {
            extract($helpers, EXTR_SKIP);
            extract($data, EXTR_SKIP);
            ob_start();
            require $file;

            return (string) ob_get_clean();
        })();
    }
}
