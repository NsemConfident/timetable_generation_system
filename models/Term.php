<?php

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

class Term
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function all(?int $academicYearId = null): array
    {
        if ($academicYearId) {
            $stmt = $this->pdo->prepare(
                'SELECT t.id, t.academic_year_id, t.name, t.start_date, t.end_date, t.created_at
                 FROM terms t WHERE t.academic_year_id = ? ORDER BY t.start_date'
            );
            $stmt->execute([$academicYearId]);
        } else {
            $stmt = $this->pdo->query(
                'SELECT t.id, t.academic_year_id, t.name, t.start_date, t.end_date, t.created_at
                 FROM terms t ORDER BY t.start_date DESC'
            );
        }
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, academic_year_id, name, start_date, end_date, created_at FROM terms WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO terms (academic_year_id, name, start_date, end_date) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['academic_year_id'],
            $data['name'],
            $data['start_date'],
            $data['end_date'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE terms SET academic_year_id = ?, name = ?, start_date = ?, end_date = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['academic_year_id'],
            $data['name'],
            $data['start_date'],
            $data['end_date'],
            $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM terms WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
