<?php

declare(strict_types=1);

$komoditasTotal = (int) ($komoditasTotal ?? 0);
$stokSummary = $stokSummary ?? ['total_records' => 0, 'latest_date' => '-', 'latest_snapshot' => []];
$forecastSummary = $forecastSummary ?? ['latestBatch' => null, 'bestRun' => null, 'forecastCards' => [], 'forecastChart' => []];
$forecastTable = $forecastTable ?? ['items' => [], 'commodityOptions' => [], 'totalItems' => 0, 'perPage' => 10, 'currentPage' => 1, 'totalPages' => 1, 'search' => '', 'commodity' => '', 'status' => ''];
$latestBatch = $forecastSummary['latestBatch'] ?? null;
$bestRun = $forecastSummary['bestRun'] ?? null;
$forecastCards = $forecastSummary['forecastCards'] ?? [];
$forecastChart = $forecastSummary['forecastChart'] ?? [];
$forecastTableItems = $forecastTable['items'] ?? [];
$forecastCommodityOptions = $forecastTable['commodityOptions'] ?? [];
$forecastSearch = (string) ($forecastTable['search'] ?? '');
$forecastCommodityFilter = (string) ($forecastTable['commodity'] ?? '');
$forecastStatusFilter = (string) ($forecastTable['status'] ?? '');
$forecastCurrentPage = (int) ($forecastTable['currentPage'] ?? 1);
$forecastTotalPages = (int) ($forecastTable['totalPages'] ?? 1);
$forecastTotalItems = (int) ($forecastTable['totalItems'] ?? 0);
$forecastPerPage = (int) ($forecastTable['perPage'] ?? 10);
$mascotFaces = [
    'curious' => base_url('/public/images/mascot/si-padi-curious.svg'),
    'excited' => base_url('/public/images/mascot/si-padi-excited.svg'),
    'alert' => base_url('/public/images/mascot/si-padi-alert.svg'),
];

$heroImage = base_url('/public/images/landing/hero-lhokseumawe.jpg');
$methodImage = base_url('/public/images/landing/lstm-network.jpg');

$cardIcons = ['grass', 'opacity', 'sanitizer', 'nutrition', 'egg', 'local_shipping'];
$snapshotByCommodity = [];
foreach ($stokSummary['latest_snapshot'] as $snapshot) {
    $snapshotByCommodity[strtolower((string) ($snapshot['nama_komoditas'] ?? ''))] = $snapshot;
}

$chartLabels = [];
$chartValues = [];
$chartCommodities = [];
foreach ($forecastChart as $row) {
    $chartLabels[] = (new DateTime((string) $row['tanggal_forecast']))->format('d M Y');
    $chartValues[] = (float) $row['forecast_denormalized'];
    $chartCommodities[] = (string) $row['komoditas'];
}

$maxForecastValue = 0.0;
foreach ($forecastCards as $card) {
    $maxForecastValue = max($maxForecastValue, (float) ($card['forecast_denormalized'] ?? 0));
}

$featuredCards = [];
foreach (array_slice($forecastCards, 0, 3) as $index => $card) {
    $commodityName = strtolower((string) ($card['komoditas'] ?? ''));
    $forecastValue = (float) ($card['forecast_denormalized'] ?? 0);
    $mape = (float) ($card['mape'] ?? 0);
    $rmse = (float) ($card['rmse'] ?? 0);
    $actualValue = isset($snapshotByCommodity[$commodityName]) ? (float) ($snapshotByCommodity[$commodityName]['jumlah_aktual'] ?? 0) : null;
    $ratio = $maxForecastValue > 0 ? (int) round(($forecastValue / $maxForecastValue) * 100) : 0;
    $ratio = max(20, min(100, $ratio));

    if ($actualValue !== null) {
        $delta = $forecastValue - $actualValue;
        if ($delta > 0) {
            $changeLabel = '+' . number_format($delta, 2, '.', '') . ' vs aktual';
            $changeColor = 'text-secondary';
            $changeIcon = 'arrow_upward';
        } elseif ($delta < 0) {
            $changeLabel = number_format($delta, 2, '.', '') . ' vs aktual';
            $changeColor = 'text-error';
            $changeIcon = 'arrow_downward';
        } else {
            $changeLabel = 'Sama dengan aktual';
            $changeColor = 'text-[#8a5a00]';
            $changeIcon = 'trending_flat';
        }
    } else {
        $changeLabel = 'Aktual belum tersedia';
        $changeColor = 'text-on-surface-variant';
        $changeIcon = 'trending_flat';
    }

    if ($mape <= 10.0) {
        $statusLabel = 'Safe';
        $statusBar = 'bg-primary';
    } elseif ($mape <= 20.0) {
        $statusLabel = 'Watchlist';
        $statusBar = 'bg-[#b78103]';
    } else {
        $statusLabel = 'Warning';
        $statusBar = 'bg-error';
    }

    $featuredCards[] = [
        'icon' => $cardIcons[$index % count($cardIcons)],
        'commodity' => (string) ($card['komoditas'] ?? '-'),
        'value' => number_format($forecastValue, 2, '.', ''),
        'unit' => (string) ($snapshotByCommodity[$commodityName]['satuan'] ?? 'Unit'),
        'ratio' => $ratio,
        'status' => $statusLabel,
        'statusBar' => $statusBar,
        'changeLabel' => $changeLabel,
        'changeColor' => $changeColor,
        'changeIcon' => $changeIcon,
        'mape' => number_format($mape, 2, '.', ''),
        'rmse' => number_format($rmse, 2, '.', ''),
        'actualValue' => $actualValue !== null ? number_format($actualValue, 2, '.', '') : null,
    ];
}

$safeCount = 0;
$watchCount = 0;
foreach ($forecastCards as $card) {
    $mape = (float) ($card['mape'] ?? 0);
    if ($mape <= 10.0) {
        $safeCount++;
    } elseif ($mape <= 20.0) {
        $watchCount++;
    }
}

$batchStatusLabel = (string) ($latestBatch['status'] ?? '-');
$bestEpoch = isset($bestRun['best_epoch']) ? (string) $bestRun['best_epoch'] : '-';
$trainSamples = isset($bestRun['train_samples']) ? (string) $bestRun['train_samples'] : '-';
$testSamples = isset($bestRun['test_samples']) ? (string) $bestRun['test_samples'] : '-';

$mascotTips = [
    'overview' => 'Saya bisa mengarahkan Anda ke ringkasan sistem, metodologi, atau tabel forecast publik.',
    'methodology' => 'Bagian metodologi menjelaskan kenapa LSTM cocok untuk data deret waktu stok pangan.',
    'dashboard' => 'Dashboard menampilkan ringkasan batch terbaru, kartu komoditas unggulan, dan grafik prediksi.',
    'predictions' => 'Gunakan filter pada tabel forecast untuk menelusuri batch, komoditas, horizon, dan status model.',
];

$mascotFaq = [
    [
        'question' => 'Apa model terbaik saat ini?',
        'answer' => 'Model terbaik saat ini adalah ' . (string) ($bestRun['komoditas'] ?? '-') . ' dengan MAPE ' . number_format((float) ($bestRun['mape'] ?? 0), 2, '.', '') . ' persen.',
    ],
    [
        'question' => 'Berapa total prediksi publik?',
        'answer' => 'Saat ini tersedia ' . $forecastTotalItems . ' data forecast publik lintas batch dan komoditas di database.',
    ],
    [
        'question' => 'Komoditas aman ada berapa?',
        'answer' => 'Ada ' . $safeCount . ' model komoditas dengan status safe dan ' . $watchCount . ' model dalam watchlist berdasarkan MAPE.',
    ],
];

$paginationStart = max(1, $forecastCurrentPage - 2);
$paginationEnd = min($forecastTotalPages, $forecastCurrentPage + 2);
?>
<!DOCTYPE html>
<html class="light scroll-smooth" lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="description" content="Landing page publik forecasting stok pangan berbasis LSTM untuk Dinas Pangan Kota Lhokseumawe.">
    <title><?= e($title ?? 'Landing Page') ?> - <?= e((string) app_config('name', 'Aplikasi')) ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <?= landing_page_theme_assets() ?>
</head>
<body class="bg-background text-on-surface">
<header class="sticky top-0 z-50 w-full border-b border-white/70 bg-[#f9f9f8]/90 backdrop-blur-xl transition-colors duration-200">
    <div class="mx-auto flex w-full max-w-screen-2xl items-center justify-between px-5 py-4 sm:px-8">
        <div class="text-sm font-bold uppercase tracking-[0.28em] text-primary sm:text-lg">
            Prediksi Stok Pangan Lhokseumawe
        </div>
        <button type="button" class="flex h-11 w-11 items-center justify-center rounded-lg border border-outline-variant/30 bg-surface-container-lowest text-primary md:hidden" id="mobileMenuButton" aria-expanded="false" aria-controls="mobileNavPanel">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <nav class="hidden items-center gap-8 md:flex">
            <a class="nav-link is-active font-semibold tracking-tight text-primary transition-colors duration-200" href="#overview">Overview</a>
            <a class="nav-link font-semibold tracking-tight text-on-surface-variant transition-colors duration-200 hover:text-primary" href="#methodology">Methodology</a>
            <a class="nav-link font-semibold tracking-tight text-on-surface-variant transition-colors duration-200 hover:text-primary" href="#dashboard">Dashboard</a>
            <a class="nav-link font-semibold tracking-tight text-on-surface-variant transition-colors duration-200 hover:text-primary" href="#predictions">Predictions</a>
        </nav>
        <div class="hidden items-center gap-4 md:flex">
            <a href="<?= e(base_url('/login')) ?>" class="interactive-button rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-on-primary transition-opacity hover:opacity-90 sm:px-6" data-magnetic>
                Masuk Sistem
            </a>
        </div>
    </div>
    <div id="mobileNavPanel" class="mobile-nav-panel is-hidden border-t border-outline-variant/20 bg-[#f9f9f8]/95 px-5 py-4 md:hidden sm:px-8">
        <div class="flex flex-col gap-3">
            <a class="rounded-lg bg-surface-container-low px-4 py-3 font-semibold text-primary" href="#overview">Overview</a>
            <a class="rounded-lg bg-surface-container-lowest px-4 py-3 font-semibold text-on-surface-variant" href="#methodology">Methodology</a>
            <a class="rounded-lg bg-surface-container-lowest px-4 py-3 font-semibold text-on-surface-variant" href="#dashboard">Dashboard</a>
            <a class="rounded-lg bg-surface-container-lowest px-4 py-3 font-semibold text-on-surface-variant" href="#predictions">Predictions</a>
            <a href="<?= e(base_url('/login')) ?>" class="mt-2 rounded-lg bg-primary px-4 py-3 text-center font-semibold text-on-primary">Masuk Sistem</a>
        </div>
    </div>
</header>

<main>
    <section id="overview" class="relative flex min-h-[819px] items-center overflow-hidden bg-surface-container-low">
        <div class="absolute inset-0 z-0">
            <div class="glass-orb left-[8%] top-[14%] h-40 w-40 animate-pulseRing"></div>
            <div class="glass-orb right-[10%] top-[18%] h-56 w-56 animate-floatSlow"></div>
            <div class="absolute inset-0 z-10 bg-gradient-to-r from-background via-background/90 to-transparent"></div>
            <img class="hero-parallax h-full w-full object-cover" alt="Hamparan lahan pangan Lhokseumawe" src="<?= e($heroImage) ?>" data-parallax>
        </div>
        <div class="relative z-20 mx-auto w-full max-w-screen-2xl px-5 sm:px-8">
            <div class="max-w-4xl reveal">
                <div class="mb-6 inline-block rounded-sm bg-secondary-container px-3 py-1 text-xs font-bold uppercase tracking-widest text-on-secondary-container">
                    Machine Learning Intelligence
                </div>
                <h1 class="mb-8 text-4xl font-extrabold leading-[1.05] tracking-[-0.04em] text-primary sm:text-[3.5rem] lg:text-[4.6rem]">
                    FORECASTING STOK PANGAN BERBASIS MACHINE LEARNING DENGAN ALGORITMA LSTM
                </h1>
                <p class="mb-10 max-w-2xl text-lg leading-relaxed text-on-surface-variant sm:text-xl">
                    Implementasi kecerdasan artifisial untuk ketahanan pangan di Dinas Pangan Kota Lhokseumawe. Seluruh angka pada halaman ini diambil dari batch model terbaru, data stok historis terakhir, dan hasil evaluasi yang tersimpan di database sistem.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="#dashboard" class="interactive-button flex items-center gap-2 rounded-lg bg-primary px-8 py-4 font-bold text-on-primary transition-all hover:-translate-y-0.5 hover:shadow-lg" data-magnetic>
                        Akses Dashboard <span class="material-symbols-outlined">trending_up</span>
                    </a>
                    <a href="#methodology" class="interactive-button rounded-lg border-2 border-primary/20 px-8 py-4 font-bold text-primary transition-all hover:bg-surface-container-high" data-magnetic>
                        Pelajari Metodologi
                    </a>
                </div>
                <div class="mt-10 grid max-w-3xl grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="overview-card glass-panel rounded-xl border border-white/60 p-4">
                        <p class="text-xs uppercase tracking-[0.18em] text-on-surface-variant">Komoditas</p>
                        <strong class="stat-number mt-2 block text-2xl font-bold text-primary" data-count="<?= e((string) $komoditasTotal) ?>">0</strong>
                    </div>
                    <div class="overview-card glass-panel rounded-xl border border-white/60 p-4">
                        <p class="text-xs uppercase tracking-[0.18em] text-on-surface-variant">Prediksi</p>
                        <strong class="stat-number mt-2 block text-2xl font-bold text-primary" data-count="<?= e((string) count($forecastCards)) ?>">0</strong>
                    </div>
                    <div class="overview-card glass-panel rounded-xl border border-white/60 p-4">
                        <p class="text-xs uppercase tracking-[0.18em] text-on-surface-variant">Safe Model</p>
                        <strong class="stat-number mt-2 block text-2xl font-bold text-primary" data-count="<?= e((string) $safeCount) ?>">0</strong>
                    </div>
                    <div class="overview-card glass-panel rounded-xl border border-white/60 p-4">
                        <p class="text-xs uppercase tracking-[0.18em] text-on-surface-variant">Watchlist</p>
                        <strong class="stat-number mt-2 block text-2xl font-bold text-primary" data-count="<?= e((string) $watchCount) ?>">0</strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="methodology" class="bg-surface py-24">
        <div class="mx-auto grid max-w-screen-2xl grid-cols-1 items-center gap-16 px-5 sm:px-8 lg:grid-cols-12">
            <div class="reveal lg:col-span-5">
                <h2 class="mb-4 text-xs font-bold uppercase tracking-[0.2em] text-on-surface-variant">Deep Learning Architecture</h2>
                <h3 class="mb-6 text-4xl font-bold text-primary">Long Short-Term Memory (LSTM)</h3>
                <p class="mb-6 leading-relaxed text-on-surface-variant">
                    Algoritma LSTM merupakan varian dari Recurrent Neural Network yang dirancang khusus untuk membaca dependensi jangka panjang pada data deret waktu. Dalam sistem ini, model mempelajari pola historis stok komoditas strategis agar proyeksi 1 tahun ke depan lebih terukur.
                </p>
                <p class="mb-8 leading-relaxed text-on-surface-variant">
                    Proses dimulai dari stok historis, preprocessing, training batch model, hingga evaluasi performa menggunakan RMSE, MAE, dan MAPE. Hasil terbaik kemudian dipublikasikan ke landing page ini agar masyarakat dan pemangku kepentingan melihat ringkasan data secara cepat.
                </p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="tilt-card rounded-xl bg-surface-container-low p-6" data-tilt>
                        <span class="material-symbols-outlined mb-3 text-primary">memory</span>
                        <h4 class="mb-2 font-bold text-primary">Temporal Memory</h4>
                        <p class="text-sm text-on-surface-variant">Menyimpan pola musiman, siklus permintaan, dan pergeseran stok secara berulang.</p>
                    </div>
                    <div class="tilt-card rounded-xl bg-surface-container-low p-6" data-tilt>
                        <span class="material-symbols-outlined mb-3 text-primary">precision_manufacturing</span>
                        <h4 class="mb-2 font-bold text-primary">Metric Driven</h4>
                        <p class="text-sm text-on-surface-variant">Evaluasi batch dilakukan dengan metrik error untuk memastikan hasil prediksi tetap layak dipublikasikan.</p>
                    </div>
                </div>
            </div>
            <div class="reveal lg:col-span-7">
                <div class="tilt-card overflow-hidden rounded-xl shadow-panel" data-tilt>
                    <img class="aspect-video w-full object-cover" alt="Visualisasi arsitektur jaringan LSTM" src="<?= e($methodImage) ?>">
                </div>
            </div>
        </div>
    </section>

    <section id="dashboard" class="bg-surface-container-low py-24">
        <div class="mx-auto max-w-screen-2xl px-5 sm:px-8">
            <div class="mb-12 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="reveal">
                    <h2 class="mb-4 text-xs font-bold uppercase tracking-[0.2em] text-on-surface-variant">Stock Intelligence</h2>
                    <h3 class="text-4xl font-bold text-primary">Main Dashboard</h3>
                </div>
                <div class="reveal flex flex-wrap gap-2 text-sm">
                    <span class="rounded-lg border border-outline-variant/20 bg-surface-container-lowest px-4 py-2 font-medium">Batch: <?= e((string) ($latestBatch['batch_code'] ?? '-')) ?></span>
                    <span class="rounded-lg border border-outline-variant/20 bg-surface-container-lowest px-4 py-2 font-bold text-primary">Tanggal Data: <?= e((string) $stokSummary['latest_date']) ?></span>
                </div>
            </div>

            <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-4">
                <?php foreach (array_slice($featuredCards, 0, 2) as $card): ?>
                    <div class="metric-card reveal rounded-xl bg-surface-container-lowest p-8 shadow-sm" data-tilt>
                        <div class="mb-4 flex items-start justify-between gap-4">
                            <span class="material-symbols-outlined rounded-full bg-primary/5 p-3 text-primary"><?= e($card['icon']) ?></span>
                            <span class="flex items-center gap-1 text-xs font-bold <?= e($card['changeColor']) ?>">
                                <span class="material-symbols-outlined text-xs"><?= e($card['changeIcon']) ?></span>
                                <?= e($card['changeLabel']) ?>
                            </span>
                        </div>
                        <p class="mb-1 text-sm font-semibold text-on-surface-variant"><?= e($card['commodity']) ?> (Forecast)</p>
                        <h4 class="text-4xl font-bold tracking-tight text-primary"><?= e($card['value']) ?> <span class="text-lg font-medium text-on-surface-variant"><?= e($card['unit']) ?></span></h4>
                        <div class="mt-6 h-1 w-full overflow-hidden rounded-full bg-surface-container-highest">
                            <div class="progress-bar h-full <?= e($card['statusBar']) ?>" data-progress="<?= e((string) $card['ratio']) ?>" style="width: <?= e((string) $card['ratio']) ?>%"></div>
                        </div>
                        <p class="mt-2 text-xs text-on-surface-variant">Stock Level: <?= e($card['status']) ?><?php if ($card['actualValue'] !== null): ?> · Aktual <?= e($card['actualValue']) ?> <?= e($card['unit']) ?><?php endif; ?></p>
                    </div>
                <?php endforeach; ?>

                <div class="reveal flex flex-col rounded-xl bg-surface-container-lowest p-8 shadow-sm lg:col-span-2 lg:row-span-2">
                    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <h4 class="font-bold text-primary">Predicted Stock Trend - Forecast Summary</h4>
                        <div class="flex flex-wrap items-center gap-4 text-xs font-bold text-on-surface-variant">
                            <span class="flex items-center gap-1"><span class="h-3 w-3 rounded-full bg-primary"></span> Predicted</span>
                            <span class="flex items-center gap-1"><span class="h-3 w-3 rounded-full bg-outline-variant"></span> Series</span>
                        </div>
                    </div>
                    <div class="chart-shell min-h-[320px] flex-grow rounded-xl bg-surface-container-low p-4 sm:p-6">
                        <div id="chartSkeleton" class="skeleton-shell absolute inset-0 z-[1] grid grid-cols-1 gap-4 p-6">
                            <div class="skeleton-block h-6 w-40 rounded"></div>
                            <div class="skeleton-block h-full min-h-[220px] rounded-xl"></div>
                            <div class="grid grid-cols-4 gap-3">
                                <div class="skeleton-block h-4 rounded"></div>
                                <div class="skeleton-block h-4 rounded"></div>
                                <div class="skeleton-block h-4 rounded"></div>
                                <div class="skeleton-block h-4 rounded"></div>
                            </div>
                        </div>
                        <canvas id="forecastSummaryChart" class="!h-[320px] !w-full"></canvas>
                    </div>
                    <div class="mt-6 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                        <div>
                            <p class="text-on-surface-variant">Total Komoditas</p>
                            <strong class="stat-number text-primary" data-count="<?= e((string) $komoditasTotal) ?>">0</strong>
                        </div>
                        <div>
                            <p class="text-on-surface-variant">Stok Historis</p>
                            <strong class="stat-number text-primary" data-count="<?= e((string) $stokSummary['total_records']) ?>">0</strong>
                        </div>
                        <div>
                            <p class="text-on-surface-variant">Prediksi Aktif</p>
                            <strong class="stat-number text-primary" data-count="<?= e((string) count($forecastCards)) ?>">0</strong>
                        </div>
                        <div>
                            <p class="text-on-surface-variant">Model Terbaik</p>
                            <strong class="text-primary"><?= e((string) ($bestRun['komoditas'] ?? '-')) ?></strong>
                        </div>
                    </div>
                </div>

                <?php if (isset($featuredCards[2])): ?>
                    <div class="metric-card reveal rounded-xl bg-surface-container-lowest p-8 shadow-sm" data-tilt>
                        <div class="mb-4 flex items-start justify-between gap-4">
                            <span class="material-symbols-outlined rounded-full bg-primary/5 p-3 text-primary"><?= e($featuredCards[2]['icon']) ?></span>
                            <span class="flex items-center gap-1 text-xs font-bold <?= e($featuredCards[2]['changeColor']) ?>">
                                <span class="material-symbols-outlined text-xs"><?= e($featuredCards[2]['changeIcon']) ?></span>
                                <?= e($featuredCards[2]['changeLabel']) ?>
                            </span>
                        </div>
                        <p class="mb-1 text-sm font-semibold text-on-surface-variant"><?= e($featuredCards[2]['commodity']) ?> (Forecast)</p>
                        <h4 class="text-4xl font-bold tracking-tight text-primary"><?= e($featuredCards[2]['value']) ?> <span class="text-lg font-medium text-on-surface-variant"><?= e($featuredCards[2]['unit']) ?></span></h4>
                        <div class="mt-6 h-1 w-full overflow-hidden rounded-full bg-surface-container-highest">
                            <div class="progress-bar h-full <?= e($featuredCards[2]['statusBar']) ?>" data-progress="<?= e((string) $featuredCards[2]['ratio']) ?>" style="width: <?= e((string) $featuredCards[2]['ratio']) ?>%"></div>
                        </div>
                        <p class="mt-2 text-xs text-on-surface-variant">Stock Level: <?= e($featuredCards[2]['status']) ?><?php if ($featuredCards[2]['actualValue'] !== null): ?> · Aktual <?= e($featuredCards[2]['actualValue']) ?> <?= e($featuredCards[2]['unit']) ?><?php endif; ?></p>
                    </div>
                <?php else: ?>
                    <div class="metric-card reveal rounded-xl bg-surface-container-lowest p-8 shadow-sm">
                        <p class="mb-1 text-sm font-semibold text-on-surface-variant">Komoditas Forecast</p>
                        <h4 class="text-4xl font-bold tracking-tight text-primary">-</h4>
                        <p class="mt-2 text-xs text-on-surface-variant">Belum ada data prediksi tambahan.</p>
                    </div>
                <?php endif; ?>

                <div class="metric-card reveal flex flex-col justify-between rounded-xl bg-primary-container p-8 text-on-primary-container shadow-sm" data-tilt>
                    <h4 class="mb-4 text-lg font-bold">Status Overview</h4>
                    <div class="space-y-4 text-sm">
                        <div class="flex justify-between gap-3"><span>Total Commodities</span><span class="font-bold"><?= e((string) $komoditasTotal) ?> Items</span></div>
                        <div class="flex justify-between gap-3"><span>Safe Models</span><span class="font-bold"><?= e((string) $safeCount) ?></span></div>
                        <div class="flex justify-between gap-3"><span>Watchlist Models</span><span class="font-bold"><?= e((string) $watchCount) ?></span></div>
                        <div class="flex justify-between gap-3"><span>Batch Status</span><span class="font-bold"><?= e($batchStatusLabel) ?></span></div>
                        <div class="flex justify-between gap-3"><span>Best Epoch</span><span class="font-bold"><?= e($bestEpoch) ?></span></div>
                        <div class="flex justify-between gap-3"><span>Train/Test Samples</span><span class="font-bold"><?= e($trainSamples) ?>/<?= e($testSamples) ?></span></div>
                    </div>
                    <a href="<?= e(base_url('/login')) ?>" class="interactive-button mt-8 rounded-lg bg-surface-container-lowest py-3 text-center text-xs font-bold uppercase tracking-widest text-primary" data-magnetic>
                        Buka Panel Admin
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section id="predictions" class="bg-surface py-24">
        <div class="mx-auto max-w-screen-2xl px-5 sm:px-8">
            <div class="mb-16 text-center reveal">
                <h2 class="mb-4 text-xs font-bold uppercase tracking-[0.2em] text-on-surface-variant">Detailed Analytics</h2>
                <h3 class="text-4xl font-bold text-primary">Forecast publik per komoditas</h3>
            </div>

            <div class="reveal mb-8 rounded-2xl border border-outline-variant/20 bg-surface-container-lowest p-5 shadow-sm sm:p-6">
                <form action="<?= e(base_url('/')) ?>#predictions" method="get" class="grid grid-cols-1 gap-4 lg:grid-cols-[1.4fr_1fr_1fr_auto_auto] lg:items-end">
                    <div>
                        <label for="search" class="mb-2 block text-xs font-bold uppercase tracking-[0.2em] text-on-surface-variant">Search</label>
                        <input id="search" name="search" type="text" value="<?= e($forecastSearch) ?>" placeholder="Cari komoditas, tanggal, lokasi gudang" class="w-full rounded-xl border border-outline-variant/30 bg-surface px-4 py-3 text-sm text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
                    </div>
                    <div>
                        <label for="commodity" class="mb-2 block text-xs font-bold uppercase tracking-[0.2em] text-on-surface-variant">Komoditas</label>
                        <select id="commodity" name="commodity" class="w-full rounded-xl border border-outline-variant/30 bg-surface px-4 py-3 text-sm text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
                            <option value="">Semua komoditas</option>
                            <?php foreach ($forecastCommodityOptions as $option): ?>
                                <option value="<?= e($option) ?>"<?= $forecastCommodityFilter === $option ? ' selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="mb-2 block text-xs font-bold uppercase tracking-[0.2em] text-on-surface-variant">Status</label>
                        <select id="status" name="status" class="w-full rounded-xl border border-outline-variant/30 bg-surface px-4 py-3 text-sm text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
                            <option value="">Semua status</option>
                            <option value="safe"<?= $forecastStatusFilter === 'safe' ? ' selected' : '' ?>>Safe</option>
                            <option value="watchlist"<?= $forecastStatusFilter === 'watchlist' ? ' selected' : '' ?>>Watchlist</option>
                            <option value="warning"<?= $forecastStatusFilter === 'warning' ? ' selected' : '' ?>>Warning</option>
                        </select>
                    </div>
                    <button type="submit" class="interactive-button rounded-xl bg-primary px-6 py-3 text-sm font-bold text-on-primary" data-magnetic>
                        Terapkan
                    </button>
                    <a href="<?= e(base_url('/')) ?>#predictions" class="rounded-xl border border-outline-variant/30 px-6 py-3 text-center text-sm font-bold text-primary transition hover:bg-surface-container-low">
                        Reset
                    </a>
                </form>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-on-surface-variant">
                    <span>Total data forecast: <strong class="text-primary"><?= e((string) $forecastTotalItems) ?></strong></span>
                    <span>Menampilkan <strong class="text-primary"><?= e((string) count($forecastTableItems)) ?></strong> dari <strong class="text-primary"><?= e((string) $forecastPerPage) ?></strong> per halaman</span>
                </div>
            </div>

            <div class="reveal table-wrap relative overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm">
                <div id="tableSkeleton" class="skeleton-shell absolute inset-0 z-[1] border-b border-outline-variant/20 bg-surface-container-lowest p-6">
                    <div class="grid grid-cols-1 gap-4">
                        <div class="skeleton-block h-5 w-52 rounded"></div>
                        <div class="skeleton-block h-12 rounded-xl"></div>
                        <div class="skeleton-block h-12 rounded-xl"></div>
                        <div class="skeleton-block h-12 rounded-xl"></div>
                    </div>
                </div>
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-surface-container-low text-xs font-bold uppercase tracking-widest text-on-surface-variant">
                            <th class="px-6 py-5 sm:px-8">Batch</th>
                            <th class="px-6 py-5 sm:px-8">Commodity</th>
                            <th class="px-6 py-5 sm:px-8">Forecast Date</th>
                            <th class="px-6 py-5 sm:px-8">Horizon</th>
                            <th class="px-6 py-5 sm:px-8">Forecast</th>
                            <th class="px-6 py-5 sm:px-8">Aktual Terbaru</th>
                            <th class="px-6 py-5 sm:px-8">RMSE / MAE</th>
                            <th class="px-6 py-5 sm:px-8">MAPE</th>
                            <th class="px-6 py-5 sm:px-8">Lokasi</th>
                            <th class="px-6 py-5 sm:px-8">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-low">
                        <?php if ($forecastTableItems !== []): ?>
                            <?php foreach ($forecastTableItems as $row): ?>
                                <?php
                                $mapeValue = (float) ($row['mape'] ?? 0);
                                $statusClass = 'bg-secondary-container text-on-secondary-container';
                                $statusLabel = 'Safe';
                                if ($mapeValue > 20.0) {
                                    $statusClass = 'bg-error-container text-on-error-container';
                                    $statusLabel = 'Warning';
                                } elseif ($mapeValue > 10.0) {
                                    $statusClass = 'bg-[#fff1c2] text-[#6b4f00]';
                                    $statusLabel = 'Watchlist';
                                }
                                ?>
                                <tr class="forecast-row transition-colors hover:bg-surface-container-low">
                                    <td class="px-6 py-5 sm:px-8"><span class="rounded-full bg-surface-container-low px-3 py-1 text-[11px] font-bold text-primary"><?= e((string) $row['batch_code']) ?></span></td>
                                    <td class="px-6 py-5 font-bold text-primary sm:px-8"><?= e((string) $row['komoditas']) ?></td>
                                    <td class="px-6 py-5 sm:px-8"><?= e((new DateTime((string) $row['tanggal_forecast']))->format('d F Y')) ?></td>
                                    <td class="px-6 py-5 sm:px-8">H+<?= e((string) $row['forecast_horizon_day']) ?></td>
                                    <td class="px-6 py-5 sm:px-8"><?= e(number_format((float) $row['forecast_denormalized'], 2, '.', '')) ?> <?= e((string) ($row['satuan'] ?? '')) ?></td>
                                    <td class="px-6 py-5 sm:px-8"><?= $row['jumlah_aktual'] !== null ? e(number_format((float) $row['jumlah_aktual'], 2, '.', '')) . ' ' . e((string) ($row['satuan'] ?? '')) : '-' ?></td>
                                    <td class="px-6 py-5 text-on-surface-variant sm:px-8"><?= e(number_format((float) $row['rmse'], 2, '.', '')) ?> / <?= e(number_format((float) $row['mae'], 2, '.', '')) ?></td>
                                    <td class="px-6 py-5 text-on-surface-variant sm:px-8"><?= e(number_format((float) $row['mape'], 2, '.', '')) ?>%</td>
                                    <td class="px-6 py-5 text-on-surface-variant sm:px-8"><?= e((string) ($row['lokasi_gudang'] ?? '-')) ?></td>
                                    <td class="px-6 py-5 sm:px-8">
                                        <span class="rounded-full px-3 py-1 text-[10px] font-bold uppercase <?= e($statusClass) ?>">
                                            <?= e($statusLabel) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td class="px-6 py-6 text-center text-on-surface-variant sm:px-8" colspan="10">Belum ada data forecast yang bisa ditampilkan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($forecastTotalPages > 1): ?>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-2 reveal">
                    <a href="<?= e(base_url('/') . query_string(['page' => max(1, $forecastCurrentPage - 1)])) ?>#predictions" class="rounded-xl border border-outline-variant/30 px-4 py-2 text-sm font-semibold text-primary transition hover:bg-surface-container-low">Sebelumnya</a>
                    <?php for ($page = $paginationStart; $page <= $paginationEnd; $page++): ?>
                        <a href="<?= e(base_url('/') . query_string(['page' => $page])) ?>#predictions" class="rounded-xl px-4 py-2 text-sm font-semibold transition <?= $page === $forecastCurrentPage ? 'bg-primary text-on-primary' : 'border border-outline-variant/30 text-primary hover:bg-surface-container-low' ?>">
                            <?= e((string) $page) ?>
                        </a>
                    <?php endfor; ?>
                    <a href="<?= e(base_url('/') . query_string(['page' => min($forecastTotalPages, $forecastCurrentPage + 1)])) ?>#predictions" class="rounded-xl border border-outline-variant/30 px-4 py-2 text-sm font-semibold text-primary transition hover:bg-surface-container-low">Berikutnya</a>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <section class="bg-surface-container-low py-24">
        <div class="mx-auto grid max-w-screen-2xl grid-cols-1 gap-6 px-5 sm:px-8 lg:grid-cols-3">
            <article class="insight-card reveal rounded-xl bg-surface-container-lowest p-8 shadow-sm" data-tilt>
                <h3 class="mb-3 text-xl font-bold text-primary">Batch Prediksi Publik</h3>
                <p class="leading-relaxed text-on-surface-variant">Forecast publik bersumber dari batch `<?= e((string) ($latestBatch['batch_code'] ?? '-')) ?>` dengan status `<?= e($batchStatusLabel) ?>` yang telah tersimpan di database sistem forecasting stok pangan.</p>
            </article>
            <article class="insight-card reveal rounded-xl bg-surface-container-lowest p-8 shadow-sm" data-tilt>
                <h3 class="mb-3 text-xl font-bold text-primary">Model Terbaik Saat Ini</h3>
                <p class="leading-relaxed text-on-surface-variant"><?= e((string) ($bestRun['komoditas'] ?? '-')) ?> tampil sebagai model terbaik pada batch terbaru dengan RMSE <?= e(number_format((float) ($bestRun['rmse'] ?? 0), 2, '.', '')) ?>, MAE <?= e(number_format((float) ($bestRun['mae'] ?? 0), 2, '.', '')) ?>, dan MAPE <?= e(number_format((float) ($bestRun['mape'] ?? 0), 2, '.', '')) ?>%.</p>
            </article>
            <article class="insight-card reveal rounded-xl bg-surface-container-lowest p-8 shadow-sm" data-tilt>
                <h3 class="mb-3 text-xl font-bold text-primary">Snapshot Aktual</h3>
                <p class="leading-relaxed text-on-surface-variant">Ringkasan stok historis terbaru tetap dipertahankan agar publik dapat membandingkan kondisi aktual dengan hasil forecast.</p>
            </article>
        </div>
    </section>

    <section class="bg-surface py-24">
        <div class="mx-auto max-w-screen-2xl px-5 sm:px-8 reveal">
            <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.2em] text-on-surface-variant">Snapshot Aktual</p>
                    <h2 class="text-3xl font-bold text-primary">Snapshot stok terbaru</h2>
                </div>
                <p class="max-w-2xl text-on-surface-variant">Data stok historis terbaru dari database ditampilkan sebagai referensi kondisi aktual sebelum prediksi dipublikasikan.</p>
            </div>
            <div class="table-wrap overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-surface-container-low text-xs font-bold uppercase tracking-widest text-on-surface-variant">
                            <th class="px-6 py-5 sm:px-8">Komoditas</th>
                            <th class="px-6 py-5 sm:px-8">Jumlah Aktual</th>
                            <th class="px-6 py-5 sm:px-8">Satuan</th>
                            <th class="px-6 py-5 sm:px-8">Lokasi Gudang</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-container-low">
                        <?php if (!empty($stokSummary['latest_snapshot'])): ?>
                            <?php foreach ($stokSummary['latest_snapshot'] as $snapshot): ?>
                                <tr class="snapshot-row transition-colors hover:bg-surface-container-low">
                                    <td class="px-6 py-5 font-bold text-primary sm:px-8"><?= e((string) $snapshot['nama_komoditas']) ?></td>
                                    <td class="px-6 py-5 sm:px-8"><?= e(number_format((float) $snapshot['jumlah_aktual'], 2, '.', '')) ?></td>
                                    <td class="px-6 py-5 sm:px-8"><?= e((string) ($snapshot['satuan'] ?? '-')) ?></td>
                                    <td class="px-6 py-5 sm:px-8"><?= e((string) $snapshot['lokasi_gudang']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td class="px-6 py-6 text-center text-on-surface-variant sm:px-8" colspan="4">Belum ada snapshot stok historis yang bisa ditampilkan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>

<div class="mascot-shell" id="mascotShell">
    <div class="mascot-bubble is-hidden" id="mascotBubble">Halo, saya siap membantu membaca insight prediksi stok pangan Anda.</div>
    <div class="mascot-card is-hidden" id="mascotCard">
        <div class="mascot-header">
            <div class="mascot-title-wrap">
                <div class="mascot-avatar" id="mascotAvatar" aria-hidden="true">
                    <img id="mascotAvatarFace" src="<?= e($mascotFaces['curious']) ?>" alt="Ekspresi maskot Si Padi Cerdas">
                </div>
                <div>
                    <div class="mascot-status">Asisten Interaktif</div>
                    <strong class="block text-primary">Si Padi Cerdas</strong>
                </div>
            </div>
            <div class="mascot-actions">
                <button type="button" class="mascot-icon-btn" id="mascotSpeakButton" aria-label="Bacakan pesan maskot">
                    <span class="material-symbols-outlined">volume_up</span>
                </button>
                <button type="button" class="mascot-icon-btn" id="mascotMinimize" aria-label="Sembunyikan panel">
                    <span class="material-symbols-outlined">remove</span>
                </button>
            </div>
        </div>
        <div class="mascot-body">
            <p id="mascotMessage">Halo, saya Si Padi Cerdas. Saya terinspirasi dari pola microinteractions, onboarding assistant, dan cursor tooltip yang umum dipakai untuk membuat landing page terasa lebih hidup. Coba pakai panduan cepat saya.</p>
            <div class="mascot-chip-row">
                <button type="button" class="mascot-chip" data-mascot-target="#overview" data-mascot-message="<?= e($mascotTips['overview']) ?>">Lihat Ringkasan</button>
                <button type="button" class="mascot-chip" data-mascot-target="#methodology" data-mascot-message="<?= e($mascotTips['methodology']) ?>">Kenapa LSTM?</button>
                <button type="button" class="mascot-chip" data-mascot-target="#dashboard" data-mascot-message="<?= e($mascotTips['dashboard']) ?>">Buka Dashboard</button>
                <button type="button" class="mascot-chip" data-mascot-target="#predictions" data-mascot-message="<?= e($mascotTips['predictions']) ?>">Cek Forecast</button>
            </div>
            <div class="mascot-tip" id="mascotTipBox">Tip: saya akan memberi konteks berbeda saat Anda berpindah section dan bisa dipakai sebagai pemandu mini di landing page.</div>
            <div class="mascot-faq">
                <?php foreach ($mascotFaq as $faq): ?>
                    <button type="button" data-mascot-faq="<?= e($faq['answer']) ?>"><?= e($faq['question']) ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <button type="button" class="mascot-toggle" id="mascotToggle" aria-expanded="false">
        <span class="mascot-avatar" aria-hidden="true">
            <img id="mascotToggleFace" src="<?= e($mascotFaces['curious']) ?>" alt="Maskot Si Padi Cerdas">
        </span>
        <span>Buka Si Padi Cerdas</span>
    </button>
</div>

<footer class="mt-20 w-full bg-[#f3f4f2]">
    <div class="mx-auto flex w-full max-w-screen-2xl flex-col items-center gap-4 px-5 py-12 text-center sm:px-8">
        <div class="mb-2 text-xl font-bold text-primary">Dinas Pangan Kota Lhokseumawe</div>
        <nav class="flex flex-wrap justify-center gap-6 sm:gap-8">
            <a class="text-sm text-on-surface-variant transition-opacity duration-300 hover:text-primary" href="#overview">Overview</a>
            <a class="text-sm text-on-surface-variant transition-opacity duration-300 hover:text-primary" href="#methodology">Methodology Documentation</a>
            <a class="text-sm text-on-surface-variant transition-opacity duration-300 hover:text-primary" href="<?= e(base_url('/login')) ?>">Contact Research Team</a>
        </nav>
        <p class="max-w-lg text-sm text-on-surface-variant">
            &copy; 2026 Dinas Pangan Kota Lhokseumawe. Sistem pemantauan stok pangan strategis berbasis AI untuk mendukung forecasting, monitoring, dan ketahanan pangan daerah.
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (() => {
        const labels = <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>;
        const values = <?= json_encode($chartValues, JSON_UNESCAPED_UNICODE) ?>;
        const commodities = <?= json_encode($chartCommodities, JSON_UNESCAPED_UNICODE) ?>;
        const canvas = document.getElementById('forecastSummaryChart');
        const navLinks = document.querySelectorAll('.nav-link');
        const sections = [...document.querySelectorAll('main section[id]')];
        const progressBars = document.querySelectorAll('.progress-bar');
        const counterElements = document.querySelectorAll('.stat-number[data-count]');
        const tiltElements = document.querySelectorAll('[data-tilt]');
        const magneticElements = document.querySelectorAll('[data-magnetic]');
        const parallaxTarget = document.querySelector('[data-parallax]');
        const chartSkeleton = document.getElementById('chartSkeleton');
        const tableSkeleton = document.getElementById('tableSkeleton');
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const mobileNavPanel = document.getElementById('mobileNavPanel');
        const mascotToggle = document.getElementById('mascotToggle');
        const mascotCard = document.getElementById('mascotCard');
        const mascotMessage = document.getElementById('mascotMessage');
        const mascotTipBox = document.getElementById('mascotTipBox');
        const mascotAvatar = document.getElementById('mascotAvatar');
        const mascotBubble = document.getElementById('mascotBubble');
        const mascotSpeakButton = document.getElementById('mascotSpeakButton');
        const mascotMinimize = document.getElementById('mascotMinimize');
        const mascotChips = document.querySelectorAll('[data-mascot-target]');
        const mascotFaqButtons = document.querySelectorAll('[data-mascot-faq]');
        const mascotAvatarFace = document.getElementById('mascotAvatarFace');
        const mascotToggleFace = document.getElementById('mascotToggleFace');
        const mascotSectionTips = <?= json_encode($mascotTips, JSON_UNESCAPED_UNICODE) ?>;
        const forecastForm = document.querySelector('#predictions form');
        const tableWrap = document.querySelector('#predictions .table-wrap');
        const mascotFaces = <?= json_encode($mascotFaces, JSON_UNESCAPED_UNICODE) ?>;

        let mascotBubbleTimer = null;
        let speechVoice = null;
        let activeUtterance = null;

        if (canvas && labels.length > 0) {
            new Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Forecast Denormalized',
                        data: values,
                        borderColor: '#003366',
                        backgroundColor: 'rgba(0, 51, 102, 0.10)',
                        fill: true,
                        tension: 0.32,
                        borderWidth: 3,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: (items) => items[0] ? `${commodities[items[0].dataIndex]} - ${items[0].label}` : '',
                                label: (context) => `Nilai: ${context.formattedValue}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(114, 119, 129, 0.12)' },
                            ticks: { color: '#424750', maxRotation: 0, autoSkip: true, maxTicksLimit: 6 }
                        },
                        y: {
                            beginAtZero: false,
                            grid: { color: 'rgba(114, 119, 129, 0.12)' },
                            ticks: { color: '#424750' }
                        }
                    }
                }
            });
        }

        window.setTimeout(() => {
            chartSkeleton?.classList.add('is-hidden');
            tableSkeleton?.classList.add('is-hidden');
        }, 550);

        const activateProgress = () => {
            progressBars.forEach((bar) => bar.classList.add('is-visible'));
        };

        const setMascotMood = (mood) => {
            if (!mascotAvatar) {
                return;
            }

            mascotAvatar.classList.remove('is-curious', 'is-excited', 'is-alert');
            if (mood) {
                mascotAvatar.classList.add(mood);
            }

            const faceKey = mood === 'is-excited' ? 'excited' : (mood === 'is-alert' ? 'alert' : 'curious');
            if (mascotAvatarFace && mascotFaces[faceKey]) {
                mascotAvatarFace.src = mascotFaces[faceKey];
            }
            if (mascotToggleFace && mascotFaces[faceKey]) {
                mascotToggleFace.src = mascotFaces[faceKey];
            }
        };

        const showMascotBubble = (message) => {
            if (!mascotBubble) {
                return;
            }

            mascotBubble.textContent = message;
            mascotBubble.classList.remove('is-hidden');

            if (mascotBubbleTimer) {
                window.clearTimeout(mascotBubbleTimer);
            }

            mascotBubbleTimer = window.setTimeout(() => {
                mascotBubble.classList.add('is-hidden');
            }, 3600);
        };

        const loadSpeechVoice = () => {
            if (!('speechSynthesis' in window)) {
                return;
            }

            const voices = window.speechSynthesis.getVoices();
            speechVoice = voices.find((voice) => /^id/i.test(voice.lang))
                || voices.find((voice) => /^ms/i.test(voice.lang))
                || voices.find((voice) => /^en/i.test(voice.lang))
                || voices[0]
                || null;
        };

        const speakText = (text) => {
            if (!('speechSynthesis' in window) || !text) {
                return;
            }

            window.speechSynthesis.cancel();
            activeUtterance = new SpeechSynthesisUtterance(text);
            loadSpeechVoice();

            if (speechVoice) {
                activeUtterance.voice = speechVoice;
                activeUtterance.lang = speechVoice.lang;
            } else {
                activeUtterance.lang = 'id-ID';
            }

            activeUtterance.rate = 1;
            activeUtterance.pitch = 1.02;

            if (mascotSpeakButton) {
                mascotSpeakButton.classList.add('is-speaking');
            }

            activeUtterance.onend = () => {
                mascotSpeakButton?.classList.remove('is-speaking');
            };
            activeUtterance.onerror = () => {
                mascotSpeakButton?.classList.remove('is-speaking');
            };

            window.speechSynthesis.speak(activeUtterance);
        };

        const animateCounter = (element) => {
            if (element.dataset.animated === 'true') {
                return;
            }

            const target = Number(element.dataset.count || 0);
            const duration = 1200;
            const start = performance.now();
            element.dataset.animated = 'true';

            const frame = (now) => {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                const current = Math.round(target * eased);
                element.textContent = target > 999 ? current.toLocaleString('id-ID') : String(current);

                if (progress < 1) {
                    requestAnimationFrame(frame);
                } else {
                    element.textContent = target.toLocaleString('id-ID');
                }
            };

            requestAnimationFrame(frame);
        };

        const sectionObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                const id = entry.target.getAttribute('id');
                navLinks.forEach((link) => {
                    link.classList.toggle('is-active', link.getAttribute('href') === `#${id}`);
                    link.classList.toggle('text-primary', link.getAttribute('href') === `#${id}`);
                    link.classList.toggle('text-on-surface-variant', link.getAttribute('href') !== `#${id}`);
                });

                if (mascotTipBox && mascotSectionTips[id]) {
                    mascotTipBox.textContent = mascotSectionTips[id];
                }

                if (id === 'dashboard') {
                    setMascotMood('is-excited');
                } else if (id === 'predictions') {
                    setMascotMood('is-alert');
                } else {
                    setMascotMood('is-curious');
                }
            });
        }, { threshold: 0.45 });

        sections.forEach((section) => sectionObserver.observe(section));

        const reveals = document.querySelectorAll('.reveal');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');

                    if (entry.target.closest('#overview') || entry.target.closest('#dashboard')) {
                        activateProgress();
                    }

                    entry.target.querySelectorAll('.stat-number[data-count]').forEach(animateCounter);
                }
            });
        }, { threshold: 0.12 });

        reveals.forEach((item) => observer.observe(item));
        counterElements.forEach((item) => observer.observe(item.closest('.reveal, .overview-card, .metric-card, .chart-shell') || item));

        tiltElements.forEach((element) => {
            element.addEventListener('mousemove', (event) => {
                const rect = element.getBoundingClientRect();
                const x = ((event.clientX - rect.left) / rect.width) - 0.5;
                const y = ((event.clientY - rect.top) / rect.height) - 0.5;
                element.style.transform = `perspective(900px) rotateX(${(-y * 6).toFixed(2)}deg) rotateY(${(x * 8).toFixed(2)}deg) translateY(-6px)`;
            });

            element.addEventListener('mouseleave', () => {
                element.style.transform = '';
            });
        });

        magneticElements.forEach((element) => {
            element.addEventListener('mousemove', (event) => {
                const rect = element.getBoundingClientRect();
                const x = event.clientX - (rect.left + rect.width / 2);
                const y = event.clientY - (rect.top + rect.height / 2);
                element.style.transform = `translate(${x * 0.08}px, ${y * 0.08}px)`;
            });

            element.addEventListener('mouseleave', () => {
                element.style.transform = '';
            });
        });

        if (parallaxTarget) {
            window.addEventListener('scroll', () => {
                const offset = window.scrollY * 0.08;
                parallaxTarget.style.transform = `scale(1.06) translateY(${offset}px)`;
            }, { passive: true });
        }

        if (mobileMenuButton && mobileNavPanel) {
            mobileMenuButton.addEventListener('click', () => {
                const expanded = mobileMenuButton.getAttribute('aria-expanded') === 'true';
                mobileMenuButton.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                mobileNavPanel.classList.toggle('is-hidden');
            });

            mobileNavPanel.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => {
                    mobileMenuButton.setAttribute('aria-expanded', 'false');
                    mobileNavPanel.classList.add('is-hidden');
                });
            });
        }

        const setMascotOpen = (open) => {
            if (!mascotCard || !mascotToggle) {
                return;
            }

            mascotCard.classList.toggle('is-hidden', !open);
            mascotToggle.setAttribute('aria-expanded', open ? 'true' : 'false');

            const label = mascotToggle.querySelector('span:last-child');
            if (label) {
                label.textContent = open ? 'Tutup Si Padi Cerdas' : 'Buka Si Padi Cerdas';
            }
        };

        if (mascotToggle && mascotCard) {
            const mascotVisited = window.localStorage.getItem('mascot-first-visit');
            loadSpeechVoice();
            if ('speechSynthesis' in window) {
                window.speechSynthesis.onvoiceschanged = loadSpeechVoice;
            }

            window.setTimeout(() => {
                setMascotOpen(true);

                if (mascotVisited !== 'yes') {
                    showMascotBubble('Halo, saya Si Padi Cerdas. Saya akan membantu Anda membaca insight penting di landing page ini.');
                    window.localStorage.setItem('mascot-first-visit', 'yes');
                }
            }, 900);

            mascotSpeakButton?.addEventListener('click', () => {
                const text = mascotMessage?.textContent?.trim() || mascotBubble?.textContent?.trim() || '';
                speakText(text);
            });

            mascotToggle.addEventListener('click', () => {
                const shouldOpen = mascotCard.classList.contains('is-hidden');
                setMascotOpen(shouldOpen);
            });

            mascotMinimize?.addEventListener('click', () => setMascotOpen(false));

            mascotChips.forEach((chip) => {
                chip.addEventListener('click', () => {
                    const target = chip.getAttribute('data-mascot-target') || '';
                    const message = chip.getAttribute('data-mascot-message') || '';
                    const section = target !== '' ? document.querySelector(target) : null;

                    if (mascotMessage && message !== '') {
                        mascotMessage.textContent = message;
                    }

                    showMascotBubble(message !== '' ? message : 'Saya mengarahkan Anda ke bagian yang dipilih.');
                    speakText(message !== '' ? message : 'Saya mengarahkan Anda ke bagian yang dipilih.');

                    if (section) {
                        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });

            mascotFaqButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const answer = button.getAttribute('data-mascot-faq') || '';
                    if (mascotMessage && answer !== '') {
                        mascotMessage.textContent = answer;
                    }
                    showMascotBubble(answer !== '' ? answer : 'Saya sedang membaca data halaman untuk Anda.');
                    speakText(answer !== '' ? answer : 'Saya sedang membaca data halaman untuk Anda.');
                    setMascotMood('is-excited');
                });
            });

            document.addEventListener('mousemove', (event) => {
                if (!mascotAvatar) {
                    return;
                }

                const rect = mascotAvatar.getBoundingClientRect();
                const x = event.clientX - (rect.left + rect.width / 2);
                const y = event.clientY - (rect.top + rect.height / 2);
                mascotAvatar.style.transform = `rotateX(${(-y * 0.04).toFixed(2)}deg) rotateY(${(x * 0.05).toFixed(2)}deg)`;
            });

            canvas?.addEventListener('mouseenter', () => {
                setMascotMood('is-excited');
                showMascotBubble('Saya sedang menyorot grafik forecast. Hover titik grafik untuk melihat detail komoditas dan tanggal.');
            });

            canvas?.addEventListener('mouseleave', () => {
                setMascotMood('is-curious');
            });

            tableWrap?.addEventListener('mouseenter', () => {
                setMascotMood('is-alert');
                showMascotBubble('Tabel forecast sedang aktif. Gunakan filter untuk menyaring komoditas, batch, atau status model.');
            });

            tableWrap?.addEventListener('mouseleave', () => {
                setMascotMood('is-curious');
            });

            forecastForm?.addEventListener('submit', () => {
                showMascotBubble('Filter diterapkan. Saya sedang membantu menampilkan forecast yang paling relevan untuk Anda.');
                setMascotMood('is-alert');
            });
        }
    })();
</script>
</body>
</html>
