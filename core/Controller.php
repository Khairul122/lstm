<?php

declare(strict_types=1);

namespace Core;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = __DIR__ . '/../app/Views/' . str_replace('.', '/', $view) . '.php';

        if (!is_file($viewFile)) {
            http_response_code(404);
            exit('View not found.');
        }

        require $viewFile;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . \base_url($path));
        exit;
    }

    protected function redirectBack(): void
    {
        $to = $_SERVER['HTTP_REFERER'] ?? \base_url('/login');
        header('Location: ' . $to);
        exit;
    }
}
