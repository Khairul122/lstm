from __future__ import annotations

import json
import math
import os
import sys
import time
from dataclasses import dataclass
from datetime import datetime, timedelta
from pathlib import Path
from typing import Any


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


ARTIFACT_DIR = Path(__file__).resolve().parent.parent / "storage" / "models"
ARTIFACT_DIR.mkdir(parents=True, exist_ok=True)


@dataclass
class BatchConfig:
    batch_id: int
    sequence_length: int
    train_ratio: float
    epochs: int
    batch_size: int
    lstm_units: int
    dropout_rate: float
    optimizer: str
    learning_rate: float


def fetch_batch_config(cursor, batch_id: int) -> BatchConfig:
    cursor.execute(
        """
        SELECT id, sequence_length, train_ratio, epochs, batch_size, lstm_units, dropout_rate, optimizer, learning_rate
        FROM lstm_batch_runs
        WHERE id = %s
        LIMIT 1
        """,
        (batch_id,),
    )
    row = cursor.fetchone()
    if row is None:
        raise SystemExit(f"Batch run {batch_id} tidak ditemukan.")

    return BatchConfig(
        batch_id=int(row[0]),
        sequence_length=int(row[1]),
        train_ratio=float(row[2]),
        epochs=int(row[3]),
        batch_size=int(row[4]),
        lstm_units=int(row[5]),
        dropout_rate=float(row[6]),
        optimizer=str(row[7]),
        learning_rate=float(row[8]),
    )


def ensure_run_rows(cursor, batch_id: int) -> list[str]:
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

    return commodities


def update_batch_status(
    cursor, batch_id: int, status: str, notes: str | None = None
) -> None:
    if notes is None:
        cursor.execute(
            "UPDATE lstm_batch_runs SET status = %s WHERE id = %s",
            (status, batch_id),
        )
        return

    cursor.execute(
        "UPDATE lstm_batch_runs SET status = %s, notes = %s WHERE id = %s",
        (status, notes, batch_id),
    )


def update_run_status(
    cursor,
    run_id: int,
    status: str,
    train_samples: int = 0,
    test_samples: int = 0,
    error_message: str | None = None,
) -> None:
    cursor.execute(
        """
        UPDATE lstm_model_runs
        SET status = %s,
            train_samples = %s,
            test_samples = %s,
            error_message = %s
        WHERE id = %s
        """,
        (status, train_samples, test_samples, error_message, run_id),
    )


def start_run(cursor, run_id: int) -> None:
    cursor.execute(
        "UPDATE lstm_model_runs SET status = 'running', train_started_at = NOW() WHERE id = %s",
        (run_id,),
    )


def finish_run(
    cursor,
    run_id: int,
    status: str,
    model_path: str | None = None,
    error_message: str | None = None,
) -> None:
    cursor.execute(
        """
        UPDATE lstm_model_runs
        SET status = %s,
            model_path = %s,
            error_message = %s,
            train_finished_at = NOW(),
            duration_seconds = TIMESTAMPDIFF(SECOND, train_started_at, NOW())
        WHERE id = %s
        """,
        (status, model_path, error_message, run_id),
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


def persist_metrics(
    cursor, run_id: int, commodity: str, metrics: dict[str, float | int]
) -> None:
    cursor.execute("DELETE FROM lstm_model_metrics WHERE run_id = %s", (run_id,))
    cursor.execute(
        """
        INSERT INTO lstm_model_metrics (
            run_id, komoditas, rmse, mae, mape, train_loss_final, val_loss_final, best_epoch, train_samples, test_samples
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


def persist_predictions(
    cursor,
    run_id: int,
    commodity: str,
    dates,
    actual_norm,
    predicted_norm,
    min_value: float,
    data_range: float,
) -> None:
    cursor.execute("DELETE FROM lstm_model_predictions WHERE run_id = %s", (run_id,))
    payload = []
    for date_value, actual_n, predicted_n in zip(dates, actual_norm, predicted_norm):
        payload.append(
            (
                run_id,
                commodity,
                date_value,
                "Uji",
                float(actual_n),
                float(predicted_n),
                denormalize(float(actual_n), min_value, data_range),
                denormalize(float(predicted_n), min_value, data_range),
            )
        )

    cursor.executemany(
        """
        INSERT INTO lstm_model_predictions (
            run_id, komoditas, tanggal, dataset_type, actual_normalized, predicted_normalized, actual_denormalized, predicted_denormalized
        ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
        """,
        payload,
    )


def persist_residuals(
    cursor, run_id: int, commodity: str, dates, actual_values, predicted_values
) -> None:
    cursor.execute("DELETE FROM lstm_model_residuals WHERE run_id = %s", (run_id,))
    payload = []
    for date_value, actual_v, predicted_v in zip(
        dates, actual_values, predicted_values
    ):
        residual = float(actual_v - predicted_v)
        absolute_error = abs(residual)
        absolute_percentage_error = (
            absolute_error / max(abs(float(actual_v)), 1e-8)
        ) * 100.0
        payload.append(
            (
                run_id,
                commodity,
                date_value,
                float(actual_v),
                float(predicted_v),
                residual,
                absolute_error,
                absolute_percentage_error,
            )
        )

    cursor.executemany(
        """
        INSERT INTO lstm_model_residuals (
            run_id, komoditas, tanggal, actual_value, predicted_value, residual, absolute_error, absolute_percentage_error
        ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
        """,
        payload,
    )


def persist_forecasts(
    cursor,
    run_id: int,
    commodity: str,
    start_date,
    forecasts_norm,
    min_value: float,
    data_range: float,
) -> None:
    cursor.execute("DELETE FROM lstm_model_forecasts WHERE run_id = %s", (run_id,))
    payload = []
    for index, forecast_n in enumerate(forecasts_norm, start=1):
        forecast_date = start_date + timedelta(days=index)
        payload.append(
            (
                run_id,
                commodity,
                forecast_date,
                index,
                float(forecast_n),
                denormalize(float(forecast_n), min_value, data_range),
            )
        )

    cursor.executemany(
        """
        INSERT INTO lstm_model_forecasts (
            run_id, komoditas, tanggal_forecast, forecast_horizon_day, forecast_normalized, forecast_denormalized
        ) VALUES (%s, %s, %s, %s, %s, %s)
        """,
        payload,
    )


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


def train_for_commodity(connection, config: BatchConfig, commodity: str) -> None:
    cursor = connection.cursor()
    run_id = fetch_run_id(cursor, config.batch_id, commodity)
    start_run(cursor, run_id)
    connection.commit()

    started = time.time()
    try:
        dataset: dict[str, Any] = load_dataset(cursor, commodity)
        x_all = dataset["x_all"]
        y_all = dataset["y_all"]
        train_mask = dataset["train_mask"]
        test_mask = dataset["test_mask"]
        dates = list(dataset["dates"])
        min_value = float(dataset["min_value"])
        data_range = float(dataset["range"])

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

        update_run_status(cursor, run_id, "running", len(x_train), len(x_test), None)
        connection.commit()

        model = build_model(config)
        callback = tf.keras.callbacks.EarlyStopping(
            monitor="val_loss", patience=6, restore_best_weights=True
        )
        history = model.fit(
            x_train,
            y_train,
            epochs=config.epochs,
            batch_size=config.batch_size,
            validation_split=0.15,
            shuffle=False,
            verbose=0,
            callbacks=[callback],
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

        val_losses = history.history.get("val_loss", [])
        train_losses = history.history.get("loss", [])
        best_epoch = int(np.argmin(val_losses) + 1) if val_losses else len(train_losses)

        model_filename = (
            f"batch_{config.batch_id}_{commodity.lower().replace(' ', '_')}.keras"
        )
        model_path = ARTIFACT_DIR / model_filename
        model.save(model_path)

        persist_metrics(
            cursor,
            run_id,
            commodity,
            {
                "rmse": rmse,
                "mae": mae,
                "mape": mape,
                "train_loss_final": float(train_losses[-1]) if train_losses else 0.0,
                "val_loss_final": float(val_losses[-1]) if val_losses else 0.0,
                "best_epoch": best_epoch,
                "train_samples": int(len(x_train)),
                "test_samples": int(len(x_test)),
            },
        )
        persist_predictions(
            cursor,
            run_id,
            commodity,
            test_dates,
            y_test,
            y_pred_norm,
            min_value,
            data_range,
        )
        persist_residuals(
            cursor, run_id, commodity, test_dates, y_test_denorm, y_pred_denorm
        )

        latest_sequence = x_all[-1].reshape(config.sequence_length)
        forecasts_norm = forecast_next_year(model, latest_sequence, 365)
        last_date = dates[-1]
        persist_forecasts(
            cursor, run_id, commodity, last_date, forecasts_norm, min_value, data_range
        )

        finish_run(
            cursor,
            run_id,
            "completed",
            str(model_path.relative_to(Path(__file__).resolve().parent.parent)),
            None,
        )
        sync_batch_counters(cursor, config.batch_id)
        connection.commit()
        print(f"[{commodity}] selesai dalam {int(time.time() - started)} detik")
    except Exception as exc:
        finish_run(cursor, run_id, "failed", None, str(exc))
        sync_batch_counters(cursor, config.batch_id)
        connection.commit()
        print(f"[{commodity}] gagal: {exc}")


def finalize_batch(cursor, batch_id: int) -> None:
    cursor.execute(
        "SELECT total_komoditas, completed_komoditas, failed_komoditas FROM lstm_batch_runs WHERE id = %s LIMIT 1",
        (batch_id,),
    )
    row = cursor.fetchone()
    if row is None:
        return

    total_komoditas = int(row[0] or 0)
    completed = int(row[1] or 0)
    failed = int(row[2] or 0)

    if failed > 0 and completed > 0:
        status = "completed_with_errors"
        notes = f"{completed} komoditas selesai, {failed} komoditas gagal."
    elif failed == total_komoditas and total_komoditas > 0:
        status = "failed"
        notes = "Seluruh komoditas gagal dilatih."
    else:
        status = "completed"
        notes = f"Seluruh {completed} komoditas berhasil dilatih."

    cursor.execute(
        """
        UPDATE lstm_batch_runs
        SET status = %s,
            notes = %s,
            train_finished_at = NOW(),
            duration_seconds = TIMESTAMPDIFF(SECOND, train_started_at, NOW())
        WHERE id = %s
        """,
        (status, notes, batch_id),
    )


def main() -> None:
    if len(sys.argv) < 2:
        raise SystemExit("Gunakan: python database/train_lstm_batch.py <batch_id>")

    batch_id = int(sys.argv[1])
    connection = mysql_connector.connect(**db_config())
    tf.random.set_seed(42)
    np.random.seed(42)

    try:
        cursor = connection.cursor()
        config = fetch_batch_config(cursor, batch_id)
        update_batch_status(cursor, batch_id, "running")
        cursor.execute(
            "UPDATE lstm_batch_runs SET train_started_at = NOW() WHERE id = %s",
            (batch_id,),
        )
        commodities = ensure_run_rows(cursor, batch_id)
        connection.commit()

        if not commodities:
            update_batch_status(
                cursor,
                batch_id,
                "failed",
                "Tidak ada komoditas preprocessing untuk dilatih.",
            )
            connection.commit()
            raise SystemExit("Tidak ada komoditas yang dapat dilatih.")

        for commodity in commodities:
            train_for_commodity(connection, config, commodity)

        cursor = connection.cursor()
        finalize_batch(cursor, batch_id)
        connection.commit()
        print(f"Batch {batch_id} selesai diproses.")
    finally:
        connection.close()


if __name__ == "__main__":
    main()
