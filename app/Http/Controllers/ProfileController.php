<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Core\Controller;
use Core\Session;

final class ProfileController extends Controller
{
    public function index(): void
    {
        $username = (string) Session::get('username', 'User');
        $role = (string) Session::get('role', '-');
        $userId = (int) Session::get('user_id', 0);

        $this->view('pages.profile.index', [
            'title' => 'Profile',
            'username' => $username,
            'role' => $role,
            'userId' => $userId,
            'activeNav' => 'profile',
            'memberSince' => date('Y-m-d'),
            'accountStatus' => 'Aktif',
        ]);
    }
}
