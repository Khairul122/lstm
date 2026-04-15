<?php

declare(strict_types=1);

$sidebarPath = __DIR__ . '/../../includes/dashboard-sidebar.php';
$panelTopbarPath = __DIR__ . '/../../includes/panel-topbar.php';
$panelHeadPath = __DIR__ . '/../../includes/panel-head.php';
$panelScriptsPath = __DIR__ . '/../../includes/panel-scripts.php';

$userId = (int) ($userId ?? 0);
$memberSince = (string) ($memberSince ?? '-');
$accountStatus = (string) ($accountStatus ?? 'Aktif');
$initial = strtoupper(substr((string) $username, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php require $panelHeadPath; ?>
    <style>
        .profile-stack {
            display: grid;
            gap: 24px;
        }

        .profile-hero {
            border-radius: 24px;
            background: #fff;
            border: 1px solid var(--line);
            padding: 32px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.05);
        }

        .profile-hero-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 24px;
            align-items: center;
        }

        .profile-avatar-panel {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 18px 20px;
            border-radius: 22px;
            background: linear-gradient(180deg, #f8fafc, #eef6ff);
            border: 1px solid #dbe7f7;
        }

        .profile-avatar-large {
            width: 78px;
            height: 78px;
            border-radius: 24px;
            display: grid;
            place-items: center;
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            border: 1px solid #93c5fd;
            color: #1d4ed8;
        }

        .profile-meta-hero strong {
            display: block;
            font-size: 1.18rem;
            margin-bottom: 6px;
        }

        .profile-meta-hero span {
            color: var(--text-muted);
        }

        .profile-card {
            border-radius: 24px;
            background: #fff;
            border: 1px solid var(--line);
            padding: 32px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.05);
        }

        .profile-card-full {
            width: 100%;
        }

        .profile-card h3 {
            margin-bottom: 10px;
            font-size: 1.05rem;
        }

        .profile-card p {
            color: var(--text-muted);
            line-height: 1.8;
        }

        .profile-info-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-top: 22px;
        }

        .profile-info-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            padding: 18px 20px;
            border-radius: 18px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .profile-info-item span {
            color: var(--text-muted);
        }

        .profile-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            background: #dcfce7;
            color: #166534;
        }

        @media (max-width: 1024px) {
            .profile-hero-grid,
            .profile-info-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="panel-shell">
    <?php require $sidebarPath; ?>

    <main class="main">
        <?php require $panelTopbarPath; ?>

        <div class="panel-content profile-stack">
            <section class="profile-hero">
                <div class="profile-hero-grid">
                    <div>
                        <h2>Profile Pengguna</h2>
                        <p>Halaman ini menampilkan ringkasan identitas akun yang sedang aktif pada sistem forecasting stok pangan.</p>
                    </div>
                    <div class="profile-avatar-panel">
                        <div class="profile-avatar-large"><?= e($initial) ?></div>
                        <div class="profile-meta-hero">
                            <strong><?= e((string) $username) ?></strong>
                            <span><?= e((string) $role) ?></span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="profile-card profile-card-full">
                <h3>Informasi Akun</h3>
                <p>Data ini bersifat read-only dan merepresentasikan sesi login yang sedang aktif.</p>
                <div class="profile-info-list">
                    <div class="profile-info-item"><span>ID Pengguna</span><strong><?= e((string) $userId) ?></strong></div>
                    <div class="profile-info-item"><span>Username</span><strong><?= e((string) $username) ?></strong></div>
                    <div class="profile-info-item"><span>Role</span><strong><?= e((string) $role) ?></strong></div>
                    <div class="profile-info-item"><span>Status Akun</span><strong><span class="profile-chip">● <?= e($accountStatus) ?></span></strong></div>
                    <div class="profile-info-item"><span>Tercatat Pada Sistem</span><strong><?= e($memberSince) ?></strong></div>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require $panelScriptsPath; ?>
</body>
</html>
