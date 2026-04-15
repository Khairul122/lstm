# Sistem Prediksi Stok Pangan Lhokseumawe

Sistem informasi berbasis PHP native untuk mengelola data stok pangan, melakukan preprocessing data time series, menjalankan training model LSTM, mengevaluasi hasil prediksi, dan menampilkan landing page publik berisi forecast komoditas.

Fitur utama:
- autentikasi admin
- manajemen master komoditas
- manajemen data stok historis
- preprocessing data untuk LSTM
- training batch model LSTM semua komoditas
- evaluasi hasil model, residual, prediksi, dan forecast
- export laporan evaluasi
- landing page publik dengan forecast, filter, pagination, maskot interaktif, dan text-to-speech

## Teknologi

- PHP 8.1+ 
- MySQL / MariaDB
- Composer
- Python 3.10+ untuk training LSTM
- TensorFlow, NumPy, dan mysql-connector-python
- Tailwind CDN, Chart.js, dan Web Speech API di frontend

## Struktur Direktori

```text
app/
  Http/Controllers/   Controller aplikasi
  Models/             Query dan logika data
  Services/           Service export dan utilitas
  Views/              Template halaman
bootstrap/            Bootstrap aplikasi
config/               Konfigurasi app dan database
core/                 Router, session, database, middleware
database/             Schema SQL dan script Python training
helpers/              Helper global
public/               CSS, JS, gambar, dan asset publik
routes/               Definisi route web
storage/              Penyimpanan model hasil training
```

## Persyaratan Sistem

Pastikan server/development environment memiliki:

- PHP 8.1 atau lebih baru
- ekstensi PHP umum untuk MySQL dan session
- Composer
- MySQL atau MariaDB aktif
- Python 3.10 atau lebih baru
- `pip`

Contoh stack lokal yang cocok:
- Laragon
- XAMPP
- Apache + PHP + MySQL manual

## Instalasi

### 1. Clone atau salin project

Tempatkan project di web root. Contoh untuk Laragon:

```bash
C:\laragon\www\LSTM
```

### 2. Install dependency PHP

Jalankan di root project:

```bash
composer install
```

Project ini memakai package:
- `tecnickcom/tcpdf`

### 3. Buat database

Buat database baru, default nama database yang dipakai aplikasi adalah:

```text
db_stok_pangan
```

Contoh SQL:

```sql
CREATE DATABASE db_stok_pangan CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### 4. Import schema database

Import file:

```text
database/schema.sql
```

Contoh via terminal:

```bash
mysql -u root db_stok_pangan < database/schema.sql
```

Schema ini akan membuat tabel:
- `users`
- `komoditas`
- `data_stok_historis`
- `lstm_batch_runs`
- `lstm_model_runs`
- `lstm_model_metrics`
- `lstm_model_predictions`
- `lstm_model_residuals`
- `lstm_model_forecasts`

Catatan:
- tabel `data_preprocessing_lstm` akan dibuat otomatis saat preprocessing dijalankan pertama kali

### 5. Konfigurasi koneksi database

Konfigurasi database dibaca dari environment variable di `config/database.php`:

```php
'host' => getenv('DB_HOST') ?: '127.0.0.1',
'port' => (int) (getenv('DB_PORT') ?: 3306),
'database' => getenv('DB_DATABASE') ?: 'db_stok_pangan',
'username' => getenv('DB_USERNAME') ?: 'root',
'password' => getenv('DB_PASSWORD') ?: '',
```

Kalau kamu memakai Laragon dengan user `root` tanpa password, biasanya config default sudah cukup.

Jika ingin memakai environment variable, set minimal:

```text
APP_URL=http://localhost/LSTM
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_stok_pangan
DB_USERNAME=root
DB_PASSWORD=
```

### 6. Konfigurasi URL aplikasi

Base URL dibaca dari `config/app.php`:

```php
'base_url' => getenv('APP_URL') ?: 'http://localhost/lstm',
```

Sesuaikan dengan URL lokal kamu. Contoh untuk Laragon:

```text
http://localhost/LSTM
```

### 7. Siapkan dependency Python untuk training

Training LSTM memanggil script `database/train_lstm_batch.py` yang membutuhkan:
- `mysql-connector-python`
- `numpy`
- `tensorflow`

Install dengan:

```bash
pip install mysql-connector-python numpy tensorflow
```

Jika memakai virtual environment, aktifkan dulu environment sebelum menjalankan training.

### 8. Pastikan folder penyimpanan model tersedia

Model hasil training disimpan di:

```text
storage/models
```

Folder ini akan dibuat otomatis oleh script training, tetapi sebaiknya pastikan web server dan Python punya hak akses tulis ke folder `storage/`.

### 9. Jalankan aplikasi

Buka browser dan akses:

```text
http://localhost/LSTM
```

## Konfigurasi Akun Admin

File `database/schema.sql` membuat user admin default, tetapi README ini tidak mengasumsikan password awal tertentu.

Cara paling aman adalah mengganti password admin secara manual setelah import schema.

### Buat hash password baru

Contoh generate hash password dengan PHP:

```bash
php -r "echo password_hash('admin123', PASSWORD_DEFAULT), PHP_EOL;"
```

### Update password admin

Ganti hash pada query berikut:

```sql
UPDATE users
SET password = 'HASIL_HASH_BARU'
WHERE username = 'admin';
```

Setelah itu login dengan:
- username: `admin`
- password: sesuai password yang kamu set sendiri

## Alur Penggunaan Aplikasi

Secara umum alurnya seperti ini:

1. Login sebagai admin
2. Tambahkan master komoditas
3. Input data stok historis
4. Jalankan preprocessing
5. Jalankan training LSTM
6. Tinjau hasil evaluasi batch dan per komoditas
7. Lihat landing page publik untuk forecast

## User Guide

### 1. Login

Route login:

```text
/login
```

Masukkan username dan password admin. Setelah login berhasil, user akan diarahkan ke dashboard.

### 2. Dashboard

Route:

```text
/dashboard
```

Fungsi dashboard:
- melihat ringkasan sistem
- akses cepat ke modul komoditas, stok historis, preprocessing, training, dan evaluasi

### 3. Manajemen Komoditas

Route:

```text
/komoditas
```

Fitur:
- tambah komoditas
- edit komoditas
- hapus komoditas

Data yang dikelola:
- kode komoditas
- nama komoditas
- satuan

Contoh komoditas:
- Beras
- Gula Pasir
- Minyak Goreng

### 4. Manajemen Stok Historis

Route:

```text
/stok-historis
```

Fitur:
- input data stok harian/periode
- edit data stok
- hapus data stok

Field utama:
- komoditas
- tanggal pencatatan
- jumlah aktual
- lokasi gudang

Saran penggunaan:
- isi data secara konsisten per tanggal
- hindari data kosong
- gunakan nama lokasi gudang yang konsisten

### 5. Preprocessing LSTM

Route:

```text
/preprocessing
```

Fitur preprocessing:
- pilih komoditas tertentu atau semua komoditas
- set `sequence_length`
- set `train_ratio`
- proses data mentah menjadi dataset siap training

Output preprocessing:
- ringkasan data per komoditas
- log preprocessing
- preview hasil preprocessing

Validasi bawaan:
- `sequence_length` antara 1 sampai 60
- `train_ratio` antara 0.50 sampai 0.95

Saran awal:
- `sequence_length = 7`
- `train_ratio = 0.8`

### 6. Training LSTM

Route:

```text
/lstm
```

Fitur:
- training batch semua komoditas hasil preprocessing
- melihat batch terbaru
- melihat model terbaik pada batch terbaru
- reset seluruh hasil training

Parameter utama training:
- `sequence_length`
- `train_ratio`
- `epochs`
- `batch_size`
- `lstm_units`
- `dropout_rate`
- `optimizer`
- `learning_rate`

Saat tombol training dijalankan, aplikasi akan memanggil:

```text
python database/train_lstm_batch.py {batch_id}
```

Hasil training akan menghasilkan:
- batch training
- run per komoditas
- metrik evaluasi
- data prediksi
- data residual
- data forecast
- file model pada `storage/models`

### 7. Evaluasi Model

Route utama:

```text
/evaluasi
```

Fitur evaluasi:
- daftar batch training
- ringkasan evaluasi keseluruhan
- pencarian batch
- detail batch per komoditas
- detail run per komoditas
- export laporan

#### Detail batch

Route:

```text
/evaluasi/batch/{id}
```

Menampilkan:
- metadata batch
- rekap evaluasi seluruh komoditas
- performa masing-masing komoditas

#### Detail run

Route:

```text
/evaluasi/run/{id}
```

Menampilkan:
- metrik model
- tabel prediksi
- tabel residual
- tabel forecast
- grafik performa dan forecast

### 8. Export Laporan

Export tersedia di modul evaluasi untuk batch dan run.

Contoh route export:

```text
/evaluasi/batch/{id}/export/batch-summary/{format}
/evaluasi/batch/{id}/export/batch-lengkap/{format}
/evaluasi/batch/{id}/export/rekap-komoditas/{format}
/evaluasi/run/{id}/export/prediksi/{format}
/evaluasi/run/{id}/export/residual/{format}
/evaluasi/run/{id}/export/forecast/{format}
```

Format bergantung implementasi service export yang tersedia di aplikasi.

### 9. Landing Page Publik

Route:

```text
/
```

Landing page menampilkan:
- hero section sistem prediksi stok pangan
- penjelasan metodologi LSTM
- dashboard ringkas forecast
- tabel forecast publik semua komoditas
- filter, search, dan pagination
- maskot interaktif dengan FAQ dan text-to-speech

Fitur publik yang bisa dipakai user:
- melihat forecast lintas batch dan komoditas
- mencari komoditas atau batch tertentu
- memfilter status model
- melihat snapshot stok terbaru

## Route Utama

Daftar route penting:

```text
GET  /                         Landing page publik
GET  /login                    Halaman login
POST /login                    Proses login
POST /logout                   Logout

GET  /dashboard                Dashboard admin
GET  /profile                  Profil user

GET  /komoditas                Daftar komoditas
GET  /stok-historis            Daftar stok historis
GET  /preprocessing            Modul preprocessing
POST /preprocessing/process    Jalankan preprocessing

GET  /lstm                     Modul training LSTM
POST /lstm/train               Jalankan training batch
POST /lstm/reset-all           Hapus seluruh hasil training

GET  /evaluasi                 Daftar batch evaluasi
GET  /evaluasi/batch/{id}      Detail batch
GET  /evaluasi/run/{id}        Detail run
```

## Troubleshooting

### 1. Gagal konek database

Periksa:
- database sudah dibuat
- schema sudah diimport
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` benar

### 2. Login gagal terus

Periksa:
- tabel `users` ada
- user `admin` ada
- password admin sudah di-set ulang dengan hash yang valid

### 3. Training gagal karena dependency Python belum ada

Jika muncul pesan seperti:

```text
Dependency `tensorflow` belum terpasang
```

Install dependency:

```bash
pip install mysql-connector-python numpy tensorflow
```

### 4. Training selesai tetapi hasil tidak muncul

Periksa:
- preprocessing sudah dijalankan
- tabel `data_preprocessing_lstm` berisi data
- Python bisa diakses dari terminal/server
- folder `storage/models` bisa ditulis

### 5. Forecast publik kosong

Periksa:
- sudah ada batch training selesai
- tabel `lstm_model_forecasts` berisi data
- data stok historis terbaru tersedia untuk snapshot

### 6. Error collation database

Jika muncul error collation, samakan collation database/tabel ke:

```sql
utf8mb4_general_ci
```

Project ini sudah disesuaikan agar tabel LSTM memakai collation tersebut.

## Pengembangan Lanjutan

Saran pengembangan berikutnya:
- tambahkan `.env` loader agar konfigurasi lebih mudah
- buat seeder akun admin dan data contoh
- buat command CLI untuk training dan preprocessing
- tambahkan queue/background worker untuk training
- tambahkan test otomatis
- tambahkan role selain admin

## Lisensi dan Catatan

Project ini menggunakan package pihak ketiga:
- `tecnickcom/tcpdf`

Asset visual publik dan animasi yang dipakai pada landing page sebaiknya selalu dicek kembali lisensi pemakaiannya sebelum digunakan ke produksi.
