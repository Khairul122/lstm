# Studi.md — Landasan Teori, Metodologi, dan Panduan Pembahasan Hasil

> Dokumen ini memetakan teori peramalan time series & LSTM ke implementasi nyata pada
> **Sistem Prediksi Stok Pangan Lhokseumawe**. Disusun agar bisa langsung dipakai/disitasi
> ulang untuk BAB II (Tinjauan Pustaka), BAB III (Metodologi Penelitian), dan BAB IV (Hasil
> dan Pembahasan) skripsi. Semua angka/rumus di bawah diambil langsung dari kode yang berjalan
> (`database/train_lstm_batch.py` dan `app/Models/DataPreprocessingLstm.php`), bukan asumsi.

---

## 1. Ruang Lingkup Penelitian

| Aspek | Nilai pada sistem ini |
|---|---|
| Objek prediksi | Stok pangan per komoditas (default seed: Beras, Bawang Merah, Cabai Merah, Daging Sapi, Telur Ayam Ras) |
| Wilayah studi | Lhokseumawe, 4 gudang (Banda Sakti, Muara Dua, Blang Mangat, Muara Satu) |
| Jenis data | Deret waktu (time series) harian, per komoditas |
| Rentang data historis (contoh seed) | 2022-01-01 s.d. 2025-01-01 (±1.097 hari/komoditas) |
| Horizon prediksi | 1 langkah ke depan (data uji) + forecast rekursif 365 hari ke depan |
| Model | LSTM (Long Short-Term Memory), 1 layer, arsitektur many-to-one |
| Jenis peramalan | Univariate time series forecasting (satu variabel: jumlah stok) |

---

## 2. BAB II — Tinjauan Pustaka (Landasan Teori)

### 2.1 Peramalan Deret Waktu (Time Series Forecasting)

Peramalan deret waktu memprediksi nilai masa depan `y_(t+1)` berdasarkan pola historis
`y_1, y_2, ..., y_t`. Karakteristik data stok pangan bersifat sekuensial dan memiliki
dependensi temporal (nilai hari ini dipengaruhi nilai beberapa hari sebelumnya) — inilah
alasan pemilihan model berbasis *Recurrent Neural Network* dibanding model regresi statis.

### 2.2 Recurrent Neural Network (RNN) dan Masalah Vanishing Gradient

RNN mempertahankan *hidden state* yang diperbarui setiap langkah waktu, sehingga secara
teoritis mampu "mengingat" informasi dari input sebelumnya. Namun RNN klasik mengalami
**vanishing/exploding gradient** saat backpropagation through time pada sekuens panjang,
sehingga sulit mempelajari dependensi jangka panjang.

### 2.3 Long Short-Term Memory (LSTM)

LSTM (Hochreiter & Schmidhuber, 1997) mengatasi masalah tersebut dengan menambahkan
**cell state** (`C_t`) dan tiga gerbang (*gate*) yang mengatur aliran informasi:

| Gerbang | Fungsi | Rumus |
|---|---|---|
| Forget gate | Menentukan informasi lama yang dibuang | `f_t = σ(W_f·[h_(t-1), x_t] + b_f)` |
| Input gate | Menentukan informasi baru yang disimpan | `i_t = σ(W_i·[h_(t-1), x_t] + b_i)` |
| Candidate cell | Kandidat nilai baru cell state | `C̃_t = tanh(W_C·[h_(t-1), x_t] + b_C)` |
| Update cell state | Kombinasi forget + input | `C_t = f_t * C_(t-1) + i_t * C̃_t` |
| Output gate | Menentukan hidden state keluaran | `o_t = σ(W_o·[h_(t-1), x_t] + b_o)`, `h_t = o_t * tanh(C_t)` |

`σ` = fungsi sigmoid, `*` = perkalian elemen-per-elemen (Hadamard product). Mekanisme gerbang
ini memungkinkan LSTM mempertahankan informasi relevan dalam jangka panjang sekaligus
membuang informasi yang tidak relevan — cocok untuk pola stok pangan yang punya tren dan
musiman (monthly factor) seperti pada data seed proyek ini.

### 2.4 Dropout sebagai Regularisasi

Dropout menonaktifkan sebagian neuron secara acak (probabilitas `dropout_rate`) pada setiap
langkah pelatihan untuk mencegah *overfitting*. Pada arsitektur proyek ini, dropout
diterapkan tepat setelah layer LSTM, sebelum masuk ke layer Dense.

### 2.5 Fungsi Aktivasi

- **ReLU** (`f(x) = max(0, x)`) — dipakai pada Dense(32) untuk non-linearitas tanpa vanishing
  gradient pada nilai positif.
- **Linear** (`f(x) = x`) — dipakai pada Dense(1) output karena target adalah nilai numerik
  kontinu (regresi), bukan klasifikasi.

### 2.6 Fungsi Loss dan Optimizer

- **Mean Squared Error (MSE)** sebagai fungsi loss: `MSE = (1/n) Σ(y_i - ŷ_i)²` — dipilih
  karena tugasnya adalah regresi dan MSE memberi penalti lebih besar untuk kesalahan besar.
- **Adam** (Kingma & Ba, 2014) — optimizer adaptif berbasis estimasi momen pertama & kedua
  dari gradien, konvergensi umumnya lebih stabil dan cepat dibanding SGD murni.
- **RMSprop** — alternatif optimizer adaptif yang menormalkan gradien dengan rata-rata
  bergerak kuadrat gradien; disediakan sebagai pilihan kedua di sistem ini.

### 2.7 Early Stopping

Teknik regularisasi yang menghentikan pelatihan ketika metrik validasi (`val_loss`) berhenti
membaik selama sejumlah epoch tertentu (*patience*), lalu mengembalikan bobot model terbaik
(`restore_best_weights`) — mencegah overfitting akibat pelatihan yang terlalu lama.

### 2.8 Metrik Evaluasi Model Regresi/Time Series

| Metrik | Rumus | Interpretasi |
|---|---|---|
| RMSE (Root Mean Squared Error) | `√((1/n) Σ(y_i - ŷ_i)²)` | Rata-rata kesalahan dalam satuan asli data (kg/unit), sensitif terhadap outlier |
| MAE (Mean Absolute Error) | `(1/n) Σ\|y_i - ŷ_i\|` | Rata-rata kesalahan absolut, lebih tahan terhadap outlier dibanding RMSE |
| MAPE (Mean Absolute Percentage Error) | `(100/n) Σ \|(y_i - ŷ_i)/y_i\|` | Kesalahan relatif dalam persen, memudahkan perbandingan antar komoditas dengan skala berbeda |

**Kategori akurasi MAPE** (acuan umum yang lazim dipakai di penelitian peramalan,
Lewis 1982):

| Rentang MAPE | Kategori Akurasi |
|---|---|
| < 10% | Sangat baik (highly accurate) |
| 10% – 20% | Baik (good) |
| 20% – 50% | Cukup/layak (reasonable) |
| > 50% | Tidak layak (inaccurate) |

> Catatan: kutip ulang rumus/kategori ini dari sumber primer (Hochreiter & Schmidhuber 1997;
> Kingma & Ba 2014; Lewis 1982) di daftar pustaka skripsi — dokumen ini hanya memetakan teori
> ke implementasi, bukan pengganti sitasi akademik.

### 2.9 Preprocessing Data Time Series

- **Deteksi missing value**: melengkapi tanggal yang bolong dalam rentang data (setiap hari
  kalender harus punya 1 baris per komoditas).
- **Deteksi outlier — metode IQR (Interquartile Range)**: `IQR = Q3 - Q1`,
  batas bawah `= Q1 - 1.5×IQR`, batas atas `= Q3 + 1.5×IQR`. Nilai di luar batas dianggap
  outlier.
- **Imputasi**: mengisi nilai hilang/outlier dengan rata-rata tetangga terdekat yang valid
  (interpolasi linear sederhana berbasis titik sebelum & sesudah).
- **Normalisasi Min-Max**: `x_norm = (x - x_min) / (x_max - x_min)`, menskalakan data ke
  rentang [0, 1] — wajib untuk LSTM karena fungsi aktivasi (tanh/sigmoid) bekerja optimal
  pada input berskala kecil dan seragam.
- **Windowing (sliding window)**: membentuk pasangan input-output `(X, y)` dengan `X` berupa
  `sequence_length` nilai berurutan sebelumnya, `y` adalah nilai pada hari berikutnya —
  mengubah masalah time series menjadi masalah *supervised learning*.

---

## 3. BAB III — Metodologi Penelitian (Sesuai Implementasi)

### 3.1 Sumber dan Objek Data

- Tabel sumber: `data_stok_historis` (kolom kunci: `id_komoditas`, `waktu_catat`,
  `jumlah_aktual`), direlasikan ke tabel master `komoditas`.
- Data diinput/dikelola melalui modul **Manajemen Stok Historis** (`/stok-historis`).
- Data mentah bisa berasal dari input manual admin atau seed sintetis
  (`database/seed_stok_pangan.py`) yang mensimulasikan pola musiman per bulan
  (`monthly_factors`) dan bobot distribusi antar-gudang.

### 3.2 Tahapan Penelitian (Alur Pipeline End-to-End)

```
1. Input/Manajemen Data  → data_stok_historis
2. Preprocessing         → data_preprocessing_lstm   (bersih, ternormalisasi, ter-windowing)
3. Training LSTM (batch) → lstm_batch_runs, lstm_model_runs, lstm_model_metrics
4. Evaluasi              → lstm_model_predictions, lstm_model_residuals
5. Forecasting           → lstm_model_forecasts (365 hari ke depan)
6. Pelaporan/Ekspor      → CSV/Excel/PDF, landing page publik
```

Setiap tahap dijalankan lewat panel admin: `/stok-historis` → `/preprocessing` → `/lstm`
(training) → `/evaluasi` (hasil).

### 3.3 Tahap Preprocessing (Rinci, per Komoditas)

Diimplementasikan di `App\Models\DataPreprocessingLstm::prepareCommodityRows()`:

1. Kelompokkan data historis per komoditas, urutkan berdasarkan tanggal.
2. Bentuk rentang tanggal penuh (`DatePeriod`) dari tanggal pertama sampai terakhir — tanggal
   yang tidak ada baris datanya otomatis berstatus **Missing Value**.
3. Hitung Q1, Q3, IQR dari seluruh nilai stok mentah (non-null) → tandai nilai di luar
   `[Q1-1.5·IQR, Q3+1.5·IQR]` sebagai **Outlier**.
4. Untuk baris Missing Value/Outlier: nilai `stok_bersih` diisi rata-rata nilai `Normal`
   terdekat sebelum & sesudahnya (atau salah satunya jika hanya satu sisi tersedia, atau
   median keseluruhan sebagai fallback terakhir).
5. Normalisasi Min-Max dihitung dari `stok_bersih` (bukan dari `stok_mentah`) agar hasil
   imputasi ikut ternormalisasi konsisten → kolom `normalisasi_minmax`.
6. Bentuk sekuens input (`input_sekuens_x`, disimpan sebagai JSON array) sepanjang
   `sequence_length` nilai normalisasi sebelumnya; untuk indeks di awal deret yang belum
   punya cukup histori, dipadatkan dengan nilai pertama deret (padding).
7. Target (`target_label_y`) = nilai normalisasi pada hari itu sendiri.
8. **Split data latih/uji dilakukan secara kronologis (bukan acak/shuffle)**:
   `splitIndex = floor(total_baris × train_ratio)` — baris indeks `< splitIndex` → `Latih`,
   sisanya → `Uji`. Ini penting secara metodologis: mencegah *data leakage* dari masa depan
   ke masa lalu, sesuai prinsip validasi time series (tidak boleh diacak seperti data tabular
   biasa).

Parameter yang bisa diatur pengguna pada tahap ini (`/preprocessing`):

| Parameter | Rentang valid | Default |
|---|---|---|
| `sequence_length` (panjang window) | 1 – 60 hari | 7 |
| `train_ratio` | 0.50 – 0.95 | 0.80 |

### 3.4 Arsitektur Model

Didefinisikan di `database/train_lstm_batch.py::build_model()`:

```
Input(shape = (sequence_length, 1))
  → LSTM(units = lstm_units, return_sequences = False)
  → Dropout(rate = dropout_rate)
  → Dense(32, activation = "relu")
  → Dense(1, activation = "linear")
```

- Loss: **MSE**, Metric tambahan: **MAE**.
- Optimizer: **Adam** atau **RMSprop** (pilihan pengguna), dengan `learning_rate` yang
  dikonfigurasi.
- Model bersifat **univariate, single-step ahead** — satu model dilatih terpisah untuk
  setiap komoditas (tidak ada multivariate/cross-commodity features).

### 3.5 Skema Pelatihan (Training)

- Setiap sesi disebut **batch training** — satu batch melatih model untuk **semua**
  komoditas yang punya data preprocessing, satu-per-satu, sequential (bukan paralel).
- `model.fit(..., validation_split=0.15, shuffle=False, callbacks=[EarlyStopping])`:
  - `shuffle=False` + `validation_split` → validasi diambil dari **15% *akhir* data latih**
    (bukan diacak), sehingga validasi tetap merepresentasikan periode waktu yang belum
    "dilihat" model — konsisten dengan prinsip validasi time series.
  - `EarlyStopping(monitor="val_loss", patience=6, restore_best_weights=True)`.
- `tf.random.set_seed(42)` dan `np.random.seed(42)` di-set di awal proses untuk
  **reproduksibilitas** hasil antar-run (mengurangi variasi acak inisialisasi bobot).

Parameter hyperparameter yang bisa diatur dari UI training (`/lstm`), dengan validasi
di `LstmController::validate()`:

| Parameter | Rentang valid | Default |
|---|---|---|
| `sequence_length` | 1 – 60 | 7 |
| `train_ratio` | 0.50 – 0.95 | 0.80 |
| `epochs` | 1 – 300 | 30 |
| `batch_size` | 1 – 256 | 16 |
| `lstm_units` | 4 – 256 | 64 |
| `dropout_rate` | 0.0 – 0.8 | 0.2 |
| `optimizer` | adam / rmsprop | adam |
| `learning_rate` | > 0 s.d. 1 | 0.001 |

### 3.6 Skema Pengujian/Evaluasi

1. Prediksi dijalankan pada data `Uji` (`model.predict`), hasil di-clip ke [0, 1] karena
   berada di ruang normalisasi.
2. **Denormalisasi**: `x_asli = (x_norm × range) + min`, dengan `range = max - min` dari
   nilai `stok_bersih` komoditas tersebut (nilai `min`/`max` disimpan per proses preprocessing
   agar konsisten dengan tahap training).
3. RMSE, MAE, MAPE dihitung dari nilai **hasil denormalisasi** (satuan asli, bukan skala
   0–1) agar bermakna secara bisnis (mis. kg, unit).
4. Residual (`actual − predicted`), absolute error, dan absolute percentage error dihitung
   per titik data uji dan disimpan untuk analisis bias model.

### 3.7 Skema Forecasting (Peramalan Masa Depan)

Diimplementasikan sebagai **peramalan rekursif (recursive/autoregressive multi-step
forecasting)** di `forecast_next_year()`:

1. Ambil sekuens ternormalisasi **terakhir** yang tersedia sebagai titik awal.
2. Prediksi 1 langkah ke depan, clip ke [0,1].
3. Nilai prediksi tersebut **dimasukkan kembali** ke ekor sekuens, lalu diprediksi lagi
   untuk langkah berikutnya — diulang 365 kali.
4. Setiap hasil didenormalisasi dan disimpan sebagai satu baris forecast dengan
   `forecast_horizon_day` (hari ke berapa dari titik prediksi).

> **Keterbatasan metodologis penting** (wajib disebut di BAB III/skripsi sebagai batasan
> penelitian): karena bersifat rekursif, kesalahan prediksi di hari ke-N ikut menjadi input
> prediksi hari ke-(N+1), sehingga **akumulasi error meningkat seiring bertambahnya horizon
> forecast** — akurasi forecast 30 hari ke depan secara teoritis lebih andal daripada
> forecast hari ke-300.

### 3.8 Reproduksibilitas & Artefak Penelitian

- Model tersimpan per (batch, komoditas): `storage/models/batch_{batch_id}_{komoditas}.keras`.
- Setiap batch, run, metrik, prediksi, residual, forecast tersimpan di database
  (`lstm_batch_runs`, `lstm_model_runs`, `lstm_model_metrics`,
  `lstm_model_predictions`, `lstm_model_residuals`, `lstm_model_forecasts`) — memungkinkan
  perbandingan antar-eksperimen (hyperparameter tuning) secara historis, tanpa menimpa hasil
  sebelumnya kecuali dihapus manual.

---

## 4. BAB IV — Panduan Membaca Hasil dan Pembahasan

### 4.1 Struktur Data Hasil yang Tersedia untuk Dibahas

| Sumber di sistem | Isi | Cocok untuk |
|---|---|---|
| `/evaluasi` (index) | Daftar semua batch, status, ringkasan | Ringkasan eksperimen di awal BAB IV |
| `/evaluasi/batch/{id}` | Rekap RMSE/MAE/MAPE **per komoditas** dalam satu batch, model terbaik | Tabel perbandingan performa antar-komoditas |
| `/evaluasi/run/{id}` | Detail 1 komoditas: metrik, grafik aktual vs prediksi, grafik residual, grafik forecast | Pembahasan mendalam per komoditas / studi kasus |
| Export "Semua Data Evaluasi" (`/evaluasi/export/semua/csv`) | Seluruh angka mentah lintas batch dalam CSV (di-zip per batch) | Lampiran skripsi, olah data tambahan di Excel/Python untuk grafik kustom |

### 4.2 Cara Menyusun Narasi Pembahasan

1. **Bandingkan RMSE/MAE antar-komoditas** — komoditas dengan rentang nilai besar (mis.
   Beras dalam satuan kg ribuan) wajar punya RMSE/MAE absolut lebih besar; gunakan **MAPE**
   untuk perbandingan yang adil antar-komoditas karena sudah berbentuk persentase.
2. **Kaitkan MAPE dengan kategori akurasi** (tabel §2.8) — contoh kalimat pembahasan:
   > "Model untuk komoditas Beras memperoleh MAPE sebesar 8,4%, termasuk kategori sangat
   > baik (highly accurate) menurut klasifikasi Lewis (1982), menunjukkan model mampu
   > menangkap pola stok historis dengan baik."
3. **Baca grafik residual** — residual yang tersebar acak di sekitar nol menandakan model
   tidak bias sistematis; pola residual yang naik/turun teratur (tren atau musiman yang belum
   tertangkap) menandakan model masih *underfitting* terhadap pola tertentu.
4. **Baca grafik forecast** — jelaskan tren jangka panjang yang dihasilkan, tapi selalu
   sertakan catatan keterbatasan akumulasi error (lihat §3.7) agar pembahasan objektif secara
   ilmiah, terutama untuk horizon > 90–180 hari.
5. **Bandingkan efek hyperparameter** — karena setiap kombinasi hyperparameter menghasilkan
   *batch* baru yang tersimpan terpisah, BAB IV bisa memuat tabel eksperimen: mis. pengaruh
   `sequence_length` (7 vs 14 vs 30) atau `lstm_units` (32 vs 64 vs 128) terhadap RMSE/MAPE
   rata-rata — ini adalah bentuk *hyperparameter sensitivity analysis* sederhana yang mudah
   diproduksi ulang lewat UI training tanpa mengubah kode.

### 4.3 Batasan Penelitian (untuk disebut eksplisit di skripsi)

- Model **univariate** — tidak memodelkan faktor eksternal (harga, cuaca, hari besar) yang
  mungkin memengaruhi stok pangan nyata; hanya belajar dari pola historis stok itu sendiri.
- Split data latih/uji berbasis urutan waktu tunggal (holdout), bukan *walk-forward
  cross-validation* — cukup untuk skala skripsi, namun perlu disebut sebagai potensi
  pengembangan lanjutan (future work) untuk validasi yang lebih ketat.
- Forecast 365 hari bersifat rekursif sehingga rentan *error accumulation* (lihat §3.7).
- Deteksi outlier IQR bersifat statistik murni — tidak membedakan outlier akibat kesalahan
  input data vs lonjakan permintaan riil (mis. saat Ramadan/Lebaran), sehingga berpotensi
  menghaluskan pola musiman ekstrem yang justru penting secara bisnis.

### 4.4 Saran Pengembangan (Future Work, jika dibutuhkan BAB V)

- Menambahkan variabel eksogen (multivariate LSTM) seperti harga pasar atau kalender hari
  besar.
- Walk-forward validation / rolling origin evaluation untuk estimasi generalisasi yang lebih
  kuat dibanding single holdout split.
- Perbandingan dengan baseline non-deep-learning (ARIMA, Exponential Smoothing) untuk
  mengukur *added value* LSTM secara kuantitatif.
- Confidence interval / prediction interval pada forecast (mis. lewat quantile regression
  atau Monte Carlo Dropout) untuk mengomunikasikan ketidakpastian forecast jangka panjang.
