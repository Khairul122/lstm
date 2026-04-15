<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use PDO;

final class StokHistoris
{
    public static function paginate(string $search = '', int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::connection();
        $search = trim($search);
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $whereSql = '';
        $bindings = [];

        if ($search !== '') {
            $whereSql = 'WHERE (
                k.kode_komoditas LIKE :search
                OR k.nama_komoditas LIKE :search
                OR dsh.waktu_catat LIKE :search
                OR dsh.lokasi_gudang LIKE :search
            )';
            $bindings[':search'] = '%' . $search . '%';
        }

        $countStmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM data_stok_historis dsh
             LEFT JOIN komoditas k ON k.id_komoditas = dsh.id_komoditas
             ' . $whereSql
        );

        foreach ($bindings as $key => $value) {
            $countStmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        $countStmt->execute();
        $totalItems = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($totalItems / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $stmt = $pdo->prepare(
            'SELECT dsh.id_stok,
                    dsh.id_komoditas,
                    dsh.waktu_catat,
                    dsh.jumlah_aktual,
                    dsh.lokasi_gudang,
                    k.kode_komoditas,
                    k.nama_komoditas,
                    k.satuan
             FROM data_stok_historis dsh
             LEFT JOIN komoditas k ON k.id_komoditas = dsh.id_komoditas
             ' . $whereSql . '
             ORDER BY dsh.waktu_catat DESC, dsh.id_stok DESC
             LIMIT :limit OFFSET :offset'
        );

        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
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

    public static function find(int $idStok): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT id_stok, id_komoditas, waktu_catat, jumlah_aktual, lokasi_gudang
             FROM data_stok_historis
             WHERE id_stok = :id_stok
             LIMIT 1'
        );
        $stmt->bindValue(':id_stok', $idStok, PDO::PARAM_INT);
        $stmt->execute();

        $item = $stmt->fetch();

        return is_array($item) ? $item : null;
    }

    public static function create(array $payload): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO data_stok_historis (id_komoditas, waktu_catat, jumlah_aktual, lokasi_gudang)
             VALUES (:id_komoditas, :waktu_catat, :jumlah_aktual, :lokasi_gudang)'
        );
        $stmt->bindValue(':id_komoditas', (int) $payload['id_komoditas'], PDO::PARAM_INT);
        $stmt->bindValue(':waktu_catat', $payload['waktu_catat'], PDO::PARAM_STR);
        $stmt->bindValue(':jumlah_aktual', (float) $payload['jumlah_aktual']);
        $stmt->bindValue(':lokasi_gudang', $payload['lokasi_gudang'], PDO::PARAM_STR);
        $stmt->execute();
    }

    public static function update(int $idStok, array $payload): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE data_stok_historis
             SET id_komoditas = :id_komoditas,
                 waktu_catat = :waktu_catat,
                 jumlah_aktual = :jumlah_aktual,
                 lokasi_gudang = :lokasi_gudang
             WHERE id_stok = :id_stok'
        );
        $stmt->bindValue(':id_komoditas', (int) $payload['id_komoditas'], PDO::PARAM_INT);
        $stmt->bindValue(':waktu_catat', $payload['waktu_catat'], PDO::PARAM_STR);
        $stmt->bindValue(':jumlah_aktual', (float) $payload['jumlah_aktual']);
        $stmt->bindValue(':lokasi_gudang', $payload['lokasi_gudang'], PDO::PARAM_STR);
        $stmt->bindValue(':id_stok', $idStok, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function delete(int $idStok): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('DELETE FROM data_stok_historis WHERE id_stok = :id_stok');
        $stmt->bindValue(':id_stok', $idStok, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function dashboardSummary(): array
    {
        $pdo = Database::connection();
        $totalRecords = (int) $pdo->query('SELECT COUNT(*) FROM data_stok_historis')->fetchColumn();
        $latestDate = (string) ($pdo->query('SELECT MAX(waktu_catat) FROM data_stok_historis')->fetchColumn() ?: '-');

        $snapshotStmt = $pdo->prepare(
            'SELECT k.nama_komoditas, dsh.jumlah_aktual, k.satuan, dsh.lokasi_gudang
             FROM data_stok_historis dsh
             INNER JOIN komoditas k ON k.id_komoditas = dsh.id_komoditas
             WHERE dsh.waktu_catat = (SELECT MAX(waktu_catat) FROM data_stok_historis)
             ORDER BY dsh.jumlah_aktual DESC
             LIMIT 5'
        );
        $snapshotStmt->execute();

        return [
            'total_records' => $totalRecords,
            'latest_date' => $latestDate,
            'latest_snapshot' => $snapshotStmt->fetchAll(),
        ];
    }
}
