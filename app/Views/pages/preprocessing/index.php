<?php

declare(strict_types=1);

$sidebarPath = __DIR__ . '/../../includes/dashboard-sidebar.php';
$panelTopbarPath = __DIR__ . '/../../includes/panel-topbar.php';
$panelHeadPath = __DIR__ . '/../../includes/panel-head.php';
$panelScriptsPath = __DIR__ . '/../../includes/panel-scripts.php';
$flashDialogPath = __DIR__ . '/../../includes/flash-dialog.php';
$form = $form ?? ['komoditas' => '', 'sequence_length' => '7', 'train_ratio' => '0.8'];
$logs = $logs ?? [];
$runResult = $runResult ?? null;
$summaryRows = $summaryRows ?? [];
$previewRows = $previewRows ?? [];
$tableSearch = (string) ($tableSearch ?? '');
$summaryCurrentPage = (int) ($summaryCurrentPage ?? 1);
$summaryTotalPages = (int) ($summaryTotalPages ?? 1);
$summaryTotalItems = (int) ($summaryTotalItems ?? 0);
$summaryPerPage = (int) ($summaryPerPage ?? 10);
$previewCurrentPage = (int) ($previewCurrentPage ?? 1);
$previewTotalPages = (int) ($previewTotalPages ?? 1);
$previewTotalItems = (int) ($previewTotalItems ?? 0);
$previewPerPage = (int) ($previewPerPage ?? 15);

$buildTableUrl = static function (string $target, int $page) use ($tableSearch, $form): string {
    $query = [
        'search' => $tableSearch,
        'komoditas' => (string) ($form['komoditas'] ?? ''),
    ];

    if ($target === 'summary') {
        $query['summary_page'] = $page;
    } else {
        $query['preview_page'] = $page;
    }

    return base_url('/preprocessing?' . http_build_query(array_filter($query, static fn ($value): bool => $value !== '')));
};

$summaryPageNumbers = [];
for ($page = 1; $page <= $summaryTotalPages; $page++) {
    if ($page === 1 || $page === $summaryTotalPages || abs($page - $summaryCurrentPage) <= 1) {
        $summaryPageNumbers[] = $page;
    }
}
$summaryPageNumbers = array_values(array_unique($summaryPageNumbers));

$previewPageNumbers = [];
for ($page = 1; $page <= $previewTotalPages; $page++) {
    if ($page === 1 || $page === $previewTotalPages || abs($page - $previewCurrentPage) <= 1) {
        $previewPageNumbers[] = $page;
    }
}
$previewPageNumbers = array_values(array_unique($previewPageNumbers));
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
                <div class="hero-badge">Preprocessing Pipeline</div>
                <div class="hero-grid">
                    <div>
                        <h2>Preprocessing Data LSTM</h2>
                        <p>Jalankan pembersihan data, deteksi missing value dan outlier, normalisasi Min-Max, pembentukan sekuens, pembagian data latih/uji, lalu simpan hasilnya ke tabel `data_preprocessing_lstm`.</p>
                    </div>
                    <div class="hero-metrics">
                        <div class="metric-chip">
                            <span>Total evaluasi</span>
                            <strong><?= e((string) count($summaryRows)) ?> komoditas</strong>
                        </div>
                        <div class="metric-chip">
                            <span>Preview proses</span>
                            <strong><?= e((string) count($previewRows)) ?> baris</strong>
                        </div>
                        <div class="metric-chip">
                            <span>Run terakhir</span>
                            <strong><?= e((string) ($runResult['savedRows'] ?? 0)) ?> tersimpan</strong>
                        </div>
                    </div>
                </div>
            </section>

            <section class="config-card">
                <div class="section-head">
                    <div class="section-copy">
                        <h2>Konfigurasi Proses</h2>
                        <p>Tentukan komoditas, panjang histori sekuens, dan rasio pembagian data sebelum menjalankan preprocessing.</p>
                    </div>
                </div>

                <form id="preprocessingForm" action="<?= e(base_url('/preprocessing/process')) ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <div class="field">
                            <label for="komoditas">Komoditas</label>
                            <select id="komoditas" name="komoditas">
                                <option value="">Semua Komoditas</option>
                                <?php foreach ($commodityOptions as $commodityOption): ?>
                                    <option value="<?= e((string) $commodityOption) ?>" <?= (string) $commodityOption === (string) $form['komoditas'] ? 'selected' : '' ?>><?= e((string) $commodityOption) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="sequence_length">Panjang Sekuens</label>
                            <input id="sequence_length" type="number" min="1" max="60" name="sequence_length" value="<?= e((string) $form['sequence_length']) ?>">
                        </div>
                        <div class="field">
                            <label for="train_ratio">Rasio Data Latih</label>
                            <input id="train_ratio" type="number" min="0.50" max="0.95" step="0.01" name="train_ratio" value="<?= e((string) $form['train_ratio']) ?>">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn" type="submit">Jalankan Preprocessing</button>
                        <a class="ghost-link" href="<?= e(base_url('/preprocessing')) ?>">Muat Ulang Halaman</a>
                    </div>
                </form>
            </section>

            <section class="summary-card">
                <div class="section-head">
                    <div class="section-copy">
                        <h2>Ringkasan Evaluasi</h2>
                        <p>Menampilkan hasil agregat preprocessing yang sudah tersimpan di database.</p>
                    </div>
                </div>

                <div class="summary-grid">
                    <div class="summary-box">
                        <span>Total Komoditas</span>
                        <strong><?= e((string) count($summaryRows)) ?></strong>
                    </div>
                    <div class="summary-box">
                        <span>Total Baris Tersimpan</span>
                        <strong><?= e((string) array_sum(array_map(static fn(array $row): int => (int) $row['total_data'], $summaryRows))) ?></strong>
                    </div>
                    <div class="summary-box">
                        <span>Total Missing Value</span>
                        <strong><?= e((string) array_sum(array_map(static fn(array $row): int => (int) $row['missing_value'], $summaryRows))) ?></strong>
                    </div>
                    <div class="summary-box">
                        <span>Total Outlier</span>
                        <strong><?= e((string) array_sum(array_map(static fn(array $row): int => (int) $row['outlier'], $summaryRows))) ?></strong>
                    </div>
                </div>
            </section>

            <?php if ($logs !== []): ?>
                <section class="log-card">
                    <div class="section-head">
                        <div class="section-copy">
                            <h2>Log Proses</h2>
                            <p>Menampilkan tahapan preprocessing dan hasil utama yang baru saja dijalankan.</p>
                        </div>
                    </div>

                    <div class="log-list">
                        <?php foreach ($logs as $log): ?>
                            <div class="log-item">
                                <span class="log-dot"></span>
                                <div><?= e((string) $log) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="table-card">
                <div class="section-head">
                    <div class="section-copy">
                        <h2>Tabel Evaluasi per Komoditas</h2>
                        <p>Evaluasi akhir preprocessing untuk masing-masing komoditas yang sudah tersimpan.</p>
                    </div>
                </div>

                <div class="toolbar">
                    <form class="search-form" action="<?= e(base_url('/preprocessing')) ?>" method="GET">
                        <input type="hidden" name="komoditas" value="<?= e((string) $form['komoditas']) ?>">
                        <div class="search-field">
                            <label for="tableSearch">Cari hasil preprocessing</label>
                            <input id="tableSearch" type="text" name="search" value="<?= e($tableSearch) ?>" placeholder="Komoditas, tanggal, status anomali, atau set data">
                        </div>
                        <button class="btn" type="submit">Cari</button>
                        <?php if ($tableSearch !== ''): ?>
                            <a class="ghost-link" href="<?= e(base_url('/preprocessing' . ((string) $form['komoditas'] !== '' ? '?komoditas=' . urlencode((string) $form['komoditas']) : ''))) ?>">Reset</a>
                        <?php endif; ?>
                    </form>

                    <div class="toolbar-meta">Evaluasi: <?= e((string) count($summaryRows)) ?> dari <?= e((string) $summaryTotalItems) ?> komoditas.</div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Komoditas</th>
                            <th>Total</th>
                            <th>Missing</th>
                            <th>Outlier</th>
                            <th>Latih</th>
                            <th>Uji</th>
                            <th>Min Bersih</th>
                            <th>Max Bersih</th>
                            <th>Avg Normalisasi</th>
                            <th>Rentang Tanggal</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($summaryRows !== []): ?>
                            <?php foreach ($summaryRows as $row): ?>
                                <tr>
                                    <td><?= e((string) $row['komoditas']) ?></td>
                                    <td><?= e((string) $row['total_data']) ?></td>
                                    <td><?= e((string) $row['missing_value']) ?></td>
                                    <td><?= e((string) $row['outlier']) ?></td>
                                    <td><?= e((string) $row['data_latih']) ?></td>
                                    <td><?= e((string) $row['data_uji']) ?></td>
                                    <td><?= e(number_format((float) $row['min_stok_bersih'], 2, '.', '')) ?></td>
                                    <td><?= e(number_format((float) $row['max_stok_bersih'], 2, '.', '')) ?></td>
                                    <td><?= e(number_format((float) $row['rata_normalisasi'], 6, '.', '')) ?></td>
                                    <td><?= e((string) $row['tanggal_awal']) ?> s.d. <?= e((string) $row['tanggal_akhir']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="empty-state">Belum ada hasil preprocessing yang tersimpan.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($summaryTotalPages > 1): ?>
                    <div class="pagination">
                        <div class="toolbar-meta">Halaman evaluasi <?= e((string) $summaryCurrentPage) ?> dari <?= e((string) $summaryTotalPages) ?></div>
                        <div class="pagination-links">
                            <a class="page-link<?= $summaryCurrentPage <= 1 ? ' disabled' : '' ?>" href="<?= e($buildTableUrl('summary', max(1, $summaryCurrentPage - 1))) ?>">Prev</a>
                            <?php foreach ($summaryPageNumbers as $index => $page): ?>
                                <?php if ($index > 0 && $summaryPageNumbers[$index - 1] + 1 !== $page): ?>
                                    <span class="page-link disabled">...</span>
                                <?php endif; ?>
                                <a class="page-link<?= $page === $summaryCurrentPage ? ' active' : '' ?>" href="<?= e($buildTableUrl('summary', $page)) ?>"><?= e((string) $page) ?></a>
                            <?php endforeach; ?>
                            <a class="page-link<?= $summaryCurrentPage >= $summaryTotalPages ? ' disabled' : '' ?>" href="<?= e($buildTableUrl('summary', min($summaryTotalPages, $summaryCurrentPage + 1))) ?>">Next</a>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <section class="table-card">
                <div class="section-head">
                    <div class="section-copy">
                        <h2>Preview Data Proses</h2>
                        <p>Cuplikan data preprocessing terbaru yang tersimpan, termasuk anomali, normalisasi, dan sekuens input.</p>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Komoditas</th>
                            <th>Stok Mentah</th>
                            <th>Status</th>
                            <th>Stok Bersih</th>
                            <th>Normalisasi</th>
                            <th>Sekuens X</th>
                            <th>Target Y</th>
                            <th>Set</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($previewRows !== []): ?>
                            <?php foreach ($previewRows as $row): ?>
                                <?php
                                $statusClass = 'normal';
                                if ((string) $row['status_anomali'] === 'Missing Value') {
                                    $statusClass = 'missing';
                                } elseif ((string) $row['status_anomali'] === 'Outlier') {
                                    $statusClass = 'outlier';
                                }
                                ?>
                                <tr>
                                    <td><?= e((string) $row['format_waktu']) ?></td>
                                    <td><?= e((string) $row['komoditas']) ?></td>
                                    <td><?= $row['stok_mentah'] !== null ? e(number_format((float) $row['stok_mentah'], 2, '.', '')) : '-' ?></td>
                                    <td><span class="status-pill <?= e($statusClass) ?>"><?= e((string) $row['status_anomali']) ?></span></td>
                                    <td><?= e(number_format((float) $row['stok_bersih'], 2, '.', '')) ?></td>
                                    <td><?= e(number_format((float) $row['normalisasi_minmax'], 6, '.', '')) ?></td>
                                    <td class="sequence-box"><?= e((string) $row['input_sekuens_x']) ?></td>
                                    <td><?= e(number_format((float) $row['target_label_y'], 6, '.', '')) ?></td>
                                    <td><?= e((string) $row['set_data']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="empty-state">Belum ada data preprocessing untuk ditampilkan.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="toolbar" style="margin-top: 18px; margin-bottom: 0;">
                    <div class="toolbar-meta">Preview: <?= e((string) count($previewRows)) ?> dari <?= e((string) $previewTotalItems) ?> baris.</div>
                </div>

                <?php if ($previewTotalPages > 1): ?>
                    <div class="pagination">
                        <div class="toolbar-meta">Halaman preview <?= e((string) $previewCurrentPage) ?> dari <?= e((string) $previewTotalPages) ?></div>
                        <div class="pagination-links">
                            <a class="page-link<?= $previewCurrentPage <= 1 ? ' disabled' : '' ?>" href="<?= e($buildTableUrl('preview', max(1, $previewCurrentPage - 1))) ?>">Prev</a>
                            <?php foreach ($previewPageNumbers as $index => $page): ?>
                                <?php if ($index > 0 && $previewPageNumbers[$index - 1] + 1 !== $page): ?>
                                    <span class="page-link disabled">...</span>
                                <?php endif; ?>
                                <a class="page-link<?= $page === $previewCurrentPage ? ' active' : '' ?>" href="<?= e($buildTableUrl('preview', $page)) ?>"><?= e((string) $page) ?></a>
                            <?php endforeach; ?>
                            <a class="page-link<?= $previewCurrentPage >= $previewTotalPages ? ' disabled' : '' ?>" href="<?= e($buildTableUrl('preview', min($previewTotalPages, $previewCurrentPage + 1))) ?>">Next</a>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>

<div class="loading-overlay" id="loadingOverlay" aria-hidden="true">
    <div class="loading-card">
        <div class="loading-spinner"></div>
        <h2>Preprocessing sedang berjalan</h2>
        <p>Mohon tunggu. Sistem sedang membersihkan data, mendeteksi anomali, membentuk sekuens, mengevaluasi hasil, dan menyimpan ke database.</p>
        <div class="loading-steps">
            <div class="loading-step">Memuat data stok historis dan memformat waktu.</div>
            <div class="loading-step">Mendeteksi missing value dan outlier, lalu membentuk stok bersih.</div>
            <div class="loading-step">Menormalisasi data, membuat sekuens, membagi latih/uji, lalu menyimpan hasil.</div>
        </div>
    </div>
</div>

<script>
    (() => {
        const form = document.getElementById('preprocessingForm');
        const overlay = document.getElementById('loadingOverlay');

        if (!form || !overlay) {
            return;
        }

        form.addEventListener('submit', () => {
            overlay.classList.add('is-visible');
            overlay.setAttribute('aria-hidden', 'false');
        });
    })();
</script>
<?php require $flashDialogPath; ?>
<?php require $panelScriptsPath; ?>
</body>
</html>
