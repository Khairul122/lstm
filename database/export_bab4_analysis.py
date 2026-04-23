from __future__ import annotations

import json
import math
import os
import random
import sys
import time
from dataclasses import dataclass
from datetime import date, datetime, timedelta
from pathlib import Path
from typing import Any, cast

import pandas as pd


def import_or_exit(module_name: str, package_name: str):
    try:
        return __import__(module_name, fromlist=[module_name.split(".")[-1]])
    except ModuleNotFoundError as exc:
        raise SystemExit(
            f"Dependency `{package_name}` belum terpasang. Install dengan `pip install {package_name}`."
        ) from exc


mysql_connector = import_or_exit("mysql.connector", "mysql-connector-python")
np = import_or_exit("numpy", "numpy")
tf = import_or_exit("tensorflow", "tensorflow")


ROOT_DIR = Path(__file__).resolve().parent.parent
EXPORT_DIR = ROOT_DIR / "storage" / "exports"
MODEL_DIR = ROOT_DIR / "storage" / "models"
EXPORT_DIR.mkdir(parents=True, exist_ok=True)
MODEL_DIR.mkdir(parents=True, exist_ok=True)


@dataclass(frozen=True)
class PreprocessingConfig:
    sequence_length: int = 7
    train_ratio: float = 0.8


@dataclass(frozen=True)
class TrainingConfig:
    epochs: int = 30
    batch_size: int = 16
    lstm_units: int = 64
    dropout_rate: float = 0.2
    optimizer: str = "adam"
    learning_rate: float = 0.001


@dataclass(frozen=True)
class BatchConfig:
    batch_id: int
    batch_code: str
    sequence_length: int
    train_ratio: float
    epochs: int
    batch_size: int
    lstm_units: int
    dropout_rate: float
    optimizer: str
    learning_rate: float


def env_or_default(name: str, default: str) -> str:
    value = os.getenv(name)
    return value if value not in (None, "") else default


def db_config() -> dict[str, object]:
    return {
        "host": env_or_default("DB_HOST", "127.0.0.1"),
        "port": int(env_or_default("DB_PORT", "3306")),
        "database": env_or_default("DB_DATABASE", "db_stok_pangan"),
        "user": env_or_default("DB_USERNAME", "root"),
        "password": env_or_default("DB_PASSWORD", ""),
        "autocommit": False,
    }


def ensure_preprocessing_table(cursor) -> None:
    cursor.execute(
        """
        CREATE TABLE IF NOT EXISTS data_preprocessing_lstm (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            tanggal_asli DATE NOT NULL,
            format_waktu DATE NOT NULL,
            komoditas VARCHAR(50) NOT NULL,
            stok_mentah FLOAT NULL,
            status_anomali ENUM('Normal', 'Missing Value', 'Outlier') NOT NULL DEFAULT 'Normal',
            stok_bersih FLOAT NOT NULL,
            normalisasi_minmax FLOAT NOT NULL,
            input_sekuens_x JSON NULL,
            target_label_y FLOAT NOT NULL,
            set_data ENUM('Latih', 'Uji') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_tanggal_komoditas (format_waktu, komoditas),
            INDEX idx_komoditas_waktu (komoditas, format_waktu),
            INDEX idx_set_data (set_data)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """
    )


def ensure_lstm_tables(cursor) -> None:
    statements = [
        """
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        """,
        """
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        """,
        """
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        """,
        """
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        """,
        """
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        """,
        """
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        """,
    ]
    for statement in statements:
        cursor.execute(statement)


def fetch_raw_rows(connection) -> list[dict[str, Any]]:
    cursor = connection.cursor(dictionary=True)
    cursor.execute(
        """
        SELECT dsh.id_stok,
               dsh.waktu_catat,
               k.id_komoditas,
               k.kode_komoditas,
               k.nama_komoditas AS komoditas,
               k.satuan,
               dsh.jumlah_aktual AS stok_mentah,
               dsh.lokasi_gudang
        FROM data_stok_historis dsh
        INNER JOIN komoditas k ON k.id_komoditas = dsh.id_komoditas
        ORDER BY k.nama_komoditas ASC, dsh.waktu_catat ASC
        """
    )
    return list(cursor.fetchall())


def quantile(values: list[float], q: float) -> float:
    if not values:
        return 0.0
    if len(values) == 1:
        return float(values[0])
    position = (len(values) - 1) * q
    lower = math.floor(position)
    upper = math.ceil(position)
    if lower == upper:
        return float(values[lower])
    weight = position - lower
    return ((1 - weight) * float(values[lower])) + (weight * float(values[upper]))


def replacement_value(
    rows: list[dict[str, Any]], current_index: int, fallback: float
) -> tuple[float, str]:
    previous = None
    for index in range(current_index - 1, -1, -1):
        if (
            rows[index]["status_anomali"] == "Normal"
            and rows[index]["stok_mentah"] is not None
        ):
            previous = float(rows[index]["stok_mentah"])
            break

    next_value = None
    for index in range(current_index + 1, len(rows)):
        if (
            rows[index]["status_anomali"] == "Normal"
            and rows[index]["stok_mentah"] is not None
        ):
            next_value = float(rows[index]["stok_mentah"])
            break

    if previous is not None and next_value is not None:
        return round(
            (previous + next_value) / 2, 2
        ), "Rata-rata tetangga normal terdekat"
    if previous is not None:
        return round(previous, 2), "Gunakan nilai normal sebelumnya"
    if next_value is not None:
        return round(next_value, 2), "Gunakan nilai normal berikutnya"
    return round(fallback, 2), "Fallback median komoditas"


def build_sequence(
    normalized_series: list[float], current_index: int, sequence_length: int
) -> list[float]:
    sequence = []
    first_value = float(normalized_series[0] if normalized_series else 0.0)
    for step in range(sequence_length, 0, -1):
        sequence_index = current_index - step
        sequence.append(
            round(
                float(normalized_series[sequence_index])
                if sequence_index >= 0
                else first_value,
                6,
            )
        )
    return sequence


def prepare_preprocessing(
    raw_rows: list[dict[str, Any]], config: PreprocessingConfig
) -> tuple[list[dict[str, Any]], list[dict[str, Any]], list[dict[str, Any]]]:
    grouped: dict[str, list[dict[str, Any]]] = {}
    for row in raw_rows:
        grouped.setdefault(str(row["komoditas"]), []).append(row)

    prepared_rows: list[dict[str, Any]] = []
    summary_rows: list[dict[str, Any]] = []
    split_rows: list[dict[str, Any]] = []

    for commodity_name, rows in grouped.items():
        mapped: dict[str, dict[str, Any]] = {}
        raw_values: list[float] = []
        code = str(rows[0]["kode_komoditas"])
        unit = str(rows[0]["satuan"])

        for row in rows:
            date_key = row["waktu_catat"].strftime("%Y-%m-%d")
            mapped[date_key] = {
                "tanggal_asli": row["waktu_catat"],
                "stok_mentah": float(row["stok_mentah"])
                if row["stok_mentah"] is not None
                else None,
                "kode_komoditas": code,
                "satuan": unit,
            }
            if row["stok_mentah"] is not None:
                raw_values.append(float(row["stok_mentah"]))

        if not mapped:
            continue

        raw_values.sort()
        q1 = quantile(raw_values, 0.25)
        q3 = quantile(raw_values, 0.75)
        iqr = q3 - q1
        lower_bound = q1 - (1.5 * iqr)
        upper_bound = q3 + (1.5 * iqr)
        median = quantile(raw_values, 0.5)

        dates = sorted(mapped.keys())
        current_date = datetime.strptime(dates[0], "%Y-%m-%d").date()
        end_date = datetime.strptime(dates[-1], "%Y-%m-%d").date()
        commodity_rows: list[dict[str, Any]] = []

        while current_date <= end_date:
            date_key = current_date.strftime("%Y-%m-%d")
            source = mapped.get(date_key)
            raw_stock = source["stok_mentah"] if source is not None else None
            status = "Normal"
            if source is None or raw_stock is None:
                status = "Missing Value"
            elif raw_stock < lower_bound or raw_stock > upper_bound:
                status = "Outlier"

            commodity_rows.append(
                {
                    "kode_komoditas": code,
                    "komoditas": commodity_name,
                    "satuan": unit,
                    "tanggal_asli": source["tanggal_asli"]
                    if source is not None
                    else current_date,
                    "format_waktu": current_date,
                    "stok_mentah": raw_stock,
                    "status_anomali": status,
                    "batas_bawah_iqr": round(lower_bound, 2),
                    "batas_atas_iqr": round(upper_bound, 2),
                    "q1": round(q1, 2),
                    "q3": round(q3, 2),
                    "iqr": round(iqr, 2),
                    "median_komoditas": round(median, 2),
                }
            )
            current_date += timedelta(days=1)

        for index, item in enumerate(commodity_rows):
            if item["status_anomali"] == "Normal" and item["stok_mentah"] is not None:
                item["stok_bersih"] = float(item["stok_mentah"])
                item["metode_imputasi"] = "Tidak ada imputasi"
            else:
                cleaned, reason = replacement_value(commodity_rows, index, median)
                item["stok_bersih"] = cleaned
                item["metode_imputasi"] = reason

        clean_values = [float(item["stok_bersih"]) for item in commodity_rows]
        min_clean = min(clean_values)
        max_clean = max(clean_values)
        data_range = max_clean - min_clean

        normalized_series: list[float] = []
        for item in commodity_rows:
            normalized = (
                ((float(item["stok_bersih"]) - min_clean) / data_range)
                if data_range > 0
                else 1.0
            )
            normalized = round(normalized, 6)
            item["min_stok_bersih"] = round(min_clean, 2)
            item["max_stok_bersih"] = round(max_clean, 2)
            item["range_stok_bersih"] = round(data_range, 2)
            item["normalisasi_minmax"] = normalized
            normalized_series.append(normalized)

        split_index = int(math.floor(len(commodity_rows) * config.train_ratio))
        for index, item in enumerate(commodity_rows):
            sequence = build_sequence(normalized_series, index, config.sequence_length)
            item["input_sekuens_x"] = json.dumps(sequence, ensure_ascii=True)
            item["target_label_y"] = float(item["normalisasi_minmax"])
            item["set_data"] = "Latih" if index < split_index else "Uji"
            item["sequence_length"] = config.sequence_length
            item["train_ratio"] = config.train_ratio
            split_rows.append(
                {
                    "kode_komoditas": code,
                    "komoditas": commodity_name,
                    "tanggal": item["format_waktu"],
                    "indeks": index + 1,
                    "total_data": len(commodity_rows),
                    "split_index": split_index,
                    "set_data": item["set_data"],
                    "normalisasi_minmax": item["normalisasi_minmax"],
                    "target_label_y": item["target_label_y"],
                }
            )

        missing_count = sum(
            1 for item in commodity_rows if item["status_anomali"] == "Missing Value"
        )
        outlier_count = sum(
            1 for item in commodity_rows if item["status_anomali"] == "Outlier"
        )
        summary_rows.append(
            {
                "kode_komoditas": code,
                "komoditas": commodity_name,
                "satuan": unit,
                "total_data": len(commodity_rows),
                "missing_value": missing_count,
                "outlier": outlier_count,
                "data_latih": split_index,
                "data_uji": len(commodity_rows) - split_index,
                "min_stok_bersih": round(min_clean, 2),
                "max_stok_bersih": round(max_clean, 2),
                "rata_normalisasi": round(
                    sum(normalized_series) / max(1, len(normalized_series)), 6
                ),
                "tanggal_awal": commodity_rows[0]["format_waktu"],
                "tanggal_akhir": commodity_rows[-1]["format_waktu"],
                "q1": round(q1, 2),
                "q3": round(q3, 2),
                "iqr": round(iqr, 2),
                "batas_bawah_iqr": round(lower_bound, 2),
                "batas_atas_iqr": round(upper_bound, 2),
            }
        )
        prepared_rows.extend(commodity_rows)

    return prepared_rows, summary_rows, split_rows


def persist_preprocessing(connection, prepared_rows: list[dict[str, Any]]) -> None:
    cursor = connection.cursor()
    ensure_preprocessing_table(cursor)
    cursor.execute("DELETE FROM data_preprocessing_lstm")
    insert_sql = """
        INSERT INTO data_preprocessing_lstm (
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
        ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
    """
    payload = []
    for row in prepared_rows:
        payload.append(
            (
                row["tanggal_asli"],
                row["format_waktu"],
                row["komoditas"],
                row["stok_mentah"],
                row["status_anomali"],
                row["stok_bersih"],
                row["normalisasi_minmax"],
                row["input_sekuens_x"],
                row["target_label_y"],
                row["set_data"],
            )
        )
    cursor.executemany(insert_sql, payload)
    connection.commit()


def create_batch(
    connection, preprocessing: PreprocessingConfig, training: TrainingConfig
) -> BatchConfig:
    cursor = connection.cursor()
    ensure_lstm_tables(cursor)
    batch_code = f"BATCH-{datetime.now().strftime('%Y%m%d-%H%M%S')}-BAB4"
    cursor.execute(
        """
        INSERT INTO lstm_batch_runs (
            batch_code, status, sequence_length, train_ratio, epochs, batch_size,
            lstm_units, dropout_rate, optimizer, learning_rate, notes
        ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        """,
        (
            batch_code,
            "queued",
            preprocessing.sequence_length,
            preprocessing.train_ratio,
            training.epochs,
            training.batch_size,
            training.lstm_units,
            training.dropout_rate,
            training.optimizer,
            training.learning_rate,
            "Rerun preprocessing + training untuk workbook Bab 4.",
        ),
    )
    batch_id = int(cursor.lastrowid)
    connection.commit()
    return BatchConfig(
        batch_id=batch_id,
        batch_code=batch_code,
        sequence_length=preprocessing.sequence_length,
        train_ratio=preprocessing.train_ratio,
        epochs=training.epochs,
        batch_size=training.batch_size,
        lstm_units=training.lstm_units,
        dropout_rate=training.dropout_rate,
        optimizer=training.optimizer,
        learning_rate=training.learning_rate,
    )


def ensure_run_rows(connection, batch_id: int) -> list[str]:
    cursor = connection.cursor()
    cursor.execute(
        "SELECT DISTINCT komoditas FROM data_preprocessing_lstm ORDER BY komoditas ASC"
    )
    commodities = [row[0] for row in cursor.fetchall()]
    for commodity in commodities:
        cursor.execute(
            """
            INSERT INTO lstm_model_runs (batch_id, komoditas, status)
            SELECT %s, %s, 'queued'
            FROM DUAL
            WHERE NOT EXISTS (
                SELECT 1 FROM lstm_model_runs WHERE batch_id = %s AND komoditas = %s
            )
            """,
            (batch_id, commodity, batch_id, commodity),
        )
    cursor.execute(
        "UPDATE lstm_batch_runs SET total_komoditas = %s WHERE id = %s",
        (len(commodities), batch_id),
    )
    connection.commit()
    return commodities


def fetch_run_id(cursor, batch_id: int, commodity: str) -> int:
    cursor.execute(
        "SELECT id FROM lstm_model_runs WHERE batch_id = %s AND komoditas = %s LIMIT 1",
        (batch_id, commodity),
    )
    row = cursor.fetchone()
    if row is None:
        raise RuntimeError(
            f"Run untuk komoditas {commodity} tidak ditemukan pada batch {batch_id}."
        )
    return int(row[0])


def update_batch_status(
    cursor, batch_id: int, status: str, notes: str | None = None
) -> None:
    if notes is None:
        cursor.execute(
            "UPDATE lstm_batch_runs SET status = %s WHERE id = %s", (status, batch_id)
        )
    else:
        cursor.execute(
            "UPDATE lstm_batch_runs SET status = %s, notes = %s WHERE id = %s",
            (status, notes, batch_id),
        )


def sync_batch_counters(cursor, batch_id: int) -> None:
    cursor.execute(
        """
        SELECT
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_runs,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_runs
        FROM lstm_model_runs
        WHERE batch_id = %s
        """,
        (batch_id,),
    )
    row = cursor.fetchone()
    completed = int(row[0] or 0)
    failed = int(row[1] or 0)
    cursor.execute(
        "UPDATE lstm_batch_runs SET completed_komoditas = %s, failed_komoditas = %s WHERE id = %s",
        (completed, failed, batch_id),
    )


def load_dataset(cursor, commodity: str) -> dict[str, object]:
    cursor.execute(
        """
        SELECT format_waktu, input_sekuens_x, target_label_y, stok_bersih, set_data
        FROM data_preprocessing_lstm
        WHERE komoditas = %s
        ORDER BY format_waktu ASC
        """,
        (commodity,),
    )
    rows = cursor.fetchall()
    if not rows:
        raise RuntimeError(f"Data preprocessing untuk {commodity} tidak ditemukan.")

    x_all = []
    y_all = []
    dates = []
    sets = []
    clean_values = []
    for row in rows:
        dates.append(row[0])
        x_all.append(json.loads(row[1]))
        y_all.append(float(row[2]))
        clean_values.append(float(row[3]))
        sets.append(row[4])

    min_value = min(clean_values)
    max_value = max(clean_values)
    data_range = max(max_value - min_value, 1e-9)

    x_np = np.array(x_all, dtype=np.float32)
    y_np = np.array(y_all, dtype=np.float32)
    x_np = np.reshape(x_np, (x_np.shape[0], x_np.shape[1], 1))
    train_mask = np.array([item == "Latih" for item in sets])
    test_mask = np.array([item == "Uji" for item in sets])

    return {
        "dates": dates,
        "x_all": x_np,
        "y_all": y_np,
        "train_mask": train_mask,
        "test_mask": test_mask,
        "min_value": min_value,
        "max_value": max_value,
        "range": data_range,
    }


def denormalize(value: float, min_value: float, data_range: float) -> float:
    return float((value * data_range) + min_value)


def compute_mape(actual, predicted) -> float:
    epsilon = 1e-8
    return float(
        np.mean(np.abs((actual - predicted) / np.maximum(np.abs(actual), epsilon)))
        * 100.0
    )


def build_model(config: BatchConfig):
    optimizer_name = config.optimizer.lower()
    if optimizer_name == "adam":
        optimizer = tf.keras.optimizers.Adam(learning_rate=config.learning_rate)
    elif optimizer_name == "rmsprop":
        optimizer = tf.keras.optimizers.RMSprop(learning_rate=config.learning_rate)
    else:
        optimizer = tf.keras.optimizers.Adam(learning_rate=config.learning_rate)

    model = tf.keras.Sequential(
        [
            tf.keras.layers.Input(shape=(config.sequence_length, 1)),
            tf.keras.layers.LSTM(config.lstm_units, return_sequences=False),
            tf.keras.layers.Dropout(config.dropout_rate),
            tf.keras.layers.Dense(32, activation="relu"),
            tf.keras.layers.Dense(1, activation="linear"),
        ]
    )
    model.compile(optimizer=optimizer, loss="mse", metrics=["mae"])
    return model


def persist_run_outputs(
    connection,
    run_id: int,
    commodity: str,
    metrics: dict[str, Any],
    predictions: list[tuple[Any, ...]],
    residuals: list[tuple[Any, ...]],
    forecasts: list[tuple[Any, ...]],
) -> None:
    cursor = connection.cursor()
    cursor.execute("DELETE FROM lstm_model_metrics WHERE run_id = %s", (run_id,))
    cursor.execute("DELETE FROM lstm_model_predictions WHERE run_id = %s", (run_id,))
    cursor.execute("DELETE FROM lstm_model_residuals WHERE run_id = %s", (run_id,))
    cursor.execute("DELETE FROM lstm_model_forecasts WHERE run_id = %s", (run_id,))
    cursor.execute(
        """
        INSERT INTO lstm_model_metrics (
            run_id, komoditas, rmse, mae, mape, train_loss_final, val_loss_final,
            best_epoch, train_samples, test_samples
        ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        """,
        (
            run_id,
            commodity,
            metrics["rmse"],
            metrics["mae"],
            metrics["mape"],
            metrics["train_loss_final"],
            metrics["val_loss_final"],
            metrics["best_epoch"],
            metrics["train_samples"],
            metrics["test_samples"],
        ),
    )
    if predictions:
        cursor.executemany(
            """
            INSERT INTO lstm_model_predictions (
                run_id, komoditas, tanggal, dataset_type, actual_normalized,
                predicted_normalized, actual_denormalized, predicted_denormalized
            ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
            """,
            predictions,
        )
    if residuals:
        cursor.executemany(
            """
            INSERT INTO lstm_model_residuals (
                run_id, komoditas, tanggal, actual_value, predicted_value,
                residual, absolute_error, absolute_percentage_error
            ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
            """,
            residuals,
        )
    if forecasts:
        cursor.executemany(
            """
            INSERT INTO lstm_model_forecasts (
                run_id, komoditas, tanggal_forecast, forecast_horizon_day,
                forecast_normalized, forecast_denormalized
            ) VALUES (%s, %s, %s, %s, %s, %s)
            """,
            forecasts,
        )
    connection.commit()


def forecast_next_year(model, latest_sequence, horizon: int = 365) -> list[float]:
    sequence = latest_sequence.astype(np.float32).reshape(-1).tolist()
    forecasts = []
    window_size = len(sequence)
    for _ in range(horizon):
        x_input = np.array(sequence[-window_size:], dtype=np.float32).reshape(
            1, window_size, 1
        )
        predicted = float(model.predict(x_input, verbose=0)[0][0])
        predicted = max(0.0, min(1.0, predicted))
        forecasts.append(predicted)
        sequence.append(predicted)
    return forecasts


def train_all_commodities(
    connection, batch_config: BatchConfig, commodities: list[str]
) -> dict[str, list[dict[str, Any]]]:
    batch_cursor = connection.cursor()
    update_batch_status(batch_cursor, batch_config.batch_id, "running")
    batch_cursor.execute(
        "UPDATE lstm_batch_runs SET train_started_at = NOW() WHERE id = %s",
        (batch_config.batch_id,),
    )
    connection.commit()

    learning_curve_rows: list[dict[str, Any]] = []
    training_rows: list[dict[str, Any]] = []
    prediction_rows: list[dict[str, Any]] = []
    inverse_rows: list[dict[str, Any]] = []
    residual_rows: list[dict[str, Any]] = []
    forecast_rows: list[dict[str, Any]] = []
    metric_rows: list[dict[str, Any]] = []

    for commodity in commodities:
        cursor = connection.cursor()
        run_id = fetch_run_id(cursor, batch_config.batch_id, commodity)
        cursor.execute(
            "UPDATE lstm_model_runs SET status = 'running', train_started_at = NOW() WHERE id = %s",
            (run_id,),
        )
        connection.commit()

        started = time.time()
        try:
            dataset = load_dataset(cursor, commodity)
            x_all = cast(Any, dataset["x_all"])
            y_all = cast(Any, dataset["y_all"])
            train_mask = cast(Any, dataset["train_mask"])
            test_mask = cast(Any, dataset["test_mask"])
            dates = list(cast(list[Any], dataset["dates"]))
            min_value = float(cast(float, dataset["min_value"]))
            max_value = float(cast(float, dataset["max_value"]))
            data_range = float(cast(float, dataset["range"]))

            x_train = x_all[train_mask]
            y_train = y_all[train_mask]
            x_test = x_all[test_mask]
            y_test = y_all[test_mask]
            test_dates = [
                date_value for date_value, is_test in zip(dates, test_mask) if is_test
            ]

            if len(x_train) < 10 or len(x_test) < 5:
                raise RuntimeError(
                    f"Data latih/uji untuk {commodity} belum cukup. Train={len(x_train)}, Test={len(x_test)}"
                )

            cursor.execute(
                """
                UPDATE lstm_model_runs
                SET status = %s, train_samples = %s, test_samples = %s, error_message = NULL
                WHERE id = %s
                """,
                ("running", int(len(x_train)), int(len(x_test)), run_id),
            )
            connection.commit()

            tf.keras.backend.clear_session()
            tf.random.set_seed(42)
            np.random.seed(42)
            random.seed(42)

            model = build_model(batch_config)
            callback = tf.keras.callbacks.EarlyStopping(
                monitor="val_loss", patience=6, restore_best_weights=True
            )
            history = model.fit(
                x_train,
                y_train,
                epochs=batch_config.epochs,
                batch_size=batch_config.batch_size,
                validation_split=0.15,
                shuffle=False,
                verbose=0,
                callbacks=[callback],
            )

            val_losses = [float(value) for value in history.history.get("val_loss", [])]
            train_losses = [float(value) for value in history.history.get("loss", [])]
            for epoch_index, loss_value in enumerate(train_losses, start=1):
                learning_curve_rows.append(
                    {
                        "batch_id": batch_config.batch_id,
                        "run_id": run_id,
                        "komoditas": commodity,
                        "epoch": epoch_index,
                        "loss": loss_value,
                        "val_loss": val_losses[epoch_index - 1]
                        if epoch_index - 1 < len(val_losses)
                        else None,
                    }
                )

            y_pred_norm = model.predict(x_test, verbose=0).reshape(-1)
            y_pred_norm = np.clip(y_pred_norm, 0.0, 1.0)
            y_test_denorm = np.array(
                [denormalize(value, min_value, data_range) for value in y_test],
                dtype=np.float32,
            )
            y_pred_denorm = np.array(
                [denormalize(value, min_value, data_range) for value in y_pred_norm],
                dtype=np.float32,
            )

            rmse = float(math.sqrt(np.mean(np.square(y_test_denorm - y_pred_denorm))))
            mae = float(np.mean(np.abs(y_test_denorm - y_pred_denorm)))
            mape = compute_mape(y_test_denorm, y_pred_denorm)
            best_epoch = (
                int(np.argmin(val_losses) + 1) if val_losses else len(train_losses)
            )
            duration_seconds = int(time.time() - started)

            model_filename = f"batch_{batch_config.batch_id}_{commodity.lower().replace(' ', '_')}.keras"
            model_path = MODEL_DIR / model_filename
            model.save(model_path)

            db_predictions: list[tuple[Any, ...]] = []
            db_residuals: list[tuple[Any, ...]] = []
            for date_value, actual_n, predicted_n, actual_d, predicted_d in zip(
                test_dates, y_test, y_pred_norm, y_test_denorm, y_pred_denorm
            ):
                db_predictions.append(
                    (
                        run_id,
                        commodity,
                        date_value,
                        "Uji",
                        float(actual_n),
                        float(predicted_n),
                        float(actual_d),
                        float(predicted_d),
                    )
                )
                residual = float(actual_d - predicted_d)
                abs_error = abs(residual)
                ape = (abs_error / max(abs(float(actual_d)), 1e-8)) * 100.0
                db_residuals.append(
                    (
                        run_id,
                        commodity,
                        date_value,
                        float(actual_d),
                        float(predicted_d),
                        residual,
                        abs_error,
                        ape,
                    )
                )
                prediction_rows.append(
                    {
                        "batch_id": batch_config.batch_id,
                        "run_id": run_id,
                        "komoditas": commodity,
                        "tanggal": date_value,
                        "dataset_type": "Uji",
                        "actual_normalized": float(actual_n),
                        "predicted_normalized": float(predicted_n),
                        "actual_denormalized": float(actual_d),
                        "predicted_denormalized": float(predicted_d),
                    }
                )
                inverse_rows.append(
                    {
                        "batch_id": batch_config.batch_id,
                        "run_id": run_id,
                        "komoditas": commodity,
                        "tanggal": date_value,
                        "min_stok_bersih": min_value,
                        "max_stok_bersih": max_value,
                        "range_stok_bersih": data_range,
                        "predicted_normalized": float(predicted_n),
                        "predicted_denormalized": float(predicted_d),
                        "actual_normalized": float(actual_n),
                        "actual_denormalized": float(actual_d),
                    }
                )
                residual_rows.append(
                    {
                        "batch_id": batch_config.batch_id,
                        "run_id": run_id,
                        "komoditas": commodity,
                        "tanggal": date_value,
                        "actual_value": float(actual_d),
                        "predicted_value": float(predicted_d),
                        "residual": residual,
                        "absolute_error": abs_error,
                        "absolute_percentage_error": ape,
                    }
                )

            latest_sequence = x_all[-1].reshape(batch_config.sequence_length)
            forecasts_norm = forecast_next_year(model, latest_sequence, 365)
            last_date = dates[-1]
            db_forecasts: list[tuple[Any, ...]] = []
            for horizon_day, forecast_norm in enumerate(forecasts_norm, start=1):
                forecast_date = last_date + timedelta(days=horizon_day)
                forecast_denorm = denormalize(
                    float(forecast_norm), min_value, data_range
                )
                db_forecasts.append(
                    (
                        run_id,
                        commodity,
                        forecast_date,
                        horizon_day,
                        float(forecast_norm),
                        float(forecast_denorm),
                    )
                )
                forecast_rows.append(
                    {
                        "batch_id": batch_config.batch_id,
                        "run_id": run_id,
                        "komoditas": commodity,
                        "tanggal_forecast": forecast_date,
                        "forecast_horizon_day": horizon_day,
                        "forecast_normalized": float(forecast_norm),
                        "forecast_denormalized": float(forecast_denorm),
                    }
                )

            metrics = {
                "rmse": rmse,
                "mae": mae,
                "mape": mape,
                "train_loss_final": float(train_losses[-1]) if train_losses else 0.0,
                "val_loss_final": float(val_losses[-1]) if val_losses else 0.0,
                "best_epoch": best_epoch,
                "train_samples": int(len(x_train)),
                "test_samples": int(len(x_test)),
            }
            persist_run_outputs(
                connection,
                run_id,
                commodity,
                metrics,
                db_predictions,
                db_residuals,
                db_forecasts,
            )
            cursor.execute(
                """
                UPDATE lstm_model_runs
                SET status = %s, model_path = %s, error_message = NULL,
                    train_finished_at = NOW(), duration_seconds = %s
                WHERE id = %s
                """,
                (
                    "completed",
                    str(model_path.relative_to(ROOT_DIR)),
                    duration_seconds,
                    run_id,
                ),
            )
            sync_batch_counters(cursor, batch_config.batch_id)
            connection.commit()

            training_rows.append(
                {
                    "batch_id": batch_config.batch_id,
                    "run_id": run_id,
                    "komoditas": commodity,
                    "status": "completed",
                    "train_samples": int(len(x_train)),
                    "test_samples": int(len(x_test)),
                    "epochs_requested": batch_config.epochs,
                    "epochs_executed": len(train_losses),
                    "best_epoch": best_epoch,
                    "train_loss_final": metrics["train_loss_final"],
                    "val_loss_final": metrics["val_loss_final"],
                    "rmse": rmse,
                    "mae": mae,
                    "mape": mape,
                    "duration_seconds": duration_seconds,
                    "model_path": str(model_path.relative_to(ROOT_DIR)),
                }
            )
            metric_rows.append(
                {
                    "batch_id": batch_config.batch_id,
                    "run_id": run_id,
                    "komoditas": commodity,
                    "rmse": rmse,
                    "mae": mae,
                    "mape": mape,
                    "train_loss_final": metrics["train_loss_final"],
                    "val_loss_final": metrics["val_loss_final"],
                    "best_epoch": best_epoch,
                    "train_samples": int(len(x_train)),
                    "test_samples": int(len(x_test)),
                }
            )
            print(f"[{commodity}] selesai dalam {duration_seconds} detik")
        except Exception as exc:
            cursor.execute(
                """
                UPDATE lstm_model_runs
                SET status = %s, error_message = %s, train_finished_at = NOW(), duration_seconds = %s
                WHERE id = %s
                """,
                ("failed", str(exc), int(time.time() - started), run_id),
            )
            sync_batch_counters(cursor, batch_config.batch_id)
            connection.commit()
            training_rows.append(
                {
                    "batch_id": batch_config.batch_id,
                    "run_id": run_id,
                    "komoditas": commodity,
                    "status": "failed",
                    "error_message": str(exc),
                }
            )
            print(f"[{commodity}] gagal: {exc}")

    final_cursor = connection.cursor()
    completed = sum(1 for row in training_rows if row.get("status") == "completed")
    failed = sum(1 for row in training_rows if row.get("status") == "failed")
    if failed > 0 and completed > 0:
        final_status = "completed_with_errors"
        notes = f"{completed} komoditas selesai, {failed} komoditas gagal."
    elif failed > 0 and completed == 0:
        final_status = "failed"
        notes = "Seluruh komoditas gagal dilatih."
    else:
        final_status = "completed"
        notes = f"Seluruh {completed} komoditas berhasil dilatih."
    final_cursor.execute(
        """
        UPDATE lstm_batch_runs
        SET status = %s,
            notes = %s,
            train_finished_at = NOW(),
            duration_seconds = TIMESTAMPDIFF(SECOND, train_started_at, NOW())
        WHERE id = %s
        """,
        (final_status, notes, batch_config.batch_id),
    )
    connection.commit()

    return {
        "learning_curve": learning_curve_rows,
        "training": training_rows,
        "predictions": prediction_rows,
        "inverse": inverse_rows,
        "residuals": residual_rows,
        "forecasts": forecast_rows,
        "metrics": metric_rows,
    }


def dataframe(
    rows: list[dict[str, Any]], columns: list[str] | None = None
) -> pd.DataFrame:
    frame = pd.DataFrame(rows)
    if columns is not None:
        for column in columns:
            if column not in frame.columns:
                frame[column] = None
        frame = frame[columns]
    return pd.DataFrame(frame)


def build_bab4_summary(
    batch_config: BatchConfig,
    preprocessing_summary: list[dict[str, Any]],
    metrics: list[dict[str, Any]],
    forecasts: list[dict[str, Any]],
) -> list[dict[str, Any]]:
    metric_by_commodity = {row["komoditas"]: row for row in metrics}
    forecast_h365: dict[str, dict[str, Any]] = {}
    for row in forecasts:
        if int(row["forecast_horizon_day"]) == 365:
            forecast_h365[row["komoditas"]] = row

    summary_rows: list[dict[str, Any]] = []
    for row in preprocessing_summary:
        commodity = row["komoditas"]
        metric = metric_by_commodity.get(commodity, {})
        forecast = forecast_h365.get(commodity, {})
        mape = float(metric.get("mape", 0.0) or 0.0)
        if mape <= 10.0:
            status_model = "Safe"
        elif mape <= 20.0:
            status_model = "Watchlist"
        else:
            status_model = "Warning"
        summary_rows.append(
            {
                "batch_code": batch_config.batch_code,
                "komoditas": commodity,
                "total_data": row["total_data"],
                "missing_value": row["missing_value"],
                "outlier": row["outlier"],
                "data_latih": row["data_latih"],
                "data_uji": row["data_uji"],
                "rmse": metric.get("rmse"),
                "mae": metric.get("mae"),
                "mape": metric.get("mape"),
                "best_epoch": metric.get("best_epoch"),
                "forecast_h365": forecast.get("forecast_denormalized"),
                "tanggal_forecast_h365": forecast.get("tanggal_forecast"),
                "status_model": status_model,
                "implikasi": (
                    "Prediksi relatif stabil dan dapat dipakai sebagai acuan operasional."
                    if status_model == "Safe"
                    else "Perlu monitoring berkala terhadap stok dan pembaruan model."
                    if status_model == "Watchlist"
                    else "Perlu evaluasi ulang data, skenario pasokan, dan penyesuaian model."
                ),
            }
        )
    return summary_rows


def export_workbook(
    output_path: Path,
    batch_config: BatchConfig,
    preprocessing: PreprocessingConfig,
    training: TrainingConfig,
    raw_rows: list[dict[str, Any]],
    prepared_rows: list[dict[str, Any]],
    preprocessing_summary: list[dict[str, Any]],
    split_rows: list[dict[str, Any]],
    training_output: dict[str, list[dict[str, Any]]],
) -> None:
    raw_df = dataframe(raw_rows)
    karakteristik_df = raw_df.groupby(
        ["kode_komoditas", "komoditas", "satuan"], as_index=False
    ).agg(
        jumlah_observasi=("stok_mentah", "count"),
        tanggal_awal=("waktu_catat", "min"),
        tanggal_akhir=("waktu_catat", "max"),
        stok_min=("stok_mentah", "min"),
        stok_max=("stok_mentah", "max"),
        stok_mean=("stok_mentah", "mean"),
        stok_median=("stok_mentah", "median"),
        stok_std=("stok_mentah", "std"),
        jumlah_gudang=("lokasi_gudang", "nunique"),
    )
    karakteristik_df = karakteristik_df.merge(
        dataframe(preprocessing_summary)[
            ["kode_komoditas", "missing_value", "outlier"]
        ],
        on="kode_komoditas",
        how="left",
    )

    prepared_df = dataframe(prepared_rows)
    tren_df = prepared_df[
        [
            "kode_komoditas",
            "komoditas",
            "format_waktu",
            "stok_mentah",
            "stok_bersih",
            "normalisasi_minmax",
            "set_data",
        ]
    ].copy()
    normalisasi_df = prepared_df[
        [
            "kode_komoditas",
            "komoditas",
            "format_waktu",
            "stok_bersih",
            "min_stok_bersih",
            "max_stok_bersih",
            "range_stok_bersih",
            "normalisasi_minmax",
        ]
    ].copy()
    sliding_df = prepared_df[
        [
            "kode_komoditas",
            "komoditas",
            "format_waktu",
            "input_sekuens_x",
            "target_label_y",
            "set_data",
            "sequence_length",
        ]
    ].copy()
    hyper_df = pd.DataFrame(
        [
            {
                "batch_id": batch_config.batch_id,
                "batch_code": batch_config.batch_code,
                "sequence_length": preprocessing.sequence_length,
                "train_ratio": preprocessing.train_ratio,
                "epochs": training.epochs,
                "batch_size": training.batch_size,
                "lstm_units": training.lstm_units,
                "dropout_rate": training.dropout_rate,
                "optimizer": training.optimizer,
                "learning_rate": training.learning_rate,
            }
        ]
    )
    metadata_df = pd.DataFrame(
        [
            {
                "keterangan": "Tanggal Generate",
                "nilai": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            },
            {"keterangan": "Database", "nilai": str(db_config()["database"])},
            {"keterangan": "Host", "nilai": str(db_config()["host"])},
            {"keterangan": "Batch ID", "nilai": batch_config.batch_id},
            {"keterangan": "Batch Code", "nilai": batch_config.batch_code},
            {"keterangan": "Jumlah Komoditas", "nilai": len(preprocessing_summary)},
            {"keterangan": "Jumlah Data Mentah", "nilai": len(raw_rows)},
            {"keterangan": "Jumlah Data Preprocessing", "nilai": len(prepared_rows)},
            {"keterangan": "Sequence Length", "nilai": preprocessing.sequence_length},
            {"keterangan": "Train Ratio", "nilai": preprocessing.train_ratio},
            {"keterangan": "Epochs", "nilai": training.epochs},
            {"keterangan": "Batch Size", "nilai": training.batch_size},
            {"keterangan": "LSTM Units", "nilai": training.lstm_units},
            {"keterangan": "Dropout Rate", "nilai": training.dropout_rate},
            {"keterangan": "Optimizer", "nilai": training.optimizer},
            {"keterangan": "Learning Rate", "nilai": training.learning_rate},
        ]
    )
    bab4_summary_df = dataframe(
        build_bab4_summary(
            batch_config,
            preprocessing_summary,
            training_output["metrics"],
            training_output["forecasts"],
        )
    )
    forecast_df = dataframe(training_output["forecasts"])
    forecast_yearly_df = forecast_df.copy()
    if not forecast_yearly_df.empty:
        forecast_yearly_df["tahun_forecast"] = pd.to_datetime(
            forecast_yearly_df["tanggal_forecast"]
        ).dt.year
        forecast_yearly_df["bulan_forecast"] = pd.to_datetime(
            forecast_yearly_df["tanggal_forecast"]
        ).dt.month
        forecast_yearly_df["nama_bulan"] = pd.to_datetime(
            forecast_yearly_df["tanggal_forecast"]
        ).dt.strftime("%B")
        forecast_yearly_df["quarter_forecast"] = (
            pd.to_datetime(forecast_yearly_df["tanggal_forecast"])
            .dt.to_period("Q")
            .astype(str)
        )

    forecast_horizon_summary_df = pd.DataFrame()
    forecast_monthly_summary_df = pd.DataFrame()
    if not forecast_yearly_df.empty:
        selected_horizons = cast(
            pd.DataFrame,
            forecast_yearly_df[
                forecast_yearly_df["forecast_horizon_day"].isin([1, 30, 90, 180, 365])
            ].copy(),
        )
        horizon_map = {1: "H+1", 30: "H+30", 90: "H+90", 180: "H+180", 365: "H+365"}
        selected_horizons["label_horizon"] = [
            horizon_map.get(int(value), f"H+{int(value)}")
            for value in selected_horizons["forecast_horizon_day"].tolist()
        ]
        forecast_horizon_summary_df = pd.DataFrame(
            cast(
                pd.DataFrame,
                selected_horizons[
                    [
                        "komoditas",
                        "label_horizon",
                        "forecast_horizon_day",
                        "tanggal_forecast",
                        "forecast_normalized",
                        "forecast_denormalized",
                    ]
                ],
            )
        ).sort_values(by=["komoditas", "forecast_horizon_day"])

        monthly_group = pd.DataFrame(
            cast(
                pd.DataFrame,
                forecast_yearly_df.groupby(
                    ["komoditas", "tahun_forecast", "bulan_forecast", "nama_bulan"],
                    as_index=False,
                ).agg(
                    forecast_min=("forecast_denormalized", "min"),
                    forecast_rata_rata=("forecast_denormalized", "mean"),
                    forecast_maks=("forecast_denormalized", "max"),
                    jumlah_hari=("forecast_denormalized", "count"),
                ),
            )
        ).sort_values(by=["komoditas", "tahun_forecast", "bulan_forecast"])
        forecast_monthly_summary_df = monthly_group

    with pd.ExcelWriter(output_path, engine="openpyxl") as writer:
        metadata_df.to_excel(writer, sheet_name="00_metadata", index=False)
        raw_df.to_excel(writer, sheet_name="01_data_mentah", index=False)
        karakteristik_df.to_excel(
            writer, sheet_name="02_karakteristik_data", index=False
        )
        tren_df.to_excel(writer, sheet_name="03_tren_historis", index=False)
        prepared_df.to_excel(writer, sheet_name="04_preprocessing_detail", index=False)
        normalisasi_df.to_excel(writer, sheet_name="05_normalisasi_minmax", index=False)
        dataframe(split_rows).to_excel(
            writer, sheet_name="06_split_train_test", index=False
        )
        sliding_df.to_excel(writer, sheet_name="07_sliding_window", index=False)
        hyper_df.to_excel(writer, sheet_name="08_hyperparameter", index=False)
        dataframe(training_output["learning_curve"]).to_excel(
            writer, sheet_name="09_learning_curve", index=False
        )
        dataframe(training_output["training"]).to_excel(
            writer, sheet_name="10_hasil_training", index=False
        )
        dataframe(training_output["predictions"]).to_excel(
            writer, sheet_name="11_hasil_testing", index=False
        )
        dataframe(training_output["inverse"]).to_excel(
            writer, sheet_name="12_inverse_transform", index=False
        )
        dataframe(training_output["residuals"]).to_excel(
            writer, sheet_name="13_metrics_residual", index=False
        )
        forecast_yearly_df.to_excel(
            writer, sheet_name="14_prediksi_1_tahun", index=False
        )
        forecast_horizon_summary_df.to_excel(
            writer, sheet_name="15_ringkasan_horizon", index=False
        )
        forecast_monthly_summary_df.to_excel(
            writer, sheet_name="16_ringkasan_bulanan", index=False
        )
        bab4_summary_df.to_excel(writer, sheet_name="17_ringkasan_bab4", index=False)


def main() -> None:
    preprocessing = PreprocessingConfig()
    training = TrainingConfig()
    output_path = (
        EXPORT_DIR
        / f"bab4_analisis_lstm_{datetime.now().strftime('%Y%m%d_%H%M%S')}.xlsx"
    )

    tf.random.set_seed(42)
    np.random.seed(42)
    random.seed(42)

    connection = mysql_connector.connect(**db_config())
    try:
        raw_rows = fetch_raw_rows(connection)
        if not raw_rows:
            raise SystemExit(
                "Data stok historis kosong. Tidak ada data untuk diproses."
            )

        prepared_rows, preprocessing_summary, split_rows = prepare_preprocessing(
            raw_rows, preprocessing
        )
        persist_preprocessing(connection, prepared_rows)

        batch_config = create_batch(connection, preprocessing, training)
        commodities = ensure_run_rows(connection, batch_config.batch_id)
        training_output = train_all_commodities(connection, batch_config, commodities)

        export_workbook(
            output_path,
            batch_config,
            preprocessing,
            training,
            raw_rows,
            prepared_rows,
            preprocessing_summary,
            split_rows,
            training_output,
        )

        print(f"Workbook berhasil dibuat: {output_path}")
        print(f"Batch ID: {batch_config.batch_id}")
        print(f"Batch Code: {batch_config.batch_code}")
    finally:
        connection.close()


if __name__ == "__main__":
    main()
