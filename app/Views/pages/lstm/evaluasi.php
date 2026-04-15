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
$latestBatch = $latestBatch ?? null;
$bestRun = $bestRun ?? null;
$flashPopup = $flashPopup ?? null;
$stats = $stats ?? ['batch_count' => 0, 'completed_count' => 0, 'running_count' => 0, 'failed_count' => 0];

$buildPageUrl = static function (int $page) use ($search): string {
    $query = ['page' => $page];
    if ($search !== '') {
        $query['search'] = $search;
    }
    return base_url('/evaluasi?' . http_build_query($query));
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
                <div class="hero-badge">Evaluasi Model</div>
                <div class="hero-grid">
                    <div>
                        <h2>Riwayat Evaluasi Batch LSTM</h2>
                        <p>Halaman ini khusus untuk membaca hasil batch training, membuka detail evaluasi tiap komoditas, memeriksa residual, dan melihat prediksi 1 tahun ke depan.</p>
                    </div>
                    <div class="metric-grid">
                        <div class="metric-box"><span>Batch Tersimpan</span><strong><?= e((string) $stats['batch_count']) ?></strong></div>
                        <div class="metric-box"><span>Batch Terbaik</span><strong><?= e((string) ($bestRun['komoditas'] ?? '-')) ?></strong></div>
                        <div class="metric-box"><span>Batch Terbaru</span><strong><?= e((string) ($latestBatch['batch_code'] ?? '-')) ?></strong></div>
                    </div>
                </div>
            </section>

            <section class="glass-card">
                <div class="recap-grid">
                    <div class="recap-box"><span>Batch Berhasil</span><strong><?= e((string) $stats['completed_count']) ?></strong></div>
                    <div class="recap-box"><span>Batch Running</span><strong><?= e((string) $stats['running_count']) ?></strong></div>
                    <div class="recap-box"><span>Batch Gagal</span><strong><?= e((string) $stats['failed_count']) ?></strong></div>
                    <div class="recap-box"><span>Training Baru</span><strong><a class="ghost-link" href="<?= e(base_url('/lstm')) ?>">Buka LSTM</a></strong></div>
                </div>
            </section>

            <section class="table-card">
                <div class="toolbar">
                    <form class="search-form" action="<?= e(base_url('/evaluasi')) ?>" method="GET">
                        <div class="search-field"><label for="search">Cari batch evaluasi</label><input id="search" type="text" name="search" value="<?= e($search) ?>" placeholder="Kode batch, status, atau catatan"></div>
                        <button class="btn" type="submit">Cari</button>
                        <?php if ($search !== ''): ?><a class="ghost-link" href="<?= e(base_url('/evaluasi')) ?>">Reset</a><?php endif; ?>
                    </form>
                    <div class="toolbar-meta">Menampilkan <?= e((string) count($items)) ?> dari <?= e((string) $totalItems) ?> batch.</div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>No</th><th>Kode Batch</th><th>Status</th><th>Komoditas</th><th>Parameter</th><th>Durasi</th><th>Aksi</th></tr></thead>
                        <tbody>
                        <?php if ($items !== []): ?>
                            <?php foreach ($items as $index => $item): ?>
                                <?php $statusClass = $item['status'] === 'completed' ? 'status-completed' : ($item['status'] === 'running' ? 'status-running' : ($item['status'] === 'failed' ? 'status-failed' : 'status-neutral')); ?>
                                <tr>
                                    <td><?= e((string) ((($currentPage - 1) * $perPage) + $index + 1)) ?></td>
                                    <td><?= e((string) $item['batch_code']) ?></td>
                                    <td><span class="status-pill <?= e($statusClass) ?>"><?= e((string) $item['status']) ?></span></td>
                                    <td><?= e((string) $item['completed_komoditas']) ?>/<?= e((string) $item['total_komoditas']) ?> selesai</td>
                                    <td>Seq <?= e((string) $item['sequence_length']) ?> | Epoch <?= e((string) $item['epochs']) ?> | Unit <?= e((string) $item['lstm_units']) ?></td>
                                    <td><?= e((string) (($item['duration_seconds'] ?? 0) . ' dtk')) ?></td>
                                    <td><a class="action-link" href="<?= e(base_url('/evaluasi/batch/' . $item['id'])) ?>">Detail Batch</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="empty-state">Belum ada hasil evaluasi batch.</td></tr>
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
        </div>
    </main>
</div>
<?php require $flashPopupPath; ?>
<script>
    (() => {
    })();
</script>
<?php require $panelScriptsPath; ?>
</body>
</html>
