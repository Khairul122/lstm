<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Komoditas;
use App\Models\StokHistoris;
use Core\Controller;
use Core\Session;

final class StokHistorisController extends Controller
{
    public function index(): void
    {
        $search = trim((string) ($_GET['search'] ?? ''));
        $page = (int) ($_GET['page'] ?? 1);
        $result = StokHistoris::paginate($search, $page, 20);

        $this->view('pages.stok-historis.index', [
            'title' => 'Data Stok Historis',
            'username' => (string) Session::get('username', 'User'),
            'role' => (string) Session::get('role', '-'),
            'items' => $result['items'],
            'search' => $result['search'],
            'currentPage' => $result['currentPage'],
            'totalPages' => $result['totalPages'],
            'totalItems' => $result['totalItems'],
            'perPage' => $result['perPage'],
            'activeNav' => 'stok-historis',
        ]);
    }

    public function create(): void
    {
        $this->view('pages.stok-historis.form', [
            'title' => 'Tambah Stok Historis',
            'username' => (string) Session::get('username', 'User'),
            'role' => (string) Session::get('role', '-'),
            'activeNav' => 'stok-historis',
            'formTitle' => 'Tambah Stok Historis',
            'formAction' => base_url('/stok-historis/store'),
            'submitLabel' => 'Simpan',
            'komoditasOptions' => $this->getKomoditasOptions(),
            'item' => [
                'id_stok' => null,
                'id_komoditas' => old('id_komoditas'),
                'waktu_catat' => old('waktu_catat'),
                'jumlah_aktual' => old('jumlah_aktual'),
                'lokasi_gudang' => old('lokasi_gudang'),
            ],
        ]);
    }

    public function store(): void
    {
        $payload = $this->sanitizePayload($_POST);
        $validationError = $this->validatePayload($payload);

        if ($validationError !== null) {
            Session::flash('error', $validationError);
            set_old_input($_POST);
            $this->redirect('/stok-historis/create');
        }

        StokHistoris::create($payload);

        clear_old_input();
        Session::flash('success', 'Data stok historis berhasil ditambahkan.');
        $this->redirect('/stok-historis');
    }

    public function edit(string $id): void
    {
        $item = StokHistoris::find((int) $id);
        if ($item === null) {
            Session::flash('error', 'Data stok historis tidak ditemukan.');
            $this->redirect('/stok-historis');
        }

        $this->view('pages.stok-historis.form', [
            'title' => 'Edit Stok Historis',
            'username' => (string) Session::get('username', 'User'),
            'role' => (string) Session::get('role', '-'),
            'activeNav' => 'stok-historis',
            'formTitle' => 'Edit Stok Historis',
            'formAction' => base_url('/stok-historis/update/' . $item['id_stok']),
            'submitLabel' => 'Perbarui',
            'komoditasOptions' => $this->getKomoditasOptions(),
            'item' => [
                'id_stok' => $item['id_stok'],
                'id_komoditas' => old('id_komoditas', (string) $item['id_komoditas']),
                'waktu_catat' => old('waktu_catat', (string) $item['waktu_catat']),
                'jumlah_aktual' => old('jumlah_aktual', (string) $item['jumlah_aktual']),
                'lokasi_gudang' => old('lokasi_gudang', (string) $item['lokasi_gudang']),
            ],
        ]);
    }

    public function update(string $id): void
    {
        $stokId = (int) $id;
        $item = StokHistoris::find($stokId);
        if ($item === null) {
            Session::flash('error', 'Data stok historis tidak ditemukan.');
            $this->redirect('/stok-historis');
        }

        $payload = $this->sanitizePayload($_POST);
        $validationError = $this->validatePayload($payload);

        if ($validationError !== null) {
            Session::flash('error', $validationError);
            set_old_input($_POST);
            $this->redirect('/stok-historis/edit/' . $stokId);
        }

        StokHistoris::update($stokId, $payload);

        clear_old_input();
        Session::flash('success', 'Data stok historis berhasil diperbarui.');
        $this->redirect('/stok-historis');
    }

    public function delete(string $id): void
    {
        $stokId = (int) $id;
        $item = StokHistoris::find($stokId);
        if ($item === null) {
            Session::flash('error', 'Data stok historis tidak ditemukan.');
            $this->redirect('/stok-historis');
        }

        StokHistoris::delete($stokId);

        Session::flash('success', 'Data stok historis berhasil dihapus.');
        $this->redirect('/stok-historis');
    }

    private function getKomoditasOptions(): array
    {
        return Komoditas::allOptions();
    }

    private function findKomoditasById(int $idKomoditas): ?array
    {
        return Komoditas::find($idKomoditas);
    }

    private function sanitizePayload(array $source): array
    {
        return [
            'id_komoditas' => trim((string) ($source['id_komoditas'] ?? '')),
            'waktu_catat' => trim((string) ($source['waktu_catat'] ?? '')),
            'jumlah_aktual' => trim((string) ($source['jumlah_aktual'] ?? '')),
            'lokasi_gudang' => trim((string) ($source['lokasi_gudang'] ?? '')),
        ];
    }

    private function validatePayload(array $payload): ?string
    {
        if ($payload['id_komoditas'] === '' || $payload['waktu_catat'] === '' || $payload['jumlah_aktual'] === '' || $payload['lokasi_gudang'] === '') {
            return 'Komoditas, tanggal catat, jumlah aktual, dan lokasi gudang wajib diisi.';
        }

        if (!ctype_digit($payload['id_komoditas'])) {
            return 'Komoditas yang dipilih tidak valid.';
        }

        if ($this->findKomoditasById((int) $payload['id_komoditas']) === null) {
            return 'Komoditas yang dipilih tidak ditemukan.';
        }

        $date = \DateTime::createFromFormat('Y-m-d', $payload['waktu_catat']);
        if (!$date || $date->format('Y-m-d') !== $payload['waktu_catat']) {
            return 'Format tanggal catat tidak valid.';
        }

        if (!is_numeric($payload['jumlah_aktual'])) {
            return 'Jumlah aktual harus berupa angka.';
        }

        if ((float) $payload['jumlah_aktual'] < 0) {
            return 'Jumlah aktual tidak boleh bernilai negatif.';
        }

        if (mb_strlen($payload['lokasi_gudang']) > 50) {
            return 'Lokasi gudang maksimal 50 karakter.';
        }

        return null;
    }
}
