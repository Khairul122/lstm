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
                radial-gradient(1200px 600px at 90% 10%, rgba(167, 200, 255, 0.35), transparent 60%),
                radial-gradient(900px 500px at 10% 90%, rgba(174, 238, 203, 0.28), transparent 60%),
                linear-gradient(135deg, #f9f9f8 0%, #eef2f8 100%);
        }
        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(66, 71, 80, 0.18), transparent);
        }
        .feature-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 1px;
            background: linear-gradient(135deg, rgba(0, 51, 102, 0.25), rgba(44, 105, 78, 0.15));
            -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity .3s ease;
        }
        .feature-card:hover::before {
            opacity: 1;
        }
        .step-connector {
            position: absolute;
            left: 50%;
            top: 44px;
            width: 100%;
            height: 2px;
            border-top: 2px dashed rgba(0, 51, 102, 0.25);
            z-index: 0;
        }
        .step-connector:last-of-type { display: none; }
        .faq-item[open] summary .faq-toggle {
            transform: rotate(45deg);
        }
        .commodity-chip {
            transition: transform .25s ease, box-shadow .25s ease, background-color .25s ease;
        }
        .commodity-chip:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.10);
            background-color: #ffffff;
        }
        .cta-pattern {
            background:
                radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.12) 0%, transparent 50%),
                linear-gradient(135deg, #003366 0%, #234a7e 100%);
        }

        /* Enhanced Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(40px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes bounceIn {
            0% { opacity: 0; transform: scale(0.3); }
            50% { opacity: 1; transform: scale(1.06); }
            70% { transform: scale(0.96); }
            100% { transform: scale(1); }
        }
        @keyframes breathe {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.04); }
        }
        @keyframes wiggle {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-6deg); }
            75% { transform: rotate(6deg); }
        }
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); opacity: .4; }
            30% { transform: translateY(-4px); opacity: 1; }
        }
        @keyframes rippleOut {
            0% { transform: scale(0.8); opacity: .7; }
            100% { transform: scale(1.8); opacity: 0; }
        }

        .stagger-item { opacity: 0; }
        .stagger-item.visible { animation: fadeInUp .7s ease forwards; }
        .stagger-item:nth-child(1) { animation-delay: .0s; }
        .stagger-item:nth-child(2) { animation-delay: .08s; }
        .stagger-item:nth-child(3) { animation-delay: .16s; }
        .stagger-item:nth-child(4) { animation-delay: .24s; }
        .stagger-item:nth-child(5) { animation-delay: .32s; }
        .stagger-item:nth-child(6) { animation-delay: .40s; }

        /* Mascot */
        .mascot-shell {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 60;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
            pointer-events: none;
        }
        .mascot-shell > * { pointer-events: auto; }
        .mascot-bubble {
            max-width: 260px;
            padding: 12px 16px;
            border-radius: 18px 18px 4px 18px;
            background: #ffffff;
            color: #1a1c1b;
            font-size: 13px;
            line-height: 1.5;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
            border: 1px solid rgba(0, 51, 102, 0.08);
            animation: bounceIn .45s ease;
        }
        .mascot-bubble.is-hidden { display: none; }
        .mascot-card {
            width: 360px;
            max-width: calc(100vw - 40px);
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.25);
            border: 1px solid rgba(0, 51, 102, 0.08);
            overflow: hidden;
            animation: bounceIn .5s ease;
            display: flex;
            flex-direction: column;
            max-height: 560px;
        }
        .mascot-card.is-hidden { display: none; }
        .mascot-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 18px;
            background: linear-gradient(135deg, #003366 0%, #234a7e 100%);
            color: #ffffff;
        }
        .mascot-title-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .mascot-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255,255,255,.18);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            transition: transform .25s ease;
            position: relative;
        }
        .mascot-avatar.is-curious { animation: breathe 3s ease-in-out infinite; }
        .mascot-avatar.is-excited { animation: wiggle .6s ease-in-out 2; }
        .mascot-avatar.is-alert { animation: breathe 1.5s ease-in-out infinite; }
        .mascot-avatar img {
            width: 36px;
            height: 36px;
            object-fit: contain;
        }
        .mascot-status {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            opacity: .75;
        }
        .mascot-actions {
            display: flex;
            gap: 6px;
        }
        .mascot-icon-btn {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: rgba(255,255,255,.15);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .2s ease, transform .2s ease;
            border: none;
            cursor: pointer;
        }
        .mascot-icon-btn:hover { background: rgba(255,255,255,.28); transform: scale(1.05); }
        .mascot-icon-btn.is-speaking {
            background: #aeeecb;
            color: #003366;
            animation: breathe 1s ease-in-out infinite;
        }
        .mascot-body {
            padding: 16px 18px;
            overflow-y: auto;
            flex: 1;
        }
        .mascot-message {
            background: #f3f4f2;
            border-radius: 14px 14px 14px 4px;
            padding: 12px 14px;
            font-size: 13px;
            line-height: 1.55;
            color: #1a1c1b;
            margin-bottom: 14px;
            min-height: 60px;
        }
        .mascot-typing {
            display: inline-flex;
            gap: 4px;
        }
        .mascot-typing span {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #424750;
            animation: typing 1.2s infinite;
        }
        .mascot-typing span:nth-child(2) { animation-delay: .15s; }
        .mascot-typing span:nth-child(3) { animation-delay: .3s; }

        .mascot-tip {
            font-size: 11px;
            color: #424750;
            background: #eef4ff;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 14px;
            border-left: 3px solid #003366;
        }
        .mascot-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 14px;
        }
        .mascot-chip {
            font-size: 11px;
            font-weight: 600;
            padding: 6px 12px;
            background: #f3f4f2;
            border: 1px solid rgba(0, 51, 102, 0.1);
            border-radius: 999px;
            color: #003366;
            cursor: pointer;
            transition: background .2s ease, transform .2s ease, border-color .2s ease;
        }
        .mascot-chip:hover {
            background: #003366;
            color: #ffffff;
            border-color: #003366;
            transform: translateY(-1px);
        }
        .mascot-input-wrap {
            display: flex;
            gap: 6px;
            padding: 12px 14px;
            border-top: 1px solid rgba(66, 71, 80, 0.1);
            background: #f9f9f8;
        }
        .mascot-input {
            flex: 1;
            border: 1px solid rgba(66, 71, 80, 0.18);
            border-radius: 12px;
            padding: 8px 12px;
            font-size: 13px;
            outline: none;
            transition: border-color .2s ease;
        }
        .mascot-input:focus { border-color: #003366; }
        .mascot-send {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: #003366;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
            transition: background .2s ease, transform .2s ease;
        }
        .mascot-send:hover { background: #234a7e; transform: scale(1.05); }
        .mascot-toggle {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #003366;
            color: #ffffff;
            border: none;
            border-radius: 999px;
            padding: 10px 18px 10px 10px;
            box-shadow: 0 20px 45px rgba(0, 51, 102, 0.32);
            cursor: pointer;
            transition: transform .25s ease, box-shadow .25s ease;
            font-weight: 600;
            font-size: 13px;
            position: relative;
        }
        .mascot-toggle::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 999px;
            background: #003366;
            opacity: .4;
            animation: rippleOut 2.5s ease-out infinite;
            z-index: -1;
        }
        .mascot-toggle:hover { transform: translateY(-2px) scale(1.03); }
        .mascot-toggle .mascot-avatar {
            width: 36px;
            height: 36px;
            background: #ffffff;
        }
        .mascot-toggle .mascot-avatar img {
            width: 30px;
            height: 30px;
        }
        .skeleton-shell { transition: opacity .4s ease, visibility .4s ease; }
        .skeleton-shell.is-hidden { opacity: 0; visibility: hidden; pointer-events: none; }
        .skeleton-block {
            background: linear-gradient(90deg, #e2e3e1 0%, #f3f4f2 50%, #e2e3e1 100%);
            background-size: 200% 100%;
            animation: shimmer 1.6s linear infinite;
        }
        .progress-bar { transform-origin: left center; }
        .progress-bar.is-visible { animation: fadeInUp .8s ease forwards; }

        /* Reduce motion if user prefers */
        @media (prefers-reduced-motion: reduce) {
            .mascot-avatar, .mascot-toggle::before { animation: none !important; }
        }

        /* ── Hero improvements ── */
        .hero-dot-grid {
            background-image: radial-gradient(rgba(0,51,102,0.10) 1px, transparent 1px);
            background-size: 32px 32px;
        }
        @keyframes gridDrift {
            0%   { background-position: 0 0; }
            100% { background-position: 32px 32px; }
        }
        .hero-dot-grid { animation: gridDrift 28s linear infinite; }

        /* ── Live batch badge pulse ── */
        .badge-live-dot {
            position: relative;
            display: inline-flex;
        }
        .badge-live-dot::before {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 999px;
            background: #aeeecb;
            opacity: 0.4;
            animation: ringPop 2.8s ease-out infinite;
        }
        @keyframes ringPop {
            0%   { transform: scale(.8); opacity: .5; }
            70%  { transform: scale(1.5); opacity: 0; }
            100% { transform: scale(1.5); opacity: 0; }
        }

        /* ── Gradient section label ── */
        .label-gradient {
            background: linear-gradient(90deg, #003366 0%, #2c694e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── Feature card enhanced ── */
        .feature-card-v2 {
            position: relative;
            background: #ffffff;
            border: 1px solid rgba(194,198,209,0.4);
            transition: transform .28s ease, box-shadow .28s ease, border-color .28s ease;
            overflow: hidden;
        }
        .feature-card-v2::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(135deg, rgba(0,51,102,0.04) 0%, rgba(44,105,78,0.04) 100%);
            opacity: 0;
            transition: opacity .3s ease;
        }
        .feature-card-v2:hover { transform: translateY(-6px); box-shadow: 0 24px 56px rgba(0,51,102,0.10); border-color: rgba(0,51,102,0.18); }
        .feature-card-v2:hover::after { opacity: 1; }
        .feature-icon-grad {
            background: linear-gradient(135deg, rgba(0,51,102,0.12) 0%, rgba(44,105,78,0.08) 100%);
            border: 1px solid rgba(0,51,102,0.08);
        }

        /* ── Step badge & connector ── */
        .step-wrapper { position: relative; }
        .step-number-badge {
            position: absolute;
            top: -14px;
            left: 50%;
            transform: translateX(-50%);
            width: 26px;
            height: 26px;
            border-radius: 999px;
            background: linear-gradient(135deg, #003366, #2c694e);
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 14px rgba(0,51,102,0.28);
            z-index: 2;
        }
        .step-h-connector {
            display: none;
        }
        @media (min-width: 1024px) {
            .step-h-connector {
                display: block;
                position: absolute;
                top: 54px;
                left: calc(50% + 42px);
                right: calc(-50% + 42px);
                height: 1px;
                background: linear-gradient(90deg, rgba(0,51,102,0.25), rgba(44,105,78,0.20));
                z-index: 0;
            }
            .step-h-connector::after {
                content: '';
                position: absolute;
                right: -1px;
                top: -4px;
                border: 4px solid transparent;
                border-left: 6px solid rgba(44,105,78,0.4);
            }
            .step-wrapper:last-child .step-h-connector { display: none; }
        }

        /* ── Metric card improved ── */
        .metric-card-v2 {
            position: relative;
            overflow: hidden;
        }
        .metric-card-v2::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #003366, #2c694e);
            opacity: 0;
            transition: opacity .28s ease;
        }
        .metric-card-v2:hover::before { opacity: 1; }

        /* ── Section accent line ── */
        .accent-underline {
            width: 40px;
            height: 3px;
            border-radius: 999px;
            background: linear-gradient(90deg, #003366, #2c694e);
            margin: 12px auto 0;
        }

        /* ── Hero stat pill ── */
        .hero-stat-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(0,51,102,0.10);
            border-radius: 999px;
            padding: 7px 14px;
            font-size: 13px;
        }

        /* ── Float for hero card ── */
        .hero-float { animation: floatSlow 6s ease-in-out infinite; }

        /* ── CTA enhanced pattern ── */
        .cta-pattern-v2 {
            background:
                radial-gradient(ellipse 80% 60% at 20% 50%, rgba(44,105,78,0.18) 0%, transparent 60%),
                radial-gradient(ellipse 60% 80% at 80% 50%, rgba(255,255,255,0.08) 0%, transparent 60%),
                linear-gradient(135deg, #002855 0%, #003366 50%, #1a4a6b 100%);
        }
        .cta-pattern-v2::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px);
            background-size: 28px 28px;
            border-radius: inherit;
        }

        /* ── Stats bar highlight ── */
        .stat-card-bar {
            position: relative;
        }
        .stat-card-bar::before {
            content: '';
            position: absolute;
            bottom: 0; left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, #003366, #2c694e);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .4s ease;
        }
        .stat-card-bar:hover::before { transform: scaleX(1); }

        /* ── FAQ improved ── */
        .faq-item-v2[open] .faq-icon { transform: rotate(45deg); }
        .faq-icon { transition: transform .25s ease; }
    </style>
</head>
<body class="bg-background text-on-surface">

<header class="sticky top-0 z-50 w-full border-b border-white/70 bg-[#f9f9f8]/90 backdrop-blur-xl">
    <div class="mx-auto flex w-full max-w-screen-2xl items-center justify-between px-5 py-4 sm:px-8">
        <a href="#beranda" class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-on-primary shadow-glow">
                <span class="material-symbols-outlined">agriculture</span>
            </span>
            <div>
                <div class="text-[10px] font-bold uppercase tracking-[0.28em] text-on-surface-variant">Dinas Pangan</div>
                <div class="text-sm font-bold tracking-tight text-primary sm:text-base">Stok Pangan Lhokseumawe</div>
            </div>
        </a>
        <button type="button" class="flex h-11 w-11 items-center justify-center rounded-lg border border-outline-variant/30 bg-surface-container-lowest text-primary md:hidden" id="mobileMenuButton" aria-expanded="false" aria-controls="mobileNavPanel">
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
            <a href="<?= e(base_url('/login')) ?>" class="interactive-button rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-on-primary transition-opacity hover:opacity-90 sm:px-6" data-magnetic>
                Masuk Sistem
            </a>
        </div>
    </div>
    <div id="mobileNavPanel" class="mobile-nav-panel hidden border-t border-outline-variant/20 bg-[#f9f9f8]/95 px-5 py-4 md:hidden sm:px-8">
        <div class="flex flex-col gap-3">
            <a class="rounded-lg bg-surface-container-low px-4 py-3 font-semibold text-primary" href="#beranda">Beranda</a>
            <a class="rounded-lg bg-surface-container-lowest px-4 py-3 font-semibold text-on-surface-variant" href="#fitur">Fitur</a>
            <a class="rounded-lg bg-surface-container-lowest px-4 py-3 font-semibold text-on-surface-variant" href="#cara-kerja">Cara Kerja</a>
            <a class="rounded-lg bg-surface-container-lowest px-4 py-3 font-semibold text-on-surface-variant" href="#dashboard">Dashboard</a>
            <a class="rounded-lg bg-surface-container-lowest px-4 py-3 font-semibold text-on-surface-variant" href="#prediksi">Prediksi</a>
            <a class="rounded-lg bg-surface-container-lowest px-4 py-3 font-semibold text-on-surface-variant" href="#faq">FAQ</a>
            <a href="<?= e(base_url('/login')) ?>" class="mt-2 rounded-lg bg-primary px-4 py-3 text-center font-semibold text-on-primary">Masuk Sistem</a>
        </div>
    </div>
</header>

<main>

    <!-- HERO -->
    <section id="beranda" class="relative overflow-hidden gradient-hero">
        <div class="absolute inset-0 z-0 opacity-20">
            <img class="h-full w-full object-cover" alt="Hamparan lahan pangan Lhokseumawe" src="<?= e($heroImage) ?>">
        </div>
        <div class="absolute inset-0 z-10 bg-gradient-to-b from-background/85 via-background/65 to-background"></div>
        <div class="hero-dot-grid absolute inset-0 z-[11] opacity-60 pointer-events-none"></div>
        <div class="relative z-20 mx-auto grid w-full max-w-screen-2xl grid-cols-1 gap-12 px-5 py-20 sm:px-8 lg:grid-cols-12 lg:py-28">
            <div class="reveal lg:col-span-7">
                <h1 class="mb-6 text-4xl font-extrabold leading-[1.05] tracking-[-0.03em] text-primary sm:text-5xl lg:text-[4rem]">
                    Forecasting Stok Pangan <span class="text-secondary">Cerdas</span><br class="hidden lg:block"> untuk Ketahanan Kota Lhokseumawe
                </h1>
                <p class="mb-8 max-w-2xl text-lg leading-relaxed text-on-surface-variant">
                    Platform prediksi stok pangan berbasis algoritma <strong class="text-primary font-semibold">Long Short-Term Memory (LSTM)</strong> yang membantu Dinas Pangan Kota Lhokseumawe memantau ketersediaan komoditas strategis secara akurat dan real-time.
                </p>
                <div class="mb-10 flex flex-wrap gap-3">
                    <a href="#dashboard" class="interactive-button flex items-center gap-2 rounded-lg bg-primary px-7 py-3.5 font-bold text-on-primary shadow-glow transition-all hover:-translate-y-0.5 hover:shadow-[0_20px_48px_rgba(0,51,102,0.28)]" data-magnetic>
                        <span class="material-symbols-outlined text-[20px]">trending_up</span>
                        Lihat Dashboard
                    </a>
                    <a href="#cara-kerja" class="interactive-button flex items-center gap-2 rounded-lg border-2 border-primary/25 bg-white/70 px-7 py-3.5 font-bold text-primary backdrop-blur-sm transition-all hover:bg-white hover:border-primary/40" data-magnetic>
                        <span class="material-symbols-outlined text-[20px]">play_circle</span>
                        Cara Kerja
                    </a>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="hero-stat-pill">
                        <span class="material-symbols-outlined text-secondary text-[18px]">verified</span>
                        <span class="font-bold text-primary"><?= e(number_format($accuracyPercent, 2, '.', '')) ?>%</span>
                        <span class="text-on-surface-variant">akurasi</span>
                    </div>
                    <div class="hero-stat-pill">
                        <span class="material-symbols-outlined text-primary text-[18px]">inventory_2</span>
                        <span class="font-bold text-primary"><?= e((string) $komoditasTotal) ?></span>
                        <span class="text-on-surface-variant">komoditas</span>
                    </div>
                    <div class="hero-stat-pill">
                        <span class="material-symbols-outlined text-primary text-[18px]">database</span>
                        <span class="font-bold text-primary"><?= e(number_format((float) $stokSummary['total_records'], 0, '.', '.')) ?></span>
                        <span class="text-on-surface-variant">data historis</span>
                    </div>
                </div>
            </div>
            <div class="reveal lg:col-span-5">
                <div class="hero-float relative rounded-2xl border border-white/70 bg-white/85 p-6 shadow-panel backdrop-blur-xl">
                    <!-- Card header -->
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-on-surface-variant">Model Terbaik · Forecast Terbaru</p>
                            <h3 class="mt-0.5 text-xl font-extrabold text-primary"><?= e((string) ($bestRun['komoditas'] ?? 'N/A')) ?></h3>
                        </div>
                        <span class="flex-shrink-0 rounded-full bg-secondary-container px-3 py-1 text-[11px] font-bold text-on-secondary-container ring-1 ring-secondary/20">
                            <span class="material-symbols-outlined text-[13px] align-[-3px]">auto_awesome</span> Best Model
                        </span>
                    </div>
                    <!-- Metric grid -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="group rounded-xl bg-gradient-to-br from-surface-container-low to-surface-container-lowest p-4 ring-1 ring-outline-variant/20 transition hover:ring-primary/20">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">RMSE</p>
                            <p class="mt-1 font-mono text-2xl font-extrabold tabular-nums text-primary"><?= e(number_format((float) ($bestRun['rmse'] ?? 0), 2, '.', '')) ?></p>
                        </div>
                        <div class="group rounded-xl bg-gradient-to-br from-surface-container-low to-surface-container-lowest p-4 ring-1 ring-outline-variant/20 transition hover:ring-primary/20">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">MAE</p>
                            <p class="mt-1 font-mono text-2xl font-extrabold tabular-nums text-primary"><?= e(number_format((float) ($bestRun['mae'] ?? 0), 2, '.', '')) ?></p>
                        </div>
                        <div class="group rounded-xl bg-gradient-to-br from-surface-container-low to-surface-container-lowest p-4 ring-1 ring-outline-variant/20 transition hover:ring-primary/20">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">MAPE</p>
                            <p class="mt-1 font-mono text-2xl font-extrabold tabular-nums text-primary"><?= e(number_format((float) ($bestRun['mape'] ?? 0), 2, '.', '')) ?><span class="text-sm font-medium text-on-surface-variant">%</span></p>
                        </div>
                        <div class="group rounded-xl bg-gradient-to-br from-surface-container-low to-surface-container-lowest p-4 ring-1 ring-outline-variant/20 transition hover:ring-primary/20">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Best Epoch</p>
                            <p class="mt-1 font-mono text-2xl font-extrabold tabular-nums text-primary"><?= e($bestEpoch) ?></p>
                        </div>
                    </div>
                    <!-- Footer info -->
                    <div class="mt-4 flex items-center gap-3 rounded-xl bg-primary-container/20 p-4">
                        <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <span class="material-symbols-outlined text-[20px]">calendar_today</span>
                        </span>
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Data Aktual Terakhir</p>
                            <p class="font-semibold text-primary"><?= e((string) $stokSummary['latest_date']) ?></p>
                        </div>
                    </div>
                    <!-- Accuracy badge -->
                    <div class="mt-3 flex items-center justify-between rounded-xl bg-secondary-container/40 px-4 py-2.5">
                        <span class="text-xs font-semibold text-on-secondary-container">Akurasi Rata-rata</span>
                        <span class="font-mono text-lg font-extrabold tabular-nums text-secondary"><?= e(number_format($accuracyPercent, 1, '.', '')) ?>%</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- STATS BAR -->
    <section class="border-y border-outline-variant/20 bg-surface-container-lowest">
        <div class="mx-auto grid max-w-screen-2xl grid-cols-2 gap-0 px-5 sm:px-8 md:grid-cols-4">
            <div class="stat-card-bar reveal flex items-center gap-4 border-r border-outline-variant/15 px-6 py-10 last:border-r-0">
                <span class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-primary/15 to-primary/8 text-primary ring-1 ring-primary/10">
                    <span class="material-symbols-outlined text-3xl">inventory_2</span>
                </span>
                <div>
                    <strong class="stat-number block font-mono text-3xl font-extrabold tabular-nums text-primary" data-count="<?= e((string) $komoditasTotal) ?>">0</strong>
                    <p class="mt-0.5 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Komoditas Dipantau</p>
                </div>
            </div>
            <div class="stat-card-bar reveal flex items-center gap-4 border-r border-outline-variant/15 px-6 py-10 last:border-r-0">
                <span class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-secondary/15 to-secondary/8 text-secondary ring-1 ring-secondary/10">
                    <span class="material-symbols-outlined text-3xl">database</span>
                </span>
                <div>
                    <strong class="stat-number block font-mono text-3xl font-extrabold tabular-nums text-primary" data-count="<?= e((string) $stokSummary['total_records']) ?>">0</strong>
                    <p class="mt-0.5 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Data Stok Historis</p>
                </div>
            </div>
            <div class="stat-card-bar reveal flex items-center gap-4 border-r border-outline-variant/15 px-6 py-10 last:border-r-0">
                <span class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-primary/15 to-primary/8 text-primary ring-1 ring-primary/10">
                    <span class="material-symbols-outlined text-3xl">model_training</span>
                </span>
                <div>
                    <strong class="stat-number block font-mono text-3xl font-extrabold tabular-nums text-primary" data-count="<?= e((string) $batchStats['batch_count']) ?>">0</strong>
                    <p class="mt-0.5 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Batch Model</p>
                </div>
            </div>
            <div class="stat-card-bar reveal flex items-center gap-4 px-6 py-10">
                <span class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#fff1c2] to-[#fde68a] text-[#8a5a00] ring-1 ring-[#f59e0b]/20">
                    <span class="material-symbols-outlined text-3xl">verified</span>
                </span>
                <div>
                    <strong class="block font-mono text-3xl font-extrabold tabular-nums text-primary"><?= e(number_format($accuracyPercent, 1, '.', '')) ?>%</strong>
                    <p class="mt-0.5 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Akurasi Rata-rata</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section id="fitur" class="bg-surface py-24">
        <div class="mx-auto max-w-screen-2xl px-5 sm:px-8">
            <div class="reveal mx-auto mb-16 max-w-3xl text-center">
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.25em] label-gradient">Fitur Unggulan</p>
                <div class="accent-underline mb-5"></div>
                <h2 class="mb-5 text-4xl font-bold text-primary sm:text-5xl">Semua yang Anda Butuhkan<br class="hidden sm:block"> untuk Memantau Stok Pangan</h2>
                <p class="text-lg leading-relaxed text-on-surface-variant">Sistem dirancang khusus untuk mendukung pengambilan keputusan berbasis data pada Dinas Pangan Kota Lhokseumawe.</p>
            </div>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($features as $feature): ?>
                    <div class="feature-card-v2 stagger-item reveal rounded-2xl p-8">
                        <span class="feature-icon-grad mb-5 inline-flex h-14 w-14 items-center justify-center rounded-2xl text-primary">
                            <span class="material-symbols-outlined text-3xl"><?= e($feature['icon']) ?></span>
                        </span>
                        <h3 class="mb-3 text-xl font-bold text-primary"><?= e($feature['title']) ?></h3>
                        <p class="leading-relaxed text-on-surface-variant"><?= e($feature['description']) ?></p>
                        <div class="mt-5 flex items-center gap-1.5 text-xs font-bold text-secondary">
                            <span class="h-1 w-4 rounded-full bg-secondary"></span>
                            <span class="h-1 w-2 rounded-full bg-secondary/40"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS -->
    <section id="cara-kerja" class="bg-surface-container-low py-24">
        <div class="mx-auto max-w-screen-2xl px-5 sm:px-8">
            <div class="reveal mx-auto mb-16 max-w-3xl text-center">
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.25em] label-gradient">Alur Sistem</p>
                <div class="accent-underline mb-5"></div>
                <h2 class="mb-5 text-4xl font-bold text-primary sm:text-5xl">Bagaimana Sistem Bekerja</h2>
                <p class="text-lg leading-relaxed text-on-surface-variant">Dari pengumpulan data hingga publikasi forecast, semua tahapan berjalan otomatis dan terukur.</p>
            </div>
            <div class="relative grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <?php foreach ($steps as $index => $step): ?>
                    <div class="step-wrapper stagger-item reveal relative rounded-2xl bg-surface-container-lowest p-8 pt-10 text-center shadow-sm transition hover:-translate-y-1 hover:shadow-panel">
                        <div class="step-number-badge"><?= e((string) ($index + 1)) ?></div>
                        <?php if ($index < count($steps) - 1): ?>
                            <div class="step-h-connector"></div>
                        <?php endif; ?>
                        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-primary-container text-on-primary shadow-glow">
                            <span class="material-symbols-outlined text-3xl"><?= e($step['icon']) ?></span>
                        </div>
                        <div class="mb-2 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Tahap <?= e((string) ($index + 1)) ?></div>
                        <h3 class="mb-3 text-lg font-bold text-primary"><?= e($step['title']) ?></h3>
                        <p class="text-sm leading-relaxed text-on-surface-variant"><?= e($step['description']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- DASHBOARD PREVIEW -->
    <section id="dashboard" class="bg-surface py-24">
        <div class="mx-auto max-w-screen-2xl px-5 sm:px-8">
            <div class="mb-12 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="reveal max-w-2xl">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.25em] label-gradient">Live Dashboard</p>
                    <div class="accent-underline mb-5 !mx-0"></div>
                    <h2 class="mb-4 text-4xl font-bold text-primary sm:text-5xl">Pratinjau Forecast Terkini</h2>
                    <p class="text-lg leading-relaxed text-on-surface-variant">Data langsung dari batch terbaru. Angka-angka di bawah ini mencerminkan kondisi aktual database forecasting.</p>
                </div>
                <div class="reveal flex flex-wrap items-center gap-2 text-sm">
                    <span class="rounded-lg border border-outline-variant/20 bg-surface-container-lowest px-4 py-2 font-medium">
                        Batch: <strong class="text-primary"><?= e((string) ($latestBatch['batch_code'] ?? '-')) ?></strong>
                    </span>
                    <span class="rounded-lg border border-outline-variant/20 bg-surface-container-lowest px-4 py-2 font-medium">
                        Tanggal Data: <strong class="text-primary"><?= e((string) $stokSummary['latest_date']) ?></strong>
                    </span>
                </div>
            </div>

            <!-- Forecast Cards Grid -->
            <div class="mb-10 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php if ($featuredCards !== []): ?>
                    <?php foreach ($featuredCards as $card): ?>
                        <div class="metric-card metric-card-v2 reveal rounded-2xl border border-outline-variant/20 bg-surface-container-lowest p-6 shadow-sm" data-tilt>
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <span class="material-symbols-outlined"><?= e($card['icon']) ?></span>
                                </span>
                                <span class="rounded-full px-3 py-1 text-[10px] font-bold uppercase <?= e($card['statusBadge']) ?>">
                                    <?= e($card['status']) ?>
                                </span>
                            </div>
                            <p class="mb-1 text-xs font-bold uppercase tracking-widest text-on-surface-variant">Forecast</p>
                            <h3 class="mb-1 text-lg font-bold text-primary"><?= e($card['commodity']) ?></h3>
                            <p class="text-3xl font-extrabold tracking-tight text-primary">
                                <?= e($card['value']) ?>
                                <span class="text-sm font-medium text-on-surface-variant"><?= e($card['unit']) ?></span>
                            </p>
                            <div class="mt-4 h-1.5 w-full overflow-hidden rounded-full bg-surface-container-highest">
                                <div class="progress-bar h-full <?= e($card['statusBar']) ?>" data-progress="<?= e((string) $card['ratio']) ?>" style="width: <?= e((string) $card['ratio']) ?>%"></div>
                            </div>
                            <div class="mt-4 flex items-center justify-between text-xs text-on-surface-variant">
                                <span class="flex items-center gap-1 <?= e($card['changeColor']) ?>">
                                    <span class="material-symbols-outlined text-sm"><?= e($card['changeIcon']) ?></span>
                                    <?= e($card['changeLabel']) ?>
                                </span>
                                <span>MAPE <strong class="text-primary"><?= e($card['mape']) ?>%</strong></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full rounded-xl bg-surface-container-lowest p-10 text-center text-on-surface-variant">
                        Belum ada forecast yang tersedia. Jalankan batch training LSTM pada panel admin terlebih dahulu.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Chart + Status Overview -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="reveal rounded-xl border border-outline-variant/20 bg-surface-container-lowest p-6 shadow-sm lg:col-span-2">
                    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-primary">Grafik Forecast Lintas Komoditas</h3>
                            <p class="text-sm text-on-surface-variant">Horizon prediksi 1 hari hingga 365 hari ke depan.</p>
                        </div>
                        <div class="flex items-center gap-3 text-xs font-bold text-on-surface-variant">
                            <span class="flex items-center gap-1"><span class="h-3 w-3 rounded-full bg-primary"></span> Predicted</span>
                        </div>
                    </div>
                    <div class="chart-shell relative min-h-[320px] rounded-xl bg-surface-container-low p-4 sm:p-6">
                        <div id="chartSkeleton" class="skeleton-shell absolute inset-0 z-[1] grid grid-cols-1 gap-4 p-6">
                            <div class="skeleton-block h-6 w-40 rounded"></div>
                            <div class="skeleton-block h-full min-h-[220px] rounded-xl"></div>
                        </div>
                        <canvas id="forecastSummaryChart" class="!h-[320px] !w-full"></canvas>
                    </div>
                </div>
                <div class="reveal flex flex-col justify-between rounded-xl bg-primary p-8 text-on-primary shadow-glow">
                    <div>
                        <h3 class="mb-5 text-lg font-bold">Status Model</h3>
                        <div class="space-y-4 text-sm">
                            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                                <span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-secondary-fixed"></span>Safe</span>
                                <strong><?= e((string) $safeCount) ?></strong>
                            </div>
                            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                                <span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-[#ffd77c]"></span>Watchlist</span>
                                <strong><?= e((string) $watchCount) ?></strong>
                            </div>
                            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                                <span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-[#ff9a9a]"></span>Warning</span>
                                <strong><?= e((string) $warningCount) ?></strong>
                            </div>
                            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                                <span>Train/Test</span>
                                <strong><?= e($trainSamples) ?>/<?= e($testSamples) ?></strong>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Best Epoch</span>
                                <strong><?= e($bestEpoch) ?></strong>
                            </div>
                        </div>
                    </div>
                    <a href="<?= e(base_url('/login')) ?>" class="interactive-button mt-8 flex items-center justify-center gap-2 rounded-lg bg-on-primary py-3 text-xs font-bold uppercase tracking-widest text-primary" data-magnetic>
                        Buka Panel Admin
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
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
    <div class="mascot-bubble is-hidden" id="mascotBubble">Halo, saya Si Padi Cerdas. Ada yang bisa saya bantu?</div>
    <div class="mascot-card is-hidden" id="mascotCard" role="dialog" aria-labelledby="mascotTitle">
        <div class="mascot-header">
            <div class="mascot-title-wrap">
                <div class="mascot-avatar is-curious" id="mascotAvatar" aria-hidden="true">
                    <img id="mascotAvatarFace" src="<?= e($mascotFaces['curious']) ?>" alt="Ekspresi maskot Si Padi">
                </div>
                <div>
                    <div class="mascot-status">Asisten Interaktif</div>
                    <strong id="mascotTitle" class="block text-sm">Si Padi Cerdas</strong>
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
        <div class="mascot-body" id="mascotBody">
            <div class="mascot-tip" id="mascotTipBox">Tip: saya akan mengganti ekspresi saat Anda menjelajahi setiap bagian landing page.</div>
            <div class="mascot-message" id="mascotMessage">
                Halo, saya Si Padi Cerdas. Saya bisa menjawab pertanyaan seputar sistem forecasting stok pangan, termasuk akurasi model, komoditas yang dipantau, dan alur kerja LSTM. Silakan pilih pertanyaan cepat atau ketik pertanyaan Anda sendiri.
            </div>
            <p class="mb-2 text-[11px] font-bold uppercase tracking-widest text-on-surface-variant">Pertanyaan Cepat</p>
            <div class="mascot-chip-row">
                <?php foreach ($mascotQuickQuestions as $index => $qa): ?>
                    <button type="button" class="mascot-chip" data-mascot-answer="<?= e($qa['answer']) ?>" data-mascot-question="<?= e($qa['question']) ?>">
                        <?= e($qa['question']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="mascot-input-wrap">
            <input type="text" class="mascot-input" id="mascotInput" placeholder="Tanyakan sesuatu...">
            <button type="button" class="mascot-send" id="mascotSend" aria-label="Kirim pertanyaan">
                <span class="material-symbols-outlined text-base">send</span>
            </button>
        </div>
    </div>
    <button type="button" class="mascot-toggle" id="mascotToggle" aria-expanded="false">
        <span class="mascot-avatar is-curious" aria-hidden="true">
            <img id="mascotToggleFace" src="<?= e($mascotFaces['curious']) ?>" alt="Maskot Si Padi Cerdas">
        </span>
        <span id="mascotToggleLabel">Tanya Si Padi</span>
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
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#003366'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#003366',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                title: (items) => items[0] ? `${commodities[items[0].dataIndex]} - ${items[0].label}` : '',
                                label: (context) => `Forecast: ${Number(context.raw).toLocaleString('id-ID', { maximumFractionDigits: 2 })}`
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
        const mascotMessage = document.getElementById('mascotMessage');
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
            activeUtterance = new SpeechSynthesisUtterance(text);
            loadSpeechVoice();
            if (speechVoice) {
                activeUtterance.voice = speechVoice;
                activeUtterance.lang = speechVoice.lang;
            } else {
                activeUtterance.lang = 'id-ID';
            }
            activeUtterance.rate = 1;
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

        const typewriteMessage = (text, shouldSpeak = true) => {
            if (!mascotMessage) return;
            if (mascotTypingTimeout) window.clearTimeout(mascotTypingTimeout);

            mascotMessage.innerHTML = '<span class="mascot-typing"><span></span><span></span><span></span></span>';
            let i = 0;
            const delay = Math.max(8, Math.min(32, 1200 / Math.max(text.length, 1)));

            const step = () => {
                if (i === 0) mascotMessage.textContent = '';
                if (i < text.length) {
                    mascotMessage.textContent = text.slice(0, i + 1);
                    i++;
                    mascotTypingTimeout = window.setTimeout(step, delay);
                } else if (shouldSpeak) {
                    speakText(text);
                }
            };
            mascotTypingTimeout = window.setTimeout(step, 320);
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
                return 'Halo! Saya Si Padi Cerdas. Silakan tanyakan apa saja tentang sistem forecasting stok pangan ini.';
            }
            if (q.includes('terima kasih') || q.includes('makasih') || q.includes('thanks')) {
                return 'Sama-sama. Senang bisa membantu Anda memahami sistem ini.';
            }
            return 'Maaf, saya belum memiliki informasi untuk pertanyaan tersebut. Coba gunakan salah satu pertanyaan cepat di atas atau bertanya tentang akurasi model, komoditas, LSTM, atau dashboard.';
        };

        const askMascot = (question, forcedAnswer = null) => {
            const answer = forcedAnswer || findAnswer(question);
            setMascotMood('is-excited');
            typewriteMessage(answer, true);
            showMascotBubble(answer.length > 100 ? answer.slice(0, 96) + '…' : answer);
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
                showMascotBubble('Halo! Klik saya untuk mulai bertanya tentang sistem forecasting stok pangan.');
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
            const text = mascotMessage?.textContent?.trim() || '';
            if (mascotSpeakButton.classList.contains('is-speaking')) {
                stopSpeech();
            } else if (text) {
                speakText(text);
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
