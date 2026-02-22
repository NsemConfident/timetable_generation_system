<?php

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

class AcademicYear
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?int $is_active = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, name, start_date, end_date, is_active, created_at FROM academic_years ORDER BY start_date DESC'
        );
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, start_date, end_date, is_active, created_at FROM academic_years WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getActive(): ?array
    {
        $stmt = $this->pdo->query(
            'SELECT id, name, start_date, end_date FROM academic_years WHERE is_active = 1 LIMIT 1'
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO academic_years (name, start_date, end_date, is_active) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['start_date'],
            $data['end_date'],
            $data['is_active'] ?? 0,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE academic_years SET name = ?, start_date = ?, end_date = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['name'] ?? null,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['is_active'] ?? 0,
            $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM academic_years WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
