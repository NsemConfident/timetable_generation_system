<?php

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

/**
 * Subject to assess in a session: class + subject (+ optional duration, supervisor).
 */
class AssessmentSubject
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function bySession(int $assessmentSessionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.id, a.assessment_session_id, a.class_id, a.subject_id, a.duration_minutes, a.supervisor_teacher_id,
                    c.name AS class_name, s.name AS subject_name, s.code AS subject_code, t.name AS supervisor_name
             FROM assessment_subjects a
             JOIN classes c ON c.id = a.class_id
             JOIN subjects s ON s.id = a.subject_id
             LEFT JOIN teachers t ON t.id = a.supervisor_teacher_id
             WHERE a.assessment_session_id = ? ORDER BY c.name, s.name'
        );
        $stmt->execute([$assessmentSessionId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, assessment_session_id, class_id, subject_id, duration_minutes, supervisor_teacher_id, created_at
             FROM assessment_subjects WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO assessment_subjects (assessment_session_id, class_id, subject_id, duration_minutes, supervisor_teacher_id)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['assessment_session_id'],
            $data['class_id'],
            $data['subject_id'],
            $data['duration_minutes'] ?? null,
            $data['supervisor_teacher_id'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM assessment_subjects WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /** Get all assessment_subject rows for a session (for generator). */
    public function getForSession(int $assessmentSessionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, assessment_session_id, class_id, subject_id, duration_minutes, supervisor_teacher_id
             FROM assessment_subjects WHERE assessment_session_id = ? ORDER BY id'
        );
        $stmt->execute([$assessmentSessionId]);
        return $stmt->fetchAll();
    }
}
