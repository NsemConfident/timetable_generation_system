<?php

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

/**
 * Assessment session: CA or Exam period (term-scoped, with date range).
 */
class AssessmentSession
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function all(?int $termId = null, ?string $type = null): array
    {
        $sql = 'SELECT s.id, s.name, s.type, s.term_id, s.academic_year_id, s.start_date, s.end_date,
                       s.default_duration_minutes, s.created_at,
                       t.name AS term_name, ay.name AS academic_year_name
                FROM assessment_sessions s
                JOIN terms t ON t.id = s.term_id
                JOIN academic_years ay ON ay.id = s.academic_year_id
                WHERE 1=1';
        $params = [];
        if ($termId !== null) {
            $sql .= ' AND s.term_id = ?';
            $params[] = $termId;
        }
        if ($type !== null) {
            $sql .= ' AND s.type = ?';
            $params[] = $type;
        }
        $sql .= ' ORDER BY s.start_date DESC';
        $stmt = $params ? $this->pdo->prepare($sql) : $this->pdo->query($sql);
        if ($params) {
            $stmt->execute($params);
        }
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.name, s.type, s.term_id, s.academic_year_id, s.start_date, s.end_date,
                    s.default_duration_minutes, s.created_at,
                    t.name AS term_name, ay.name AS academic_year_name
             FROM assessment_sessions s
             JOIN terms t ON t.id = s.term_id
             JOIN academic_years ay ON ay.id = s.academic_year_id
             WHERE s.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO assessment_sessions (name, type, term_id, academic_year_id, start_date, end_date, default_duration_minutes)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['type'],
            $data['term_id'],
            $data['academic_year_id'],
            $data['start_date'],
            $data['end_date'],
            $data['default_duration_minutes'] ?? 60,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE assessment_sessions SET name = ?, type = ?, term_id = ?, academic_year_id = ?, start_date = ?, end_date = ?, default_duration_minutes = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $data['type'],
            $data['term_id'],
            $data['academic_year_id'],
            $data['start_date'],
            $data['end_date'],
            $data['default_duration_minutes'] ?? 60,
            $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM assessment_sessions WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
