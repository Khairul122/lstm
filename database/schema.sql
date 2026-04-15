CREATE TABLE IF NOT EXISTS users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (username, password, role)
SELECT 'admin', '$2y$10$gcPyO7i8V2z4RgekpQTT9eGlz2Ojde6U66RU8RfGhBnqskEPLBbSa', 'admin'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'admin'
);

CREATE TABLE IF NOT EXISTS komoditas (
    id_komoditas INT AUTO_INCREMENT PRIMARY KEY,
    kode_komoditas VARCHAR(20) NOT NULL UNIQUE,
    nama_komoditas VARCHAR(50) NOT NULL,
    satuan VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS data_stok_historis (
    id_stok INT AUTO_INCREMENT PRIMARY KEY,
    id_komoditas INT,
    waktu_catat DATE NOT NULL,
    jumlah_aktual FLOAT NOT NULL,
    lokasi_gudang VARCHAR(50) NOT NULL,
    FOREIGN KEY (id_komoditas) REFERENCES komoditas(id_komoditas) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lstm_batch_runs (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lstm_model_runs (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lstm_model_metrics (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lstm_model_predictions (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lstm_model_residuals (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lstm_model_forecasts (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
