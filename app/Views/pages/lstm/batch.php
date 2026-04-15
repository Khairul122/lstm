<?php

declare(strict_types=1);

$sidebarPath = __DIR__ . '/../../includes/dashboard-sidebar.php';
$panelTopbarPath = __DIR__ . '/../../includes/panel-topbar.php';
$panelHeadPath = __DIR__ . '/../../includes/panel-head.php';
$panelScriptsPath = __DIR__ . '/../../includes/panel-scripts.php';
$flashPopupPath = __DIR__ . '/../../includes/flash-popup.php';
$search = (string) ($search ?? '');
$currentPage = (int) ($currentPage ?? 1);
$totalPages = (int) ($totalPages ?? 1);
$totalItems = (int) ($totalItems ?? 0);
$perPage = (int) ($perPage ?? 10);
$items = $items ?? [];
$recap = $recap ?? [];
$batch = $batch ?? [];
$bestRun = $bestRun ?? null;
$flashPopup = $flashPopup ?? null;

$recapLabels = array_map(static fn(array $row): string => (string) $row['komoditas'], $recap);
$recapRmse = array_map(static fn(array $row): float => (float) ($row['rmse'] ?? 0), $recap);
$recapMae = array_map(static fn(array $row): float => (float) ($row['mae'] ?? 0), $recap);
$recapMape = array_map(static fn(array $row): float => (float) ($row['mape'] ?? 0), $recap);
$recapTrainLoss = array_map(static fn(array $row): float => (float) ($row['train_loss_final'] ?? 0), $recap);
$recapValLoss = array_map(static fn(array $row): float => (float) ($row['val_loss_final'] ?? 0), $recap);

$buildPageUrl = static function (int $page) use ($search, $batch): string {
    $query = ['page' => $page];
    if ($search !== '') {
        $query['search'] = $search;
    }
    return base_url('/lstm/batch/' . $batch['id'] . '?' . http_build_query($query));
};

$pageNumbers = [];
for ($page = 1; $page <= $totalPages; $page++) {
    if ($page === 1 || $page === $totalPages || abs($page - $currentPage) <= 1) {
        $pageNumbers[] = $page;
    }
}
$pageNumbers = array_values(array_unique($pageNumbers));
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
                <div class="hero-grid">
                    <div>
                        <h2>Detail Batch <?= e((string) $batch['batch_code']) ?></h2>
                        <p><?= e((string) ($batch['notes'] ?? 'Batch training LSTM semua komoditas.')) ?></p>
                        <a class="badge-link" href="<?= e(base_url('/evaluasi')) ?>">Kembali ke daftar batch</a>
                    </div>
                    <div class="metric-grid">
                        <div class="metric-box"><span>Status</span><strong><?= e((string) $batch['status']) ?></strong></div>
                        <div class="metric-box"><span>Komoditas Selesai</span><strong><?= e((string) $batch['completed_komoditas']) ?>/<?= e((string) $batch['total_komoditas']) ?></strong></div>
                        <div class="metric-box"><span>Komoditas Gagal</span><strong><?= e((string) $batch['failed_komoditas']) ?></strong></div>
                        <div class="metric-box"><span>Durasi</span><strong><?= e((string) (($batch['duration_seconds'] ?? 0) . ' dtk')) ?></strong></div>
                    </div>
                </div>
            </section>

            <section class="glass-card">
                <div class="recap-grid">
                    <div class="recap-box"><span>RMSE Terbaik</span><strong><?= e($bestRun !== null && $bestRun['rmse'] !== null ? number_format((float) $bestRun['rmse'], 2, '.', '') : '-') ?></strong></div>
                    <div class="recap-box"><span>MAE Terbaik</span><strong><?= e($bestRun !== null && $bestRun['mae'] !== null ? number_format((float) $bestRun['mae'], 2, '.', '') : '-') ?></strong></div>
                    <div class="recap-box"><span>MAPE Terbaik</span><strong><?= e($bestRun !== null && $bestRun['mape'] !== null ? number_format((float) $bestRun['mape'], 2, '.', '') . '%' : '-') ?></strong></div>
                    <div class="recap-box"><span>Komoditas Terbaik</span><strong><?= e((string) ($bestRun['komoditas'] ?? '-')) ?></strong></div>
                </div>
            </section>

            <section class="glass-card">
                <div class="section-copy" style="margin-bottom:16px;"><h2>Export Evaluasi Batch</h2><p>Unduh hasil evaluasi batch ini dalam format PDF, Excel, atau CSV sesuai kebutuhan laporan dan analisis lanjutan.</p></div>
                <div class="export-grid">
                    <div class="export-box">
                        <h3>Rekap Batch</h3>
                        <p>Metadata batch, konfigurasi model, waktu proses, dan status keseluruhan.</p>
                        <div class="export-actions">
                            <a class="action-link" href="<?= e(base_url('/evaluasi/batch/' . $batch['id'] . '/export/batch-summary/pdf')) ?>">PDF</a>
                            <a class="action-link" href="<?= e(base_url('/evaluasi/batch/' . $batch['id'] . '/export/batch-summary/excel')) ?>">Excel</a>
                            <a class="action-link" href="<?= e(base_url('/evaluasi/batch/' . $batch['id'] . '/export/batch-summary/csv')) ?>">CSV</a>
                        </div>
                    </div>
                    <div class="export-box">
                        <h3>Batch Lengkap</h3>
                        <p>Ringkasan batch plus jumlah data prediksi, residual, forecast, dan artefak model per komoditas.</p>
                        <div class="export-actions">
                            <a class="action-link" href="<?= e(base_url('/evaluasi/batch/' . $batch['id'] . '/export/batch-lengkap/pdf')) ?>">PDF</a>
                            <a class="action-link" href="<?= e(base_url('/evaluasi/batch/' . $batch['id'] . '/export/batch-lengkap/excel')) ?>">Excel</a>
                            <a class="action-link" href="<?= e(base_url('/evaluasi/batch/' . $batch['id'] . '/export/batch-lengkap/csv')) ?>">CSV</a>
                        </div>
                    </div>
                    <div class="export-box">
                        <h3>Rekap Komoditas</h3>
                        <p>Metrik evaluasi per komoditas: RMSE, MAE, MAPE, train loss, val loss, dan best epoch.</p>
                        <div class="export-actions">
                            <a class="action-link" href="<?= e(base_url('/evaluasi/batch/' . $batch['id'] . '/export/rekap-komoditas/pdf')) ?>">PDF</a>
                            <a class="action-link" href="<?= e(base_url('/evaluasi/batch/' . $batch['id'] . '/export/rekap-komoditas/excel')) ?>">Excel</a>
                            <a class="action-link" href="<?= e(base_url('/evaluasi/batch/' . $batch['id'] . '/export/rekap-komoditas/csv')) ?>">CSV</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="table-card">
                <div class="toolbar">
                    <form class="search-form" action="<?= e(base_url('/lstm/batch/' . $batch['id'])) ?>" method="GET">
                        <div class="search-field"><label for="search">Cari run komoditas</label><input id="search" type="text" name="search" value="<?= e($search) ?>" placeholder="Komoditas atau status"></div>
                        <button class="btn" type="submit">Cari</button>
                        <?php if ($search !== ''): ?><a class="badge-link" href="<?= e(base_url('/lstm/batch/' . $batch['id'])) ?>">Reset</a><?php endif; ?>
                    </form>
                    <div class="toolbar-meta">Menampilkan <?= e((string) count($items)) ?> dari <?= e((string) $totalItems) ?> run komoditas.</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>No</th><th>Komoditas</th><th>Status</th><th>Train/Test</th><th>RMSE</th><th>MAE</th><th>MAPE</th><th>Aksi</th></tr></thead>
                        <tbody>
                        <?php if ($items !== []): ?>
                            <?php foreach ($items as $index => $item): ?>
                                <?php $statusClass = $item['status'] === 'completed' ? 'status-completed' : ($item['status'] === 'running' ? 'status-running' : ($item['status'] === 'failed' ? 'status-failed' : 'status-neutral')); ?>
                                <tr>
                                    <td><?= e((string) ((($currentPage - 1) * $perPage) + $index + 1)) ?></td>
                                    <td><?= e((string) $item['komoditas']) ?></td>
                                    <td><span class="status-pill <?= e($statusClass) ?>"><?= e((string) $item['status']) ?></span></td>
                                    <td><?= e((string) $item['train_samples']) ?>/<?= e((string) $item['test_samples']) ?></td>
                                    <td><?= e($item['rmse'] !== null ? number_format((float) $item['rmse'], 2, '.', '') : '-') ?></td>
                                    <td><?= e($item['mae'] !== null ? number_format((float) $item['mae'], 2, '.', '') : '-') ?></td>
                                    <td><?= e($item['mape'] !== null ? number_format((float) $item['mape'], 2, '.', '') . '%' : '-') ?></td>
                                    <td><a class="action-link" href="<?= e(base_url('/evaluasi/run/' . $item['id'])) ?>">Detail Model</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="empty-state">Belum ada run komoditas untuk batch ini.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <div class="toolbar-meta">Halaman <?= e((string) $currentPage) ?> dari <?= e((string) $totalPages) ?></div>
                        <div class="pagination-links">
                            <a class="page-link<?= $currentPage <= 1 ? ' disabled' : '' ?>" href="<?= e($buildPageUrl(max(1, $currentPage - 1))) ?>">Prev</a>
                            <?php foreach ($pageNumbers as $index => $page): ?>
                                <?php if ($index > 0 && $pageNumbers[$index - 1] + 1 !== $page): ?><span class="page-link disabled">...</span><?php endif; ?>
                                <a class="page-link<?= $page === $currentPage ? ' active' : '' ?>" href="<?= e($buildPageUrl($page)) ?>"><?= e((string) $page) ?></a>
                            <?php endforeach; ?>
                            <a class="page-link<?= $currentPage >= $totalPages ? ' disabled' : '' ?>" href="<?= e($buildPageUrl(min($totalPages, $currentPage + 1))) ?>">Next</a>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <section class="table-card recap-table-card">
                <div class="section-copy" style="margin-bottom:18px;"><h2>Rekap Evaluasi Seluruh Komoditas</h2><p>Ringkasan metrik batch untuk membandingkan hasil training masing-masing komoditas.</p></div>
                <div class="table-wrap">
                    <table class="recap-table">
                        <thead><tr><th>Komoditas</th><th>Status</th><th>RMSE</th><th>MAE</th><th>MAPE</th><th>Train Loss</th><th>Val Loss</th><th>Best Epoch</th></tr></thead>
                        <tbody>
                        <?php if ($recap !== []): ?>
                            <?php foreach ($recap as $row): ?>
                                <?php $statusClass = $row['status'] === 'completed' ? 'status-completed' : ($row['status'] === 'running' ? 'status-running' : ($row['status'] === 'failed' ? 'status-failed' : 'status-neutral')); ?>
                                <tr>
                                    <td class="cell-emphasis"><?= e((string) $row['komoditas']) ?></td>
                                    <td><span class="status-pill <?= e($statusClass) ?>"><?= e((string) $row['status']) ?></span></td>
                                    <td class="number-cell"><?= e($row['rmse'] !== null ? number_format((float) $row['rmse'], 2, '.', '') : '-') ?></td>
                                    <td class="number-cell"><?= e($row['mae'] !== null ? number_format((float) $row['mae'], 2, '.', '') : '-') ?></td>
                                    <td class="number-cell"><?= e($row['mape'] !== null ? number_format((float) $row['mape'], 2, '.', '') . '%' : '-') ?></td>
                                    <td class="number-cell"><?= e($row['train_loss_final'] !== null ? number_format((float) $row['train_loss_final'], 4, '.', '') : '-') ?></td>
                                    <td class="number-cell"><?= e($row['val_loss_final'] !== null ? number_format((float) $row['val_loss_final'], 4, '.', '') : '-') ?></td>
                                    <td class="number-cell"><?= e((string) ($row['best_epoch'] ?? '-')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="empty-state">Belum ada metrik evaluasi yang tersedia.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="chart-grid">
                <article class="chart-card">
                    <div class="section-copy" style="margin-bottom: 16px;"><h2>Perbandingan RMSE, MAE, dan MAPE</h2><p>Grafik evaluasi keseluruhan komoditas pada batch ini.</p></div>
                    <canvas id="metricsChart"></canvas>
                </article>
                <article class="chart-card">
                    <div class="section-copy" style="margin-bottom: 16px;"><h2>Perbandingan Train Loss dan Val Loss</h2><p>Grafik kestabilan pelatihan model untuk semua komoditas pada batch ini.</p></div>
                    <canvas id="lossChart"></canvas>
                </article>
            </section>
        </div>
    </main>
</div>
<?php require $flashPopupPath; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (() => {
        const labels = <?= json_encode($recapLabels, JSON_UNESCAPED_UNICODE) ?>;
        const rmseData = <?= json_encode($recapRmse, JSON_UNESCAPED_UNICODE) ?>;
        const maeData = <?= json_encode($recapMae, JSON_UNESCAPED_UNICODE) ?>;
        const mapeData = <?= json_encode($recapMape, JSON_UNESCAPED_UNICODE) ?>;
        const trainLossData = <?= json_encode($recapTrainLoss, JSON_UNESCAPED_UNICODE) ?>;
        const valLossData = <?= json_encode($recapValLoss, JSON_UNESCAPED_UNICODE) ?>;

        const createBarChart = (canvasId, datasets) => {
            const canvas = document.getElementById(canvasId);
            if (!canvas || labels.length === 0) return;

            new Chart(canvas, {
                type: 'bar',
                data: { labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom' } },
                    scales: {
                        x: { ticks: { autoSkip: false } },
                        y: { beginAtZero: true }
                    }
                }
            });
        };

        createBarChart('metricsChart', [
            { label: 'RMSE', data: rmseData, backgroundColor: 'rgba(37, 99, 235, 0.78)', borderRadius: 8 },
            { label: 'MAE', data: maeData, backgroundColor: 'rgba(20, 184, 166, 0.78)', borderRadius: 8 },
            { label: 'MAPE', data: mapeData, backgroundColor: 'rgba(249, 115, 22, 0.78)', borderRadius: 8 }
        ]);

        createBarChart('lossChart', [
            { label: 'Train Loss', data: trainLossData, backgroundColor: 'rgba(124, 58, 237, 0.78)', borderRadius: 8 },
            { label: 'Val Loss', data: valLossData, backgroundColor: 'rgba(236, 72, 153, 0.78)', borderRadius: 8 }
        ]);
    })();
</script>
<?php require $panelScriptsPath; ?>
</body>
</html>
