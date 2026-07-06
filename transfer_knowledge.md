# Transfer Knowledge — Panduan Sidang Skripsi

> Dokumen ini adalah bekal untuk menjelaskan **Sistem Prediksi Stok Pangan Lhokseumawe** di
> depan dosen penguji: penjelasan sistem dengan bahasa sederhana, alur demo yang disarankan,
> daftar istilah kunci, dan bank pertanyaan-jawaban (Q&A) yang kemungkinan besar ditanyakan.
> Baca `studi.md` untuk detail teori/rumus/metodologi — dokumen ini fokus ke cara
> **menjelaskan dan mempertahankan** sistem secara lisan.

---

## 1. Elevator Pitch (30 Detik)

> "Sistem ini membantu memprediksi stok pangan di Lhokseumawe untuk beberapa komoditas
> pokok — beras, bawang merah, cabai merah, daging sapi, dan telur ayam ras — menggunakan
> model deep learning bernama LSTM. Alurnya: data stok historis dibersihkan dan dinormalisasi,
> lalu dilatih jadi model prediksi per komoditas, hasilnya dievaluasi pakai RMSE/MAE/MAPE,
> dan sistem bisa memproyeksikan stok 1 tahun ke depan. Semua bisa dijalankan ulang dari panel
> admin tanpa perlu coding, dan hasilnya bisa diekspor untuk laporan."

---

## 2. Alur Sistem End-to-End (Bahasa Sederhana)

```
[1] Admin input data stok harian per komoditas
        ↓
[2] Sistem "membersihkan" data: isi tanggal bolong, perbaiki nilai aneh (outlier),
    lalu skalakan semua nilai ke rentang 0–1 (biar model gampang belajar)
        ↓
[3] Data dipotong-potong jadi "jendela" kecil (contoh: 7 hari terakhir → prediksi hari ke-8)
        ↓
[4] Model LSTM dilatih terpisah untuk TIAP komoditas (bukan 1 model gabungan)
        ↓
[5] Model diuji pakai data yang belum pernah dilihat → dihitung skor error (RMSE/MAE/MAPE)
        ↓
[6] Model dipakai memprediksi 365 hari ke depan (looping: hasil hari ini jadi input hari besok)
        ↓
[7] Semua hasil bisa dilihat di dashboard, diekspor CSV, dan ditampilkan ke publik
```

**Kalimat kunci kalau ditanya "kenapa per komoditas, bukan 1 model besar?"**
> "Karena setiap komoditas punya pola dan skala nilai yang berbeda jauh — misalnya beras
> dalam satuan ribuan kg, bawang merah dalam ratusan kg. Melatih model terpisah per komoditas
> membuat setiap model fokus mempelajari pola komoditas itu sendiri, dan performanya bisa
> dievaluasi & dibandingkan secara independen."

---

## 3. Peta Modul Aplikasi (Untuk Ditunjuk Saat Demo)

| Langkah demo | URL | Yang ditunjukkan | File kode utama |
|---|---|---|---|
| 1. Login | `/login` | Autentikasi admin | `AuthController.php` |
| 2. Kelola komoditas | `/komoditas` | Master data komoditas | `KomoditasController.php` |
| 3. Kelola stok historis | `/stok-historis` | Input/CRUD data stok harian | `StokHistorisController.php` |
| 4. Preprocessing | `/preprocessing` | Jalankan pembersihan + normalisasi + windowing, lihat ringkasan & preview, export hasil | `PreprocessingController.php`, `DataPreprocessingLstm.php` |
| 5. Training | `/lstm` | Atur hyperparameter, jalankan training batch semua komoditas sekaligus | `LstmController.php`, `database/train_lstm_batch.py` |
| 6. Evaluasi | `/evaluasi` → `/evaluasi/batch/{id}` → `/evaluasi/run/{id}` | Skor RMSE/MAE/MAPE, grafik prediksi vs aktual, residual, forecast 1 tahun | `LstmBatchRun.php` |
| 7. Export laporan | Tombol "Download Semua (CSV)" di `/evaluasi` | Semua data evaluasi dalam satu ZIP CSV | `LstmExportService.php`, `ExportResponse.php` |
| 8. Landing page publik | `/` | Forecast untuk masyarakat umum, mascot interaktif + text-to-speech | `HomeController.php` |

**Saran urutan demo saat sidang**: tunjukkan alur linear 1 → 7 di atas dengan data yang
*sudah* diproses sebelumnya (jangan training live saat sidang — training bisa memakan waktu
lama tergantung epoch/jumlah komoditas dan berjalan sebagai proses background terpisah dari
tampilan web).

---

## 4. Glosarium Cepat (Biar Lancar Menjelaskan)

| Istilah | Penjelasan singkat versi sidang |
|---|---|
| LSTM | Jenis jaringan saraf tiruan yang dirancang khusus untuk data berurutan (time series), punya "memori" untuk pola jangka panjang |
| Sequence length | Berapa hari data lampau yang dilihat model untuk memprediksi 1 hari berikutnya (default: 7 hari) |
| Train ratio | Persentase data yang dipakai untuk melatih model, sisanya untuk menguji (default: 80% latih, 20% uji) |
| Epoch | Satu putaran penuh model "belajar" dari seluruh data latih |
| Batch size | Jumlah data yang diproses bersamaan sebelum model memperbarui bobotnya |
| Dropout | Teknik mencegah model "menghafal" (overfitting) dengan mematikan sebagian neuron secara acak saat latihan |
| Normalisasi Min-Max | Mengubah semua nilai ke rentang 0–1 supaya model lebih mudah dan stabil belajar |
| Outlier (IQR) | Nilai yang jauh menyimpang dari kebanyakan data, dideteksi pakai rumus statistik kuartil |
| RMSE / MAE | Ukuran rata-rata kesalahan model dalam satuan asli data (kg/unit) |
| MAPE | Ukuran kesalahan model dalam bentuk persen — lebih mudah dibandingkan antar komoditas |
| Residual | Selisih antara nilai asli dan nilai prediksi model |
| Forecast rekursif | Cara memprediksi jauh ke depan dengan memakai hasil prediksi hari ini sebagai input prediksi hari besok, diulang-ulang |
| Batch training | Satu sesi pelatihan yang melatih model untuk SEMUA komoditas sekaligus, satu per satu |

---

## 5. Bank Pertanyaan & Jawaban (Prediksi Q&A Dosen Penguji)

### Tentang Metode

**Q: Kenapa pakai LSTM, bukan model lain seperti ARIMA atau regresi biasa?**
> LSTM dipilih karena mampu mempelajari pola non-linear dan dependensi jangka panjang pada
> data deret waktu tanpa perlu asumsi statistik ketat seperti stasioneritas pada ARIMA.
> (Jika ditanya "apakah sudah dibandingkan dengan ARIMA?" — jawab jujur: belum, itu jadi
> saran pengembangan/future work, lihat `studi.md` §4.4.)

**Q: Kenapa split data latih/uji tidak diacak (random)?**
> Karena ini data deret waktu — kalau diacak, model bisa "mengintip" data masa depan saat
> latihan (data leakage), yang membuat evaluasi menjadi tidak realistis. Split dilakukan
> berurutan berdasarkan waktu: bagian awal untuk latih, bagian akhir untuk uji.

**Q: Bagaimana menangani missing value dan outlier?**
> Missing value diisi otomatis untuk tanggal yang datanya kosong. Outlier dideteksi memakai
> metode IQR (Interquartile Range) — nilai di luar rentang wajar dianggap outlier. Keduanya
> lalu diisi (imputasi) dengan rata-rata nilai normal terdekat sebelum dan sesudahnya.

**Q: Kenapa data dinormalisasi ke 0–1?**
> Karena LSTM menggunakan fungsi aktivasi seperti tanh/sigmoid yang bekerja optimal pada
> input berskala kecil dan seragam; normalisasi juga membuat proses pelatihan lebih stabil
> dan cepat konvergen.

**Q: Bagaimana cara sistem memprediksi 1 tahun ke depan padahal cuma dilatih untuk 1 langkah
ke depan?**
> Menggunakan pendekatan rekursif/autoregressive: hasil prediksi hari ini dipakai sebagai
> input untuk memprediksi hari berikutnya, diulang sebanyak 365 kali. Konsekuensinya,
> kesalahan kecil bisa terakumulasi semakin jauh horizonnya — ini saya sebut sebagai
> keterbatasan penelitian di BAB III/V.

### Tentang Evaluasi

**Q: Apa bedanya RMSE, MAE, dan MAPE, kenapa pakai ketiganya?**
> RMSE dan MAE mengukur kesalahan dalam satuan asli data, tapi RMSE lebih sensitif terhadap
> kesalahan besar (outlier). MAPE mengukur kesalahan dalam persen sehingga adil dipakai untuk
> membandingkan antar komoditas yang skalanya jauh berbeda (misal beras vs bawang merah).

**Q: Berapa nilai MAPE yang dianggap bagus?**
> Secara umum: di bawah 10% dianggap sangat baik, 10–20% baik, 20–50% masih layak, di atas
> 50% dianggap kurang layak (acuan klasifikasi Lewis, 1982) — nilai spesifik hasil pengujian
> ada di halaman `/evaluasi` dan tabel BAB IV.

**Q: Kenapa hasil evaluasi tiap komoditas bisa beda jauh?**
> Karena tiap komoditas dilatih sebagai model terpisah dengan pola data historis yang
> berbeda — ada yang lebih stabil (fluktuasi kecil), ada yang lebih volatil (fluktuasi
> besar/banyak outlier), sehingga tingkat kesulitan prediksinya juga berbeda.

### Tentang Sistem/Implementasi

**Q: Bagasa apa yang dipakai membangun sistem ini, kenapa tidak pakai framework seperti
Laravel?**
> Sistem web dibangun dengan PHP native memakai arsitektur MVC sederhana buatan sendiri
> (router, controller, koneksi database) — dipilih agar ringan dan mudah dijelaskan alurnya
> tanpa "kotak hitam" framework besar. Proses training model LSTM sendiri memakai Python
> dengan TensorFlow/Keras, dipanggil sebagai proses terpisah dari sisi PHP.

**Q: Bagaimana PHP dan Python "berkomunikasi"?**
> Keduanya berbagi database MySQL yang sama. Saat tombol "Training" ditekan di web (PHP),
> sistem menyimpan konfigurasi ke database lalu menjalankan script Python sebagai proses
> latar belakang (background process). Python membaca konfigurasi dari database, melatih
> model, lalu menulis kembali hasilnya (metrik, prediksi, residual, forecast) ke tabel-tabel
> database yang sama — sehingga halaman web bisa langsung menampilkannya begitu Python
> selesai menulis.

**Q: Apakah trainingnya realtime/instan?**
> Tidak — training berjalan di background, bisa memakan waktu dari hitungan detik sampai
> menit tergantung jumlah epoch, jumlah komoditas, dan spesifikasi komputer. Halaman detail
> batch bisa dibuka ulang secara berkala untuk melihat progres/status terbaru.

**Q: Di mana model yang sudah dilatih disimpan?**
> Setiap model (per batch, per komoditas) disimpan sebagai file `.keras` di folder
> `storage/models/`, sehingga bisa dipakai ulang tanpa perlu melatih dari nol setiap saat
> hasil ingin dilihat.

**Q: Apakah semua hasil training tersimpan permanen, atau tertimpa tiap kali training baru?**
> Tersimpan semua, tidak tertimpa. Setiap kali tombol "Training" ditekan, sistem membuat
> **batch baru** dengan kode unik, sehingga histori eksperimen sebelumnya (dengan
> hyperparameter berbeda) tetap bisa dibandingkan di halaman evaluasi. Ada juga fitur reset
> manual kalau ingin membersihkan semua histori.

### Tentang Batasan & Keputusan Desain

**Q: Apa kelemahan/keterbatasan sistem ini?**
> Tiga hal utama: (1) model bersifat univariate, hanya belajar dari data stok itu sendiri
> tanpa faktor eksternal seperti harga atau hari besar; (2) forecast jangka panjang bersifat
> rekursif sehingga rawan akumulasi kesalahan; (3) validasi memakai satu kali pembagian
> data (holdout), bukan validasi berlapis (cross-validation) yang lebih ketat secara
> statistik.

**Q: Kalau ditanya "kenapa hyperparameter defaultnya segitu (7 hari, 80%, 30 epoch, dst)?"**
> Nilai-nilai itu adalah titik awal (default) yang wajar dipakai secara umum di penelitian
> peramalan time series berskala kecil-menengah, dan sengaja dibuat bisa diubah dari
> antarmuka (bukan hardcode) supaya eksperimen dengan kombinasi hyperparameter lain bisa
> dilakukan tanpa mengubah kode — hasil dari kombinasi berbeda tersimpan sebagai batch
> terpisah untuk dibandingkan.

---

## 6. Angka & Fakta Cepat (Cheat Sheet)

- Arsitektur model: `Input → LSTM(units) → Dropout → Dense(32, ReLU) → Dense(1, Linear)`
- Loss: MSE, metrik tambahan saat latihan: MAE
- Optimizer pilihan: Adam (default) atau RMSprop
- Default hyperparameter: sequence_length=7, train_ratio=0.8, epochs=30, batch_size=16,
  lstm_units=64, dropout=0.2, learning_rate=0.001
- Validasi internal training: 15% dari data latih (bagian akhir kronologis, bukan acak)
- Early stopping: sabar 6 epoch tanpa perbaikan `val_loss`, lalu kembalikan bobot terbaik
- Horizon forecast: 365 hari, dihitung rekursif
- Seed acak tetap (42) untuk TensorFlow & NumPy → hasil bisa direproduksi ulang
- Semua tahapan (preprocessing, training, evaluasi, forecast, export) bisa diulang dari
  antarmuka web tanpa menyentuh kode

---

## 7. Jika Ditanya Hal yang Tidak Bisa Dijawab Pasti

Jawaban paling aman dan jujur untuk pertanyaan di luar cakupan yang sudah diuji
(mis. "apakah sudah dibandingkan dengan model X", "bagaimana performa di data 5 tahun"):

> "Itu belum menjadi bagian dari cakupan penelitian ini, dan saya catat sebagai saran
> pengembangan lanjutan (future work) di BAB V — namun arsitektur sistem ini memang dirancang
> supaya eksperimen semacam itu bisa dijalankan langsung dari antarmuka tanpa mengubah kode,
> jadi memungkinkan untuk ditindaklanjuti."

Ini jauh lebih aman daripada menjawab spekulatif dengan angka yang tidak pernah benar-benar
diuji di sistem.
