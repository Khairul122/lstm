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
  <style>body{font-family:'Plus Jakarta Sans',sans-serif}</style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
  <main class="w-full max-w-xl mx-auto p-6 text-center">
    <div class="bg-white rounded-2xl shadow-lg p-6">
      <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
      <!-- Public Lottie animation (casual 404) -->
      <lottie-player src="https://assets2.lottiefiles.com/packages/lf20_touohxv0.json" background="transparent" speed="1" style="width:320px;height:320px;margin:0 auto;" loop autoplay aria-label="Animasi 404"></lottie-player>

      <h1 class="text-4xl font-bold text-gray-800 mt-4">Ups — Halaman Tidak Ditemukan</h1>
      <p class="text-gray-600 mt-2 mb-4">Maaf, tautan yang Anda kunjungi tidak tersedia.</p>

      <a href="<?= base_url('/') ?>" class="inline-block mt-3 px-6 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Kembali ke Beranda</a>
    </div>
  </main>
</body>
</html>
