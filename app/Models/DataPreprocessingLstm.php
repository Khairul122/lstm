<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use PDO;

final class DataPreprocessingLstm
{
    public static function commodityOptions(): array
    {
        $pdo = Database::connection();

        return $pdo->query(
            'SELECT DISTINCT k.nama_komoditas
             FROM data_stok_historis dsh
             INNER JOIN komoditas k ON k.id_komoditas = dsh.id_komoditas
             ORDER BY k.nama_komoditas ASC'
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function process(array $options): array
    {
        self::ensureTable();

        $commodity = trim((string) ($options['komoditas'] ?? ''));
        $sequenceLength = max(1, (int) ($options['sequence_length'] ?? 7));
        $trainRatio = (float) ($options['train_ratio'] ?? 0.8);
        $trainRatio = max(0.5, min(0.95, $trainRatio));

        $sourceRows = self::sourceRows($commodity !== '' ? $commodity : null);
        $grouped = self::groupByCommodity($sourceRows);

        $logs = [];
        $summaries = [];
        $previewRows = [];
        $savedRows = 0;

        if ($grouped === []) {
            return [
                'logs' => ['Tidak ada data stok historis yang dapat diproses.'],
                'summaries' => [],
                'previewRows' => [],
                'savedRows' => 0,
                'sequenceLength' => $sequenceLength,
                'trainRatio' => $trainRatio,
                'commodity' => $commodity,
            ];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            foreach ($grouped as $commodityName => $rows) {
                $prepared = self::prepareCommodityRows($commodityName, $rows, $sequenceLength, $trainRatio);

                // Setiap run selalu meregenerasi SELURUH rentang tanggal komoditas ini dari nol, jadi
                // baris lama untuk komoditas ini dibuang dulu sebelum insert ulang. Tanpa ini, baris
                // yang tanggalnya sudah tidak ada di data_stok_historis (mis. setelah dihapus/diedit)
                // tetap nyangkut selamanya di data_preprocessing_lstm dan ikut terpakai saat training.
                self::deleteCommodityRows($commodityName);
                self::persistRows($prepared['rows']);

                $savedRows += count($prepared['rows']);
                $summaries[] = $prepared['summary'];
                $logs[] = sprintf(
                    'Komoditas %s diproses: %d baris, %d missing value, %d outlier, min %.2f, max %.2f.',
                    $commodityName,
                    $prepared['summary']['total_data'],
                    $prepared['summary']['missing_value'],
                    $prepared['summary']['outlier'],
                    $prepared['summary']['min_stok_bersih'],
                    $prepared['summary']['max_stok_bersih']
                );

                foreach (array_slice($prepared['rows'], 0, 18) as $previewRow) {
                    $previewRows[] = $previewRow;
                }
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        usort(
            $previewRows,
            static fn (array $left, array $right): int => strcmp($right['format_waktu'], $left['format_waktu'])
        );

        return [
            'logs' => $logs,
            'summaries' => $summaries,
            'previewRows' => array_slice($previewRows, 0, 24),
            'savedRows' => $savedRows,
            'sequenceLength' => $sequenceLength,
            'trainRatio' => $trainRatio,
            'commodity' => $commodity,
        ];
    }

    public static function latestRows(string $commodity = '', int $limit = 30): array
    {
        self::ensureTable();

        $pdo = Database::connection();
        $sql = 'SELECT id,
                       tanggal_asli,
                       format_waktu,
                       komoditas,
                       stok_mentah,
                       status_anomali,
                       stok_bersih,
                       normalisasi_minmax,
                       input_sekuens_x,
                       target_label_y,
                       set_data,
                       created_at
                FROM data_preprocessing_lstm';

        if ($commodity !== '') {
            $sql .= ' WHERE komoditas = :komoditas';
        }

        $sql .= ' ORDER BY format_waktu DESC, komoditas ASC LIMIT :limit';
        $stmt = $pdo->prepare($sql);

        if ($commodity !== '') {
            $stmt->bindValue(':komoditas', $commodity, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function summaryPaginate(string $search = '', int $page = 1, int $perPage = 10): array
    {
        self::ensureTable();

        $pdo = Database::connection();
        $search = trim($search);
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $whereSql = '';
        if ($search !== '') {
            $whereSql = 'WHERE komoditas LIKE :search';
        }

        $countStmt = $pdo->prepare(
            'SELECT COUNT(DISTINCT komoditas) FROM data_preprocessing_lstm ' . $whereSql
        );

        if ($search !== '') {
            $countStmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }

        $countStmt->execute();
        $totalItems = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($totalItems / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $stmt = $pdo->prepare(
            'SELECT komoditas,
                    COUNT(*) AS total_data,
                    SUM(CASE WHEN status_anomali = "Missing Value" THEN 1 ELSE 0 END) AS missing_value,
                    SUM(CASE WHEN status_anomali = "Outlier" THEN 1 ELSE 0 END) AS outlier,
                    SUM(CASE WHEN set_data = "Latih" THEN 1 ELSE 0 END) AS data_latih,
                    SUM(CASE WHEN set_data = "Uji" THEN 1 ELSE 0 END) AS data_uji,
                    MIN(stok_bersih) AS min_stok_bersih,
                    MAX(stok_bersih) AS max_stok_bersih,
                    AVG(normalisasi_minmax) AS rata_normalisasi,
                    MIN(format_waktu) AS tanggal_awal,
                    MAX(format_waktu) AS tanggal_akhir
             FROM data_preprocessing_lstm
             ' . $whereSql . '
             GROUP BY komoditas
             ORDER BY komoditas ASC
             LIMIT :limit OFFSET :offset'
        );

        if ($search !== '') {
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'totalItems' => $totalItems,
            'perPage' => $perPage,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
        ];
    }

    public static function previewPaginate(string $search = '', int $page = 1, int $perPage = 15): array
    {
        self::ensureTable();

        $pdo = Database::connection();
        $search = trim($search);
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $whereSql = '';
        if ($search !== '') {
            $whereSql = 'WHERE komoditas LIKE :s1
                OR tanggal_asli LIKE :s2
                OR format_waktu LIKE :s3
                OR status_anomali LIKE :s4
                OR set_data LIKE :s5';
        }

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM data_preprocessing_lstm ' . $whereSql);
        if ($search !== '') {
            $term = '%' . $search . '%';
            $countStmt->bindValue(':s1', $term, PDO::PARAM_STR);
            $countStmt->bindValue(':s2', $term, PDO::PARAM_STR);
            $countStmt->bindValue(':s3', $term, PDO::PARAM_STR);
            $countStmt->bindValue(':s4', $term, PDO::PARAM_STR);
            $countStmt->bindValue(':s5', $term, PDO::PARAM_STR);
        }
        $countStmt->execute();

        $totalItems = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($totalItems / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $stmt = $pdo->prepare(
            'SELECT id,
                    tanggal_asli,
                    format_waktu,
                    komoditas,
                    stok_mentah,
                    status_anomali,
                    stok_bersih,
                    normalisasi_minmax,
                    input_sekuens_x,
                    target_label_y,
                    set_data,
                    created_at
             FROM data_preprocessing_lstm
             ' . $whereSql . '
             ORDER BY format_waktu DESC, komoditas ASC
             LIMIT :limit OFFSET :offset'
        );

        if ($search !== '') {
            $term = '%' . $search . '%';
            $stmt->bindValue(':s1', $term, PDO::PARAM_STR);
            $stmt->bindValue(':s2', $term, PDO::PARAM_STR);
            $stmt->bindValue(':s3', $term, PDO::PARAM_STR);
            $stmt->bindValue(':s4', $term, PDO::PARAM_STR);
            $stmt->bindValue(':s5', $term, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'totalItems' => $totalItems,
            'perPage' => $perPage,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
        ];
    }

    public static function summaryExportRows(): array
    {
        self::ensureTable();
        $pdo = Database::connection();

        return $pdo->query(
            'SELECT komoditas AS "Komoditas",
                    COUNT(*) AS "Total Data",
                    SUM(CASE WHEN status_anomali = "Missing Value" THEN 1 ELSE 0 END) AS "Missing Value",
                    SUM(CASE WHEN status_anomali = "Outlier" THEN 1 ELSE 0 END) AS "Outlier",
                    SUM(CASE WHEN set_data = "Latih" THEN 1 ELSE 0 END) AS "Data Latih",
                    SUM(CASE WHEN set_data = "Uji" THEN 1 ELSE 0 END) AS "Data Uji",
                    MIN(stok_bersih) AS "Min Stok Bersih",
                    MAX(stok_bersih) AS "Max Stok Bersih",
                    ROUND(AVG(normalisasi_minmax), 6) AS "Rata Normalisasi",
                    MIN(format_waktu) AS "Tanggal Awal",
                    MAX(format_waktu) AS "Tanggal Akhir"
             FROM data_preprocessing_lstm
             GROUP BY komoditas
             ORDER BY komoditas ASC'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function previewExportRows(): array
    {
        self::ensureTable();
        $pdo = Database::connection();

        return $pdo->query(
            'SELECT format_waktu AS "Tanggal",
                    komoditas AS "Komoditas",
                    IFNULL(stok_mentah, "-") AS "Stok Mentah",
                    status_anomali AS "Status Anomali",
                    stok_bersih AS "Stok Bersih",
                    ROUND(normalisasi_minmax, 6) AS "Normalisasi Min-Max",
                    input_sekuens_x AS "Input Sekuens X",
                    ROUND(target_label_y, 6) AS "Target Label Y",
                    set_data AS "Set Data"
             FROM data_preprocessing_lstm
             ORDER BY format_waktu DESC, komoditas ASC'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function evaluationSummary(string $commodity = ''): array
    {
        self::ensureTable();

        $pdo = Database::connection();
        $sql = 'SELECT komoditas,
                       COUNT(*) AS total_data,
                       SUM(CASE WHEN status_anomali = "Missing Value" THEN 1 ELSE 0 END) AS missing_value,
                       SUM(CASE WHEN status_anomali = "Outlier" THEN 1 ELSE 0 END) AS outlier,
                       SUM(CASE WHEN set_data = "Latih" THEN 1 ELSE 0 END) AS data_latih,
                       SUM(CASE WHEN set_data = "Uji" THEN 1 ELSE 0 END) AS data_uji,
                       MIN(stok_bersih) AS min_stok_bersih,
                       MAX(stok_bersih) AS max_stok_bersih,
                       AVG(normalisasi_minmax) AS rata_normalisasi,
                       MIN(format_waktu) AS tanggal_awal,
                       MAX(format_waktu) AS tanggal_akhir
                FROM data_preprocessing_lstm';

        if ($commodity !== '') {
            $sql .= ' WHERE komoditas = :komoditas';
        }

        $sql .= ' GROUP BY komoditas ORDER BY komoditas ASC';
        $stmt = $pdo->prepare($sql);

        if ($commodity !== '') {
            $stmt->bindValue(':komoditas', $commodity, PDO::PARAM_STR);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function resetAll(): void
    {
        self::ensureTable();
        $pdo = Database::connection();
        $pdo->exec('TRUNCATE TABLE data_preprocessing_lstm');
    }

    private static function ensureTable(): void
    {
        $pdo = Database::connection();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS data_preprocessing_lstm (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tanggal_asli VARCHAR(20) NOT NULL,
                format_waktu DATE NOT NULL,
                komoditas VARCHAR(50) NOT NULL,
                stok_mentah FLOAT NULL,
                status_anomali ENUM("Normal", "Missing Value", "Outlier") NOT NULL DEFAULT "Normal",
                stok_bersih FLOAT NOT NULL,
                normalisasi_minmax FLOAT NOT NULL,
                input_sekuens_x JSON NULL,
                target_label_y FLOAT NOT NULL,
                set_data ENUM("Latih", "Uji") NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_tanggal_komoditas (format_waktu, komoditas),
                INDEX idx_komoditas_waktu (komoditas, format_waktu),
                INDEX idx_set_data (set_data)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    private static function sourceRows(?string $commodity): array
    {
        $pdo = Database::connection();
        $sql = 'SELECT dsh.waktu_catat,
                       k.nama_komoditas AS komoditas,
                       dsh.jumlah_aktual AS stok_mentah
                FROM data_stok_historis dsh
                INNER JOIN komoditas k ON k.id_komoditas = dsh.id_komoditas';

        if ($commodity !== null) {
            $sql .= ' WHERE k.nama_komoditas = :komoditas';
        }

        $sql .= ' ORDER BY k.nama_komoditas ASC, dsh.waktu_catat ASC';
        $stmt = $pdo->prepare($sql);

        if ($commodity !== null) {
            $stmt->bindValue(':komoditas', $commodity, PDO::PARAM_STR);
        }

        $stmt->execute();

        return $stmt->fetchAll();
    }

    private static function groupByCommodity(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $grouped[$row['komoditas']][] = $row;
        }

        return $grouped;
    }

    private static function prepareCommodityRows(string $commodityName, array $rows, int $sequenceLength, float $trainRatio): array
    {
        $mapped = [];

        foreach ($rows as $row) {
            $mapped[$row['waktu_catat']] = [
                'tanggal_asli' => (string) $row['waktu_catat'],
                'stok_mentah' => $row['stok_mentah'] !== null ? (float) $row['stok_mentah'] : null,
            ];
        }

        if ($mapped === []) {
            return [
                'rows' => [],
                'summary' => [],
            ];
        }

        $dates = array_keys($mapped);
        sort($dates);
        $startDate = new DateTimeImmutable((string) reset($dates));
        $endDate = new DateTimeImmutable((string) end($dates));
        $period = new DatePeriod($startDate, new DateInterval('P1D'), $endDate->modify('+1 day'));

        // Bentuk kerangka baris untuk seluruh rentang tanggal terlebih dahulu, sebelum status
        // anomali/statistik apa pun dihitung.
        $skeleton = [];
        foreach ($period as $date) {
            $dateKey = $date->format('Y-m-d');
            $skeleton[] = [
                'tanggal_asli' => $mapped[$dateKey]['tanggal_asli'] ?? $dateKey,
                'format_waktu' => $dateKey,
                'komoditas' => $commodityName,
                'stok_mentah' => $mapped[$dateKey]['stok_mentah'] ?? null,
            ];
        }

        // Titik potong latih/uji dihitung lebih dulu dari jumlah baris saja (tidak bergantung pada
        // statistik apa pun), sehingga seluruh statistik di bawah ini bisa dibatasi hanya pada porsi
        // data latih.
        $splitIndex = (int) floor(count($skeleton) * $trainRatio);

        // PENCEGAHAN DATA LEAKAGE: Q1/Q3/IQR untuk deteksi outlier dihitung HANYA dari nilai mentah
        // pada porsi data latih (indeks < $splitIndex), bukan dari seluruh data historis. Jika
        // dihitung dari seluruh data (termasuk periode uji), batas kewajaran outlier akan
        // "mengetahui" sebaran nilai pada periode uji sebelum model sempat dilatih, sehingga
        // parameter praproses ikut bocor dari masa depan.
        $trainRawValues = [];
        foreach (array_slice($skeleton, 0, $splitIndex) as $item) {
            if ($item['stok_mentah'] !== null) {
                $trainRawValues[] = (float) $item['stok_mentah'];
            }
        }

        // Fallback untuk kasus degenerate: komoditas dengan riwayat stok sangat sedikit (mis. baru
        // 1-2 hari data) membuat floor(jumlah_hari * train_ratio) == 0, sehingga porsi latih kosong.
        // Tanpa fallback ini Q1/Q3 selalu 0 dan SEMUA baris tertandai "Outlier" secara keliru. Pada
        // kasus seperti ini memang tidak ada batas latih/uji yang berarti, jadi aman memakai seluruh
        // baris komoditas sebagai statistik cadangan.
        if ($trainRawValues === []) {
            foreach ($skeleton as $item) {
                if ($item['stok_mentah'] !== null) {
                    $trainRawValues[] = (float) $item['stok_mentah'];
                }
            }
        }
        sort($trainRawValues);
        $q1 = self::quantile($trainRawValues, 0.25);
        $q3 = self::quantile($trainRawValues, 0.75);
        $iqr = $q3 - $q1;
        $lowerBound = $q1 - (1.5 * $iqr);
        $upperBound = $q3 + (1.5 * $iqr);
        $median = self::quantile($trainRawValues, 0.5);

        $prepared = [];

        foreach ($skeleton as $item) {
            $rawStock = $item['stok_mentah'];
            $status = 'Normal';

            if ($rawStock === null) {
                $status = 'Missing Value';
            } elseif ($rawStock < $lowerBound || $rawStock > $upperBound) {
                $status = 'Outlier';
            }

            $item['status_anomali'] = $status;
            $prepared[] = $item;
        }

        foreach ($prepared as $index => $item) {
            if ($item['status_anomali'] === 'Normal' && $item['stok_mentah'] !== null) {
                $prepared[$index]['stok_bersih'] = (float) $item['stok_mentah'];
                continue;
            }

            $prepared[$index]['stok_bersih'] = self::replacementValue($prepared, $index, $splitIndex, $median);
        }

        // PENCEGAHAN DATA LEAKAGE: parameter normalisasi Min-Max (xmin, xmax) juga HANYA dihitung
        // dari stok_bersih pada porsi data latih, lalu parameter yang sama (beku/frozen) dipakai
        // untuk menormalisasi seluruh baris termasuk data uji. Ini mengikuti prinsip fit-on-train,
        // transform-on-all yang wajib pada validasi deret waktu, sehingga skala normalisasi yang
        // dipelajari model tidak "mengintip" rentang nilai pada periode uji.
        $trainCleanValues = array_map(
            static fn (array $item): float => (float) $item['stok_bersih'],
            array_slice($prepared, 0, $splitIndex)
        );

        // Fallback yang sama seperti di atas: porsi latih kosong membuat min()/max() throw
        // ValueError (crash total, bukan exception yang bisa ditangani dengan baik) kalau tidak
        // dijaga di sini.
        if ($trainCleanValues === []) {
            $trainCleanValues = array_map(
                static fn (array $item): float => (float) $item['stok_bersih'],
                $prepared
            );
        }

        $minClean = min($trainCleanValues);
        $maxClean = max($trainCleanValues);
        $range = $maxClean - $minClean;

        foreach ($prepared as $index => $item) {
            $normalized = $range > 0 ? (($item['stok_bersih'] - $minClean) / $range) : 1.0;
            $prepared[$index]['normalisasi_minmax'] = round($normalized, 6);
        }

        $normalizedSeries = array_map(static fn (array $item): float => (float) $item['normalisasi_minmax'], $prepared);

        foreach ($prepared as $index => $item) {
            $sequence = self::buildSequence($normalizedSeries, $index, $sequenceLength);
            $prepared[$index]['input_sekuens_x'] = json_encode($sequence, JSON_UNESCAPED_UNICODE);
            $prepared[$index]['target_label_y'] = $item['normalisasi_minmax'];
            $prepared[$index]['set_data'] = $index < $splitIndex ? 'Latih' : 'Uji';
        }

        $missingCount = 0;
        $outlierCount = 0;

        foreach ($prepared as $item) {
            if ($item['status_anomali'] === 'Missing Value') {
                $missingCount++;
            }

            if ($item['status_anomali'] === 'Outlier') {
                $outlierCount++;
            }
        }

        return [
            'rows' => $prepared,
            'summary' => [
                'komoditas' => $commodityName,
                'total_data' => count($prepared),
                'missing_value' => $missingCount,
                'outlier' => $outlierCount,
                'data_latih' => $splitIndex,
                'data_uji' => count($prepared) - $splitIndex,
                'min_stok_bersih' => round($minClean, 2),
                'max_stok_bersih' => round($maxClean, 2),
                'rata_normalisasi' => round(array_sum($normalizedSeries) / max(1, count($normalizedSeries)), 6),
                'tanggal_awal' => $prepared[0]['format_waktu'],
                'tanggal_akhir' => $prepared[count($prepared) - 1]['format_waktu'],
            ],
        ];
    }

    private static function deleteCommodityRows(string $commodityName): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('DELETE FROM data_preprocessing_lstm WHERE komoditas = :komoditas');
        $stmt->bindValue(':komoditas', $commodityName, PDO::PARAM_STR);
        $stmt->execute();
    }

    private static function persistRows(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO data_preprocessing_lstm (
                tanggal_asli,
                format_waktu,
                komoditas,
                stok_mentah,
                status_anomali,
                stok_bersih,
                normalisasi_minmax,
                input_sekuens_x,
                target_label_y,
                set_data
            ) VALUES (
                :tanggal_asli,
                :format_waktu,
                :komoditas,
                :stok_mentah,
                :status_anomali,
                :stok_bersih,
                :normalisasi_minmax,
                :input_sekuens_x,
                :target_label_y,
                :set_data
            )
            ON DUPLICATE KEY UPDATE
                tanggal_asli = VALUES(tanggal_asli),
                stok_mentah = VALUES(stok_mentah),
                status_anomali = VALUES(status_anomali),
                stok_bersih = VALUES(stok_bersih),
                normalisasi_minmax = VALUES(normalisasi_minmax),
                input_sekuens_x = VALUES(input_sekuens_x),
                target_label_y = VALUES(target_label_y),
                set_data = VALUES(set_data)'
        );

        foreach ($rows as $row) {
            $stmt->bindValue(':tanggal_asli', $row['tanggal_asli'], PDO::PARAM_STR);
            $stmt->bindValue(':format_waktu', $row['format_waktu'], PDO::PARAM_STR);
            $stmt->bindValue(':komoditas', $row['komoditas'], PDO::PARAM_STR);

            if ($row['stok_mentah'] === null) {
                $stmt->bindValue(':stok_mentah', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':stok_mentah', (float) $row['stok_mentah']);
            }

            $stmt->bindValue(':status_anomali', $row['status_anomali'], PDO::PARAM_STR);
            $stmt->bindValue(':stok_bersih', (float) $row['stok_bersih']);
            $stmt->bindValue(':normalisasi_minmax', (float) $row['normalisasi_minmax']);
            $stmt->bindValue(':input_sekuens_x', $row['input_sekuens_x'], PDO::PARAM_STR);
            $stmt->bindValue(':target_label_y', (float) $row['target_label_y']);
            $stmt->bindValue(':set_data', $row['set_data'], PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    private static function replacementValue(array $rows, int $currentIndex, int $splitIndex, float $fallback): float
    {
        // PENCEGAHAN DATA LEAKAGE: pencarian tetangga untuk interpolasi tidak boleh melewati batas
        // latih/uji. Tanpa batas ini, nilai bersih pada baris LATIH bisa "mengintip" angka mentah
        // dari periode UJI (atau sebaliknya) lewat proses imputasi missing value/outlier.
        $isTrainRow = $currentIndex < $splitIndex;
        $lowerBound = $isTrainRow ? 0 : $splitIndex;
        $upperBound = $isTrainRow ? $splitIndex - 1 : count($rows) - 1;

        $previous = null;
        for ($index = $currentIndex - 1; $index >= $lowerBound; $index--) {
            if ($rows[$index]['status_anomali'] === 'Normal' && $rows[$index]['stok_mentah'] !== null) {
                $previous = (float) $rows[$index]['stok_mentah'];
                break;
            }
        }

        $next = null;
        for ($index = $currentIndex + 1; $index <= $upperBound; $index++) {
            if ($rows[$index]['status_anomali'] === 'Normal' && $rows[$index]['stok_mentah'] !== null) {
                $next = (float) $rows[$index]['stok_mentah'];
                break;
            }
        }

        if ($previous !== null && $next !== null) {
            return round(($previous + $next) / 2, 2);
        }

        if ($previous !== null) {
            return round($previous, 2);
        }

        if ($next !== null) {
            return round($next, 2);
        }

        return round($fallback, 2);
    }

    private static function buildSequence(array $normalizedSeries, int $currentIndex, int $sequenceLength): array
    {
        $sequence = [];
        $firstValue = (float) ($normalizedSeries[0] ?? 0.0);

        for ($step = $sequenceLength; $step >= 1; $step--) {
            $sequenceIndex = $currentIndex - $step;
            $sequence[] = round($sequenceIndex >= 0 ? (float) $normalizedSeries[$sequenceIndex] : $firstValue, 6);
        }

        return $sequence;
    }

    private static function quantile(array $values, float $quantile): float
    {
        $count = count($values);

        if ($count === 0) {
            return 0.0;
        }

        if ($count === 1) {
            return (float) $values[0];
        }

        $position = ($count - 1) * $quantile;
        $lower = (int) floor($position);
        $upper = (int) ceil($position);

        if ($lower === $upper) {
            return (float) $values[$lower];
        }

        $weight = $position - $lower;

        return ((1 - $weight) * (float) $values[$lower]) + ($weight * (float) $values[$upper]);
    }

    public static function dashboardSummary(): array
    {
        self::ensureTable();

        $pdo = Database::connection();
        $totalRows = (int) $pdo->query('SELECT COUNT(*) FROM data_preprocessing_lstm')->fetchColumn();
        $totalCommodity = (int) $pdo->query('SELECT COUNT(DISTINCT komoditas) FROM data_preprocessing_lstm')->fetchColumn();

        $summaryStmt = $pdo->query(
            'SELECT
                SUM(CASE WHEN status_anomali = "Missing Value" THEN 1 ELSE 0 END) AS total_missing,
                SUM(CASE WHEN status_anomali = "Outlier" THEN 1 ELSE 0 END) AS total_outlier,
                SUM(CASE WHEN set_data = "Latih" THEN 1 ELSE 0 END) AS total_latih,
                SUM(CASE WHEN set_data = "Uji" THEN 1 ELSE 0 END) AS total_uji,
                MAX(format_waktu) AS latest_date
             FROM data_preprocessing_lstm'
        );
        $summary = $summaryStmt->fetch();

        return [
            'total_rows' => $totalRows,
            'total_commodity' => $totalCommodity,
            'total_missing' => (int) ($summary['total_missing'] ?? 0),
            'total_outlier' => (int) ($summary['total_outlier'] ?? 0),
            'total_latih' => (int) ($summary['total_latih'] ?? 0),
            'total_uji' => (int) ($summary['total_uji'] ?? 0),
            'latest_date' => (string) ($summary['latest_date'] ?? '-'),
        ];
    }
}
