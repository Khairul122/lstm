<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database;
use PDO;

final class Komoditas
{
    public static function paginate(string $search = '', int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::connection();
        $search = trim($search);
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $whereSql = '';
        if ($search !== '') {
            $whereSql = 'WHERE kode_komoditas LIKE :search OR nama_komoditas LIKE :search OR satuan LIKE :search';
        }

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM komoditas ' . $whereSql);
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
            'SELECT id_komoditas, kode_komoditas, nama_komoditas, satuan
             FROM komoditas
             ' . $whereSql . '
             ORDER BY id_komoditas DESC
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

    public static function allOptions(): array
    {
        $pdo = Database::connection();

        return $pdo->query(
            'SELECT id_komoditas, kode_komoditas, nama_komoditas, satuan
             FROM komoditas
             ORDER BY nama_komoditas ASC'
        )->fetchAll();
    }

    public static function totalCount(): int
    {
        $pdo = Database::connection();

        return (int) $pdo->query('SELECT COUNT(*) FROM komoditas')->fetchColumn();
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT id_komoditas, kode_komoditas, nama_komoditas, satuan
             FROM komoditas
             WHERE id_komoditas = :id_komoditas
             LIMIT 1'
        );
        $stmt->bindValue(':id_komoditas', $id, PDO::PARAM_INT);
        $stmt->execute();

        $item = $stmt->fetch();

        return is_array($item) ? $item : null;
    }

    public static function findByKode(string $kode, ?int $exceptId = null): ?array
    {
        $pdo = Database::connection();
        $sql = 'SELECT id_komoditas, kode_komoditas, nama_komoditas, satuan
                FROM komoditas
                WHERE kode_komoditas = :kode_komoditas';

        if ($exceptId !== null) {
            $sql .= ' AND id_komoditas != :except_id';
        }

        $sql .= ' LIMIT 1';

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':kode_komoditas', $kode, PDO::PARAM_STR);

        if ($exceptId !== null) {
            $stmt->bindValue(':except_id', $exceptId, PDO::PARAM_INT);
        }

        $stmt->execute();
        $item = $stmt->fetch();

        return is_array($item) ? $item : null;
    }

    public static function create(array $payload): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO komoditas (kode_komoditas, nama_komoditas, satuan)
             VALUES (:kode_komoditas, :nama_komoditas, :satuan)'
        );
        $stmt->bindValue(':kode_komoditas', $payload['kode_komoditas'], PDO::PARAM_STR);
        $stmt->bindValue(':nama_komoditas', $payload['nama_komoditas'], PDO::PARAM_STR);
        $stmt->bindValue(':satuan', $payload['satuan'], PDO::PARAM_STR);
        $stmt->execute();
    }

    public static function update(int $id, array $payload): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE komoditas
             SET kode_komoditas = :kode_komoditas,
                 nama_komoditas = :nama_komoditas,
                 satuan = :satuan
             WHERE id_komoditas = :id_komoditas'
        );
        $stmt->bindValue(':kode_komoditas', $payload['kode_komoditas'], PDO::PARAM_STR);
        $stmt->bindValue(':nama_komoditas', $payload['nama_komoditas'], PDO::PARAM_STR);
        $stmt->bindValue(':satuan', $payload['satuan'], PDO::PARAM_STR);
        $stmt->bindValue(':id_komoditas', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function delete(int $id): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('DELETE FROM komoditas WHERE id_komoditas = :id_komoditas');
        $stmt->bindValue(':id_komoditas', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
}
