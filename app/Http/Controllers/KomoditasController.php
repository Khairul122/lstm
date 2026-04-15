<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Komoditas;
use Core\Controller;
use Core\Session;

final class KomoditasController extends Controller
{
    public function index(): void
    {
        $search = trim((string) ($_GET['search'] ?? ''));
        $page = (int) ($_GET['page'] ?? 1);
        $result = Komoditas::paginate($search, $page, 20);

        $this->view('pages.komoditas.index', [
            'title' => 'Data Komoditas',
            'username' => (string) Session::get('username', 'User'),
            'role' => (string) Session::get('role', '-'),
            'items' => $result['items'],
            'search' => $result['search'],
            'currentPage' => $result['currentPage'],
            'totalPages' => $result['totalPages'],
            'totalItems' => $result['totalItems'],
            'perPage' => $result['perPage'],
            'activeNav' => 'komoditas',
        ]);
    }

    public function create(): void
    {
        $this->view('pages.komoditas.form', [
            'title' => 'Tambah Komoditas',
            'username' => (string) Session::get('username', 'User'),
            'role' => (string) Session::get('role', '-'),
            'activeNav' => 'komoditas',
            'formTitle' => 'Tambah Komoditas',
            'formAction' => base_url('/komoditas/store'),
            'submitLabel' => 'Simpan',
            'item' => [
                'id_komoditas' => null,
                'kode_komoditas' => old('kode_komoditas'),
                'nama_komoditas' => old('nama_komoditas'),
                'satuan' => old('satuan'),
            ],
        ]);
    }

    public function store(): void
    {
        $kodeKomoditas = strtoupper(trim((string) ($_POST['kode_komoditas'] ?? '')));
        $namaKomoditas = trim((string) ($_POST['nama_komoditas'] ?? ''));
        $satuan = trim((string) ($_POST['satuan'] ?? ''));

        if ($kodeKomoditas === '' || $namaKomoditas === '' || $satuan === '') {
            Session::flash('error', 'Kode, nama komoditas, dan satuan wajib diisi.');
            set_old_input($_POST);
            $this->redirect('/komoditas/create');
        }

        if (!preg_match('/^[A-Z]{3}-\d{3}$/', $kodeKomoditas)) {
            Session::flash('error', 'Kode komoditas harus berformat seperti BRS-001.');
            set_old_input($_POST);
            $this->redirect('/komoditas/create');
        }

        if (Komoditas::findByKode($kodeKomoditas) !== null) {
            Session::flash('error', 'Kode komoditas sudah digunakan.');
            set_old_input($_POST);
            $this->redirect('/komoditas/create');
        }

        Komoditas::create([
            'kode_komoditas' => $kodeKomoditas,
            'nama_komoditas' => $namaKomoditas,
            'satuan' => $satuan,
        ]);

        clear_old_input();
        Session::flash('success', 'Data komoditas berhasil ditambahkan.');
        $this->redirect('/komoditas');
    }

    public function edit(string $id): void
    {
        $item = Komoditas::find((int) $id);
        if ($item === null) {
            Session::flash('error', 'Data komoditas tidak ditemukan.');
            $this->redirect('/komoditas');
        }

        $this->view('pages.komoditas.form', [
            'title' => 'Edit Komoditas',
            'username' => (string) Session::get('username', 'User'),
            'role' => (string) Session::get('role', '-'),
            'activeNav' => 'komoditas',
            'formTitle' => 'Edit Komoditas',
            'formAction' => base_url('/komoditas/update/' . $item['id_komoditas']),
            'submitLabel' => 'Perbarui',
            'item' => [
                'id_komoditas' => $item['id_komoditas'],
                'kode_komoditas' => old('kode_komoditas', (string) $item['kode_komoditas']),
                'nama_komoditas' => old('nama_komoditas', (string) $item['nama_komoditas']),
                'satuan' => old('satuan', (string) $item['satuan']),
            ],
        ]);
    }

    public function update(string $id): void
    {
        $komoditasId = (int) $id;
        $item = Komoditas::find($komoditasId);
        if ($item === null) {
            Session::flash('error', 'Data komoditas tidak ditemukan.');
            $this->redirect('/komoditas');
        }

        $kodeKomoditas = strtoupper(trim((string) ($_POST['kode_komoditas'] ?? '')));
        $namaKomoditas = trim((string) ($_POST['nama_komoditas'] ?? ''));
        $satuan = trim((string) ($_POST['satuan'] ?? ''));

        if ($kodeKomoditas === '' || $namaKomoditas === '' || $satuan === '') {
            Session::flash('error', 'Kode, nama komoditas, dan satuan wajib diisi.');
            set_old_input($_POST);
            $this->redirect('/komoditas/edit/' . $komoditasId);
        }

        if (!preg_match('/^[A-Z]{3}-\d{3}$/', $kodeKomoditas)) {
            Session::flash('error', 'Kode komoditas harus berformat seperti BRS-001.');
            set_old_input($_POST);
            $this->redirect('/komoditas/edit/' . $komoditasId);
        }

        if (Komoditas::findByKode($kodeKomoditas, $komoditasId) !== null) {
            Session::flash('error', 'Kode komoditas sudah digunakan.');
            set_old_input($_POST);
            $this->redirect('/komoditas/edit/' . $komoditasId);
        }

        Komoditas::update($komoditasId, [
            'kode_komoditas' => $kodeKomoditas,
            'nama_komoditas' => $namaKomoditas,
            'satuan' => $satuan,
        ]);

        clear_old_input();
        Session::flash('success', 'Data komoditas berhasil diperbarui.');
        $this->redirect('/komoditas');
    }

    public function delete(string $id): void
    {
        $komoditasId = (int) $id;
        $item = Komoditas::find($komoditasId);
        if ($item === null) {
            Session::flash('error', 'Data komoditas tidak ditemukan.');
            $this->redirect('/komoditas');
        }

        Komoditas::delete($komoditasId);

        Session::flash('success', 'Data komoditas berhasil dihapus.');
        $this->redirect('/komoditas');
    }
}
