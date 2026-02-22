<?php

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

class TimetableEntry
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function all(?int $termId = null): array
    {
        $sql = 'SELECT e.id, e.class_id, e.teacher_id, e.subject_id, e.room_id,
                       e.school_day_id, e.time_slot_id, e.academic_year_id, e.term_id,
                       c.name AS class_name, t.name AS teacher_name, s.name AS subject_name,
                       r.name AS room_name, sd.name AS day_name, ts.name AS slot_name,
                       ts.start_time, ts.end_time
                FROM timetable_entries e
                JOIN classes c ON c.id = e.class_id
                JOIN teachers t ON t.id = e.teacher_id
                JOIN subjects s ON s.id = e.subject_id
                LEFT JOIN rooms r ON r.id = e.room_id
                JOIN school_days sd ON sd.id = e.school_day_id
                JOIN time_slots ts ON ts.id = e.time_slot_id
                WHERE 1=1';
        $params = [];
        if ($termId) {
            $sql .= ' AND e.term_id = ?';
            $params[] = $termId;
        }
        $sql .= ' ORDER BY e.term_id, sd.day_order, ts.slot_order, c.name';
        $stmt = $params ? $this->pdo->prepare($sql) : $this->pdo->query($sql);
        if ($params) {
            $stmt->execute($params);
        }
        return $stmt->fetchAll();
    }

    public function byClass(int $classId, ?int $termId = null): array
    {
        $sql = 'SELECT e.id, e.class_id, e.teacher_id, e.subject_id, e.room_id,
                       e.school_day_id, e.time_slot_id,
                       s.name AS subject_name, t.name AS teacher_name, r.name AS room_name,
                       sd.name AS day_name, sd.day_order, ts.name AS slot_name, ts.slot_order, ts.start_time, ts.end_time
                FROM timetable_entries e
                JOIN subjects s ON s.id = e.subject_id
                JOIN teachers t ON t.id = e.teacher_id
                LEFT JOIN rooms r ON r.id = e.room_id
                JOIN school_days sd ON sd.id = e.school_day_id
                JOIN time_slots ts ON ts.id = e.time_slot_id
                WHERE e.class_id = ?';
        $params = [$classId];
        if ($termId) {
            $sql .= ' AND e.term_id = ?';
            $params[] = $termId;
        }
        $sql .= ' ORDER BY sd.day_order, ts.slot_order';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function byTeacher(int $teacherId, ?int $termId = null): array
    {
        $sql = 'SELECT e.id, e.class_id, e.teacher_id, e.subject_id, e.room_id,
                       e.school_day_id, e.time_slot_id,
                       c.name AS class_name, s.name AS subject_name, r.name AS room_name,
                       sd.name AS day_name, sd.day_order, ts.name AS slot_name, ts.slot_order, ts.start_time, ts.end_time
                FROM timetable_entries e
                JOIN classes c ON c.id = e.class_id
                JOIN subjects s ON s.id = e.subject_id
                LEFT JOIN rooms r ON r.id = e.room_id
                JOIN school_days sd ON sd.id = e.school_day_id
                JOIN time_slots ts ON ts.id = e.time_slot_id
                WHERE e.teacher_id = ?';
        $params = [$teacherId];
        if ($termId) {
            $sql .= ' AND e.term_id = ?';
            $params[] = $termId;
        }
        $sql .= ' ORDER BY sd.day_order, ts.slot_order';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, class_id, teacher_id, subject_id, room_id, school_day_id, time_slot_id, academic_year_id, term_id
             FROM timetable_entries WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO timetable_entries (class_id, teacher_id, subject_id, room_id, school_day_id, time_slot_id, academic_year_id, term_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['class_id'],
            $data['teacher_id'],
            $data['subject_id'],
            $data['room_id'] ?? null,
            $data['school_day_id'],
            $data['time_slot_id'],
            $data['academic_year_id'],
            $data['term_id'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE timetable_entries SET class_id = ?, teacher_id = ?, subject_id = ?, room_id = ?, school_day_id = ?, time_slot_id = ?, academic_year_id = ?, term_id = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['class_id'],
            $data['teacher_id'],
            $data['subject_id'],
            $data['room_id'] ?? null,
            $data['school_day_id'],
            $data['time_slot_id'],
            $data['academic_year_id'],
            $data['term_id'],
            $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM timetable_entries WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function deleteByTerm(int $termId): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM timetable_entries WHERE term_id = ?');
        $stmt->execute([$termId]);
        return $stmt->rowCount();
    }

    /** Check if slot (class+day+slot+term) is taken */
    public function isSlotTaken(int $classId, int $schoolDayId, int $timeSlotId, int $termId, ?int $excludeEntryId = null): bool
    {
        $sql = 'SELECT 1 FROM timetable_entries WHERE class_id = ? AND school_day_id = ? AND time_slot_id = ? AND term_id = ?';
        $params = [$classId, $schoolDayId, $timeSlotId, $termId];
        if ($excludeEntryId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeEntryId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    /** Check if teacher is busy at day+slot+term */
    public function isTeacherBusy(int $teacherId, int $schoolDayId, int $timeSlotId, int $termId, ?int $excludeEntryId = null): bool
    {
        $sql = 'SELECT 1 FROM timetable_entries WHERE teacher_id = ? AND school_day_id = ? AND time_slot_id = ? AND term_id = ?';
        $params = [$teacherId, $schoolDayId, $timeSlotId, $termId];
        if ($excludeEntryId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeEntryId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    /** Check if room is busy at day+slot+term */
    public function isRoomBusy(?int $roomId, int $schoolDayId, int $timeSlotId, int $termId, ?int $excludeEntryId = null): bool
    {
        if ($roomId === null) {
            return false;
        }
        $sql = 'SELECT 1 FROM timetable_entries WHERE room_id = ? AND school_day_id = ? AND time_slot_id = ? AND term_id = ?';
        $params = [$roomId, $schoolDayId, $timeSlotId, $termId];
        if ($excludeEntryId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeEntryId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }
}
