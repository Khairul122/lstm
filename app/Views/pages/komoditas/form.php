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
                    <p>Lengkapi form berikut untuk menyimpan data komoditas.</p>
                </div>

                <div class="section-actions">
                    <a class="btn-secondary" href="<?= e(base_url('/komoditas')) ?>">Kembali ke Daftar</a>
                </div>
            </section>

            <section class="form-card">
                <div class="form-layout">
                    <div class="form-main">
                        <div class="form-intro">
                            <div class="eyebrow">Komoditas Form</div>
                            <h2><?= e((string) $formTitle) ?></h2>
                            <p>Gunakan form di bawah ini untuk menambah atau memperbarui data komoditas secara cepat dari panel.</p>
                        </div>

                        <form action="<?= e((string) $formAction) ?>" method="POST" class="form-grid">
                            <?= csrf_field() ?>

                            <div class="field">
                                <label for="kode_komoditas">Kode Komoditas</label>
                                <input
                                    id="kode_komoditas"
                                    type="text"
                                    name="kode_komoditas"
                                    value="<?= e((string) ($item['kode_komoditas'] ?? '')) ?>"
                                    placeholder="Contoh: BRS-001"
                                    pattern="[A-Za-z]{3}-[0-9]{3}"
                                    required
                                >
                            </div>

                            <div class="field">
                                <label for="nama_komoditas">Nama Komoditas</label>
                                <input
                                    id="nama_komoditas"
                                    type="text"
                                    name="nama_komoditas"
                                    value="<?= e((string) ($item['nama_komoditas'] ?? '')) ?>"
                                    placeholder="Contoh: Beras"
                                    required
                                >
                            </div>

                            <div class="field">
                                <label for="satuan">Satuan</label>
                                <input
                                    id="satuan"
                                    type="text"
                                    name="satuan"
                                    value="<?= e((string) ($item['satuan'] ?? '')) ?>"
                                    placeholder="Contoh: Kg"
                                    required
                                >
                            </div>

                            <div class="actions">
                                <button class="btn" type="submit"><?= e((string) $submitLabel) ?></button>
                                <a class="btn-secondary" href="<?= e(base_url('/komoditas')) ?>">Batal</a>
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
