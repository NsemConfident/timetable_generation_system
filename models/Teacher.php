<?php

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

class Teacher
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, user_id, name, email, created_at FROM teachers ORDER BY name'
        );
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, name, email, created_at FROM teachers WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO teachers (user_id, name, email) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'] ?? null,
            $data['name'],
            $data['email'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        if (array_key_exists('user_id', $data)) {
            $fields[] = 'user_id = ?';
            $params[] = $data['user_id'];
        }
        if (array_key_exists('name', $data)) {
            $fields[] = 'name = ?';
            $params[] = $data['name'];
        }
        if (array_key_exists('email', $data)) {
            $fields[] = 'email = ?';
            $params[] = $data['email'];
        }
        if (empty($fields)) {
            return true;
        }
        $params[] = $id;
        $stmt = $this->pdo->prepare('UPDATE teachers SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM teachers WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function getSubjects(int $teacherId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.name, s.code FROM subjects s
             JOIN teacher_subjects ts ON ts.subject_id = s.id
             WHERE ts.teacher_id = ? ORDER BY s.name'
        );
        $stmt->execute([$teacherId]);
        return $stmt->fetchAll();
    }

    public function setSubjects(int $teacherId, array $subjectIds): void
    {
        $this->pdo->prepare('DELETE FROM teacher_subjects WHERE teacher_id = ?')->execute([$teacherId]);
        $stmt = $this->pdo->prepare('INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)');
        foreach ($subjectIds as $sid) {
            $stmt->execute([$teacherId, $sid]);
        }
    }

    public function getAvailability(int $teacherId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ta.id, ta.school_day_id, ta.time_slot_id, ta.is_available,
                    sd.name AS day_name, sd.day_order, ts.name AS slot_name, ts.start_time, ts.end_time
             FROM teacher_availability ta
             JOIN school_days sd ON sd.id = ta.school_day_id
             JOIN time_slots ts ON ts.id = ta.time_slot_id
             WHERE ta.teacher_id = ? ORDER BY sd.day_order, ts.slot_order'
        );
        $stmt->execute([$teacherId]);
        return $stmt->fetchAll();
    }

    public function setAvailability(int $teacherId, array $slots): void
    {
        $this->pdo->prepare('DELETE FROM teacher_availability WHERE teacher_id = ?')->execute([$teacherId]);
        $stmt = $this->pdo->prepare(
            'INSERT INTO teacher_availability (teacher_id, school_day_id, time_slot_id, is_available) VALUES (?, ?, ?, ?)'
        );
        foreach ($slots as $s) {
            $stmt->execute([
                $teacherId,
                $s['school_day_id'],
                $s['time_slot_id'],
                $s['is_available'] ?? 1,
            ]);
        }
    }

    /** Check if teacher is available at day + time_slot (default: available if no row) */
    public function isAvailable(int $teacherId, int $schoolDayId, int $timeSlotId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT is_available FROM teacher_availability
             WHERE teacher_id = ? AND school_day_id = ? AND time_slot_id = ?'
        );
        $stmt->execute([$teacherId, $schoolDayId, $timeSlotId]);
        $row = $stmt->fetch();
        return $row ? (bool) $row['is_available'] : true;
    }
}
