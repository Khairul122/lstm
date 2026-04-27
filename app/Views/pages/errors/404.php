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
  <main class="max-w-3xl mx-auto p-8 text-center">
    <div class="inline-flex items-center justify-center w-40 h-40 bg-white rounded-full shadow-md mx-auto mb-6">
      <!-- Simple illustrative SVG maskot -->
      <svg width="84" height="84" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M11 3h2v10h-2z" fill="#4F46E5"/>
        <path d="M5 21h14v-2a6 6 0 00-6-6H11a6 6 0 00-6 6v2z" fill="#A78BFA"/>
      </svg>
    </div>

    <h1 class="text-5xl font-bold text-gray-800 mb-4">404</h1>
    <p class="text-xl text-gray-600 mb-6">Maaf, halaman yang Anda minta tidak ditemukan.</p>
    <div class="space-x-2">
      <a href="<?= base_url('/') ?>" class="inline-block bg-indigo-600 text-white px-5 py-3 rounded-md hover:bg-indigo-700">Kembali ke Beranda</a>
      <a href="<?= base_url('/login') ?>" class="inline-block bg-white border border-gray-200 text-gray-700 px-5 py-3 rounded-md">Halaman Login</a>
    </div>

    <p class="text-sm text-gray-400 mt-6">Sistem Prediksi Stok Pangan — jika perlu, laporkan tautan rusak ke admin.</p>
  </main>
</body>
</html>
