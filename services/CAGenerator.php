<?php

declare(strict_types=1);

namespace Services;

use Models\AssessmentSession;
use Models\AssessmentSubject;
use Models\AssessmentTimetable;
use Models\BreakPeriod;
use Models\Room;
use Models\SchoolDay;
use Models\Teacher;
use Models\TimeSlot;
use Utils\Response;

/**
 * CA (Continuous Assessment) timetable generator.
 * Constraints: max 2 exams per class per day, respect teacher/supervisor availability,
 * avoid class and room clashes, use school days and time slots (excluding break periods).
 * Algorithm: greedy + backtracking — schedule hardest (longest duration) first.
 */
class CAGenerator
{
    private const MAX_EXAMS_PER_CLASS_PER_DAY = 2;

    private AssessmentSession $sessionModel;
    private AssessmentSubject $subjectModel;
    private AssessmentTimetable $timetableModel;
    private Teacher $teacherModel;
    private SchoolDay $schoolDayModel;
    private TimeSlot $timeSlotModel;
    private BreakPeriod $breakPeriodModel;
    private Room $roomModel;

    private int $sessionId;
    private array $daySlots = [];
    private array $demands = [];
    private array $roomIds = [];

    public function __construct()
    {
        $this->sessionModel = new AssessmentSession();
        $this->subjectModel = new AssessmentSubject();
        $this->timetableModel = new AssessmentTimetable();
        $this->teacherModel = new Teacher();
        $this->schoolDayModel = new SchoolDay();
        $this->timeSlotModel = new TimeSlot();
        $this->breakPeriodModel = new BreakPeriod();
        $this->roomModel = new Room();
    }

    public function generate(int $assessmentSessionId): array
    {
        $session = $this->sessionModel->findById($assessmentSessionId);
        if (!$session) {
            Response::notFound('Assessment session not found.');
        }
        if ($session['type'] !== 'ca') {
            Response::error('This endpoint is for CA sessions only. Use exam generate for exam sessions.');
        }

        $this->sessionId = $assessmentSessionId;
        $subjects = $this->subjectModel->getForSession($assessmentSessionId);
        if (empty($subjects)) {
            Response::error('No subjects in this assessment session. Add subjects first via POST /api/assessments/{id}/subjects.');
        }

        $days = $this->schoolDayModel->all();
        $slots = $this->timeSlotModel->all();
        $breakSlotIds = $this->breakPeriodModel->getBreakSlotIds(null);

        foreach ($days as $d) {
            $this->daySlots[(int) $d['id']] = [];
            foreach ($slots as $s) {
                if (in_array((int) $s['id'], $breakSlotIds, true)) {
                    continue;
                }
                $this->daySlots[(int) $d['id']][(int) $s['slot_order']] = (int) $s['id'];
            }
        }

        $totalSlots = 0;
        foreach ($this->daySlots as $slotList) {
            $totalSlots += count($slotList);
        }
        if ($totalSlots === 0 || empty($days) || empty($slots)) {
            Response::error('No available time slots. Add school days and time slots, and ensure not all slots are break periods.');
        }

        $this->demands = $subjects;
        usort($this->demands, function ($a, $b) {
            $durA = (int) ($a['duration_minutes'] ?? 60);
            $durB = (int) ($b['duration_minutes'] ?? 60);
            if ($durB !== $durA) {
                return $durB <=> $durA;
            }
            if ((int) $a['class_id'] !== (int) $b['class_id']) {
                return (int) $a['class_id'] <=> (int) $b['class_id'];
            }
            return (int) $a['subject_id'] <=> (int) $b['subject_id'];
        });

        $rooms = $this->roomModel->all();
        $this->roomIds = array_map('intval', array_column($rooms, 'id'));

        $this->timetableModel->deleteBySession($assessmentSessionId);

        $success = $this->backtrack(0);
        if (!$success) {
            Response::error(
                'CA timetable generation failed: could not place all exams. ' .
                'Ensure enough slots, rooms, and that supervisor availability allows scheduling (max ' . self::MAX_EXAMS_PER_CLASS_PER_DAY . ' exams per class per day).'
            );
        }

        $entries = $this->timetableModel->bySession($assessmentSessionId);
        return [
            'assessment_session_id' => $assessmentSessionId,
            'entries_count' => count($entries),
            'entries' => $entries,
        ];
    }

    private function backtrack(int $demandIndex): bool
    {
        if ($demandIndex >= count($this->demands)) {
            return true;
        }

        $d = $this->demands[$demandIndex];
        $assessmentSubjectId = (int) $d['id'];
        $classId = (int) $d['class_id'];
        $subjectId = (int) $d['subject_id'];
        $supervisorId = isset($d['supervisor_teacher_id']) && $d['supervisor_teacher_id'] !== null
            ? (int) $d['supervisor_teacher_id'] : null;

        $attempts = [];
        foreach (array_keys($this->daySlots) as $dayId) {
            $countOnDay = $this->timetableModel->countClassExamsOnDay($this->sessionId, $classId, $dayId, null);
            if ($countOnDay >= self::MAX_EXAMS_PER_CLASS_PER_DAY) {
                continue;
            }
            foreach ($this->daySlots[$dayId] as $slotOrder => $slotId) {
                if ($this->canPlace($classId, $dayId, $slotId, $supervisorId, null)) {
                    $attempts[] = ['day_id' => $dayId, 'slot_id' => $slotId];
                }
            }
        }

        shuffle($attempts);
        foreach ($attempts as $cell) {
            $dayId = $cell['day_id'];
            $slotId = $cell['slot_id'];
            $roomId = $this->pickRoom($dayId, $slotId);
            if (!$this->canPlace($classId, $dayId, $slotId, $supervisorId, $roomId)) {
                continue;
            }
            $this->place($assessmentSubjectId, $classId, $roomId, $dayId, $slotId, $supervisorId);
            if ($this->backtrack($demandIndex + 1)) {
                return true;
            }
            $this->unplace($assessmentSubjectId, $roomId, $dayId, $slotId);
        }
        return false;
    }

    private function canPlace(int $classId, int $dayId, int $slotId, ?int $supervisorId, ?int $roomId): bool
    {
        if ($this->timetableModel->isClassBusy($this->sessionId, $classId, $dayId, $slotId, null)) {
            return false;
        }
        if ($roomId !== null && $this->timetableModel->isRoomBusy($this->sessionId, $roomId, $dayId, $slotId, null)) {
            return false;
        }
        if ($supervisorId !== null) {
            if ($this->timetableModel->isSupervisorBusy($this->sessionId, $supervisorId, $dayId, $slotId, null)) {
                return false;
            }
            if (!$this->teacherModel->isAvailable($supervisorId, $dayId, $slotId)) {
                return false;
            }
        }
        return true;
    }

    private function place(int $assessmentSubjectId, int $classId, ?int $roomId, int $dayId, int $slotId, ?int $supervisorId): void
    {
        $this->timetableModel->create([
            'assessment_session_id' => $this->sessionId,
            'assessment_subject_id' => $assessmentSubjectId,
            'room_id' => $roomId,
            'school_day_id' => $dayId,
            'time_slot_id' => $slotId,
            'supervisor_teacher_id' => $supervisorId,
        ]);
    }

    private function unplace(int $assessmentSubjectId, ?int $roomId, int $dayId, int $slotId): void
    {
        $pdo = \Config\Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(
            'DELETE FROM assessment_timetable WHERE assessment_session_id = ? AND assessment_subject_id = ? AND school_day_id = ? AND time_slot_id = ? LIMIT 1'
        );
        $stmt->execute([$this->sessionId, $assessmentSubjectId, $dayId, $slotId]);
    }

    private function pickRoom(int $dayId, int $slotId): ?int
    {
        foreach ($this->roomIds as $rid) {
            if (!$this->timetableModel->isRoomBusy($this->sessionId, $rid, $dayId, $slotId, null)) {
                return $rid;
            }
        }
        return null;
    }
}
