<?php

declare(strict_types=1);

namespace VampirKoylu\Controllers;

use VampirKoylu\Exceptions\RuleViolationException;
use VampirKoylu\Http\Request;
use VampirKoylu\Http\Response;
use VampirKoylu\Http\View;

abstract class Controller
{
    public function __construct(protected readonly View $view)
    {
    }

    protected function render(Request $request, string $template, array $data = [], int $status = 200): Response
    {
        return Response::html($this->view->render($template, $data, $request), $status);
    }

    protected function redirect(Request $request, string $path): Response
    {
        return Response::redirect($request->url($path));
    }

    protected function attempt(Request $request, string $path, string $successMessage, callable $action): Response
    {
        try {
            $action();
            if ($successMessage !== '') {
                $request->session->flash('success', $successMessage);
            }
        } catch (RuleViolationException $error) {
            $request->session->flash('error', $error->getMessage());
        }

        return $this->redirect($request, $path);
    }
}
