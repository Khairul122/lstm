<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Session;

final class GuestMiddleware
{
    public function handle(): void
    {
        if (Session::get('user_id')) {
            header('Location: ' . \base_url('/dashboard'));
            exit;
        }
    }
}
