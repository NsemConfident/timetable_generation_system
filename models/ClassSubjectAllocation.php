<?php

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

class ClassSubjectAllocation
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function all(?int $termId = null): array
    {
        $sql = 'SELECT a.id, a.class_id, a.subject_id, a.teacher_id, a.periods_per_week,
                       a.academic_year_id, a.term_id,
                       c.name AS class_name, s.name AS subject_name, t.name AS teacher_name
                FROM class_subject_allocations a
                JOIN classes c ON c.id = a.class_id
                JOIN subjects s ON s.id = a.subject_id
                JOIN teachers t ON t.id = a.teacher_id
                WHERE 1=1';
        $params = [];
        if ($termId) {
            $sql .= ' AND a.term_id = ?';
            $params[] = $termId;
        }
        $sql .= ' ORDER BY c.name, s.name';
        $stmt = $params ? $this->pdo->prepare($sql) : $this->pdo->query($sql);
        if ($params) {
            $stmt->execute($params);
        }
        return $stmt->fetchAll();
    }

    public function byClass(int $classId, ?int $termId = null): array
    {
        $sql = 'SELECT a.id, a.class_id, a.subject_id, a.teacher_id, a.periods_per_week,
                       a.academic_year_id, a.term_id,
                       s.name AS subject_name, s.code AS subject_code, t.name AS teacher_name
                FROM class_subject_allocations a
                JOIN subjects s ON s.id = a.subject_id
                JOIN teachers t ON t.id = a.teacher_id
                WHERE a.class_id = ?';
        $params = [$classId];
        if ($termId) {
            $sql .= ' AND a.term_id = ?';
            $params[] = $termId;
        }
        $sql .= ' ORDER BY s.name';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, class_id, subject_id, teacher_id, periods_per_week, academic_year_id, term_id
             FROM class_subject_allocations WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO class_subject_allocations (class_id, subject_id, teacher_id, periods_per_week, academic_year_id, term_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['class_id'],
            $data['subject_id'],
            $data['teacher_id'],
            $data['periods_per_week'] ?? 1,
            $data['academic_year_id'],
            $data['term_id'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE class_subject_allocations SET class_id = ?, subject_id = ?, teacher_id = ?, periods_per_week = ?, academic_year_id = ?, term_id = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['class_id'],
            $data['subject_id'],
            $data['teacher_id'],
            $data['periods_per_week'] ?? 1,
            $data['academic_year_id'],
            $data['term_id'],
            $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM class_subject_allocations WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /** Get allocations for timetable generation: term_id -> list of [class_id, subject_id, teacher_id, periods_per_week] */
    public function getForTerm(int $termId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT class_id, subject_id, teacher_id, periods_per_week, academic_year_id
             FROM class_subject_allocations WHERE term_id = ? ORDER BY class_id, subject_id'
        );
        $stmt->execute([$termId]);
        return $stmt->fetchAll();
    }
}
