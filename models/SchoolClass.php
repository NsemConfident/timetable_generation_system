<?php

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

class SchoolClass
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function all(?int $termId = null, ?int $academicYearId = null): array
    {
        $sql = 'SELECT c.id, c.academic_year_id, c.term_id, c.name, c.created_at,
                       t.name AS term_name, ay.name AS academic_year_name
                FROM classes c
                JOIN terms t ON t.id = c.term_id
                JOIN academic_years ay ON ay.id = c.academic_year_id
                WHERE 1=1';
        $params = [];
        if ($termId) {
            $sql .= ' AND c.term_id = ?';
            $params[] = $termId;
        }
        if ($academicYearId) {
            $sql .= ' AND c.academic_year_id = ?';
            $params[] = $academicYearId;
        }
        $sql .= ' ORDER BY c.name';
        $stmt = $params ? $this->pdo->prepare($sql) : $this->pdo->query($sql);
        if ($params) {
            $stmt->execute($params);
        }
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.academic_year_id, c.term_id, c.name, c.created_at,
                    t.name AS term_name, ay.name AS academic_year_name
             FROM classes c
             JOIN terms t ON t.id = c.term_id
             JOIN academic_years ay ON ay.id = c.academic_year_id
             WHERE c.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO classes (academic_year_id, term_id, name) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $data['academic_year_id'],
            $data['term_id'],
            $data['name'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE classes SET academic_year_id = ?, term_id = ?, name = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['academic_year_id'],
            $data['term_id'],
            $data['name'],
            $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM classes WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
