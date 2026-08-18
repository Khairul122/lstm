from __future__ import annotations

import math
import os
import random
from dataclasses import dataclass
from datetime import date, datetime, timedelta
from typing import Iterable


START_DATE = date(2022, 1, 1)
END_DATE = date(2025, 1, 1)
WAREHOUSE_WEIGHTS = {
    "Banda Sakti": 0.34,
    "Muara Dua": 0.28,
    "Blang Mangat": 0.20,
    "Muara Satu": 0.18,
}


@dataclass(frozen=True)
class Commodity:
    code: str
    name: str
    unit: str
    base_stock: float
    min_stock: float
    max_stock: float
    monthly_factors: dict[int, float]


COMMODITIES: tuple[Commodity, ...] = (
    Commodity(
        code="BRS-001",
        name="Beras",
        unit="kg",
        base_stock=162_000.0,
        min_stock=108_000.0,
        max_stock=248_000.0,
        monthly_factors={
            1: 0.92,
            2: 1.15,
            3: 1.18,
            4: 1.12,
            5: 1.00,
            6: 0.96,
            7: 0.93,
            8: 0.99,
            9: 1.05,
            10: 1.01,
            11: 0.94,
            12: 0.90,
        },
    ),
    Commodity(
        code="DSP-002",
        name="Daging Sapi",
        unit="kg",
        base_stock=18_500.0,
        min_stock=8_000.0,
        max_stock=31_000.0,
        monthly_factors={
            1: 0.95,
            2: 0.96,
            3: 0.91,
            4: 1.02,
            5: 0.98,
            6: 0.93,
            7: 0.94,
            8: 0.99,
            9: 1.01,
            10: 0.98,
            11: 0.99,
            12: 1.03,
        },
    ),
    Commodity(
        code="CBM-003",
        name="Cabai Merah",
        unit="kg",
        base_stock=14_500.0,
        min_stock=6_500.0,
        max_stock=23_000.0,
        monthly_factors={
            1: 0.92,
            2: 0.96,
            3: 0.85,
            4: 0.88,
            5: 1.02,
            6: 1.08,
            7: 1.12,
            8: 1.06,
            9: 0.97,
            10: 0.95,
            11: 0.91,
            12: 0.89,
        },
    ),
    Commodity(
        code="BWM-004",
        name="Bawang Merah",
        unit="kg",
        base_stock=12_000.0,
        min_stock=5_500.0,
        max_stock=20_500.0,
        monthly_factors={
            1: 0.94,
            2: 0.98,
            3: 0.86,
            4: 0.89,
            5: 1.04,
            6: 1.10,
            7: 1.16,
            8: 1.08,
            9: 0.98,
            10: 0.95,
            11: 0.92,
            12: 0.90,
        },
    ),
    Commodity(
        code="TAY-005",
        name="Telur Ayam Ras",
        unit="kg",
        base_stock=24_000.0,
        min_stock=13_500.0,
        max_stock=35_000.0,
        monthly_factors={
            1: 0.96,
            2: 0.98,
            3: 0.90,
            4: 0.92,
            5: 0.99,
            6: 1.00,
            7: 1.01,
            8: 1.02,
            9: 1.00,
            10: 0.98,
            11: 0.97,
            12: 0.96,
        },
    ),
)


RAMADAN_START = {
    2022: date(2022, 4, 3),
    2023: date(2023, 3, 23),
    2024: date(2024, 3, 12),
}


def daterange(start: date, end: date) -> Iterable[date]:
    current = start
    while current <= end:
        yield current
        current += timedelta(days=1)


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


def ensure_schema(cursor) -> None:
    cursor.execute(
        """
        CREATE TABLE IF NOT EXISTS komoditas (
            id_komoditas INT AUTO_INCREMENT PRIMARY KEY,
            kode_komoditas VARCHAR(20) NOT NULL UNIQUE,
            nama_komoditas VARCHAR(50) NOT NULL,
            satuan VARCHAR(20) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """
    )
    cursor.execute(
        """
        CREATE TABLE IF NOT EXISTS data_stok_historis (
            id_stok INT AUTO_INCREMENT PRIMARY KEY,
            id_komoditas INT NOT NULL,
            waktu_catat DATE NOT NULL,
            jumlah_aktual FLOAT NOT NULL,
            lokasi_gudang VARCHAR(50) NOT NULL,
            FOREIGN KEY (id_komoditas) REFERENCES komoditas(id_komoditas)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            UNIQUE KEY uniq_komoditas_tanggal (id_komoditas, waktu_catat)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """
    )

    cursor.execute("SHOW COLUMNS FROM komoditas LIKE 'kode_komoditas'")
    if cursor.fetchone() is None:
        cursor.execute(
            "ALTER TABLE komoditas ADD COLUMN kode_komoditas VARCHAR(20) NOT NULL AFTER id_komoditas"
        )
        cursor.execute(
            "ALTER TABLE komoditas ADD UNIQUE KEY uniq_kode_komoditas (kode_komoditas)"
        )

    cursor.execute("SHOW COLUMNS FROM data_stok_historis LIKE 'lokasi_gudang'")
    if cursor.fetchone() is None:
        cursor.execute(
            "ALTER TABLE data_stok_historis ADD COLUMN lokasi_gudang VARCHAR(50) NOT NULL DEFAULT 'Banda Sakti' AFTER jumlah_aktual"
        )

    cursor.execute(
        "SHOW INDEX FROM data_stok_historis WHERE Key_name = 'uniq_komoditas_tanggal'"
    )
    if cursor.fetchone() is None:
        cursor.execute(
            "ALTER TABLE data_stok_historis ADD UNIQUE KEY uniq_komoditas_tanggal (id_komoditas, waktu_catat)"
        )


def upsert_master(cursor) -> dict[str, int]:
    payload = [(item.code, item.name, item.unit) for item in COMMODITIES]
    cursor.executemany(
        """
        INSERT INTO komoditas (kode_komoditas, nama_komoditas, satuan)
        VALUES (%s, %s, %s)
        ON DUPLICATE KEY UPDATE
            nama_komoditas = VALUES(nama_komoditas),
            satuan = VALUES(satuan)
        """,
        payload,
    )
    cursor.execute(
        "SELECT id_komoditas, kode_komoditas FROM komoditas WHERE kode_komoditas IN (%s, %s, %s, %s, %s)",
        tuple(item.code for item in COMMODITIES),
    )
    return {code: item_id for item_id, code in cursor.fetchall()}


def weighted_location(rng: random.Random) -> str:
    sample = rng.random()
    cumulative = 0.0
    for district, weight in WAREHOUSE_WEIGHTS.items():
        cumulative += weight
        if sample <= cumulative:
            return district
    return "Muara Satu"


def eased_delta(target: float, current: float, cap: float = 0.018) -> float:
    gap_ratio = (target - current) / max(current, 1.0)
    return max(-cap, min(cap, gap_ratio * 0.25))


def event_multiplier(item: Commodity, current_date: date) -> float:
    year_ramadan = RAMADAN_START.get(current_date.year)
    if year_ramadan is None:
        return 1.0

    days_to_ramadan = (year_ramadan - current_date).days
    days_after_ramadan = (current_date - year_ramadan).days

    if item.name == "Daging Sapi":
        if 1 <= days_to_ramadan <= 3:
            return 0.72
        if 4 <= days_to_ramadan <= 7:
            return 0.84
        if 0 <= days_after_ramadan <= 3:
            return 0.90
    elif item.name in {"Cabai Merah", "Bawang Merah", "Telur Ayam Ras"}:
        if 1 <= days_to_ramadan <= 3:
            return 0.82
        if 4 <= days_to_ramadan <= 7:
            return 0.90
        if 0 <= days_after_ramadan <= 5:
            return 0.93
    elif item.name == "Beras":
        if 1 <= days_to_ramadan <= 5:
            return 0.91
        if 0 <= days_after_ramadan <= 4:
            return 0.95

    return 1.0


def annual_factor(item: Commodity, current_date: date) -> float:
    base = {
        2022: 1.00,
        2023: 0.97,
        2024: 1.03,
        2025: 1.02,
    }.get(current_date.year, 1.0)

    if item.name == "Beras" and current_date.year == 2024:
        base *= 1.04
    if item.name in {"Cabai Merah", "Bawang Merah"} and current_date.year == 2024:
        base *= 1.02

    return base


def generate_series(
    item: Commodity, rng: random.Random
) -> list[tuple[str, float, str]]:
    rows: list[tuple[str, float, str]] = []
    current_stock = item.base_stock * item.monthly_factors[START_DATE.month]

    for current_date in daterange(START_DATE, END_DATE):
        month_factor = item.monthly_factors[current_date.month]
        target = (
            item.base_stock
            * month_factor
            * annual_factor(item, current_date)
            * event_multiplier(item, current_date)
        )

        day_of_year = current_date.timetuple().tm_yday
        micro_wave = math.sin((day_of_year / 14.0) + (len(item.code) * 0.7)) * 0.0035
        weekly_wave = math.sin((current_date.weekday() / 6.0) * math.pi) * 0.0018
        random_drift = rng.uniform(-0.004, 0.004)
        corrective = eased_delta(target, current_stock)

        step = corrective + micro_wave + weekly_wave + random_drift
        step = max(-0.03, min(0.03, step))
        current_stock = current_stock * (1.0 + step)
        current_stock = (current_stock * 0.78) + (target * 0.22)
        current_stock = max(item.min_stock, min(item.max_stock, current_stock))

        rows.append(
            (
                current_date.isoformat(),
                round(current_stock, 2),
                weighted_location(rng),
            )
        )

    return rows


def build_payload(id_by_code: dict[str, int]) -> list[tuple[int, str, float, str]]:
    rows: list[tuple[int, str, float, str]] = []
    for index, item in enumerate(COMMODITIES, start=1):
        rng = random.Random(20250415 + (index * 97))
        series = generate_series(item, rng)
        item_id = id_by_code[item.code]
        rows.extend(
            (item_id, waktu_catat, jumlah_aktual, lokasi_gudang)
            for waktu_catat, jumlah_aktual, lokasi_gudang in series
        )
    return rows


def seed() -> None:
    try:
        mysql_connector = __import__("mysql.connector", fromlist=["connector"])
    except ModuleNotFoundError as exc:
        raise SystemExit(
            "Seeder gagal: dependency `mysql-connector-python` belum terpasang. "
            "Install dengan `pip install mysql-connector-python`."
        ) from exc

    config = db_config()
    connection = mysql_connector.connect(**config)

    try:
        cursor = connection.cursor()
        ensure_schema(cursor)

        id_by_code = upsert_master(cursor)
        payload = build_payload(id_by_code)

        cursor.executemany(
            """
            INSERT INTO data_stok_historis (id_komoditas, waktu_catat, jumlah_aktual, lokasi_gudang)
            VALUES (%s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE
                jumlah_aktual = VALUES(jumlah_aktual),
                lokasi_gudang = VALUES(lokasi_gudang)
            """,
            payload,
        )

        connection.commit()

        cursor.execute("SELECT COUNT(*) FROM komoditas")
        komoditas_count = cursor.fetchone()[0]
        cursor.execute(
            """
            SELECT COUNT(*)
            FROM data_stok_historis
            WHERE waktu_catat BETWEEN %s AND %s
            """,
            (START_DATE.isoformat(), END_DATE.isoformat()),
        )
        stok_count = cursor.fetchone()[0]

        print(f"Seeder selesai pada {datetime.now().isoformat(timespec='seconds')}")
        print(f"Komoditas tersedia: {komoditas_count}")
        print(f"Baris stok historis periode {START_DATE} s.d. {END_DATE}: {stok_count}")
    finally:
        connection.close()


if __name__ == "__main__":
    try:
        seed()
    except Exception as exc:
        raise SystemExit(f"Seeder gagal: {exc}")
