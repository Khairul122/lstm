<?php

declare(strict_types=1);

$error = flash('error');
$success = flash('success');
$dialogMessage = $error ?: $success;
$dialogType = $error ? 'error' : 'success';
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Masuk ke Panel Sistem Forecasting Stok Pangan Berbasis LSTM — Dinas Pangan Kota Lhokseumawe">
    <title><?= e($title ?? 'Login') ?> - <?= e((string) app_config('name', 'Aplikasi')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(base_url('/public/css/auth.css')) ?>?v=<?= time() ?>">
</head>
<body class="auth-body">
<main class="auth-shell">
    <section class="auth-side-form" data-animate="fade-up">
        <div class="login-card">
            <header class="login-head" style="text-align: center; margin-bottom: 28px;">
                <div style="width: 64px; height: 64px; background: hsla(220, 63%, 50%, 0.1); border-radius: 18px; margin: 0 auto 18px; display: grid; place-items: center;">
                    <img src="<?= e(base_url('/public/images/logo/logo.png')) ?>" alt="Logo" style="width: 40px; height: 40px; object-fit: contain;">
                </div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary); margin: 0 0 6px;">LSTM Pangan</h1>
                <h2 style="font-size: 0.95rem; font-weight: 600; color: var(--text-secondary); margin: 0;">Dinas Pangan Kota Lhokseumawe</h2>
                <p style="margin-top: 14px; font-size: 0.95rem;">Masukkan kredensial Anda untuk mengakses panel sistem.</p>
            </header>

            <form action="<?= e(base_url('/login')) ?>" method="POST" class="login-form" id="loginForm" novalidate>
                <?= csrf_field() ?>

                <label class="input-group">
                    <span>Username</span>
                    <input
                        type="text"
                        name="username"
                        value="<?= e(old('username')) ?>"
                        placeholder="contoh: admin"
                        autocomplete="username"
                        required
                    >
                </label>

                <label class="input-group">
                    <span>Password</span>
                    <div class="password-wrap">
                        <input
                            type="password"
                            name="password"
                            id="passwordField"
                            placeholder="Minimal 8 karakter"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="toggle-password" id="togglePassword" aria-label="Tampilkan password">
                            Show
                        </button>
                    </div>
                </label>

                <button type="submit" class="btn-login" id="loginSubmit">Masuk</button>
            </form>
        </div>
    </section>
</main>

<?php if ($dialogMessage): ?>
    <div class="dialog-overlay" id="authDialog" role="dialog" aria-modal="true" aria-labelledby="dialogTitle" aria-describedby="dialogDescription">
        <div class="dialog-card <?= $dialogType === 'error' ? 'dialog-error' : 'dialog-success' ?>">
            <button type="button" class="dialog-dismiss" id="dialogDismiss" aria-label="Tutup dialog">×</button>
            <div class="dialog-icon-wrap" aria-hidden="true">
                <?php if ($dialogType === 'error'): ?>
                    <svg class="dialog-icon" viewBox="0 0 64 64" fill="none">
                        <circle cx="32" cy="32" r="30" class="dialog-icon-bg"></circle>
                        <path d="M23 23L41 41M41 23L23 41" class="dialog-icon-stroke"></path>
                    </svg>
                <?php else: ?>
                    <svg class="dialog-icon" viewBox="0 0 64 64" fill="none">
                        <circle cx="32" cy="32" r="30" class="dialog-icon-bg"></circle>
                        <path d="M20 33L28 41L45 24" class="dialog-icon-stroke"></path>
                    </svg>
                <?php endif; ?>
            </div>
            <div class="dialog-badge"><?= $dialogType === 'error' ? 'Authentication Alert' : 'Session Active' ?></div>
            <h3 id="dialogTitle"><?= $dialogType === 'error' ? 'Login Gagal' : 'Login Berhasil' ?></h3>
            <p id="dialogDescription"><?= e((string) $dialogMessage) ?></p>
            <div class="dialog-actions">
                <button type="button" class="dialog-close" id="dialogClose">
                    <?= $dialogType === 'error' ? 'Coba Lagi' : 'Lanjut' ?>
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="<?= e(base_url('/public/js/auth.js')) ?>" defer></script>
</body>
</html>
