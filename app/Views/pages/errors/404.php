<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>404 - Halaman Tidak Ditemukan</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    body{font-family:'Plus Jakarta Sans',sans-serif}
    /* small enhancements for the LSTM project look */
    .muted { color: #6B7280 }
  </style>
</head>
<body class="bg-gradient-to-b from-gray-50 to-white min-h-screen flex items-center justify-center">
  <main class="w-full max-w-4xl mx-auto p-6 flex flex-col md:flex-row items-center gap-8">
    <section class="flex-1 text-center md:text-left">
      <h1 class="text-6xl font-extrabold text-gray-800 mb-2">404</h1>
      <p class="text-lg text-gray-600 mb-6">Ups — halaman yang Anda cari tidak ditemukan.</p>

      <form id="errorsSearch" class="mb-4 flex items-center justify-center md:justify-start" role="search" aria-label="Cari di situs">
        <input id="searchInput" name="q" type="search" placeholder="Cari komoditas atau halaman..." class="w-full md:w-80 px-4 py-3 rounded-l-md border border-gray-200 focus:outline-none" />
        <button type="submit" class="px-4 py-3 bg-indigo-600 text-white rounded-r-md hover:bg-indigo-700">Cari</button>
      </form>

      <div class="flex flex-wrap gap-3 justify-center md:justify-start">
        <a href="<?= base_url('/') ?>" class="px-4 py-2 bg-white border rounded-md muted hover:shadow">Beranda</a>
        <a href="<?= base_url('/login') ?>" class="px-4 py-2 bg-white border rounded-md muted hover:shadow">Login</a>
        <a href="<?= base_url('/komoditas') ?>" class="px-4 py-2 bg-white border rounded-md muted hover:shadow">Daftar Komoditas</a>
        <a href="<?= base_url('/evaluasi') ?>" class="px-4 py-2 bg-white border rounded-md muted hover:shadow">Evaluasi</a>
      </div>

      <p class="text-sm text-gray-400 mt-6">Sistem Prediksi Stok Pangan — laporkan tautan rusak ke admin jika perlu.</p>
    </section>

    <aside class="w-64 md:w-96 flex-shrink-0">
      <!-- Lottie player; local fallback to public/img/404-lottie.json -->
      <div class="bg-white rounded-xl p-4 shadow-md">
        <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
        <lottie-player id="lottie404" src="<?= base_url('/public/img/404-lottie.json') ?>" background="transparent" speed="1" style="width:100%;height:320px;" loop autoplay aria-label="Animasi maskot 404"></lottie-player>
      </div>

      <div class="mt-4 text-center text-sm muted">Coba kembali ke beranda atau cari sesuatu yang Anda butuhkan.</div>
    </aside>
  </main>

  <script src="<?= base_url('/public/js/errors-404.js') ?>"></script>
</body>
</html>
