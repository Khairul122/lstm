<?php

declare(strict_types=1);

$sidebarPath = __DIR__ . '/../../includes/dashboard-sidebar.php';
$panelTopbarPath = __DIR__ . '/../../includes/panel-topbar.php';
$panelHeadPath = __DIR__ . '/../../includes/panel-head.php';
$panelScriptsPath = __DIR__ . '/../../includes/panel-scripts.php';
$flashPopupPath = __DIR__ . '/../../includes/flash-popup.php';
$run = $run ?? [];
$predictionResult = $predictionResult ?? ['items' => [], 'search' => '', 'currentPage' => 1, 'totalPages' => 1, 'totalItems' => 0, 'perPage' => 15];
$residualResult = $residualResult ?? ['items' => [], 'search' => '', 'currentPage' => 1, 'totalPages' => 1, 'totalItems' => 0, 'perPage' => 15];
$forecastResult = $forecastResult ?? ['items' => [], 'search' => '', 'currentPage' => 1, 'totalPages' => 1, 'totalItems' => 0, 'perPage' => 15];
$predictionSeries = $predictionSeries ?? [];
$residualSeries = $residualSeries ?? [];
$forecastSeries = $forecastSeries ?? [];
$historicalSeries = $historicalSeries ?? [];
$flashPopup = $flashPopup ?? null;

$buildRunUrl = static function (string $kind, int $page, string $search, array $run): string {
    $query = [];
    $query[$kind . '_page'] = $page;
    if ($search !== '') {
        $query[$kind . '_search'] = $search;
    }
    return base_url('/evaluasi/run/' . $run['id'] . '?' . http_build_query($query));
};

$predictionLabels = array_map(static fn(array $row): string => (string) $row['tanggal'], $predictionSeries);
$predictionActual = array_map(static fn(array $row): float => (float) $row['actual_denormalized'], $predictionSeries);
$predictionPred = array_map(static fn(array $row): float => (float) $row['predicted_denormalized'], $predictionSeries);
$residualLabels = array_map(static fn(array $row): string => (string) $row['tanggal'], $residualSeries);
$residualValues = array_map(static fn(array $row): float => (float) $row['residual'], $residualSeries);
$forecastLabels = array_map(static fn(array $row): string => (string) $row['tanggal_forecast'], $forecastSeries);
$forecastValues = array_map(static fn(array $row): float => (float) $row['forecast_denormalized'], $forecastSeries);
$historicalLabels = array_map(static fn(array $row): string => (string) $row['format_waktu'], $historicalSeries);
$historicalValues = array_map(static fn(array $row): float => (float) $row['stok_bersih'], $historicalSeries);
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
                        <h2>Model LSTM - <?= e((string) $run['komoditas']) ?></h2>
                        <p>Halaman ini menampilkan konstruksi model, evaluasi metrik, visualisasi aktual vs prediksi, residual analysis, dan prediksi stok pangan 1 tahun ke depan.</p>
                        <a class="section-link" href="<?= e(base_url('/evaluasi/batch/' . $run['batch_id'])) ?>">Kembali ke Detail Batch</a>
                    </div>
                    <div class="metric-box">
                        <span>Status Run</span>
                        <strong><?= e((string) $run['status']) ?></strong>
                    </div>
                </div>
            </section>

            <section class="glass-card">
                <div class="stats-grid">
                    <div class="stat-card"><span>RMSE</span><strong><?= e($run['rmse'] !== null ? number_format((float) $run['rmse'], 2, '.', '') : '-') ?></strong></div>
                    <div class="stat-card"><span>MAE</span><strong><?= e($run['mae'] !== null ? number_format((float) $run['mae'], 2, '.', '') : '-') ?></strong></div>
                    <div class="stat-card"><span>MAPE</span><strong><?= e($run['mape'] !== null ? number_format((float) $run['mape'], 2, '.', '') . '%' : '-') ?></strong></div>
                    <div class="stat-card"><span>Best Epoch</span><strong><?= e((string) ($run['best_epoch'] ?? '-')) ?></strong></div>
                </div>
            </section>

            <section class="glass-card">
                <div class="section-copy" style="margin-bottom:16px;"><h2>Export Hasil Evaluasi Run</h2><p>Unduh data evaluasi rinci untuk komoditas ini dalam format PDF, Excel, atau CSV.</p></div>
                <div class="export-grid">
                    <div class="export-box">
                        <h3>Prediksi</h3>
                        <p>Perbandingan aktual vs prediksi pada data uji.</p>
                        <div class="export-actions">
                            <a class="action-link" href="<?= e(base_url('/evaluasi/run/' . $run['id'] . '/export/prediksi/pdf')) ?>">PDF</a>
                            <a class="action-link" href="<?= e(base_url('/evaluasi/run/' . $run['id'] . '/export/prediksi/excel')) ?>">Excel</a>
                            <a class="action-link" href="<?= e(base_url('/evaluasi/run/' . $run['id'] . '/export/prediksi/csv')) ?>">CSV</a>
                        </div>
                    </div>
                    <div class="export-box">
                        <h3>Residual</h3>
                        <p>Error, absolute error, dan absolute percentage error per tanggal.</p>
                        <div class="export-actions">
                            <a class="action-link" href="<?= e(base_url('/evaluasi/run/' . $run['id'] . '/export/residual/pdf')) ?>">PDF</a>
                            <a class="action-link" href="<?= e(base_url('/evaluasi/run/' . $run['id'] . '/export/residual/excel')) ?>">Excel</a>
                            <a class="action-link" href="<?= e(base_url('/evaluasi/run/' . $run['id'] . '/export/residual/csv')) ?>">CSV</a>
                        </div>
                    </div>
                    <div class="export-box">
                        <h3>Forecast</h3>
                        <p>Prediksi stok 1 tahun ke depan dengan horizon harian.</p>
                        <div class="export-actions">
                            <a class="action-link" href="<?= e(base_url('/evaluasi/run/' . $run['id'] . '/export/forecast/pdf')) ?>">PDF</a>
                            <a class="action-link" href="<?= e(base_url('/evaluasi/run/' . $run['id'] . '/export/forecast/excel')) ?>">Excel</a>
                            <a class="action-link" href="<?= e(base_url('/evaluasi/run/' . $run['id'] . '/export/forecast/csv')) ?>">CSV</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="chart-grid">
                <article class="chart-card"><div class="section-copy"><h2>Aktual vs Prediksi (Data Uji)</h2><p class="muted">Visualisasi nilai aktual dan hasil prediksi model pada data uji.</p></div><canvas id="predictionChart"></canvas></article>
                <article class="chart-card"><div class="section-copy"><h2>Analisis Residual</h2><p class="muted">Error harian model untuk membantu inspeksi bias dan variabilitas prediksi.</p></div><canvas id="residualChart"></canvas></article>
                <article class="chart-card"><div class="section-copy"><h2>Prediksi Stok 1 Tahun ke Depan</h2><p class="muted">Forecast recursive selama 365 hari untuk komoditas terpilih.</p></div><canvas id="forecastChart"></canvas></article>
                <article class="chart-card"><div class="section-copy"><h2>Overview Historis vs Prediksi</h2><p class="muted">Gabungan historis stok bersih terbaru dengan forecast masa depan.</p></div><canvas id="overviewChart"></canvas></article>
            </section>

            <?php
            $tables = [
                'prediction' => ['title' => 'Perbandingan Aktual vs Prediksi', 'result' => $predictionResult, 'columns' => ['Tanggal', 'Actual', 'Predicted', 'Dataset']],
                'residual' => ['title' => 'Residual (Error Analysis)', 'result' => $residualResult, 'columns' => ['Tanggal', 'Residual', 'Abs Error', 'APE']],
                'forecast' => ['title' => 'Forecast 1 Tahun ke Depan', 'result' => $forecastResult, 'columns' => ['Tanggal Forecast', 'Horizon', 'Forecast Norm', 'Forecast Aktual']],
            ];
            ?>

            <?php foreach ($tables as $key => $table): ?>
                <?php $result = $table['result']; ?>
                <section class="table-card">
                    <div class="toolbar">
                        <div class="section-copy"><h2><?= e($table['title']) ?></h2><p class="muted">Tabel interaktif dengan search dan pagination.</p></div>
                        <form class="search-form" action="<?= e(base_url('/evaluasi/run/' . $run['id'])) ?>" method="GET">
                            <div class="search-field"><label for="<?= e($key) ?>_search">Cari data</label><input id="<?= e($key) ?>_search" type="text" name="<?= e($key) ?>_search" value="<?= e((string) $result['search']) ?>" placeholder="Cari berdasarkan tanggal atau atribut"></div>
                            <button class="btn" type="submit">Cari</button>
                            <?php if ((string) $result['search'] !== ''): ?><a class="section-link" href="<?= e(base_url('/evaluasi/run/' . $run['id'])) ?>">Reset</a><?php endif; ?>
                        </form>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <?php foreach ($table['columns'] as $column): ?><th><?= e($column) ?></th><?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($result['items'] !== []): ?>
                                <?php foreach ($result['items'] as $index => $row): ?>
                                    <tr>
                                        <td><?= e((string) ((($result['currentPage'] - 1) * $result['perPage']) + $index + 1)) ?></td>
                                        <?php if ($key === 'prediction'): ?>
                                            <td><?= e((string) $row['tanggal']) ?></td><td><?= e(number_format((float) $row['actual_denormalized'], 2, '.', '')) ?></td><td><?= e(number_format((float) $row['predicted_denormalized'], 2, '.', '')) ?></td><td><?= e((string) $row['dataset_type']) ?></td>
                                        <?php elseif ($key === 'residual'): ?>
                                            <td><?= e((string) $row['tanggal']) ?></td><td><?= e(number_format((float) $row['residual'], 2, '.', '')) ?></td><td><?= e(number_format((float) $row['absolute_error'], 2, '.', '')) ?></td><td><?= e(number_format((float) $row['absolute_percentage_error'], 2, '.', '')) ?>%</td>
                                        <?php else: ?>
                                            <td><?= e((string) $row['tanggal_forecast']) ?></td><td><?= e((string) $row['forecast_horizon_day']) ?></td><td><?= e(number_format((float) $row['forecast_normalized'], 6, '.', '')) ?></td><td><?= e(number_format((float) $row['forecast_denormalized'], 2, '.', '')) ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="empty-state">Belum ada data untuk bagian ini.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($result['totalPages'] > 1): ?>
                        <div class="pagination">
                            <div class="muted">Halaman <?= e((string) $result['currentPage']) ?> dari <?= e((string) $result['totalPages']) ?></div>
                            <div class="pagination-links">
                                <a class="page-link<?= $result['currentPage'] <= 1 ? ' disabled' : '' ?>" href="<?= e($buildRunUrl($key, max(1, $result['currentPage'] - 1), (string) $result['search'], $run)) ?>">Prev</a>
                                <a class="page-link active" href="#"><?= e((string) $result['currentPage']) ?></a>
                                <a class="page-link<?= $result['currentPage'] >= $result['totalPages'] ? ' disabled' : '' ?>" href="<?= e($buildRunUrl($key, min($result['totalPages'], $result['currentPage'] + 1), (string) $result['search'], $run)) ?>">Next</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    </main>
</div>
<?php require $flashPopupPath; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (() => {
        const predictionLabels = <?= json_encode($predictionLabels, JSON_UNESCAPED_UNICODE) ?>;
        const predictionActual = <?= json_encode($predictionActual, JSON_UNESCAPED_UNICODE) ?>;
        const predictionPred = <?= json_encode($predictionPred, JSON_UNESCAPED_UNICODE) ?>;
        const residualLabels = <?= json_encode($residualLabels, JSON_UNESCAPED_UNICODE) ?>;
        const residualValues = <?= json_encode($residualValues, JSON_UNESCAPED_UNICODE) ?>;
        const forecastLabels = <?= json_encode($forecastLabels, JSON_UNESCAPED_UNICODE) ?>;
        const forecastValues = <?= json_encode($forecastValues, JSON_UNESCAPED_UNICODE) ?>;
        const historicalLabels = <?= json_encode($historicalLabels, JSON_UNESCAPED_UNICODE) ?>;
        const historicalValues = <?= json_encode($historicalValues, JSON_UNESCAPED_UNICODE) ?>;

        const createLineChart = (canvasId, labels, datasets) => {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            new Chart(canvas, {
                type: 'line',
                data: { labels, datasets },
                options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { position: 'bottom' } }, scales: { x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } }, y: { beginAtZero: false } } }
            });
        };

        createLineChart('predictionChart', predictionLabels, [
            { label: 'Aktual', data: predictionActual, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.12)', fill: false, tension: .25 },
            { label: 'Prediksi', data: predictionPred, borderColor: '#14b8a6', backgroundColor: 'rgba(20,184,166,.12)', fill: false, tension: .25 }
        ]);

        createLineChart('residualChart', residualLabels, [
            { label: 'Residual', data: residualValues, borderColor: '#f97316', backgroundColor: 'rgba(249,115,22,.12)', fill: true, tension: .25 }
        ]);

        createLineChart('forecastChart', forecastLabels, [
            { label: 'Forecast 1 Tahun', data: forecastValues, borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,.12)', fill: true, tension: .25 }
        ]);

        const overviewLabels = historicalLabels.concat(forecastLabels);
        const overviewActual = historicalValues.concat(new Array(forecastValues.length).fill(null));
        const overviewForecast = new Array(historicalValues.length).fill(null).concat(forecastValues);
        createLineChart('overviewChart', overviewLabels, [
            { label: 'Historis', data: overviewActual, borderColor: '#1d4ed8', backgroundColor: 'rgba(29,78,216,.12)', fill: false, tension: .22 },
            { label: 'Forecast', data: overviewForecast, borderColor: '#059669', backgroundColor: 'rgba(5,150,105,.12)', fill: false, tension: .22 }
        ]);
    })();
</script>
<?php require $panelScriptsPath; ?>
</body>
</html>
