<?php

declare(strict_types=1);

$sidebarPath = __DIR__ . '/../../includes/dashboard-sidebar.php';
$panelTopbarPath = __DIR__ . '/../../includes/panel-topbar.php';
$panelHeadPath = __DIR__ . '/../../includes/panel-head.php';
$panelScriptsPath = __DIR__ . '/../../includes/panel-scripts.php';
$flashPopupPath = __DIR__ . '/../../includes/flash-popup.php';
$form = $form ?? [];
$latestBatch = $latestBatch ?? null;
$bestRun = $bestRun ?? null;
$flashPopup = $flashPopup ?? null;
$stats = $stats ?? ['batch_count' => 0, 'completed_count' => 0, 'running_count' => 0, 'failed_count' => 0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php require $panelHeadPath; ?>
</head>
<body>
<div class="panel-shell">
    <?php require $sidebarPath; ?>
    <main class="main">
        <?php require $panelTopbarPath; ?>
        <div class="panel-content page-stack">
            <section class="hero-panel">
                <div class="hero-badge">TensorFlow / Keras</div>
                <div class="hero-grid">
                    <div>
                        <h2>Training Batch LSTM Semua Komoditas</h2>
                        <p>Halaman ini fokus pada konstruksi model dan pelatihan batch. Evaluasi hasil dipisahkan ke halaman `Evaluasi` agar alur kerja lebih rapi dan mudah dibaca.</p>
                    </div>
                    <div class="metric-grid">
                        <div class="metric-box"><span>Batch Tersimpan</span><strong><?= e((string) $stats['batch_count']) ?></strong></div>
                        <div class="metric-box"><span>Batch Terbaru</span><strong><?= e((string) ($latestBatch['batch_code'] ?? '-')) ?></strong></div>
                        <div class="metric-box"><span>Model Terbaik</span><strong><?= e((string) ($bestRun['komoditas'] ?? '-')) ?></strong></div>
                    </div>
                </div>
            </section>

            <section class="glass-card">
                <div class="section-copy">
                    <h2>Konfigurasi Model LSTM</h2>
                    <p>Semua komoditas akan dilatih dalam satu batch run dengan parameter global berikut.</p>
                </div>
                <form id="lstmTrainingForm" action="<?= e(base_url('/lstm/train')) ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <div class="field"><label for="sequence_length">Sequence Length</label><input id="sequence_length" type="number" min="1" max="60" name="sequence_length" value="<?= e((string) $form['sequence_length']) ?>"></div>
                        <div class="field"><label for="train_ratio">Train Ratio</label><input id="train_ratio" type="number" min="0.50" max="0.95" step="0.01" name="train_ratio" value="<?= e((string) $form['train_ratio']) ?>"></div>
                        <div class="field"><label for="epochs">Epochs</label><input id="epochs" type="number" min="1" max="300" name="epochs" value="<?= e((string) $form['epochs']) ?>"></div>
                        <div class="field"><label for="batch_size">Batch Size</label><input id="batch_size" type="number" min="1" max="256" name="batch_size" value="<?= e((string) $form['batch_size']) ?>"></div>
                        <div class="field"><label for="lstm_units">LSTM Units</label><input id="lstm_units" type="number" min="4" max="256" name="lstm_units" value="<?= e((string) $form['lstm_units']) ?>"></div>
                        <div class="field"><label for="dropout_rate">Dropout Rate</label><input id="dropout_rate" type="number" min="0" max="0.8" step="0.01" name="dropout_rate" value="<?= e((string) $form['dropout_rate']) ?>"></div>
                        <div class="field"><label for="learning_rate">Learning Rate</label><input id="learning_rate" type="number" min="0.0001" max="1" step="0.0001" name="learning_rate" value="<?= e((string) $form['learning_rate']) ?>"></div>
                        <div class="field"><label for="optimizer">Optimizer</label><select id="optimizer" name="optimizer"><option value="adam" <?= $form['optimizer'] === 'adam' ? 'selected' : '' ?>>Adam</option><option value="rmsprop" <?= $form['optimizer'] === 'rmsprop' ? 'selected' : '' ?>>RMSprop</option></select></div>
                    </div>
                    <div class="actions">
                        <button class="btn" type="submit">Latih Semua Komoditas</button>
                        <a class="ghost-link" href="<?= e(base_url('/evaluasi')) ?>">Buka Halaman Evaluasi</a>
                    </div>
                </form>
            </section>

            <section class="glass-card">
                <div class="table-kpis">
                    <div class="kpi-box"><span>Batch Berhasil</span><strong><?= e((string) $stats['completed_count']) ?></strong></div>
                    <div class="kpi-box"><span>Batch Running</span><strong><?= e((string) $stats['running_count']) ?></strong></div>
                    <div class="kpi-box"><span>Batch Gagal</span><strong><?= e((string) $stats['failed_count']) ?></strong></div>
                    <div class="kpi-box"><span>Batch Terakhir</span><strong><?= e((string) ($latestBatch['batch_code'] ?? '-')) ?></strong></div>
                </div>
            </section>

            <section class="glass-card">
                <div class="section-copy">
                    <h2>Kelola Data Model LSTM</h2>
                    <p>Jika ingin memulai ulang dari nol, gunakan reset semua. Proses ini akan menghapus seluruh batch, metrics, prediksi, residual, forecast, dan file model yang tersimpan.</p>
                </div>
                <div class="cta-row">
                    <?php if ($latestBatch !== null): ?>
                        <a class="ghost-link" href="<?= e(base_url('/evaluasi/batch/' . $latestBatch['id'])) ?>">Lihat Batch Terakhir</a>
                    <?php endif; ?>
                    <form
                        action="<?= e(base_url('/lstm/reset-all')) ?>"
                        method="POST"
                        data-confirm-dialog
                        data-confirm-title="Reset Semua Data LSTM"
                        data-confirm-message="Semua batch, metrics, prediksi, residual, forecast, dan file model LSTM akan dihapus permanen. Lanjutkan reset penuh?"
                        data-confirm-badge="Danger Zone"
                        data-confirm-action-label="Ya, Reset Semua"
                        data-confirm-cancel-label="Batalkan"
                        data-confirm-type="warning"
                    >
                        <?= csrf_field() ?>
                        <button class="danger-outline" type="submit">Reset Semua</button>
                    </form>
                </div>
            </section>
        </div>
    </main>
</div>

<div class="loading-overlay" id="loadingOverlay" aria-hidden="true">
    <div class="loading-card">
        <div class="loading-spinner"></div>
        <h2>Batch training sedang berjalan</h2>
        <p>Model LSTM sedang dibangun untuk seluruh komoditas. Mohon tunggu hingga evaluasi, residual, dan prediksi 1 tahun ke depan selesai disimpan.</p>
        <div class="loading-steps">
            <div class="loading-step">Memuat dataset preprocessing seluruh komoditas.</div>
            <div class="loading-step">Membangun model LSTM dan melatih setiap komoditas.</div>
            <div class="loading-step">Menghitung RMSE, MAE, MAPE, lalu menyimpan prediksi dan forecast.</div>
        </div>
    </div>
</div>

<?php require $flashPopupPath; ?>
<script>
    (() => {
        const form = document.getElementById('lstmTrainingForm');
        const overlay = document.getElementById('loadingOverlay');

        if (form && overlay) {
            form.addEventListener('submit', () => {
                overlay.classList.add('is-visible');
                overlay.setAttribute('aria-hidden', 'false');
            });
        }
    })();
</script>
<?php require $panelScriptsPath; ?>
</body>
</html>
