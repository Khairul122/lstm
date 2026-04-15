<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use PDO;

final class LstmBatchRun
{
    public static function ensureTables(): void
    {
        $pdo = Database::connection();

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS lstm_batch_runs (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                batch_code VARCHAR(40) NOT NULL UNIQUE,
                status ENUM('queued', 'running', 'completed', 'completed_with_errors', 'failed') NOT NULL DEFAULT 'queued',
                total_komoditas INT NOT NULL DEFAULT 0,
                completed_komoditas INT NOT NULL DEFAULT 0,
                failed_komoditas INT NOT NULL DEFAULT 0,
                sequence_length INT NOT NULL,
                train_ratio FLOAT NOT NULL,
                epochs INT NOT NULL,
                batch_size INT NOT NULL,
                lstm_units INT NOT NULL,
                dropout_rate FLOAT NOT NULL DEFAULT 0.2,
                optimizer VARCHAR(30) NOT NULL DEFAULT 'adam',
                learning_rate FLOAT NOT NULL DEFAULT 0.001,
                notes TEXT NULL,
                train_started_at DATETIME NULL,
                train_finished_at DATETIME NULL,
                duration_seconds INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_batch_status (status),
                INDEX idx_batch_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS lstm_model_runs (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                batch_id BIGINT NOT NULL,
                komoditas VARCHAR(50) NOT NULL,
                status ENUM('queued', 'running', 'completed', 'failed') NOT NULL DEFAULT 'queued',
                train_samples INT NOT NULL DEFAULT 0,
                test_samples INT NOT NULL DEFAULT 0,
                model_path VARCHAR(255) NULL,
                notes TEXT NULL,
                error_message TEXT NULL,
                train_started_at DATETIME NULL,
                train_finished_at DATETIME NULL,
                duration_seconds INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_lstm_model_runs_batch FOREIGN KEY (batch_id) REFERENCES lstm_batch_runs(id) ON DELETE CASCADE ON UPDATE CASCADE,
                INDEX idx_model_runs_batch (batch_id),
                INDEX idx_model_runs_komoditas (komoditas),
                INDEX idx_model_runs_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS lstm_model_metrics (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                run_id BIGINT NOT NULL,
                komoditas VARCHAR(50) NOT NULL,
                rmse FLOAT NOT NULL,
                mae FLOAT NOT NULL,
                mape FLOAT NOT NULL,
                train_loss_final FLOAT NULL,
                val_loss_final FLOAT NULL,
                best_epoch INT NULL,
                train_samples INT NOT NULL DEFAULT 0,
                test_samples INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_lstm_metrics_run FOREIGN KEY (run_id) REFERENCES lstm_model_runs(id) ON DELETE CASCADE ON UPDATE CASCADE,
                UNIQUE KEY uniq_metrics_run (run_id),
                INDEX idx_metrics_komoditas (komoditas)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS lstm_model_predictions (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                run_id BIGINT NOT NULL,
                komoditas VARCHAR(50) NOT NULL,
                tanggal DATE NOT NULL,
                dataset_type ENUM('Latih', 'Uji') NOT NULL DEFAULT 'Uji',
                actual_normalized FLOAT NOT NULL,
                predicted_normalized FLOAT NOT NULL,
                actual_denormalized FLOAT NOT NULL,
                predicted_denormalized FLOAT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_lstm_predictions_run FOREIGN KEY (run_id) REFERENCES lstm_model_runs(id) ON DELETE CASCADE ON UPDATE CASCADE,
                INDEX idx_predictions_run (run_id),
                INDEX idx_predictions_komoditas_tanggal (komoditas, tanggal)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS lstm_model_residuals (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                run_id BIGINT NOT NULL,
                komoditas VARCHAR(50) NOT NULL,
                tanggal DATE NOT NULL,
                actual_value FLOAT NOT NULL,
                predicted_value FLOAT NOT NULL,
                residual FLOAT NOT NULL,
                absolute_error FLOAT NOT NULL,
                absolute_percentage_error FLOAT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_lstm_residuals_run FOREIGN KEY (run_id) REFERENCES lstm_model_runs(id) ON DELETE CASCADE ON UPDATE CASCADE,
                INDEX idx_residuals_run (run_id),
                INDEX idx_residuals_komoditas_tanggal (komoditas, tanggal)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS lstm_model_forecasts (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                run_id BIGINT NOT NULL,
                komoditas VARCHAR(50) NOT NULL,
                tanggal_forecast DATE NOT NULL,
                forecast_horizon_day INT NOT NULL,
                forecast_normalized FLOAT NOT NULL,
                forecast_denormalized FLOAT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_lstm_forecasts_run FOREIGN KEY (run_id) REFERENCES lstm_model_runs(id) ON DELETE CASCADE ON UPDATE CASCADE,
                INDEX idx_forecasts_run (run_id),
                INDEX idx_forecasts_komoditas_tanggal (komoditas, tanggal_forecast)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }

    public static function createBatch(array $payload): int
    {
        self::ensureTables();
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO lstm_batch_runs (
                batch_code,
                status,
                sequence_length,
                train_ratio,
                epochs,
                batch_size,
                lstm_units,
                dropout_rate,
                optimizer,
                learning_rate,
                notes
            ) VALUES (
                :batch_code,
                :status,
                :sequence_length,
                :train_ratio,
                :epochs,
                :batch_size,
                :lstm_units,
                :dropout_rate,
                :optimizer,
                :learning_rate,
                :notes
            )'
        );
        $stmt->execute([
            ':batch_code' => $payload['batch_code'],
            ':status' => $payload['status'],
            ':sequence_length' => $payload['sequence_length'],
            ':train_ratio' => $payload['train_ratio'],
            ':epochs' => $payload['epochs'],
            ':batch_size' => $payload['batch_size'],
            ':lstm_units' => $payload['lstm_units'],
            ':dropout_rate' => $payload['dropout_rate'],
            ':optimizer' => $payload['optimizer'],
            ':learning_rate' => $payload['learning_rate'],
            ':notes' => $payload['notes'],
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function paginate(string $search = '', int $page = 1, int $perPage = 10): array
    {
        self::ensureTables();
        $pdo = Database::connection();
        $search = trim($search);
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $whereSql = '';
        if ($search !== '') {
            $whereSql = 'WHERE batch_code LIKE :search OR status LIKE :search OR notes LIKE :search';
        }

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM lstm_batch_runs ' . $whereSql);
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
            'SELECT * FROM lstm_batch_runs ' . $whereSql . ' ORDER BY id DESC LIMIT :limit OFFSET :offset'
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

    public static function latest(): ?array
    {
        self::ensureTables();
        $pdo = Database::connection();
        $stmt = $pdo->query('SELECT * FROM lstm_batch_runs ORDER BY id DESC LIMIT 1');
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public static function summaryStats(): array
    {
        self::ensureTables();
        $pdo = Database::connection();

        $batchCount = (int) $pdo->query('SELECT COUNT(*) FROM lstm_batch_runs')->fetchColumn();
        $completedCount = (int) $pdo->query("SELECT COUNT(*) FROM lstm_batch_runs WHERE status IN ('completed', 'completed_with_errors')")->fetchColumn();
        $runningCount = (int) $pdo->query("SELECT COUNT(*) FROM lstm_batch_runs WHERE status = 'running'")->fetchColumn();
        $failedCount = (int) $pdo->query("SELECT COUNT(*) FROM lstm_batch_runs WHERE status = 'failed'")->fetchColumn();

        return [
            'batch_count' => $batchCount,
            'completed_count' => $completedCount,
            'running_count' => $runningCount,
            'failed_count' => $failedCount,
        ];
    }

    public static function find(int $id): ?array
    {
        self::ensureTables();
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM lstm_batch_runs WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public static function commodityRuns(int $batchId, string $search = '', int $page = 1, int $perPage = 10): array
    {
        self::ensureTables();
        $pdo = Database::connection();
        $search = trim($search);
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $whereSql = 'WHERE r.batch_id = :batch_id';
        if ($search !== '') {
            $whereSql .= ' AND (r.komoditas LIKE :search OR r.status LIKE :search)';
        }

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM lstm_model_runs r ' . $whereSql);
        $countStmt->bindValue(':batch_id', $batchId, PDO::PARAM_INT);
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
            'SELECT r.*, m.rmse, m.mae, m.mape, m.train_loss_final, m.val_loss_final
             FROM lstm_model_runs r
             LEFT JOIN lstm_model_metrics m ON m.run_id = r.id
             ' . $whereSql . '
             ORDER BY r.id ASC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':batch_id', $batchId, PDO::PARAM_INT);
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

    public static function evaluationRecap(int $batchId): array
    {
        self::ensureTables();
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT r.komoditas,
                    r.status,
                    r.train_samples,
                    r.test_samples,
                    m.rmse,
                    m.mae,
                    m.mape,
                    m.train_loss_final,
                    m.val_loss_final,
                    m.best_epoch
             FROM lstm_model_runs r
             LEFT JOIN lstm_model_metrics m ON m.run_id = r.id
             WHERE r.batch_id = :batch_id
             ORDER BY r.komoditas ASC'
        );
        $stmt->bindValue(':batch_id', $batchId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function bestRunForBatch(int $batchId): ?array
    {
        self::ensureTables();
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT r.*, m.rmse, m.mae, m.mape, m.train_loss_final, m.val_loss_final, m.best_epoch
             FROM lstm_model_runs r
             INNER JOIN lstm_model_metrics m ON m.run_id = r.id
             WHERE r.batch_id = :batch_id AND r.status = "completed"
             ORDER BY m.rmse ASC
             LIMIT 1'
        );
        $stmt->bindValue(':batch_id', $batchId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public static function findRun(int $runId): ?array
    {
        self::ensureTables();
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT r.*, m.rmse, m.mae, m.mape, m.train_loss_final, m.val_loss_final, m.best_epoch
             FROM lstm_model_runs r
             LEFT JOIN lstm_model_metrics m ON m.run_id = r.id
             WHERE r.id = :id
             LIMIT 1'
        );
        $stmt->bindValue(':id', $runId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public static function predictionsPaginate(int $runId, string $search = '', int $page = 1, int $perPage = 15): array
    {
        return self::paginateChildTable(
            'SELECT * FROM lstm_model_predictions WHERE run_id = :run_id',
            'SELECT COUNT(*) FROM lstm_model_predictions WHERE run_id = :run_id',
            ' AND (komoditas LIKE :search OR tanggal LIKE :search OR dataset_type LIKE :search)',
            ' ORDER BY tanggal ASC LIMIT :limit OFFSET :offset',
            $runId,
            $search,
            $page,
            $perPage
        );
    }

    public static function residualsPaginate(int $runId, string $search = '', int $page = 1, int $perPage = 15): array
    {
        return self::paginateChildTable(
            'SELECT * FROM lstm_model_residuals WHERE run_id = :run_id',
            'SELECT COUNT(*) FROM lstm_model_residuals WHERE run_id = :run_id',
            ' AND (komoditas LIKE :search OR tanggal LIKE :search)',
            ' ORDER BY tanggal ASC LIMIT :limit OFFSET :offset',
            $runId,
            $search,
            $page,
            $perPage
        );
    }

    public static function forecastsPaginate(int $runId, string $search = '', int $page = 1, int $perPage = 15): array
    {
        return self::paginateChildTable(
            'SELECT * FROM lstm_model_forecasts WHERE run_id = :run_id',
            'SELECT COUNT(*) FROM lstm_model_forecasts WHERE run_id = :run_id',
            ' AND (komoditas LIKE :search OR tanggal_forecast LIKE :search)',
            ' ORDER BY tanggal_forecast ASC LIMIT :limit OFFSET :offset',
            $runId,
            $search,
            $page,
            $perPage
        );
    }

    public static function predictionSeries(int $runId): array
    {
        self::ensureTables();
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT tanggal, actual_denormalized, predicted_denormalized
             FROM lstm_model_predictions
             WHERE run_id = :run_id
             ORDER BY tanggal ASC'
        );
        $stmt->bindValue(':run_id', $runId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function residualSeries(int $runId): array
    {
        self::ensureTables();
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT tanggal, residual, absolute_error
             FROM lstm_model_residuals
             WHERE run_id = :run_id
             ORDER BY tanggal ASC'
        );
        $stmt->bindValue(':run_id', $runId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function forecastSeries(int $runId): array
    {
        self::ensureTables();
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT tanggal_forecast, forecast_denormalized
             FROM lstm_model_forecasts
             WHERE run_id = :run_id
             ORDER BY tanggal_forecast ASC'
        );
        $stmt->bindValue(':run_id', $runId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function predictionRows(int $runId): array
    {
        self::ensureTables();
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT komoditas, tanggal, dataset_type, actual_normalized, predicted_normalized, actual_denormalized, predicted_denormalized
             FROM lstm_model_predictions
             WHERE run_id = :run_id
             ORDER BY tanggal ASC'
        );
        $stmt->bindValue(':run_id', $runId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function residualRows(int $runId): array
    {
        self::ensureTables();
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT komoditas, tanggal, actual_value, predicted_value, residual, absolute_error, absolute_percentage_error
             FROM lstm_model_residuals
             WHERE run_id = :run_id
             ORDER BY tanggal ASC'
        );
        $stmt->bindValue(':run_id', $runId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function forecastRows(int $runId): array
    {
        self::ensureTables();
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT komoditas, tanggal_forecast, forecast_horizon_day, forecast_normalized, forecast_denormalized
             FROM lstm_model_forecasts
             WHERE run_id = :run_id
             ORDER BY tanggal_forecast ASC'
        );
        $stmt->bindValue(':run_id', $runId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function historicalOverview(string $commodity, int $limit = 365): array
    {
        self::ensureTables();
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT format_waktu, stok_bersih
             FROM data_preprocessing_lstm
             WHERE komoditas = :komoditas
             ORDER BY format_waktu DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':komoditas', $commodity, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_reverse($stmt->fetchAll());
    }

    public static function landingForecastSummary(): array
    {
        self::ensureTables();
        $pdo = Database::connection();

        $latestBatch = self::latest();
        if ($latestBatch === null) {
            return [
                'latestBatch' => null,
                'bestRun' => null,
                'forecastCards' => [],
                'forecastChart' => [],
            ];
        }

        $batchId = (int) $latestBatch['id'];
        $bestRun = self::bestRunForBatch($batchId);

        $forecastCardsStmt = $pdo->prepare(
            'SELECT r.id AS run_id,
                    r.komoditas,
                    f.tanggal_forecast,
                    f.forecast_denormalized,
                    m.rmse,
                    m.mae,
                    m.mape
             FROM lstm_model_runs r
             INNER JOIN lstm_model_metrics m ON m.run_id = r.id
             INNER JOIN lstm_model_forecasts f ON f.run_id = r.id
             WHERE r.batch_id = :batch_id
               AND f.forecast_horizon_day = 365
             ORDER BY r.komoditas ASC'
        );
        $forecastCardsStmt->bindValue(':batch_id', $batchId, PDO::PARAM_INT);
        $forecastCardsStmt->execute();
        $forecastCards = $forecastCardsStmt->fetchAll();

        $chartStmt = $pdo->prepare(
            'SELECT r.komoditas,
                    f.tanggal_forecast,
                    f.forecast_denormalized
             FROM lstm_model_runs r
             INNER JOIN lstm_model_forecasts f ON f.run_id = r.id
             WHERE r.batch_id = :batch_id
               AND f.forecast_horizon_day IN (1, 30, 90, 180, 365)
             ORDER BY r.komoditas ASC, f.forecast_horizon_day ASC'
        );
        $chartStmt->bindValue(':batch_id', $batchId, PDO::PARAM_INT);
        $chartStmt->execute();

        return [
            'latestBatch' => $latestBatch,
            'bestRun' => $bestRun,
            'forecastCards' => $forecastCards,
            'forecastChart' => $chartStmt->fetchAll(),
        ];
    }

    public static function landingForecastPaginate(
        string $search = '',
        string $commodity = '',
        string $status = '',
        int $page = 1,
        int $perPage = 10
    ): array {
        self::ensureTables();
        $pdo = Database::connection();
        $latestBatch = self::latest();

        if ($latestBatch === null) {
            return [
                'items' => [],
                'commodityOptions' => [],
                'totalItems' => 0,
                'perPage' => $perPage,
                'currentPage' => 1,
                'totalPages' => 1,
                'search' => trim($search),
                'commodity' => trim($commodity),
                'status' => trim($status),
            ];
        }

        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $search = trim($search);
        $commodity = trim($commodity);
        $status = trim($status);
        $offset = ($page - 1) * $perPage;
        $commodityStmt = $pdo->prepare(
            'SELECT DISTINCT r.komoditas
              FROM lstm_model_runs r
              INNER JOIN lstm_model_forecasts f ON f.run_id = r.id
             ORDER BY r.komoditas ASC'
        );
        $commodityStmt->execute();
        $commodityOptions = array_map(
            static fn(array $row): string => (string) $row['komoditas'],
            $commodityStmt->fetchAll()
        );

        $whereSql = ' WHERE 1=1';
        if ($search !== '') {
            $whereSql .= ' AND (r.komoditas LIKE :search OR f.tanggal_forecast LIKE :search OR latest_snapshot.lokasi_gudang LIKE :search OR b.batch_code LIKE :search)';
        }
        if ($commodity !== '') {
            $whereSql .= ' AND r.komoditas = :commodity';
        }
        if ($status !== '') {
            if ($status === 'safe') {
                $whereSql .= ' AND m.mape <= 10';
            } elseif ($status === 'watchlist') {
                $whereSql .= ' AND m.mape > 10 AND m.mape <= 20';
            } elseif ($status === 'warning') {
                $whereSql .= ' AND m.mape > 20';
            }
        }

        $baseFrom =
            ' FROM lstm_model_runs r
              INNER JOIN lstm_batch_runs b ON b.id = r.batch_id
              INNER JOIN lstm_model_metrics m ON m.run_id = r.id
              INNER JOIN lstm_model_forecasts f ON f.run_id = r.id
              LEFT JOIN (
                    SELECT k.nama_komoditas, dsh.jumlah_aktual, k.satuan, dsh.lokasi_gudang
                    FROM data_stok_historis dsh
                   INNER JOIN komoditas k ON k.id_komoditas = dsh.id_komoditas
                   WHERE dsh.waktu_catat = (SELECT MAX(waktu_catat) FROM data_stok_historis)
              ) latest_snapshot ON latest_snapshot.nama_komoditas COLLATE utf8mb4_general_ci = r.komoditas COLLATE utf8mb4_general_ci';

        $countStmt = $pdo->prepare('SELECT COUNT(*)' . $baseFrom . $whereSql);
        if ($search !== '') {
            $countStmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }
        if ($commodity !== '') {
            $countStmt->bindValue(':commodity', $commodity, PDO::PARAM_STR);
        }
        $countStmt->execute();

        $totalItems = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($totalItems / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $stmt = $pdo->prepare(
            'SELECT b.batch_code,
                    r.komoditas,
                    f.tanggal_forecast,
                    f.forecast_horizon_day,
                    f.forecast_denormalized,
                    m.rmse,
                    m.mae,
                    m.mape,
                    latest_snapshot.jumlah_aktual,
                    latest_snapshot.satuan,
                    latest_snapshot.lokasi_gudang' .
            $baseFrom .
            $whereSql .
            ' ORDER BY r.komoditas ASC
              LIMIT :limit OFFSET :offset'
        );
        if ($search !== '') {
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }
        if ($commodity !== '') {
            $stmt->bindValue(':commodity', $commodity, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'commodityOptions' => $commodityOptions,
            'totalItems' => $totalItems,
            'perPage' => $perPage,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'commodity' => $commodity,
            'status' => $status,
        ];
    }

    public static function resetAll(): void
    {
        self::ensureTables();
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $pdo->exec('DELETE FROM lstm_model_forecasts');
            $pdo->exec('DELETE FROM lstm_model_residuals');
            $pdo->exec('DELETE FROM lstm_model_predictions');
            $pdo->exec('DELETE FROM lstm_model_metrics');
            $pdo->exec('DELETE FROM lstm_model_runs');
            $pdo->exec('DELETE FROM lstm_batch_runs');
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        $modelDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'models';
        if (is_dir($modelDirectory)) {
            $files = glob($modelDirectory . DIRECTORY_SEPARATOR . '*.keras');
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
        }
    }

    private static function paginateChildTable(
        string $baseSelect,
        string $baseCount,
        string $searchClause,
        string $orderClause,
        int $runId,
        string $search,
        int $page,
        int $perPage
    ): array {
        self::ensureTables();
        $pdo = Database::connection();
        $search = trim($search);
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $countSql = $baseCount;
        $selectSql = $baseSelect;
        if ($search !== '') {
            $countSql .= $searchClause;
            $selectSql .= $searchClause;
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->bindValue(':run_id', $runId, PDO::PARAM_INT);
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

        $stmt = $pdo->prepare($selectSql . $orderClause);
        $stmt->bindValue(':run_id', $runId, PDO::PARAM_INT);
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
}
