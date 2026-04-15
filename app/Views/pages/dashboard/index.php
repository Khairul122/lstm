<?php

declare(strict_types=1);

$sidebarPath = __DIR__ . '/../../includes/dashboard-sidebar.php';
$panelTopbarPath = __DIR__ . '/../../includes/panel-topbar.php';
$panelHeadPath = __DIR__ . '/../../includes/panel-head.php';
$panelScriptsPath = __DIR__ . '/../../includes/panel-scripts.php';
$authPopup = flash('auth_popup');
$komoditasTotal = (int) ($komoditasTotal ?? 0);
$stokSummary = $stokSummary ?? ['total_records' => 0, 'latest_date' => '-', 'latest_snapshot' => []];
$preprocessingSummary = $preprocessingSummary ?? ['total_rows' => 0, 'total_commodity' => 0, 'total_missing' => 0, 'total_outlier' => 0, 'total_latih' => 0, 'total_uji' => 0, 'latest_date' => '-'];
$latestBatch = $latestBatch ?? null;
$bestRun = $bestRun ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php require $panelHeadPath; ?>
    <style>
        .dashboard-stack {
            display: grid;
            gap: 24px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .summary-card-panel {
            border-radius: 24px;
            background: #fff;
            border: 1px solid var(--line);
            padding: 28px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.05);
        }

        .summary-card-panel h3 {
            margin-bottom: 10px;
            font-size: 1.08rem;
        }

        .summary-card-panel p {
            color: var(--text-muted);
            line-height: 1.8;
        }

        .summary-list {
            display: grid;
            gap: 14px;
            margin-top: 18px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .summary-item span {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .snapshot-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 18px;
        }

        .snapshot-table th,
        .snapshot-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: middle;
        }

        .snapshot-table th {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            background: var(--surface-soft);
        }

        .snapshot-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .cta-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 22px;
        }

        .ghost-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 16px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.22);
            background: rgba(255,255,255,0.16);
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            backdrop-filter: blur(8px);
        }

        @media (max-width: 1024px) {
            .summary-grid {
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

        <div class="panel-content dashboard-stack">
            <section class="section-head fade-up">
                <div class="section-copy">
                    <h2>Dashboard</h2>
                    <p>Ringkasan data komoditas, stok historis, preprocessing, dan hasil evaluasi model LSTM dalam satu tampilan panel.</p>
                </div>
            </section>

            <div class="stats-grid fade-up" style="animation-delay:0.05s;">
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    </div>
                    <div class="stat-body">
                        <div class="stat-label">Total Komoditas</div>
                        <div class="stat-value"><?= e((string) $komoditasTotal) ?></div>
                        <div class="stat-note">Komoditas aktif di master data</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--teal-soft); border-color:var(--teal-light);">
                        <svg viewBox="0 0 24 24" style="stroke:var(--teal-dark);"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    </div>
                    <div class="stat-body">
                        <div class="stat-label">Stok Historis</div>
                        <div class="stat-value"><?= e((string) $stokSummary['total_records']) ?></div>
                        <div class="stat-note">Update terakhir <?= e((string) $stokSummary['latest_date']) ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--accent-soft); border-color:var(--accent-light);">
                        <svg viewBox="0 0 24 24" style="stroke:var(--accent-main);"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    </div>
                    <div class="stat-body">
                        <div class="stat-label">Preprocessing</div>
                        <div class="stat-value"><?= e((string) $preprocessingSummary['total_rows']) ?></div>
                        <div class="stat-note">Data latih <?= e((string) $preprocessingSummary['total_latih']) ?> / uji <?= e((string) $preprocessingSummary['total_uji']) ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#eef2ff; border-color:#c7d2fe;">
                        <svg viewBox="0 0 24 24" style="stroke:#4f46e5;"><path d="M4 19V5"></path><path d="M9 19V9"></path><path d="M14 19V7"></path><path d="M19 19V11"></path><path d="M3 19H21"></path></svg>
                    </div>
                    <div class="stat-body">
                        <div class="stat-label">Model LSTM</div>
                        <div class="stat-value"><?= e((string) ($latestBatch['batch_code'] ?? '-')) ?></div>
                        <div class="stat-note">Komoditas terbaik <?= e((string) ($bestRun['komoditas'] ?? '-')) ?></div>
                    </div>
                </div>
            </div>

            <section class="panel-grid fade-up" style="animation-delay:0.1s;">
                <article class="panel-card hero-card">
                    <div class="hero-badge">
                        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        LSTM Pangan · Machine Learning
                    </div>
                    <h2>Selamat datang, <?= e($username) ?>!</h2>
                    <p>
                        Anda terhubung ke sistem <strong style="color:rgba(255,255,255,0.9);">Forecasting Stok Pangan</strong> berbasis algoritma LSTM untuk mendukung monitoring, preprocessing data, evaluasi model, dan prediksi stok pangan Kota Lhokseumawe.
                    </p>
                    <div class="cta-row">
                        <a href="<?= e(base_url('/stok-historis')) ?>" class="ghost-btn">Data Stok</a>
                        <a href="<?= e(base_url('/preprocessing')) ?>" class="ghost-btn">Preprocessing</a>
                        <a href="<?= e(base_url('/lstm')) ?>" class="ghost-btn">Training LSTM</a>
                        <a href="<?= e(base_url('/evaluasi')) ?>" class="ghost-btn">Evaluasi</a>
                    </div>
                </article>

                <article class="panel-card info-card">
                    <h2 style="font-size:1.1rem; margin-bottom:4px;">Informasi Sesi</h2>
                    <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:0;">Detail akun yang sedang aktif</p>
                    <div class="meta-list">
                        <div class="meta-item"><span>Username</span><strong><?= e($username) ?></strong></div>
                        <div class="meta-item"><span>Role</span><strong><?= e($role) ?></strong></div>
                        <div class="meta-item"><span>Status</span><strong><span class="chip chip-green"><svg viewBox="0 0 8 8" width="6" height="6"><circle cx="4" cy="4" r="4" fill="currentColor"/></svg>Aktif</span></strong></div>
                        <div class="meta-item"><span>Sistem</span><strong class="chip chip-blue">LSTM v1.0</strong></div>
                    </div>
                </article>
            </section>

            <section class="summary-grid fade-up" style="animation-delay:0.15s;">
                <article class="summary-card-panel">
                    <h3>Ringkasan Stok Historis</h3>
                    <p>Snapshot stok terbaru membantu memantau komoditas dengan jumlah aktual tertinggi pada tanggal pencatatan terakhir.</p>
                    <?php if (!empty($stokSummary['latest_snapshot'])): ?>
                        <table class="snapshot-table">
                            <thead>
                                <tr>
                                    <th>Komoditas</th>
                                    <th>Jumlah</th>
                                    <th>Gudang</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stokSummary['latest_snapshot'] as $snapshot): ?>
                                    <tr>
                                        <td><?= e((string) $snapshot['nama_komoditas']) ?></td>
                                        <td><?= e(number_format((float) $snapshot['jumlah_aktual'], 2, '.', '')) ?> <?= e((string) ($snapshot['satuan'] ?? '')) ?></td>
                                        <td><?= e((string) $snapshot['lokasi_gudang']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="summary-item"><span>Snapshot terbaru</span><strong>Belum tersedia</strong></div>
                    <?php endif; ?>
                </article>

                <article class="summary-card-panel">
                    <h3>Ringkasan Preprocessing & Evaluasi</h3>
                    <p>Panel ini merangkum kesiapan dataset preprocessing dan hasil evaluasi LSTM terbaru secara singkat.</p>
                    <div class="summary-list">
                        <div class="summary-item"><span>Total baris preprocessing</span><strong><?= e((string) $preprocessingSummary['total_rows']) ?></strong></div>
                        <div class="summary-item"><span>Total komoditas preprocessing</span><strong><?= e((string) $preprocessingSummary['total_commodity']) ?></strong></div>
                        <div class="summary-item"><span>Missing Value / Outlier</span><strong><?= e((string) $preprocessingSummary['total_missing']) ?> / <?= e((string) $preprocessingSummary['total_outlier']) ?></strong></div>
                        <div class="summary-item"><span>Data Latih / Uji</span><strong><?= e((string) $preprocessingSummary['total_latih']) ?> / <?= e((string) $preprocessingSummary['total_uji']) ?></strong></div>
                        <div class="summary-item"><span>Batch LSTM terbaru</span><strong><?= e((string) ($latestBatch['batch_code'] ?? '-')) ?></strong></div>
                        <div class="summary-item"><span>Komoditas terbaik</span><strong><?= e((string) ($bestRun['komoditas'] ?? '-')) ?></strong></div>
                    </div>
                </article>
            </section>
        </div>
    </main>
</div>

<?php if (is_array($authPopup)): ?>
    <div class="popup-overlay" id="authPopupOverlay" data-redirect-url="" data-auto-close="true">
        <div class="popup-card popup-card-<?= e((string) ($authPopup['type'] ?? 'neutral')) ?> popup-card-dialog">
            <div class="popup-orb popup-orb-a"></div>
            <div class="popup-orb popup-orb-b"></div>
            <button type="button" class="popup-dismiss" id="authPopupDismiss" aria-label="Tutup popup">×</button>
            <div class="popup-header-row">
                <div class="popup-icon popup-icon-<?= e((string) ($authPopup['type'] ?? 'neutral')) ?>" aria-hidden="true">
                    <?php if (($authPopup['type'] ?? 'neutral') === 'success'): ?>
                        <svg viewBox="0 0 64 64">
                            <circle cx="32" cy="32" r="24"></circle>
                            <path d="M21 33L28 40L43 25"></path>
                        </svg>
                    <?php else: ?>
                        <svg viewBox="0 0 64 64">
                            <circle cx="32" cy="32" r="24"></circle>
                            <path d="M32 20V35"></path>
                            <path d="M32 44H32.01"></path>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="popup-status-stack">
                    <div class="popup-badge popup-badge-<?= e((string) ($authPopup['type'] ?? 'neutral')) ?>">System Notice</div>
                    <div class="popup-progress"><span></span></div>
                </div>
            </div>
            <div class="popup-copy">
                <h2><?= e((string) ($authPopup['title'] ?? 'Informasi')) ?></h2>
                <p><?= e((string) ($authPopup['message'] ?? '')) ?></p>
            </div>
            <div class="popup-actions">
                <button type="button" class="popup-button popup-button-<?= e((string) ($authPopup['type'] ?? 'neutral')) ?>" id="authPopupButton">Lanjut</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    (() => {
        const logoutForm = document.querySelector('.profile-logout-form');

        if (logoutForm) {
            logoutForm.addEventListener('submit', (event) => {
                event.preventDefault();

                if (document.getElementById('logoutPopupOverlay')) {
                    return;
                }

                const overlay = document.createElement('div');
                overlay.id = 'logoutPopupOverlay';
                overlay.className = 'popup-overlay';
                overlay.dataset.autoClose = 'true';
                overlay.innerHTML = `
                    <div class="popup-card popup-card-neutral popup-card-dialog">
                        <div class="popup-orb popup-orb-a"></div>
                        <div class="popup-orb popup-orb-b"></div>
                        <button type="button" class="popup-dismiss" id="logoutPopupDismiss" aria-label="Tutup popup">×</button>
                        <div class="popup-header-row">
                            <div class="popup-icon popup-icon-neutral" aria-hidden="true">
                                <svg viewBox="0 0 64 64">
                                    <circle cx="32" cy="32" r="24"></circle>
                                    <path d="M32 20V35"></path>
                                    <path d="M32 44H32.01"></path>
                                </svg>
                            </div>
                            <div class="popup-status-stack">
                                <div class="popup-badge popup-badge-neutral">Session End</div>
                                <div class="popup-progress"><span></span></div>
                            </div>
                        </div>
                        <div class="popup-copy">
                            <h2>Logout Berhasil</h2>
                            <p>Anda akan keluar dari dashboard dan kembali ke halaman login.</p>
                        </div>
                        <div class="popup-actions">
                            <button type="button" class="popup-button popup-button-neutral" id="logoutPopupButton">Keluar Sekarang</button>
                        </div>
                    </div>
                `;

                document.body.appendChild(overlay);

                const proceedLogout = () => {
                    HTMLFormElement.prototype.submit.call(logoutForm);
                };

                const dismiss = overlay.querySelector('#logoutPopupDismiss');
                const button = overlay.querySelector('#logoutPopupButton');

                if (dismiss) dismiss.addEventListener('click', proceedLogout);
                if (button) button.addEventListener('click', proceedLogout);
                overlay.addEventListener('click', (clickEvent) => {
                    if (clickEvent.target === overlay) {
                        proceedLogout();
                    }
                });

                window.setTimeout(proceedLogout, 1400);
            });
        }
    })();
</script>
<?php require $panelScriptsPath; ?>
</body>
</html>
