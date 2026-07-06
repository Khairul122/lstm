<?php

declare(strict_types=1);

$komoditasTotal = (int) ($komoditasTotal ?? 0);
$komoditasList = $komoditasList ?? [];
$stokSummary = $stokSummary ?? ['total_records' => 0, 'latest_date' => '-', 'latest_snapshot' => []];
$forecastSummary = $forecastSummary ?? ['latestBatch' => null, 'bestRun' => null, 'forecastCards' => [], 'forecastChart' => []];
$forecastTable = $forecastTable ?? ['items' => [], 'commodityOptions' => [], 'totalItems' => 0, 'perPage' => 10, 'currentPage' => 1, 'totalPages' => 1, 'search' => '', 'commodity' => '', 'status' => ''];
$batchStats = $batchStats ?? ['batch_count' => 0, 'completed_count' => 0, 'running_count' => 0, 'failed_count' => 0];

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

$heroImage = base_url('/public/images/landing/hero-lhokseumawe.jpg');
$methodImage = base_url('/public/images/landing/lstm-network.jpg');

$mascotFaces = [
    'curious' => base_url('/public/images/mascot/si-padi-curious.svg'),
    'excited' => base_url('/public/images/mascot/si-padi-excited.svg'),
    'alert' => base_url('/public/images/mascot/si-padi-alert.svg'),
];

$commodityIcons = [
    'beras' => 'grass',
    'gula' => 'cookie',
    'minyak' => 'water_drop',
    'daging' => 'restaurant',
    'ayam' => 'egg',
    'telur' => 'egg_alt',
    'bawang' => 'spa',
    'cabai' => 'local_fire_department',
    'tepung' => 'breakfast_dining',
    'garam' => 'grain',
    'ikan' => 'set_meal',
    'sayur' => 'eco',
];

$resolveCommodityIcon = static function (string $name) use ($commodityIcons): string {
    $lower = strtolower($name);
    foreach ($commodityIcons as $keyword => $icon) {
        if (str_contains($lower, $keyword)) {
            return $icon;
        }
    }
    return 'inventory_2';
};

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

$safeCount = 0;
$watchCount = 0;
$warningCount = 0;
$averageMape = 0.0;
$totalForecastVolume = 0.0;
foreach ($forecastCards as $card) {
    $mape = (float) ($card['mape'] ?? 0);
    $averageMape += $mape;
    $totalForecastVolume += (float) ($card['forecast_denormalized'] ?? 0);
    if ($mape <= 10.0) {
        $safeCount++;
    } elseif ($mape <= 20.0) {
        $watchCount++;
    } else {
        $warningCount++;
    }
}
if (count($forecastCards) > 0) {
    $averageMape /= count($forecastCards);
}
$accuracyPercent = max(0.0, min(100.0, 100.0 - $averageMape));

$maxForecastValue = 0.0;
foreach ($forecastCards as $card) {
    $maxForecastValue = max($maxForecastValue, (float) ($card['forecast_denormalized'] ?? 0));
}

$featuredCards = [];
foreach (array_slice($forecastCards, 0, 6) as $card) {
    $commodityName = strtolower((string) ($card['komoditas'] ?? ''));
    $forecastValue = (float) ($card['forecast_denormalized'] ?? 0);
    $mape = (float) ($card['mape'] ?? 0);
    $rmse = (float) ($card['rmse'] ?? 0);
    $actualValue = isset($snapshotByCommodity[$commodityName]) ? (float) ($snapshotByCommodity[$commodityName]['jumlah_aktual'] ?? 0) : null;
    $ratio = $maxForecastValue > 0 ? (int) round(($forecastValue / $maxForecastValue) * 100) : 0;
    $ratio = max(15, min(100, $ratio));

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
        $statusBadge = 'bg-secondary-container text-on-secondary-container';
    } elseif ($mape <= 20.0) {
        $statusLabel = 'Watchlist';
        $statusBar = 'bg-[#b78103]';
        $statusBadge = 'bg-[#fff1c2] text-[#6b4f00]';
    } else {
        $statusLabel = 'Warning';
        $statusBar = 'bg-error';
        $statusBadge = 'bg-error-container text-on-error-container';
    }

    $featuredCards[] = [
        'icon' => $resolveCommodityIcon((string) ($card['komoditas'] ?? '')),
        'commodity' => (string) ($card['komoditas'] ?? '-'),
        'value' => number_format($forecastValue, 2, '.', ''),
        'unit' => (string) ($snapshotByCommodity[$commodityName]['satuan'] ?? 'Unit'),
        'ratio' => $ratio,
        'status' => $statusLabel,
        'statusBar' => $statusBar,
        'statusBadge' => $statusBadge,
        'changeLabel' => $changeLabel,
        'changeColor' => $changeColor,
        'changeIcon' => $changeIcon,
        'mape' => number_format($mape, 2, '.', ''),
        'rmse' => number_format($rmse, 2, '.', ''),
        'actualValue' => $actualValue !== null ? number_format($actualValue, 2, '.', '') : null,
    ];
}

$batchStatusLabel = (string) ($latestBatch['status'] ?? '-');
$bestEpoch = isset($bestRun['best_epoch']) ? (string) $bestRun['best_epoch'] : '-';
$trainSamples = isset($bestRun['train_samples']) ? (string) $bestRun['train_samples'] : '-';
$testSamples = isset($bestRun['test_samples']) ? (string) $bestRun['test_samples'] : '-';

$features = [
    [
        'icon' => 'auto_awesome',
        'title' => 'Prediksi Berbasis LSTM',
        'description' => 'Memanfaatkan algoritma Long Short-Term Memory untuk mempelajari pola deret waktu stok pangan secara presisi.',
    ],
    [
        'icon' => 'monitoring',
        'title' => 'Monitoring Real-Time',
        'description' => 'Pantau kondisi stok terkini seluruh komoditas strategis langsung dari database yang selalu diperbarui.',
    ],
    [
        'icon' => 'insights',
        'title' => 'Evaluasi Akurasi',
        'description' => 'Setiap model dievaluasi dengan metrik RMSE, MAE, dan MAPE untuk memastikan hasil prediksi layak dipublikasikan.',
    ],
    [
        'icon' => 'inventory_2',
        'title' => 'Multi Komoditas',
        'description' => 'Mendukung banyak komoditas pangan sekaligus dengan satu batch training terpusat.',
    ],
    [
        'icon' => 'query_stats',
        'title' => 'Forecast Jangka Panjang',
        'description' => 'Mampu memprediksi stok hingga 1 tahun ke depan dengan horizon harian, bulanan, dan tahunan.',
    ],
    [
        'icon' => 'verified_user',
        'title' => 'Transparan & Terbuka',
        'description' => 'Seluruh angka pada halaman ini bersumber dari data asli dalam database forecasting stok pangan.',
    ],
];

$steps = [
    [
        'icon' => 'cloud_upload',
        'title' => 'Pengumpulan Data',
        'description' => 'Data stok historis komoditas pangan dicatat secara berkala sebagai dasar training model.',
    ],
    [
        'icon' => 'tune',
        'title' => 'Preprocessing',
        'description' => 'Data dinormalisasi dan dibentuk menjadi sequence deret waktu yang siap diolah model LSTM.',
    ],
    [
        'icon' => 'model_training',
        'title' => 'Training Batch',
        'description' => 'Model LSTM dilatih untuk setiap komoditas dengan pemisahan data latih dan uji.',
    ],
    [
        'icon' => 'online_prediction',
        'title' => 'Publikasi Forecast',
        'description' => 'Hasil prediksi terbaik dipublikasikan ke landing page sebagai referensi ketahanan pangan.',
    ],
];

$faqs = [
    [
        'question' => 'Apa itu sistem forecasting stok pangan ini?',
        'answer' => 'Sistem berbasis machine learning yang memprediksi stok komoditas pangan strategis di Kota Lhokseumawe menggunakan algoritma LSTM untuk mendukung ketahanan pangan daerah.',
    ],
    [
        'question' => 'Dari mana sumber data yang ditampilkan?',
        'answer' => 'Seluruh angka pada halaman ini diambil langsung dari database sistem berisi stok historis, hasil training batch model LSTM, dan evaluasi akurasi terbaru.',
    ],
    [
        'question' => 'Mengapa memilih algoritma LSTM?',
        'answer' => 'LSTM dirancang untuk menangkap dependensi jangka panjang pada data deret waktu, sehingga cocok untuk memodelkan fluktuasi stok pangan yang dipengaruhi pola musiman.',
    ],
    [
        'question' => 'Seberapa akurat prediksi saat ini?',
        'answer' => 'Rata-rata akurasi model saat ini mencapai ' . number_format($accuracyPercent, 2, '.', '') . '% dengan ' . $safeCount . ' model berstatus Safe, ' . $watchCount . ' Watchlist, dan ' . $warningCount . ' Warning.',
    ],
    [
        'question' => 'Bagaimana cara mengakses dashboard lengkap?',
        'answer' => 'Pengguna internal Dinas Pangan dapat masuk melalui tombol "Masuk Sistem" untuk mengelola komoditas, stok historis, dan menjalankan training batch baru.',
    ],
];

$paginationStart = max(1, $forecastCurrentPage - 2);
$paginationEnd = min($forecastTotalPages, $forecastCurrentPage + 2);

$bestRunName = (string) ($bestRun['komoditas'] ?? '-');
$bestRunRmse = number_format((float) ($bestRun['rmse'] ?? 0), 2, '.', '');
$bestRunMae = number_format((float) ($bestRun['mae'] ?? 0), 2, '.', '');
$bestRunMape = number_format((float) ($bestRun['mape'] ?? 0), 2, '.', '');
$accuracyFormatted = number_format($accuracyPercent, 2, '.', '');
$topCommodities = [];
foreach (array_slice($featuredCards, 0, 3) as $card) {
    $topCommodities[] = $card['commodity'] . ' (' . $card['value'] . ' ' . $card['unit'] . ')';
}
$topCommoditiesLabel = $topCommodities !== [] ? implode(', ', $topCommodities) : 'belum tersedia';

$mascotQuickQuestions = [
    [
        'question' => 'Model apa yang paling akurat?',
        'keywords' => ['model', 'akurat', 'terbaik', 'best'],
        'answer' => 'Model terbaik saat ini adalah ' . $bestRunName . ' dengan RMSE ' . $bestRunRmse . ', MAE ' . $bestRunMae . ', dan MAPE ' . $bestRunMape . ' persen.',
    ],
    [
        'question' => 'Berapa akurasi rata-rata sistem?',
        'keywords' => ['akurasi', 'rata-rata', 'mape', 'presisi'],
        'answer' => 'Akurasi rata-rata model saat ini sekitar ' . $accuracyFormatted . ' persen. Sistem memiliki ' . $safeCount . ' model berstatus Safe, ' . $watchCount . ' Watchlist, dan ' . $warningCount . ' Warning.',
    ],
    [
        'question' => 'Berapa komoditas yang dipantau?',
        'keywords' => ['komoditas', 'berapa banyak', 'jumlah'],
        'answer' => 'Sistem memantau ' . $komoditasTotal . ' komoditas pangan strategis dengan total ' . (int) $stokSummary['total_records'] . ' data stok historis tersimpan di database.',
    ],
    [
        'question' => 'Apa itu algoritma LSTM?',
        'keywords' => ['lstm', 'algoritma', 'cara kerja', 'metodologi'],
        'answer' => 'LSTM atau Long Short-Term Memory adalah varian Recurrent Neural Network yang dirancang untuk menangkap dependensi jangka panjang pada data deret waktu. Sangat cocok untuk memodelkan fluktuasi stok pangan musiman.',
    ],
    [
        'question' => 'Kapan data terakhir diperbarui?',
        'keywords' => ['tanggal', 'data terbaru', 'update', 'kapan'],
        'answer' => 'Data stok historis terakhir tercatat pada tanggal ' . (string) $stokSummary['latest_date'] . '. Batch prediksi terbaru adalah ' . (string) ($latestBatch['batch_code'] ?? '-') . ' dengan status ' . $batchStatusLabel . '.',
    ],
    [
        'question' => 'Komoditas mana yang prediksinya tertinggi?',
        'keywords' => ['tertinggi', 'prediksi tinggi', 'top', 'atas'],
        'answer' => 'Tiga komoditas dengan nilai prediksi tertinggi saat ini: ' . $topCommoditiesLabel . '.',
    ],
    [
        'question' => 'Bagaimana cara masuk ke dashboard?',
        'keywords' => ['login', 'masuk', 'dashboard', 'admin'],
        'answer' => 'Klik tombol Masuk Sistem di pojok kanan atas untuk mengakses panel admin. Anda memerlukan kredensial resmi Dinas Pangan Kota Lhokseumawe.',
    ],
    [
        'question' => 'Apa perbedaan Safe, Watchlist, dan Warning?',
        'keywords' => ['safe', 'watchlist', 'warning', 'status', 'perbedaan'],
        'answer' => 'Safe berarti MAPE di bawah 10 persen, Watchlist antara 10 sampai 20 persen, dan Warning di atas 20 persen. Semakin rendah MAPE, semakin akurat modelnya.',
    ],
];

$mascotSectionTips = [
    'beranda' => 'Selamat datang. Saya Si Padi Cerdas, asisten virtual sistem forecasting stok pangan.',
    'fitur' => 'Ini adalah fitur unggulan sistem kami. Silakan tanyakan apa saja tentang kemampuan platform.',
    'cara-kerja' => 'Bagian ini menjelaskan alur kerja sistem dari pengumpulan data hingga publikasi prediksi.',
    'dashboard' => 'Dashboard menampilkan prediksi real-time dari batch LSTM terbaru. Hover tiap kartu untuk detail.',
    'komoditas' => 'Semua komoditas yang dipantau sistem tersaji di sini. Setiap komoditas memiliki model tersendiri.',
    'prediksi' => 'Gunakan filter pada tabel untuk menjelajahi forecast per komoditas, lokasi, atau status akurasi.',
    'faq' => 'Pertanyaan umum tersedia di sini. Anda juga bisa bertanya langsung kepada saya.',
    'cta' => 'Siap memulai? Masuk sistem untuk mengelola data dan menjalankan training model.',
];
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
    <style>
        .gradient-hero {
            background:
                radial-gradient(1200px 600px at 90% 10%, rgba(13, 148, 136, 0.12), transparent 60%),
                radial-gradient(900px 500px at 10% 90%, rgba(15, 59, 117, 0.15), transparent 60%),
                linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            position: relative;
        }
        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(148, 163, 184, 0.15), transparent);
        }
        .faq-item-v2[open] .faq-icon {
            transform: rotate(45deg);
            background-color: #0f3b75;
            color: #ffffff;
        }
        .faq-icon {
            transition: all .25s ease;
        }
        .commodity-chip {
            transition: all .3s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(226, 232, 240, 0.8);
            background-color: rgba(255, 255, 255, 0.6);
        }
        .commodity-chip:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.04);
            background-color: #ffffff;
            border-color: rgba(15, 59, 117, 0.15);
        }
        .cta-pattern-v2 {
            background:
                radial-gradient(ellipse 80% 60% at 20% 50%, rgba(13, 148, 136, 0.22) 0%, transparent 60%),
                radial-gradient(ellipse 60% 80% at 80% 50%, rgba(255, 255, 255, 0.08) 0%, transparent 60%),
                linear-gradient(135deg, #071e3d 0%, #0f3b75 50%, #1d4ed8 100%);
        }
        .cta-pattern-v2::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px);
            background-size: 28px 28px;
            border-radius: inherit;
        }

        /* Ambient floating shapes */
        @keyframes float-shape-1 {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(5deg); }
        }
        @keyframes float-shape-2 {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(-10deg); }
        }
        .float-shape-indigo {
            animation: float-shape-1 7s ease-in-out infinite;
        }
        .float-shape-teal {
            animation: float-shape-2 9s ease-in-out infinite;
        }

        .hero-dot-grid {
            background-image: radial-gradient(rgba(15, 59, 117, 0.06) 1.5px, transparent 1.5px);
            background-size: 24px 24px;
            animation: gridDrift 40s linear infinite;
        }
        @keyframes gridDrift {
            0%   { background-position: 0 0; }
            100% { background-position: 24px 24px; }
        }

        .hero-stat-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 99px;
            padding: 6px 14px;
            font-size: 13px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);
            transition: all .2s ease;
        }
        .hero-stat-pill:hover {
            transform: translateY(-1.5px);
            border-color: rgba(15, 59, 117, 0.15);
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.04);
        }

        .hero-float {
            animation: floatSlow 6s ease-in-out infinite;
        }

        /* Staggered items */
        .stagger-item { opacity: 0; }
        .stagger-item.visible { animation: fadeInUp .7s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .stagger-item:nth-child(1) { animation-delay: .0s; }
        .stagger-item:nth-child(2) { animation-delay: .06s; }
        .stagger-item:nth-child(3) { animation-delay: .12s; }
        .stagger-item:nth-child(4) { animation-delay: .18s; }
        .stagger-item:nth-child(5) { animation-delay: .24s; }
        .stagger-item:nth-child(6) { animation-delay: .30s; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stat-card-bar {
            position: relative;
            background: #ffffff;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .stat-card-bar:hover {
            background: #f8fafc;
            transform: translateY(-2px);
            z-index: 10;
        }
        .stat-card-bar::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, #0f3b75, #0d9488);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .stat-card-bar:hover::after {
            transform: scaleX(1);
        }

        /* Pulse live badge */
        .badge-live-pulse {
            position: relative;
        }
        .badge-live-pulse::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background-color: inherit;
            opacity: 0.4;
            animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
        }
    </style>
</head>
<body class="bg-background text-on-surface">

<header class="sticky top-0 z-50 w-full border-b border-slate-200/50 bg-white/75 backdrop-blur-md">
    <div class="mx-auto flex w-full max-w-screen-2xl items-center justify-between px-5 py-4 sm:px-8">
        <a href="#beranda" class="flex items-center gap-3 group">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-secondary text-white shadow-glow transition-transform group-hover:scale-105">
                <span class="material-symbols-outlined">agriculture</span>
            </span>
            <div>
                <div class="text-[9px] font-extrabold uppercase tracking-[0.28em] text-secondary">Dinas Pangan</div>
                <div class="text-sm font-extrabold tracking-tight text-primary sm:text-base">Stok Pangan Lhokseumawe</div>
            </div>
        </a>
        <button type="button" class="flex h-11 w-11 items-center justify-center rounded-lg border border-slate-200 bg-white text-primary md:hidden" id="mobileMenuButton" aria-expanded="false" aria-controls="mobileNavPanel">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <nav class="hidden items-center gap-8 md:flex">
            <a class="nav-link is-active font-semibold tracking-tight text-primary transition-colors duration-200" href="#beranda">Beranda</a>
            <a class="nav-link font-semibold tracking-tight text-on-surface-variant transition-colors duration-200 hover:text-primary" href="#fitur">Fitur</a>
            <a class="nav-link font-semibold tracking-tight text-on-surface-variant transition-colors duration-200 hover:text-primary" href="#cara-kerja">Cara Kerja</a>
            <a class="nav-link font-semibold tracking-tight text-on-surface-variant transition-colors duration-200 hover:text-primary" href="#dashboard">Dashboard</a>
            <a class="nav-link font-semibold tracking-tight text-on-surface-variant transition-colors duration-200 hover:text-primary" href="#prediksi">Prediksi</a>
            <a class="nav-link font-semibold tracking-tight text-on-surface-variant transition-colors duration-200 hover:text-primary" href="#faq">FAQ</a>
        </nav>
        <div class="hidden items-center gap-3 md:flex">
            <a href="<?= e(base_url('/login')) ?>" class="interactive-button rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-glow hover:bg-gradient-to-r hover:from-primary hover:to-secondary transition-all sm:px-6" data-magnetic>
                Masuk Sistem
            </a>
        </div>
    </div>
    <div id="mobileNavPanel" class="mobile-nav-panel hidden border-t border-slate-200 bg-white/95 px-5 py-4 md:hidden sm:px-8 backdrop-blur-md">
        <div class="flex flex-col gap-3">
            <a class="rounded-xl bg-slate-100 px-4 py-3 font-semibold text-primary" href="#beranda">Beranda</a>
            <a class="rounded-xl bg-white border border-slate-100 px-4 py-3 font-semibold text-on-surface-variant" href="#fitur">Fitur</a>
            <a class="rounded-xl bg-white border border-slate-100 px-4 py-3 font-semibold text-on-surface-variant" href="#cara-kerja">Cara Kerja</a>
            <a class="rounded-xl bg-white border border-slate-100 px-4 py-3 font-semibold text-on-surface-variant" href="#dashboard">Dashboard</a>
            <a class="rounded-xl bg-white border border-slate-100 px-4 py-3 font-semibold text-on-surface-variant" href="#prediksi">Prediksi</a>
            <a class="rounded-xl bg-white border border-slate-100 px-4 py-3 font-semibold text-on-surface-variant" href="#faq">FAQ</a>
            <a href="<?= e(base_url('/login')) ?>" class="mt-2 rounded-xl bg-primary py-3 text-center font-semibold text-white shadow-glow">Masuk Sistem</a>
        </div>
    </div>
</header>

<main>

    <!-- HERO -->
    <section id="beranda" class="relative overflow-hidden gradient-hero">
        <div class="bg-blob-indigo top-10 left-10 float-shape-indigo"></div>
        <div class="bg-blob-teal bottom-10 right-10 float-shape-teal"></div>
        <div class="absolute inset-0 z-0 opacity-15">
            <img class="h-full w-full object-cover" alt="Hamparan lahan pangan Lhokseumawe" src="<?= e($heroImage) ?>">
        </div>
        <div class="absolute inset-0 z-10 bg-gradient-to-b from-background/90 via-background/70 to-background"></div>
        <div class="hero-dot-grid absolute inset-0 z-[11] opacity-70 pointer-events-none"></div>
        <div class="relative z-20 mx-auto grid w-full max-w-screen-2xl grid-cols-1 gap-12 px-5 py-20 sm:px-8 lg:grid-cols-12 lg:py-28">
            <div class="reveal lg:col-span-7">
                <!-- Glowing Live Badge -->
                <div class="inline-flex items-center gap-2 rounded-full bg-secondary-container px-3.5 py-1 text-xs font-bold text-on-secondary-container ring-1 ring-secondary/20 mb-6 badge-live-pulse">
                    <span class="h-2 w-2 rounded-full bg-secondary"></span>
                    Sistem Prediksi Aktif
                </div>
                <h1 class="mb-6 text-4xl font-extrabold leading-[1.05] tracking-[-0.03em] text-primary sm:text-5xl lg:text-[4rem]">
                    Forecasting Stok Pangan <span class="bg-gradient-to-r from-secondary to-teal-600 bg-clip-text text-transparent">Cerdas</span><br class="hidden lg:block"> untuk Lhokseumawe
                </h1>
                <p class="mb-8 max-w-2xl text-lg leading-relaxed text-on-surface-variant">
                    Platform prediksi stok pangan berbasis algoritma <strong class="text-primary font-bold">Long Short-Term Memory (LSTM)</strong> yang membantu Dinas Pangan Kota Lhokseumawe memantau ketersediaan komoditas strategis secara akurat dan real-time.
                </p>
                <div class="mb-10 flex flex-wrap gap-3">
                    <a href="#dashboard" class="interactive-button flex items-center gap-2 rounded-xl bg-primary px-7 py-3.5 font-bold text-white shadow-glow hover:bg-gradient-to-r hover:from-primary hover:to-secondary transition-all hover:-translate-y-0.5" data-magnetic>
                        <span class="material-symbols-outlined text-[20px]">trending_up</span>
                        Lihat Dashboard
                    </a>
                    <a href="#cara-kerja" class="interactive-button flex items-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-7 py-3.5 font-bold text-primary backdrop-blur-sm transition-all hover:bg-white hover:border-slate-300" data-magnetic>
                        <span class="material-symbols-outlined text-[20px]">play_circle</span>
                        Cara Kerja
                    </a>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="hero-stat-pill">
                        <span class="material-symbols-outlined text-secondary text-[18px]">verified</span>
                        <span class="font-bold text-primary font-outfit text-base"><?= e(number_format($accuracyPercent, 2, '.', '')) ?>%</span>
                        <span class="text-on-surface-variant">akurasi</span>
                    </div>
                    <div class="hero-stat-pill">
                        <span class="material-symbols-outlined text-primary text-[18px]">inventory_2</span>
                        <span class="font-bold text-primary font-outfit text-base"><?= e((string) $komoditasTotal) ?></span>
                        <span class="text-on-surface-variant">komoditas</span>
                    </div>
                    <div class="hero-stat-pill">
                        <span class="material-symbols-outlined text-primary text-[18px]">database</span>
                        <span class="font-bold text-primary font-outfit text-base"><?= e(number_format((float) $stokSummary['total_records'], 0, '.', '.')) ?></span>
                        <span class="text-on-surface-variant">data historis</span>
                    </div>
                </div>
            </div>
            <div class="reveal lg:col-span-5">
                <div class="hero-float relative rounded-3xl border border-white/80 bg-white/70 p-6 shadow-panel backdrop-blur-xl">
                    <!-- Card header -->
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[9px] font-extrabold uppercase tracking-widest text-slate-400">Model Terbaik · Terupdate</p>
                            <h3 class="mt-0.5 text-xl font-extrabold text-primary"><?= e((string) ($bestRun['komoditas'] ?? 'N/A')) ?></h3>
                        </div>
                        <span class="flex-shrink-0 rounded-full bg-secondary-container px-3 py-1 text-[10px] font-bold text-on-secondary-container ring-1 ring-secondary/20">
                            <span class="material-symbols-outlined text-[13px] align-[-3px]">auto_awesome</span> Best Model
                        </span>
                    </div>
                    <!-- Metric grid -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="group rounded-2xl bg-white/50 border border-slate-100 p-4 transition hover:bg-white hover:border-primary/20">
                            <p class="text-[9px] font-extrabold uppercase tracking-widest text-slate-400">RMSE</p>
                            <p class="mt-1 font-outfit text-2xl font-extrabold tabular-nums text-primary"><?= e(number_format((float) ($bestRun['rmse'] ?? 0), 2, '.', '')) ?></p>
                        </div>
                        <div class="group rounded-2xl bg-white/50 border border-slate-100 p-4 transition hover:bg-white hover:border-primary/20">
                            <p class="text-[9px] font-extrabold uppercase tracking-widest text-slate-400">MAE</p>
                            <p class="mt-1 font-outfit text-2xl font-extrabold tabular-nums text-primary"><?= e(number_format((float) ($bestRun['mae'] ?? 0), 2, '.', '')) ?></p>
                        </div>
                        <div class="group rounded-2xl bg-white/50 border border-slate-100 p-4 transition hover:bg-white hover:border-primary/20">
                            <p class="text-[9px] font-extrabold uppercase tracking-widest text-slate-400">MAPE</p>
                            <p class="mt-1 font-outfit text-2xl font-extrabold tabular-nums text-primary"><?= e(number_format((float) ($bestRun['mape'] ?? 0), 2, '.', '')) ?><span class="text-sm font-medium text-slate-400">%</span></p>
                        </div>
                        <div class="group rounded-2xl bg-white/50 border border-slate-100 p-4 transition hover:bg-white hover:border-primary/20">
                            <p class="text-[9px] font-extrabold uppercase tracking-widest text-slate-400">Best Epoch</p>
                            <p class="mt-1 font-outfit text-2xl font-extrabold tabular-nums text-primary"><?= e($bestEpoch) ?></p>
                        </div>
                    </div>
                    <!-- Footer info -->
                    <div class="mt-4 flex items-center gap-3 rounded-2xl bg-slate-50 border border-slate-100 p-4">
                        <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <span class="material-symbols-outlined text-[20px]">calendar_today</span>
                        </span>
                        <div>
                            <p class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400">Data Aktual Terakhir</p>
                            <p class="font-bold text-primary text-sm"><?= e((string) $stokSummary['latest_date']) ?></p>
                        </div>
                    </div>
                    <!-- Circular Accuracy Meter -->
                    <div class="mt-3 flex items-center justify-between rounded-2xl bg-teal-50/50 border border-teal-500/10 p-3">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-teal-500/10 text-teal-600">
                                <span class="material-symbols-outlined text-[20px]">insights</span>
                            </span>
                            <div>
                                <p class="text-[9px] font-extrabold uppercase tracking-wider text-teal-700">Akurasi Rata-rata</p>
                                <p class="text-xs font-semibold text-slate-600">Model Converged</p>
                            </div>
                        </div>
                        <div class="relative flex items-center justify-center h-12 w-12">
                            <svg class="w-full h-full transform -rotate-90">
                                <circle cx="24" cy="24" r="19" stroke="rgba(13, 148, 136, 0.08)" stroke-width="3" fill="transparent"/>
                                <circle cx="24" cy="24" r="19" stroke="#0d9488" stroke-width="3" fill="transparent" stroke-dasharray="119.38" stroke-dashoffset="<?= e((string) (119.38 - (119.38 * $accuracyPercent) / 100)) ?>"/>
                            </svg>
                            <span class="absolute text-[10px] font-extrabold text-teal-700 font-outfit"><?= e(number_format($accuracyPercent, 0)) ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- STATS BAR -->
    <section class="border-y border-slate-200/50 bg-white relative z-20 shadow-sm">
        <div class="mx-auto grid max-w-screen-2xl grid-cols-2 gap-0 px-5 sm:px-8 md:grid-cols-4">
            <div class="stat-card-bar reveal flex items-center gap-5 border-r border-slate-100 px-6 py-12 last:border-r-0">
                <span class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary ring-1 ring-primary/5 shadow-sm">
                    <span class="material-symbols-outlined text-3xl">inventory_2</span>
                </span>
                <div>
                    <strong class="stat-number block font-outfit text-3xl font-extrabold tabular-nums text-primary" data-count="<?= e((string) $komoditasTotal) ?>">0</strong>
                    <p class="mt-1 text-[9px] font-extrabold uppercase tracking-widest text-slate-400">Komoditas Dipantau</p>
                </div>
            </div>
            <div class="stat-card-bar reveal flex items-center gap-5 border-r border-slate-100 px-6 py-12 last:border-r-0">
                <span class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-teal-500/10 text-secondary ring-1 ring-teal-500/5 shadow-sm">
                    <span class="material-symbols-outlined text-3xl">database</span>
                </span>
                <div>
                    <strong class="stat-number block font-outfit text-3xl font-extrabold tabular-nums text-primary" data-count="<?= e((string) $stokSummary['total_records']) ?>">0</strong>
                    <p class="mt-1 text-[9px] font-extrabold uppercase tracking-widest text-slate-400">Data Stok Historis</p>
                </div>
            </div>
            <div class="stat-card-bar reveal flex items-center gap-5 border-r border-slate-100 px-6 py-12 last:border-r-0">
                <span class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary ring-1 ring-primary/5 shadow-sm">
                    <span class="material-symbols-outlined text-3xl">model_training</span>
                </span>
                <div>
                    <strong class="stat-number block font-outfit text-3xl font-extrabold tabular-nums text-primary" data-count="<?= e((string) $batchStats['batch_count']) ?>">0</strong>
                    <p class="mt-1 text-[9px] font-extrabold uppercase tracking-widest text-slate-400">Batch Model LSTM</p>
                </div>
            </div>
            <div class="stat-card-bar reveal flex items-center gap-5 px-6 py-12">
                <span class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-teal-50/70 text-teal-700 ring-1 ring-teal-500/10 shadow-sm">
                    <span class="material-symbols-outlined text-3xl">verified</span>
                </span>
                <div>
                    <strong class="block font-outfit text-3xl font-extrabold tabular-nums text-primary"><?= e(number_format($accuracyPercent, 1, '.', '')) ?>%</strong>
                    <p class="mt-1 text-[9px] font-extrabold uppercase tracking-widest text-slate-400">Akurasi Rata-rata</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section id="fitur" class="bg-slate-50 py-24 relative overflow-hidden">
        <div class="bg-blob-indigo top-40 right-10 opacity-10"></div>
        <div class="mx-auto max-w-screen-2xl px-5 sm:px-8 relative z-10">
            <div class="reveal mx-auto mb-16 max-w-3xl text-center">
                <p class="mb-2.5 text-xs font-extrabold uppercase tracking-[0.25em] label-gradient">Fitur Unggulan</p>
                <div class="accent-underline mb-5"></div>
                <h2 class="mb-5 text-4xl font-extrabold text-primary sm:text-5xl tracking-tight">Teknologi Cerdas untuk<br class="hidden sm:block"> Monitoring Stok Pangan</h2>
                <p class="text-lg text-on-surface-variant leading-relaxed">Sistem dirancang khusus untuk mendukung pengambilan keputusan berbasis data yang cepat dan presisi pada Dinas Pangan Kota Lhokseumawe.</p>
            </div>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($features as $feature): ?>
                    <div class="feature-card-v2 hover-lift card-glow-primary stagger-item reveal rounded-3xl p-8 bg-white border border-slate-100 shadow-panel">
                        <span class="feature-icon-grad mb-6 inline-flex h-14 w-14 items-center justify-center rounded-2xl text-primary transition-transform duration-300">
                            <span class="material-symbols-outlined text-3xl transition-transform duration-300 group-hover:rotate-12"><?= e($feature['icon']) ?></span>
                        </span>
                        <h3 class="mb-3 text-xl font-bold text-primary tracking-tight"><?= e($feature['title']) ?></h3>
                        <p class="leading-relaxed text-on-surface-variant text-sm"><?= e($feature['description']) ?></p>
                        <div class="mt-6 flex items-center gap-1.5 text-xs font-bold text-secondary">
                            <span class="h-1.5 w-5 rounded-full bg-secondary"></span>
                            <span class="h-1.5 w-1.5 rounded-full bg-secondary/35"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS -->
    <section id="cara-kerja" class="bg-white py-24 relative overflow-hidden">
        <div class="bg-blob-teal bottom-10 left-10 opacity-10"></div>
        <div class="mx-auto max-w-screen-2xl px-5 sm:px-8 relative z-10">
            <div class="reveal mx-auto mb-16 max-w-3xl text-center">
                <p class="mb-2.5 text-xs font-extrabold uppercase tracking-[0.25em] label-gradient">Alur Jaringan LSTM</p>
                <div class="accent-underline mb-5"></div>
                <h2 class="mb-5 text-4xl font-extrabold text-primary sm:text-5xl tracking-tight">Bagaimana Sistem Bekerja</h2>
                <p class="text-lg text-on-surface-variant leading-relaxed">Dari pengumpulan data hingga publikasi forecast, semua tahapan berjalan secara otomatis, terintegrasi, dan terukur.</p>
            </div>
            <div class="relative grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <?php foreach ($steps as $index => $step): ?>
                    <div class="step-wrapper stagger-item reveal relative rounded-3xl bg-slate-50/50 border border-slate-100/80 p-8 pt-12 text-center transition hover:-translate-y-1 hover:shadow-panel hover:bg-white duration-300">
                        <div class="step-number-badge font-outfit"><?= e((string) ($index + 1)) ?></div>
                        <?php if ($index < count($steps) - 1): ?>
                            <div class="step-connector-animated"></div>
                        <?php endif; ?>
                        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-secondary text-white shadow-glow">
                            <span class="material-symbols-outlined text-3xl"><?= e($step['icon']) ?></span>
                        </div>
                        <div class="mb-2 text-[9px] font-extrabold uppercase tracking-widest text-slate-400">Tahap <?= e((string) ($index + 1)) ?></div>
                        <h3 class="mb-3 text-lg font-bold text-primary tracking-tight"><?= e($step['title']) ?></h3>
                        <p class="text-xs leading-relaxed text-on-surface-variant"><?= e($step['description']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- DASHBOARD PREVIEW -->
    <section id="dashboard" class="bg-slate-50 py-24 relative overflow-hidden">
        <div class="bg-blob-indigo top-10 left-1/3 opacity-10"></div>
        <div class="mx-auto max-w-screen-2xl px-5 sm:px-8 relative z-10">
            <div class="mb-12 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="reveal max-w-2xl">
                    <p class="mb-2 text-xs font-extrabold uppercase tracking-[0.25em] label-gradient">Live Dashboard</p>
                    <div class="accent-underline mb-5 !mx-0"></div>
                    <h2 class="mb-4 text-4xl font-extrabold text-primary sm:text-5xl tracking-tight">Pratinjau Forecast Terkini</h2>
                    <p class="text-lg text-on-surface-variant leading-relaxed">Data langsung dari batch training terbaru. Angka-angka di bawah ini mencerminkan kondisi riil database forecasting.</p>
                </div>
                <div class="reveal flex flex-wrap items-center gap-2.5 text-xs">
                    <span class="rounded-xl border border-slate-200 bg-white px-4.5 py-2.5 font-bold shadow-sm">
                        Batch: <strong class="text-primary font-outfit"><?= e((string) ($latestBatch['batch_code'] ?? '-')) ?></strong>
                    </span>
                    <span class="rounded-xl border border-slate-200 bg-white px-4.5 py-2.5 font-bold shadow-sm">
                        Tanggal Data: <strong class="text-primary font-outfit"><?= e((string) $stokSummary['latest_date']) ?></strong>
                    </span>
                </div>
            </div>

            <!-- Forecast Cards Grid -->
            <div class="mb-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?php if ($featuredCards !== []): ?>
                    <?php foreach ($featuredCards as $card): ?>
                        <div class="metric-card hover-lift card-glow-primary reveal rounded-3xl border border-slate-100 bg-white p-6 shadow-panel" data-tilt>
                            <div class="mb-5 flex items-start justify-between gap-3">
                                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary ring-1 ring-primary/5 shadow-sm">
                                    <span class="material-symbols-outlined"><?= e($card['icon']) ?></span>
                                </span>
                                <span class="rounded-full px-3 py-1 text-[9px] font-extrabold uppercase ring-1 <?= e($card['statusBadge']) ?>">
                                    <?= e($card['status']) ?>
                                </span>
                            </div>
                            <p class="mb-1 text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Forecast</p>
                            <h3 class="mb-1.5 text-xl font-bold text-primary tracking-tight"><?= e($card['commodity']) ?></h3>
                            <p class="text-3xl font-extrabold tracking-tight text-primary font-outfit">
                                <?= e($card['value']) ?>
                                <span class="text-xs font-semibold text-slate-400"><?= e($card['unit']) ?></span>
                            </p>
                            <div class="mt-5 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="progress-bar h-full <?= e($card['statusBar']) ?> rounded-full" data-progress="<?= e((string) $card['ratio']) ?>" style="width: <?= e((string) $card['ratio']) ?>%"></div>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-xs text-slate-500 font-medium">
                                <span class="flex items-center gap-1 <?= e($card['changeColor']) ?> font-bold">
                                    <span class="material-symbols-outlined text-sm"><?= e($card['changeIcon']) ?></span>
                                    <?= e($card['changeLabel']) ?>
                                </span>
                                <span>MAPE <strong class="text-primary font-outfit font-bold"><?= e($card['mape']) ?>%</strong></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full rounded-3xl border border-slate-100 bg-white p-12 text-center text-on-surface-variant shadow-panel">
                        Belum ada forecast yang tersedia. Jalankan batch training LSTM pada panel admin terlebih dahulu.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Chart + Status Overview -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="reveal rounded-3xl border border-slate-100 bg-white p-6 shadow-panel lg:col-span-2 relative overflow-hidden">
                    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-primary tracking-tight">Grafik Forecast Lintas Komoditas</h3>
                            <p class="text-xs text-on-surface-variant">Horizon prediksi harian hingga 365 hari ke depan.</p>
                        </div>
                        <div class="flex items-center gap-3 text-xs font-bold text-slate-500">
                            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-primary ring-2 ring-primary/20"></span> Predicted</span>
                        </div>
                    </div>
                    <div class="chart-shell relative min-h-[320px] rounded-2xl bg-slate-50/50 border border-slate-100/50 p-4 sm:p-6">
                        <div id="chartSkeleton" class="skeleton-shell absolute inset-0 z-[1] grid grid-cols-1 gap-4 p-6">
                            <div class="skeleton-block h-6 w-40 rounded-lg"></div>
                            <div class="skeleton-block h-full min-h-[220px] rounded-2xl"></div>
                        </div>
                        <canvas id="forecastSummaryChart" class="!h-[320px] !w-full"></canvas>
                    </div>
                </div>
                <div class="reveal flex flex-col justify-between rounded-3xl bg-gradient-to-br from-primary to-primary-container p-8 text-white shadow-glow relative overflow-hidden">
                    <div class="bg-white/5 absolute -right-10 -top-10 h-32 w-32 rounded-full pointer-events-none"></div>
                    <div class="relative z-10">
                        <h3 class="mb-6 text-lg font-bold tracking-tight">Ringkasan Status Model</h3>
                        <div class="space-y-4 text-sm">
                            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                                <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-secondary ring-4 ring-secondary/20"></span>Safe (MAPE &le; 10%)</span>
                                <strong class="font-outfit text-base"><?= e((string) $safeCount) ?></strong>
                            </div>
                            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                                <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-[#f59e0b] ring-4 ring-[#f59e0b]/20"></span>Watchlist (10% - 20%)</span>
                                <strong class="font-outfit text-base"><?= e((string) $watchCount) ?></strong>
                            </div>
                            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                                <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-error ring-4 ring-error/20"></span>Warning (MAPE &gt; 20%)</span>
                                <strong class="font-outfit text-base"><?= e((string) $warningCount) ?></strong>
                            </div>
                            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                                <span class="text-white/70">Train / Test Samples</span>
                                <strong class="font-outfit text-base"><?= e($trainSamples) ?> / <?= e($testSamples) ?></strong>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-white/70">Best Training Epoch</span>
                                <strong class="font-outfit text-base"><?= e($bestEpoch) ?></strong>
                            </div>
                        </div>
                    </div>
                    <a href="<?= e(base_url('/login')) ?>" class="interactive-button mt-8 flex items-center justify-center gap-2 rounded-xl bg-white py-3.5 text-xs font-extrabold uppercase tracking-widest text-primary shadow-lg transition-transform hover:-translate-y-0.5" data-magnetic>
                        Buka Panel Admin
                        <span class="material-symbols-outlined text-sm font-bold">arrow_forward</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- COMMODITIES GRID -->
    <?php if (!empty($komoditasList)): ?>
        <section id="komoditas" class="bg-surface-container-low py-24">
            <div class="mx-auto max-w-screen-2xl px-5 sm:px-8">
                <div class="reveal mx-auto mb-12 max-w-3xl text-center">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.25em] label-gradient">Komoditas Pangan</p>
                    <div class="accent-underline mb-5"></div>
                    <h2 class="mb-5 text-4xl font-bold text-primary sm:text-5xl">Seluruh Komoditas yang Dipantau</h2>
                    <p class="text-lg text-on-surface-variant">Sistem memantau <strong class="text-primary"><?= e((string) $komoditasTotal) ?></strong> komoditas strategis. Setiap komoditas memiliki model prediksi dan histori stok sendiri.</p>
                </div>
                <div class="reveal grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    <?php foreach ($komoditasList as $komoditas): ?>
                        <?php
                        $namaKomoditas = (string) ($komoditas['nama_komoditas'] ?? '-');
                        $kodeKomoditas = (string) ($komoditas['kode_komoditas'] ?? '-');
                        $satuanKomoditas = (string) ($komoditas['satuan'] ?? '-');
                        $icon = $resolveCommodityIcon($namaKomoditas);
                        ?>
                        <div class="commodity-chip rounded-xl border border-outline-variant/20 bg-surface-container-lowest/70 p-5 text-center">
                            <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                                <span class="material-symbols-outlined"><?= e($icon) ?></span>
                            </span>
                            <h4 class="text-sm font-bold text-primary"><?= e($namaKomoditas) ?></h4>
                            <p class="mt-1 text-[11px] font-semibold uppercase tracking-widest text-on-surface-variant"><?= e($kodeKomoditas) ?></p>
                            <p class="mt-2 text-[11px] text-on-surface-variant">Satuan: <strong><?= e($satuanKomoditas) ?></strong></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- PREDICTIONS TABLE -->
    <section id="prediksi" class="bg-surface py-24">
        <div class="mx-auto max-w-screen-2xl px-5 sm:px-8">
            <div class="reveal mb-12 text-center">
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.25em] label-gradient">Detail Prediksi</p>
                <div class="accent-underline mb-5"></div>
                <h2 class="mb-5 text-4xl font-bold text-primary sm:text-5xl">Tabel Forecast per Komoditas</h2>
                <p class="mx-auto max-w-2xl text-lg text-on-surface-variant">Jelajahi seluruh hasil prediksi dengan filter komoditas, status akurasi, atau kata kunci lokasi gudang.</p>
            </div>

            <div class="reveal mb-8 rounded-xl border border-outline-variant/20 bg-surface-container-lowest p-5 shadow-sm sm:p-6">
                <form action="<?= e(base_url('/')) ?>#prediksi" method="get" class="grid grid-cols-1 gap-4 lg:grid-cols-[1.4fr_1fr_1fr_auto_auto] lg:items-end">
                    <div>
                        <label for="search" class="mb-2 block text-xs font-bold uppercase tracking-[0.2em] text-on-surface-variant">Cari</label>
                        <input id="search" name="search" type="text" value="<?= e($forecastSearch) ?>" placeholder="Komoditas, tanggal, lokasi gudang" class="w-full rounded-xl border border-outline-variant/30 bg-surface px-4 py-3 text-sm text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10">
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
                    <a href="<?= e(base_url('/')) ?>#prediksi" class="rounded-xl border border-outline-variant/30 px-6 py-3 text-center text-sm font-bold text-primary transition hover:bg-surface-container-low">
                        Reset
                    </a>
                </form>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-on-surface-variant">
                    <span>Total data forecast: <strong class="text-primary"><?= e((string) $forecastTotalItems) ?></strong></span>
                    <span>Menampilkan <strong class="text-primary"><?= e((string) count($forecastTableItems)) ?></strong> dari <strong class="text-primary"><?= e((string) $forecastPerPage) ?></strong> per halaman</span>
                </div>
            </div>

            <div class="reveal table-wrap relative overflow-hidden rounded-xl border border-outline-variant/20 bg-surface-container-lowest shadow-sm">
                <div id="tableSkeleton" class="skeleton-shell absolute inset-0 z-[1] border-b border-outline-variant/20 bg-surface-container-lowest p-6">
                    <div class="grid grid-cols-1 gap-4">
                        <div class="skeleton-block h-5 w-52 rounded"></div>
                        <div class="skeleton-block h-12 rounded-xl"></div>
                        <div class="skeleton-block h-12 rounded-xl"></div>
                        <div class="skeleton-block h-12 rounded-xl"></div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-surface-container-low text-xs font-bold uppercase tracking-widest text-on-surface-variant">
                                <th class="px-6 py-5">Batch</th>
                                <th class="px-6 py-5">Komoditas</th>
                                <th class="px-6 py-5">Tanggal Forecast</th>
                                <th class="px-6 py-5">Horizon</th>
                                <th class="px-6 py-5">Forecast</th>
                                <th class="px-6 py-5">Aktual Terbaru</th>
                                <th class="px-6 py-5">RMSE / MAE</th>
                                <th class="px-6 py-5">MAPE</th>
                                <th class="px-6 py-5">Lokasi</th>
                                <th class="px-6 py-5">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-container-low">
                            <?php if ($forecastTableItems !== []): ?>
                                <?php foreach ($forecastTableItems as $row): ?>
                                    <?php
                                    $mapeValue = (float) ($row['mape'] ?? 0);
                                    $rowStatusClass = 'bg-secondary-container text-on-secondary-container';
                                    $rowStatusLabel = 'Safe';
                                    if ($mapeValue > 20.0) {
                                        $rowStatusClass = 'bg-error-container text-on-error-container';
                                        $rowStatusLabel = 'Warning';
                                    } elseif ($mapeValue > 10.0) {
                                        $rowStatusClass = 'bg-[#fff1c2] text-[#6b4f00]';
                                        $rowStatusLabel = 'Watchlist';
                                    }
                                    ?>
                                    <tr class="forecast-row transition-colors hover:bg-surface-container-low">
                                        <td class="px-6 py-5"><span class="rounded-full bg-surface-container-low px-3 py-1 text-[11px] font-bold text-primary"><?= e((string) $row['batch_code']) ?></span></td>
                                        <td class="px-6 py-5 font-bold text-primary"><?= e((string) $row['komoditas']) ?></td>
                                        <td class="px-6 py-5"><?= e((new DateTime((string) $row['tanggal_forecast']))->format('d F Y')) ?></td>
                                        <td class="px-6 py-5">H+<?= e((string) $row['forecast_horizon_day']) ?></td>
                                        <td class="px-6 py-5"><?= e(number_format((float) $row['forecast_denormalized'], 2, '.', '')) ?> <?= e((string) ($row['satuan'] ?? '')) ?></td>
                                        <td class="px-6 py-5"><?= $row['jumlah_aktual'] !== null ? e(number_format((float) $row['jumlah_aktual'], 2, '.', '')) . ' ' . e((string) ($row['satuan'] ?? '')) : '-' ?></td>
                                        <td class="px-6 py-5 text-on-surface-variant"><?= e(number_format((float) $row['rmse'], 2, '.', '')) ?> / <?= e(number_format((float) $row['mae'], 2, '.', '')) ?></td>
                                        <td class="px-6 py-5 text-on-surface-variant"><?= e(number_format((float) $row['mape'], 2, '.', '')) ?>%</td>
                                        <td class="px-6 py-5 text-on-surface-variant"><?= e((string) ($row['lokasi_gudang'] ?? '-')) ?></td>
                                        <td class="px-6 py-5">
                                            <span class="rounded-full px-3 py-1 text-[10px] font-bold uppercase <?= e($rowStatusClass) ?>">
                                                <?= e($rowStatusLabel) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td class="px-6 py-10 text-center text-on-surface-variant" colspan="10">Belum ada data forecast yang bisa ditampilkan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($forecastTotalPages > 1): ?>
                <div class="reveal mt-8 flex flex-wrap items-center justify-center gap-2">
                    <a href="<?= e(base_url('/') . query_string(['page' => max(1, $forecastCurrentPage - 1)])) ?>#prediksi" class="rounded-xl border border-outline-variant/30 px-4 py-2 text-sm font-semibold text-primary transition hover:bg-surface-container-low">Sebelumnya</a>
                    <?php for ($page = $paginationStart; $page <= $paginationEnd; $page++): ?>
                        <a href="<?= e(base_url('/') . query_string(['page' => $page])) ?>#prediksi" class="rounded-xl px-4 py-2 text-sm font-semibold transition <?= $page === $forecastCurrentPage ? 'bg-primary text-on-primary' : 'border border-outline-variant/30 text-primary hover:bg-surface-container-low' ?>">
                            <?= e((string) $page) ?>
                        </a>
                    <?php endfor; ?>
                    <a href="<?= e(base_url('/') . query_string(['page' => min($forecastTotalPages, $forecastCurrentPage + 1)])) ?>#prediksi" class="rounded-xl border border-outline-variant/30 px-4 py-2 text-sm font-semibold text-primary transition hover:bg-surface-container-low">Berikutnya</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- STOCK SNAPSHOT -->
    <?php if (!empty($stokSummary['latest_snapshot'])): ?>
        <section class="bg-surface-container-low py-24">
            <div class="mx-auto max-w-screen-2xl px-5 sm:px-8">
                <div class="reveal mb-12 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="mb-2 text-xs font-bold uppercase tracking-[0.25em] label-gradient">Snapshot Aktual</p>
                        <div class="mb-3 h-[3px] w-10 rounded-full bg-gradient-to-r from-primary to-secondary"></div>
                        <h2 class="text-4xl font-bold text-primary">Kondisi Stok Terbaru</h2>
                    </div>
                    <p class="max-w-2xl text-on-surface-variant">Data stok historis terbaru sebagai referensi kondisi aktual sebelum dibandingkan dengan hasil forecast.</p>
                </div>
                <div class="reveal overflow-hidden rounded-xl border border-outline-variant/20 bg-surface-container-lowest shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left">
                            <thead>
                                <tr class="bg-surface-container-low text-xs font-bold uppercase tracking-widest text-on-surface-variant">
                                    <th class="px-6 py-5">Komoditas</th>
                                    <th class="px-6 py-5">Jumlah Aktual</th>
                                    <th class="px-6 py-5">Satuan</th>
                                    <th class="px-6 py-5">Lokasi Gudang</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-container-low">
                                <?php foreach ($stokSummary['latest_snapshot'] as $snapshot): ?>
                                    <tr class="snapshot-row transition-colors hover:bg-surface-container-low">
                                        <td class="px-6 py-5 font-bold text-primary">
                                            <div class="flex items-center gap-3">
                                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                                    <span class="material-symbols-outlined text-xl"><?= e($resolveCommodityIcon((string) $snapshot['nama_komoditas'])) ?></span>
                                                </span>
                                                <?= e((string) $snapshot['nama_komoditas']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5"><?= e(number_format((float) $snapshot['jumlah_aktual'], 2, '.', '')) ?></td>
                                        <td class="px-6 py-5"><?= e((string) ($snapshot['satuan'] ?? '-')) ?></td>
                                        <td class="px-6 py-5 text-on-surface-variant"><?= e((string) $snapshot['lokasi_gudang']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- METHODOLOGY -->
    <section class="bg-surface py-24">
        <div class="mx-auto grid max-w-screen-2xl grid-cols-1 items-center gap-16 px-5 sm:px-8 lg:grid-cols-12">
            <div class="reveal lg:col-span-6">
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.25em] label-gradient">Metodologi</p>
                <div class="mb-5 h-[3px] w-10 rounded-full bg-gradient-to-r from-primary to-secondary"></div>
                <h2 class="mb-6 text-4xl font-bold text-primary sm:text-5xl">Mengapa Long Short-Term Memory?</h2>
                <p class="mb-5 leading-relaxed text-on-surface-variant">
                    LSTM adalah varian Recurrent Neural Network yang dirancang untuk menangkap dependensi jangka panjang pada data deret waktu. Arsitektur ini memiliki gerbang memori yang memungkinkan model mengingat pola stok lintas musim dan tahun.
                </p>
                <p class="mb-8 leading-relaxed text-on-surface-variant">
                    Pada sistem ini, setiap komoditas dilatih dengan batch parameter yang seragam. Hasil evaluasi RMSE, MAE, dan MAPE menentukan kelayakan model untuk publikasi.
                </p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-outline-variant/20 bg-surface-container-low p-6">
                        <span class="material-symbols-outlined mb-3 text-primary">memory</span>
                        <h4 class="mb-2 font-bold text-primary">Temporal Memory</h4>
                        <p class="text-sm text-on-surface-variant">Menangkap pola musiman dan siklus permintaan stok.</p>
                    </div>
                    <div class="rounded-xl border border-outline-variant/20 bg-surface-container-low p-6">
                        <span class="material-symbols-outlined mb-3 text-primary">precision_manufacturing</span>
                        <h4 class="mb-2 font-bold text-primary">Metric Driven</h4>
                        <p class="text-sm text-on-surface-variant">Evaluasi menggunakan metrik error standar deret waktu.</p>
                    </div>
                </div>
            </div>
            <div class="reveal lg:col-span-6">
                <div class="overflow-hidden rounded-xl border border-outline-variant/20 shadow-panel">
                    <img class="aspect-video w-full object-cover" alt="Visualisasi arsitektur jaringan LSTM" src="<?= e($methodImage) ?>">
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="bg-surface-container-low py-24">
        <div class="mx-auto max-w-4xl px-5 sm:px-8">
            <div class="reveal mb-12 text-center">
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.25em] label-gradient">FAQ</p>
                <div class="accent-underline mb-5"></div>
                <h2 class="mb-5 text-4xl font-bold text-primary sm:text-5xl">Pertanyaan yang Sering Diajukan</h2>
                <p class="text-lg leading-relaxed text-on-surface-variant">Ringkasan informasi penting tentang sistem forecasting stok pangan ini.</p>
            </div>
            <div class="reveal space-y-3">
                <?php foreach ($faqs as $index => $faq): ?>
                    <details class="faq-item faq-item-v2 group rounded-2xl border border-outline-variant/20 bg-surface-container-lowest p-5 transition hover:border-primary/25 hover:shadow-sm"<?= $index === 0 ? ' open' : '' ?>>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left font-bold text-primary">
                            <span class="text-[15px]"><?= e($faq['question']) ?></span>
                            <span class="faq-icon material-symbols-outlined flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">add</span>
                        </summary>
                        <p class="mt-4 leading-relaxed text-on-surface-variant"><?= e($faq['answer']) ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- FINAL CTA -->
    <section id="cta" class="py-24">
        <div class="mx-auto max-w-screen-2xl px-5 sm:px-8">
            <div class="reveal cta-pattern-v2 relative overflow-hidden rounded-2xl p-10 text-on-primary shadow-glow sm:p-16">
                <div class="relative z-10 grid grid-cols-1 items-center gap-10 lg:grid-cols-[1.5fr_1fr]">
                    <div>
                        <p class="mb-3 text-xs font-bold uppercase tracking-[0.25em] text-secondary-fixed">Siap Memulai?</p>
                        <h2 class="mb-5 text-3xl font-bold leading-tight sm:text-5xl">
                            Akses dashboard admin untuk mengelola data dan menjalankan training model.
                        </h2>
                        <p class="max-w-xl text-lg text-on-primary/80">
                            Masuk dengan kredensial Dinas Pangan untuk mengatur komoditas, mengunggah stok historis baru, dan menjalankan batch training LSTM.
                        </p>
                    </div>
                    <div class="flex flex-col gap-3">
                        <a href="<?= e(base_url('/login')) ?>" class="interactive-button flex items-center justify-center gap-2 rounded-xl bg-white px-8 py-4 font-bold text-primary shadow-md transition-all hover:-translate-y-0.5 hover:shadow-xl" data-magnetic>
                            <span class="material-symbols-outlined text-[20px]">lock_open</span>
                            Masuk Sistem
                            <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
                        </a>
                        <a href="#dashboard" class="interactive-button flex items-center justify-center gap-2 rounded-xl border-2 border-white/30 bg-transparent px-8 py-4 font-bold text-white transition-all hover:bg-white/10 hover:border-white/50" data-magnetic>
                            <span class="material-symbols-outlined text-[20px]">preview</span>
                            Lihat Pratinjau Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>
<!-- MASCOT Si Padi Cerdas -->
<div class="mascot-shell" id="mascotShell">
    <div class="mascot-bubble is-hidden" id="mascotBubble">Halo! Ada yang bisa saya bantu tentang sistem forecasting?</div>
    <div class="mascot-card is-hidden" id="mascotCard" role="dialog" aria-labelledby="mascotTitle">
        <div class="mascot-header">
            <div class="mascot-title-wrap">
                <div class="mascot-avatar is-curious" id="mascotAvatar" aria-hidden="true">
                    <img id="mascotAvatarFace" src="<?= e($mascotFaces['curious']) ?>" alt="Ekspresi maskot Si Padi">
                </div>
                <div>
                    <div class="mascot-status font-outfit">Asisten Interaktif</div>
                    <strong id="mascotTitle" class="block text-sm tracking-tight font-bold">Si Padi Cerdas</strong>
                </div>
            </div>
            <div class="mascot-actions">
                <button type="button" class="mascot-icon-btn" id="mascotSpeakButton" aria-label="Bacakan pesan" title="Bacakan">
                    <span class="material-symbols-outlined text-base">volume_up</span>
                </button>
                <button type="button" class="mascot-icon-btn" id="mascotMinimize" aria-label="Tutup panel" title="Tutup">
                    <span class="material-symbols-outlined text-base">close</span>
                </button>
            </div>
        </div>
        <div class="mascot-body custom-scrollbar" id="mascotBody">
            <div class="mascot-tip" id="mascotTipBox">Tip: saya akan mengubah ekspresi ketika Anda berpindah bagian halaman.</div>
            
            <!-- Chat Log container representing real messages -->
            <div id="mascotChatLog" class="flex flex-col gap-3 overflow-y-auto pr-1 flex-1 custom-scrollbar min-h-[160px] max-h-[220px]">
                <div class="mascot-message-bot">
                    Halo! Saya <strong>Si Padi Cerdas</strong>. Saya siap menjawab pertanyaan seputar sistem prediksi stok pangan Lhokseumawe ini. Silakan pilih pertanyaan di bawah atau ketik langsung pertanyaan Anda!
                </div>
            </div>
            
            <p class="text-[9px] font-extrabold uppercase tracking-widest text-slate-400 mt-2 mb-1.5">Pertanyaan Cepat</p>
            <div class="mascot-chip-row custom-scrollbar overflow-x-auto pb-1 flex flex-wrap gap-1.5 max-h-[100px] overflow-y-auto">
                <?php foreach ($mascotQuickQuestions as $index => $qa): ?>
                    <button type="button" class="mascot-chip" data-mascot-answer="<?= e($qa['answer']) ?>" data-mascot-question="<?= e($qa['question']) ?>">
                        <?= e($qa['question']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="mascot-input-wrap">
            <input type="text" class="mascot-input" id="mascotInput" placeholder="Tanyakan tentang LSTM, akurasi...">
            <button type="button" class="mascot-send" id="mascotSend" aria-label="Kirim pertanyaan">
                <span class="material-symbols-outlined text-base">send</span>
            </button>
        </div>
    </div>
    <button type="button" class="mascot-toggle" id="mascotToggle" aria-expanded="false">
        <span class="mascot-avatar is-curious" aria-hidden="true">
            <img id="mascotToggleFace" src="<?= e($mascotFaces['curious']) ?>" alt="Maskot Si Padi Cerdas">
        </span>
        <span id="mascotToggleLabel" class="font-bold">Tanya Si Padi</span>
    </button>
</div>

<footer class="border-t border-outline-variant/20 bg-[#0f1c2e] text-white/80">
    <div class="mx-auto max-w-screen-2xl px-5 py-16 sm:px-8">
        <div class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-1">
                <div class="mb-4 flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-on-primary">
                        <span class="material-symbols-outlined">agriculture</span>
                    </span>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.2em] text-white/60">Dinas Pangan</div>
                        <div class="text-lg font-bold text-white">Stok Pangan Lhokseumawe</div>
                    </div>
                </div>
                <p class="text-sm leading-relaxed text-white/70">
                    Sistem pemantauan dan prediksi stok pangan strategis berbasis machine learning untuk mendukung ketahanan pangan Kota Lhokseumawe.
                </p>
            </div>
            <div>
                <h4 class="mb-4 text-sm font-bold uppercase tracking-widest text-white">Navigasi</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="#beranda" class="text-white/70 transition hover:text-white">Beranda</a></li>
                    <li><a href="#fitur" class="text-white/70 transition hover:text-white">Fitur</a></li>
                    <li><a href="#cara-kerja" class="text-white/70 transition hover:text-white">Cara Kerja</a></li>
                    <li><a href="#dashboard" class="text-white/70 transition hover:text-white">Dashboard</a></li>
                    <li><a href="#prediksi" class="text-white/70 transition hover:text-white">Prediksi</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-4 text-sm font-bold uppercase tracking-widest text-white">Sumber Daya</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="#faq" class="text-white/70 transition hover:text-white">FAQ</a></li>
                    <li><a href="#cara-kerja" class="text-white/70 transition hover:text-white">Metodologi LSTM</a></li>
                    <li><a href="<?= e(base_url('/login')) ?>" class="text-white/70 transition hover:text-white">Portal Admin</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-4 text-sm font-bold uppercase tracking-widest text-white">Kontak</h4>
                <ul class="space-y-3 text-sm text-white/70">
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-base text-white">location_on</span>
                        <span>Kota Lhokseumawe, Aceh, Indonesia</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-base text-white">schedule</span>
                        <span>Senin - Jumat, 08:00 - 16:00 WIB</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-base text-white">apartment</span>
                        <span>Dinas Pangan Kota Lhokseumawe</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-8 text-sm text-white/60 md:flex-row">
            <p>&copy; <?= e(date('Y')) ?> Dinas Pangan Kota Lhokseumawe. Seluruh hak cipta dilindungi.</p>
            <p>Dibangun dengan algoritma <strong class="text-white">Long Short-Term Memory</strong></p>
        </div>
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
        const chartSkeleton = document.getElementById('chartSkeleton');
        const tableSkeleton = document.getElementById('tableSkeleton');
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const mobileNavPanel = document.getElementById('mobileNavPanel');

        if (canvas && labels.length > 0) {
            const ctx = canvas.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(15, 59, 117, 0.28)');
            gradient.addColorStop(1, 'rgba(13, 148, 136, 0.01)');

            new Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Prediksi Stok',
                        data: values,
                        borderColor: '#0f3b75',
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.35,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#0d9488',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f3b75',
                            titleFont: { size: 13, weight: 'bold', family: 'Plus Jakarta Sans' },
                            bodyFont: { size: 12, family: 'Plus Jakarta Sans' },
                            padding: 12,
                            cornerRadius: 12,
                            borderColor: 'rgba(255,255,255,0.1)',
                            borderWidth: 1,
                            callbacks: {
                                title: (items) => items[0] ? `${commodities[items[0].dataIndex]} - ${items[0].label}` : '',
                                label: (context) => ` Forecast: ${Number(context.raw).toLocaleString('id-ID', { maximumFractionDigits: 2 })}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(148, 163, 184, 0.06)' },
                            ticks: { color: '#64748b', maxRotation: 0, autoSkip: true, maxTicksLimit: 6, font: { family: 'Plus Jakarta Sans', weight: 'bold', size: 10 } }
                        },
                        y: {
                            beginAtZero: false,
                            grid: { color: 'rgba(148, 163, 184, 0.06)' },
                            ticks: { color: '#64748b', font: { family: 'Plus Jakarta Sans', size: 10 } }
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
                    const isActive = link.getAttribute('href') === `#${id}`;
                    link.classList.toggle('is-active', isActive);
                    link.classList.toggle('text-primary', isActive);
                    link.classList.toggle('text-on-surface-variant', !isActive);
                });
            });
        }, { threshold: 0.45 });

        sections.forEach((section) => sectionObserver.observe(section));

        const reveals = document.querySelectorAll('.reveal');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');

                    if (entry.target.closest('#beranda') || entry.target.closest('#dashboard')) {
                        activateProgress();
                    }

                    entry.target.querySelectorAll('.stat-number[data-count]').forEach(animateCounter);
                }
            });
        }, { threshold: 0.12 });

        reveals.forEach((item) => observer.observe(item));
        counterElements.forEach((item) => observer.observe(item.closest('.reveal') || item));

        tiltElements.forEach((element) => {
            element.addEventListener('mousemove', (event) => {
                const rect = element.getBoundingClientRect();
                const x = ((event.clientX - rect.left) / rect.width) - 0.5;
                const y = ((event.clientY - rect.top) / rect.height) - 0.5;
                element.style.transform = `perspective(900px) rotateX(${(-y * 4).toFixed(2)}deg) rotateY(${(x * 5).toFixed(2)}deg) translateY(-4px)`;
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
                element.style.transform = `translate(${x * 0.06}px, ${y * 0.06}px)`;
            });

            element.addEventListener('mouseleave', () => {
                element.style.transform = '';
            });
        });

        if (mobileMenuButton && mobileNavPanel) {
            mobileMenuButton.addEventListener('click', () => {
                const expanded = mobileMenuButton.getAttribute('aria-expanded') === 'true';
                mobileMenuButton.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                mobileNavPanel.classList.toggle('hidden');
            });

            mobileNavPanel.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => {
                    mobileMenuButton.setAttribute('aria-expanded', 'false');
                    mobileNavPanel.classList.add('hidden');
                });
            });
        }

        // ===================== MASCOT SI PADI =====================
        const mascotFaces = <?= json_encode($mascotFaces, JSON_UNESCAPED_UNICODE) ?>;
        const mascotKnowledge = <?= json_encode($mascotQuickQuestions, JSON_UNESCAPED_UNICODE) ?>;
        const mascotSectionTips = <?= json_encode($mascotSectionTips, JSON_UNESCAPED_UNICODE) ?>;

        const mascotShell = document.getElementById('mascotShell');
        const mascotToggle = document.getElementById('mascotToggle');
        const mascotCard = document.getElementById('mascotCard');
        const mascotBubble = document.getElementById('mascotBubble');
        const mascotChatLog = document.getElementById('mascotChatLog');
        const mascotTipBox = document.getElementById('mascotTipBox');
        const mascotAvatar = document.getElementById('mascotAvatar');
        const mascotAvatarFace = document.getElementById('mascotAvatarFace');
        const mascotToggleFace = document.getElementById('mascotToggleFace');
        const mascotToggleLabel = document.getElementById('mascotToggleLabel');
        const mascotSpeakButton = document.getElementById('mascotSpeakButton');
        const mascotMinimize = document.getElementById('mascotMinimize');
        const mascotChips = document.querySelectorAll('[data-mascot-answer]');
        const mascotInput = document.getElementById('mascotInput');
        const mascotSend = document.getElementById('mascotSend');

        let mascotBubbleTimer = null;
        let speechVoice = null;
        let activeUtterance = null;
        let mascotTypingTimeout = null;

        const setMascotMood = (mood) => {
            if (!mascotAvatar) return;
            const faceKey = mood === 'is-excited' ? 'excited' : (mood === 'is-alert' ? 'alert' : 'curious');

            [mascotAvatar, mascotToggle?.querySelector('.mascot-avatar')].forEach((el) => {
                if (!el) return;
                el.classList.remove('is-curious', 'is-excited', 'is-alert');
                el.classList.add(mood);
            });
            if (mascotAvatarFace && mascotFaces[faceKey]) mascotAvatarFace.src = mascotFaces[faceKey];
            if (mascotToggleFace && mascotFaces[faceKey]) mascotToggleFace.src = mascotFaces[faceKey];
        };

        const showMascotBubble = (message) => {
            if (!mascotBubble) return;
            mascotBubble.textContent = message;
            mascotBubble.classList.remove('is-hidden');
            if (mascotBubbleTimer) window.clearTimeout(mascotBubbleTimer);
            mascotBubbleTimer = window.setTimeout(() => {
                mascotBubble.classList.add('is-hidden');
            }, 4500);
        };

        const loadSpeechVoice = () => {
            if (!('speechSynthesis' in window)) return;
            const voices = window.speechSynthesis.getVoices();
            speechVoice = voices.find((v) => /^id/i.test(v.lang))
                || voices.find((v) => /^ms/i.test(v.lang))
                || voices.find((v) => /^en/i.test(v.lang))
                || voices[0]
                || null;
        };

        const speakText = (text) => {
            if (!('speechSynthesis' in window) || !text) return;
            window.speechSynthesis.cancel();
            
            const temp = document.createElement('div');
            temp.innerHTML = text;
            const cleanText = temp.textContent || temp.innerText || "";
            
            activeUtterance = new SpeechSynthesisUtterance(cleanText);
            loadSpeechVoice();
            if (speechVoice) {
                activeUtterance.voice = speechVoice;
                activeUtterance.lang = speechVoice.lang;
            } else {
                activeUtterance.lang = 'id-ID';
            }
            activeUtterance.rate = 1.05;
            activeUtterance.pitch = 1.05;

            mascotSpeakButton?.classList.add('is-speaking');
            activeUtterance.onend = () => mascotSpeakButton?.classList.remove('is-speaking');
            activeUtterance.onerror = () => mascotSpeakButton?.classList.remove('is-speaking');
            window.speechSynthesis.speak(activeUtterance);
        };

        const stopSpeech = () => {
            if ('speechSynthesis' in window) window.speechSynthesis.cancel();
            mascotSpeakButton?.classList.remove('is-speaking');
        };

        const appendUserBubble = (text) => {
            if (!mascotChatLog) return;
            const msgNode = document.createElement('div');
            msgNode.className = 'mascot-message-user reveal visible';
            msgNode.textContent = text;
            mascotChatLog.appendChild(msgNode);
            mascotChatLog.scrollTop = mascotChatLog.scrollHeight;
        };

        const appendBotBubble = (text, shouldSpeak = true) => {
            if (!mascotChatLog) return;
            if (mascotTypingTimeout) window.clearTimeout(mascotTypingTimeout);

            const msgNode = document.createElement('div');
            msgNode.className = 'mascot-message-bot reveal visible';
            msgNode.innerHTML = '<span class="mascot-typing"><span></span><span></span><span></span></span>';
            mascotChatLog.appendChild(msgNode);
            mascotChatLog.scrollTop = mascotChatLog.scrollHeight;

            let i = 0;
            const delay = Math.max(8, Math.min(24, 1200 / Math.max(text.length, 1)));

            const step = () => {
                if (i === 0) msgNode.textContent = '';
                if (i < text.length) {
                    msgNode.textContent = text.slice(0, i + 1);
                    i++;
                    mascotTypingTimeout = window.setTimeout(step, delay);
                } else {
                    msgNode.textContent = text;
                    if (shouldSpeak) speakText(text);
                }
                mascotChatLog.scrollTop = mascotChatLog.scrollHeight;
            };
            mascotTypingTimeout = window.setTimeout(step, 650);
        };

        const findAnswer = (query) => {
            const q = query.toLowerCase().trim();
            if (!q) return null;
            let bestMatch = null;
            let bestScore = 0;

            mascotKnowledge.forEach((entry) => {
                let score = 0;
                (entry.keywords || []).forEach((kw) => {
                    if (q.includes(kw.toLowerCase())) score += 2;
                });
                if (entry.question && q.includes(entry.question.toLowerCase().slice(0, 6))) score += 1;
                if (score > bestScore) {
                    bestScore = score;
                    bestMatch = entry;
                }
            });

            if (bestMatch) return bestMatch.answer;
            if (q.includes('halo') || q.includes('hai') || q.includes('hi')) {
                return 'Halo! Saya Si Padi Cerdas. Silakan tanyakan apa saja tentang sistem forecasting stok pangan Lhokseumawe ini.';
            }
            if (q.includes('terima kasih') || q.includes('makasih') || q.includes('thanks')) {
                return 'Sama-sama. Senang bisa membantu Anda memahami sistem ini!';
            }
            return 'Maaf, saya belum memiliki informasi lengkap tentang hal tersebut. Cobalah menanyakan tentang: akurasi model, komoditas, cara kerja LSTM, atau dashboard.';
        };

        const askMascot = (question, forcedAnswer = null) => {
            appendUserBubble(question);
            const answer = forcedAnswer || findAnswer(question);
            setMascotMood('is-excited');
            
            appendBotBubble(answer, true);
            showMascotBubble(answer.length > 80 ? answer.slice(0, 76) + '…' : answer);
            
            window.setTimeout(() => setMascotMood('is-curious'), 3000);
        };

        const setMascotOpen = (open) => {
            if (!mascotCard || !mascotToggle) return;
            mascotCard.classList.toggle('is-hidden', !open);
            mascotToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (mascotToggleLabel) mascotToggleLabel.textContent = open ? 'Tutup' : 'Tanya Si Padi';
            if (!open) stopSpeech();
        };

        if ('speechSynthesis' in window) {
            loadSpeechVoice();
            window.speechSynthesis.onvoiceschanged = loadSpeechVoice;
        }

        // Section tips
        const mascotSectionObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                const id = entry.target.getAttribute('id');
                if (mascotTipBox && mascotSectionTips[id]) {
                    mascotTipBox.textContent = mascotSectionTips[id];
                }
                if (id === 'dashboard' || id === 'prediksi') setMascotMood('is-excited');
                else if (id === 'cta') setMascotMood('is-alert');
                else setMascotMood('is-curious');
            });
        }, { threshold: 0.35 });
        sections.forEach((section) => mascotSectionObserver.observe(section));

        // Initial greeting
        const mascotVisited = window.localStorage.getItem('mascot-first-visit-v2');
        window.setTimeout(() => {
            if (mascotVisited !== 'yes') {
                showMascotBubble('Halo! Klik saya untuk mulai bertanya tentang sistem forecasting.');
                window.localStorage.setItem('mascot-first-visit-v2', 'yes');
            }
        }, 1400);

        mascotToggle?.addEventListener('click', () => {
            const shouldOpen = mascotCard.classList.contains('is-hidden');
            setMascotOpen(shouldOpen);
            if (shouldOpen) setMascotMood('is-excited');
        });
        mascotMinimize?.addEventListener('click', () => setMascotOpen(false));
        mascotSpeakButton?.addEventListener('click', () => {
            if (mascotSpeakButton.classList.contains('is-speaking')) {
                stopSpeech();
            } else {
                const botBubbles = mascotChatLog.querySelectorAll('.mascot-message-bot');
                if (botBubbles.length > 0) {
                    const lastBotText = botBubbles[botBubbles.length - 1].textContent || '';
                    if (lastBotText) speakText(lastBotText);
                }
            }
        });

        mascotChips.forEach((chip) => {
            chip.addEventListener('click', () => {
                const answer = chip.getAttribute('data-mascot-answer') || '';
                const question = chip.getAttribute('data-mascot-question') || '';
                askMascot(question, answer);
            });
        });

        const submitMascot = () => {
            const query = mascotInput?.value?.trim() || '';
            if (!query) return;
            askMascot(query);
            mascotInput.value = '';
        };
        mascotSend?.addEventListener('click', submitMascot);
        mascotInput?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                submitMascot();
            }
        });

        // Ambient mouse tracking for mascot avatar
        document.addEventListener('mousemove', (event) => {
            if (!mascotAvatar || mascotCard.classList.contains('is-hidden')) return;
            const rect = mascotAvatar.getBoundingClientRect();
            const x = event.clientX - (rect.left + rect.width / 2);
            const y = event.clientY - (rect.top + rect.height / 2);
            mascotAvatar.style.transform = `rotateX(${(-y * 0.03).toFixed(2)}deg) rotateY(${(x * 0.04).toFixed(2)}deg)`;
        });

        // Hover on chart / table changes mascot mood
        canvas?.addEventListener('mouseenter', () => {
            setMascotMood('is-excited');
            showMascotBubble('Hover titik grafik untuk melihat detail forecast per komoditas dan tanggal.');
        });
        document.querySelector('#prediksi .table-wrap')?.addEventListener('mouseenter', () => {
            setMascotMood('is-alert');
            showMascotBubble('Gunakan filter di atas tabel untuk menyaring forecast sesuai kebutuhan Anda.');
        });
    })();
</script>

</body>
</html>
