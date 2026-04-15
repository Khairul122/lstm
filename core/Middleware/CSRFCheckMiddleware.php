<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\CSRF;

final class CSRFCheckMiddleware
{
    public function handle(): void
    {
        $token = $_POST['_token'] ?? null;
        if (!CSRF::validate(is_string($token) ? $token : null)) {
            http_response_code(419);
            exit('CSRF token tidak valid.');
        }
    }
}
