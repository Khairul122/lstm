<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Core\Controller;
use Core\Database;
use Core\Session;
use PDO;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('pages.auth.login', [
            'title' => 'Login',
        ]);
    }

    public function login(): void
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            Session::flash('error', 'Username dan password wajib diisi.');
            set_old_input($_POST);
            $this->redirectBack();
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id_user, username, password, role FROM users WHERE username = :username LIMIT 1');
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string) $user['password'])) {
            Session::flash('error', 'Username atau password salah.');
            set_old_input($_POST);
            $this->redirectBack();
        }

        Session::regenerate();
        Session::set('user_id', (int) $user['id_user']);
        Session::set('username', (string) $user['username']);
        Session::set('role', (string) $user['role']);
        Session::flash('auth_popup', [
            'type' => 'success',
            'title' => 'Login Berhasil',
            'message' => 'Selamat datang, ' . (string) $user['username'] . '. Anda berhasil masuk ke dashboard.',
        ]);
        clear_old_input();

        if ((string) $user['role'] === 'admin') {
            $this->redirect('/dashboard');
        }

        $this->redirect('/');
    }

    public function logout(): void
    {
        Session::destroy();
        Session::start();
        $this->redirect('/login');
    }
}
