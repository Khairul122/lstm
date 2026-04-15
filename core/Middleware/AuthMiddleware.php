<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Session;

final class AuthMiddleware
{
    public function handle(): void
    {
        if (!Session::get('user_id')) {
            Session::flash('error', 'Silakan login terlebih dahulu.');
            header('Location: ' . \base_url('/login'));
            exit;
        }
    }
}
