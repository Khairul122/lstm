<?php

declare(strict_types=1);

$sidebarPath = __DIR__ . '/../../includes/dashboard-sidebar.php';
$panelTopbarPath = __DIR__ . '/../../includes/panel-topbar.php';
$panelHeadPath = __DIR__ . '/../../includes/panel-head.php';
$panelScriptsPath = __DIR__ . '/../../includes/panel-scripts.php';
$flashDialogPath = __DIR__ . '/../../includes/flash-dialog.php';
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

        <div class="panel-content">
            <section class="section-head">
                <div class="section-copy">
                    <h2><?= e((string) $formTitle) ?></h2>
                    <p>Catat histori stok aktual per komoditas berdasarkan tanggal pencatatan.</p>
                </div>

                <div class="section-actions">
                    <a class="btn-secondary" href="<?= e(base_url('/stok-historis')) ?>">Kembali ke Daftar</a>
                </div>
            </section>

            <section class="form-card">
                <div class="form-layout">
                    <div class="form-main">
                        <div class="form-intro">
                            <div class="eyebrow">Stok Historis</div>
                            <h2><?= e((string) $formTitle) ?></h2>
                            <p>Gunakan form ini untuk menambahkan atau memperbarui data stok historis tanpa mengubah gaya panel yang sudah ada.</p>
                        </div>

                        <form action="<?= e((string) $formAction) ?>" method="POST" class="form-grid">
                            <?= csrf_field() ?>

                            <div class="field">
                                <label for="id_komoditas">Komoditas</label>
                                <select id="id_komoditas" name="id_komoditas" required>
                                    <option value="">Pilih komoditas</option>
                                    <?php foreach ($komoditasOptions as $komoditas): ?>
                                        <option value="<?= e((string) $komoditas['id_komoditas']) ?>" <?= (string) ($item['id_komoditas'] ?? '') === (string) $komoditas['id_komoditas'] ? 'selected' : '' ?>>
                                            <?= e((string) $komoditas['kode_komoditas']) ?> - <?= e((string) $komoditas['nama_komoditas']) ?> - <?= e((string) $komoditas['satuan']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="field">
                                <label for="waktu_catat">Tanggal Catat</label>
                                <input
                                    id="waktu_catat"
                                    type="date"
                                    name="waktu_catat"
                                    value="<?= e((string) ($item['waktu_catat'] ?? '')) ?>"
                                    required
                                >
                            </div>

                            <div class="field field-full">
                                <label for="jumlah_aktual">Jumlah Aktual</label>
                                <input
                                    id="jumlah_aktual"
                                    type="number"
                                    step="0.01"
                                    name="jumlah_aktual"
                                    value="<?= e((string) ($item['jumlah_aktual'] ?? '')) ?>"
                                    placeholder="Contoh: 120.50"
                                    required
                                >
                            </div>

                            <div class="field field-full">
                                <label for="lokasi_gudang">Lokasi Gudang</label>
                                <input
                                    id="lokasi_gudang"
                                    type="text"
                                    name="lokasi_gudang"
                                    value="<?= e((string) ($item['lokasi_gudang'] ?? '')) ?>"
                                    placeholder="Contoh: Banda Sakti"
                                    maxlength="50"
                                    required
                                >
                            </div>

                            <div class="actions">
                                <button class="btn" type="submit"><?= e((string) $submitLabel) ?></button>
                                <a class="btn-secondary" href="<?= e(base_url('/stok-historis')) ?>">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>
<?php require $flashDialogPath; ?>
<?php require $panelScriptsPath; ?>
</body>
</html>
