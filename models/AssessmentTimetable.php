<?php

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

/**
 * Generated assessment timetable: one row = one scheduled exam/CA slot.
 */
class AssessmentTimetable
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function bySession(int $assessmentSessionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.id, e.assessment_session_id, e.assessment_subject_id, e.room_id, e.school_day_id, e.time_slot_id, e.supervisor_teacher_id,
                    a.class_id, a.subject_id, c.name AS class_name, s.name AS subject_name,
                    r.name AS room_name, sd.name AS day_name, sd.day_order, ts.name AS slot_name, ts.start_time, ts.end_time,
                    t.name AS supervisor_name
             FROM assessment_timetable e
             JOIN assessment_subjects a ON a.id = e.assessment_subject_id
             JOIN classes c ON c.id = a.class_id
             JOIN subjects s ON s.id = a.subject_id
             LEFT JOIN rooms r ON r.id = e.room_id
             JOIN school_days sd ON sd.id = e.school_day_id
             JOIN time_slots ts ON ts.id = e.time_slot_id
             LEFT JOIN teachers t ON t.id = e.supervisor_teacher_id
             WHERE e.assessment_session_id = ? ORDER BY sd.day_order, ts.slot_order, c.name'
        );
        $stmt->execute([$assessmentSessionId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, assessment_session_id, assessment_subject_id, room_id, school_day_id, time_slot_id, supervisor_teacher_id, created_at
             FROM assessment_timetable WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO assessment_timetable (assessment_session_id, assessment_subject_id, room_id, school_day_id, time_slot_id, supervisor_teacher_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['assessment_session_id'],
            $data['assessment_subject_id'],
            $data['room_id'] ?? null,
            $data['school_day_id'],
            $data['time_slot_id'],
            $data['supervisor_teacher_id'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE assessment_timetable SET room_id = ?, school_day_id = ?, time_slot_id = ?, supervisor_teacher_id = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['room_id'] ?? null,
            $data['school_day_id'],
            $data['time_slot_id'],
            $data['supervisor_teacher_id'] ?? null,
            $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function deleteBySession(int $assessmentSessionId): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM assessment_timetable WHERE assessment_session_id = ?');
        $stmt->execute([$assessmentSessionId]);
        return $stmt->rowCount();
    }

    /** Check if class has exam at day+slot in this session. */
    public function isClassBusy(int $assessmentSessionId, int $classId, int $schoolDayId, int $timeSlotId, ?int $excludeEntryId = null): bool
    {
        $sql = 'SELECT 1 FROM assessment_timetable e
                JOIN assessment_subjects a ON a.id = e.assessment_subject_id
                WHERE e.assessment_session_id = ? AND a.class_id = ? AND e.school_day_id = ? AND e.time_slot_id = ?';
        $params = [$assessmentSessionId, $classId, $schoolDayId, $timeSlotId];
        if ($excludeEntryId !== null) {
            $sql .= ' AND e.id != ?';
            $params[] = $excludeEntryId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    /** Check if room is used at day+slot in this session. */
    public function isRoomBusy(int $assessmentSessionId, ?int $roomId, int $schoolDayId, int $timeSlotId, ?int $excludeEntryId = null): bool
    {
        if ($roomId === null) {
            return false;
        }
        $sql = 'SELECT 1 FROM assessment_timetable WHERE assessment_session_id = ? AND room_id = ? AND school_day_id = ? AND time_slot_id = ?';
        $params = [$assessmentSessionId, $roomId, $schoolDayId, $timeSlotId];
        if ($excludeEntryId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeEntryId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    /** Count exams per class per day in session (for CA: max 2 per class per day). */
    public function countClassExamsOnDay(int $assessmentSessionId, int $classId, int $schoolDayId, ?int $excludeEntryId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM assessment_timetable e
                JOIN assessment_subjects a ON a.id = e.assessment_subject_id
                WHERE e.assessment_session_id = ? AND a.class_id = ? AND e.school_day_id = ?';
        $params = [$assessmentSessionId, $classId, $schoolDayId];
        if ($excludeEntryId !== null) {
            $sql .= ' AND e.id != ?';
            $params[] = $excludeEntryId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Check if supervisor (teacher) is busy at day+slot in this session. */
    public function isSupervisorBusy(int $assessmentSessionId, ?int $teacherId, int $schoolDayId, int $timeSlotId, ?int $excludeEntryId = null): bool
    {
        if ($teacherId === null) {
            return false;
        }
        $sql = 'SELECT 1 FROM assessment_timetable WHERE assessment_session_id = ? AND supervisor_teacher_id = ? AND school_day_id = ? AND time_slot_id = ?';
        $params = [$assessmentSessionId, $teacherId, $schoolDayId, $timeSlotId];
        if ($excludeEntryId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeEntryId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }
}
