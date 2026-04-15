<?php

declare(strict_types=1);

$sidebarPath = __DIR__ . '/../../includes/dashboard-sidebar.php';
$panelTopbarPath = __DIR__ . '/../../includes/panel-topbar.php';
$panelHeadPath = __DIR__ . '/../../includes/panel-head.php';
$panelScriptsPath = __DIR__ . '/../../includes/panel-scripts.php';
$flashDialogPath = __DIR__ . '/../../includes/flash-dialog.php';
$search = (string) ($search ?? '');
$currentPage = (int) ($currentPage ?? 1);
$totalPages = (int) ($totalPages ?? 1);
$totalItems = (int) ($totalItems ?? 0);
$perPage = (int) ($perPage ?? 20);

$buildPageUrl = static function (int $page) use ($search): string {
    $query = ['page' => $page];
    if ($search !== '') {
        $query['search'] = $search;
    }
    return base_url('/komoditas?' . http_build_query($query));
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
    <style>
        .table-card {
            padding: 24px;
        }

        .toolbar {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .search-form {
            display: flex;
            gap: 14px;
            align-items: end;
            flex-wrap: wrap;
        }

        .search-field {
            min-width: 320px;
        }

        .search-field label {
            display: block;
            margin-bottom: 10px;
            font-size: 0.86rem;
            color: var(--text-secondary);
            font-weight: 700;
        }

        .search-field input {
            width: 100%;
            min-height: 50px;
            padding: 0 16px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fff;
        }

        .toolbar-meta {
            color: var(--text-secondary);
            font-size: 0.92rem;
        }

        .table-wrap {
            overflow-x: auto;
            border-radius: 20px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th,
        td {
            padding: 20px 22px;
            text-align: left;
            border-bottom: 1px solid var(--line);
            vertical-align: middle;
        }

        th {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-secondary);
            font-weight: 800;
            background: var(--surface-soft);
            white-space: nowrap;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        tbody tr:hover td {
            background: rgba(37, 99, 235, 0.03);
        }

        .empty-state {
            padding: 34px 22px;
            text-align: center;
            color: var(--text-secondary);
        }

        .action-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .delete-form {
            margin: 0;
        }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 22px;
        }

        .pagination-links {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 42px;
            height: 42px;
            padding: 0 14px;
            border-radius: 12px;
            border: 1px solid var(--line);
            text-decoration: none;
            color: var(--text-primary);
            background: #fff;
            font-weight: 700;
        }

        .page-link.active {
            background: var(--brand-main);
            border-color: var(--brand-main);
            color: #fff;
        }

        .page-link.disabled {
            pointer-events: none;
            opacity: 0.45;
        }

        @media (max-width: 640px) {
            .search-field {
                min-width: 100%;
            }

            .btn,
            .action-link,
            .action-delete {
                width: 100%;
                justify-content: center;
            }

            th,
            td {
                padding: 16px 16px;
            }
        }
    </style>

</head>

<body>
<div class="panel-shell">
    <?php require $sidebarPath; ?>

    <main class="main">
        <?php require $panelTopbarPath; ?>

        <div class="panel-content">
            <section class="section-head">
                <div class="section-copy">
                    <h2>Data Komoditas</h2>
                    <p>Kelola daftar komoditas dan satuannya dari panel admin.</p>
                </div>

                <div class="section-actions">
                    <a class="btn" href="<?= e(base_url('/komoditas/create')) ?>">Tambah Komoditas</a>
                </div>
            </section>

            <section class="table-card">
                <div class="toolbar">
                    <form class="search-form" action="<?= e(base_url('/komoditas')) ?>" method="GET">
                        <div class="search-field">
                            <label for="search">Cari komoditas</label>
                            <input id="search" type="text" name="search" value="<?= e($search) ?>" placeholder="Kode, nama komoditas, atau satuan">
                        </div>
                        <button class="btn" type="submit">Cari</button>
                        <?php if ($search !== ''): ?>
                            <a class="action-link" href="<?= e(base_url('/komoditas')) ?>">Reset</a>
                        <?php endif; ?>
                    </form>

                    <div class="toolbar-meta">Menampilkan <?= e((string) count($items)) ?> dari <?= e((string) $totalItems) ?> data.</div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Komoditas</th>
                                <th>Satuan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $index => $item): ?>
                                <tr>
                                    <td><?= e((string) ((($currentPage - 1) * $perPage) + $index + 1)) ?></td>
                                    <td><?= e((string) $item['kode_komoditas']) ?></td>
                                    <td><?= e((string) $item['nama_komoditas']) ?></td>
                                    <td><?= e((string) $item['satuan']) ?></td>
                                    <td>
                                        <div class="action-row">
                                            <a class="action-link" href="<?= e(base_url('/komoditas/edit/' . $item['id_komoditas'])) ?>">Edit</a>
                                            <form
                                                class="delete-form"
                                                action="<?= e(base_url('/komoditas/delete/' . $item['id_komoditas'])) ?>"
                                                method="POST"
                                                data-confirm-dialog
                                                data-confirm-title="Hapus Komoditas"
                                                data-confirm-message="Komoditas ini akan dihapus permanen dari sistem. Lanjutkan penghapusan?"
                                                data-confirm-badge="Danger Zone"
                                                data-confirm-action-label="Ya, Hapus"
                                                data-confirm-cancel-label="Batalkan"
                                                data-confirm-type="warning"
                                            >
                                                <?= csrf_field() ?>
                                                <button class="action-delete" type="submit">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">Belum ada data komoditas.</td>
                            </tr>
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
                                <?php if ($index > 0 && $pageNumbers[$index - 1] + 1 !== $page): ?>
                                    <span class="page-link disabled">...</span>
                                <?php endif; ?>
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
<?php require $flashDialogPath; ?>
<?php require $panelScriptsPath; ?>
</body>
</html>
