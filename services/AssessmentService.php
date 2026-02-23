<?php

declare(strict_types=1);

namespace Services;

use Models\AssessmentSession;
use Models\AssessmentSubject;
use Models\AssessmentTimetable;
use Utils\Response;

/**
 * Assessment (CA/Exam) session and timetable orchestration.
 * Dispatches generation to CAGenerator or ExamGenerator by session type.
 */
class AssessmentService
{
    private AssessmentSession $sessionModel;
    private AssessmentSubject $subjectModel;
    private AssessmentTimetable $timetableModel;

    public function __construct()
    {
        $this->sessionModel = new AssessmentSession();
        $this->subjectModel = new AssessmentSubject();
        $this->timetableModel = new AssessmentTimetable();
    }

    public function generate(int $assessmentSessionId): array
    {
        $session = $this->sessionModel->findById($assessmentSessionId);
        if (!$session) {
            Response::notFound('Assessment session not found.');
        }
        if ($session['type'] === 'ca') {
            return (new CAGenerator())->generate($assessmentSessionId);
        }
        if ($session['type'] === 'exam') {
            return (new ExamGenerator())->generate($assessmentSessionId);
        }
        Response::error('Invalid assessment session type.');
        return [];
    }

    public function getTimetable(int $assessmentSessionId): array
    {
        $session = $this->sessionModel->findById($assessmentSessionId);
        if (!$session) {
            Response::notFound('Assessment session not found.');
        }
        return $this->timetableModel->bySession($assessmentSessionId);
    }

    /** Swap two assessment timetable entries (day/slot/room). */
    public function swap(int $entryId1, int $entryId2): array
    {
        $e1 = $this->timetableModel->findById($entryId1);
        $e2 = $this->timetableModel->findById($entryId2);
        if (!$e1 || !$e2) {
            Response::notFound('One or both timetable entries not found.');
        }
        if ((int) $e1['assessment_session_id'] !== (int) $e2['assessment_session_id']) {
            Response::error('Cannot swap entries from different sessions.');
        }
        $sessionId = (int) $e1['assessment_session_id'];

        $day1 = (int) $e1['school_day_id'];
        $slot1 = (int) $e1['time_slot_id'];
        $day2 = (int) $e2['school_day_id'];
        $slot2 = (int) $e2['time_slot_id'];

        $subj1 = (new AssessmentSubject())->findById((int) $e1['assessment_subject_id']);
        $subj2 = (new AssessmentSubject())->findById((int) $e2['assessment_subject_id']);
        if (!$subj1 || !$subj2) {
            Response::notFound('Assessment subject not found.');
        }
        $class1 = (int) $subj1['class_id'];
        $class2 = (int) $subj2['class_id'];

        $timetableModel = $this->timetableModel;
        if ($timetableModel->isClassBusy($sessionId, $class1, $day2, $slot2, $entryId1)) {
            Response::error('Class already has an exam in the target slot.');
        }
        if ($timetableModel->isClassBusy($sessionId, $class2, $day1, $slot1, $entryId2)) {
            Response::error('Class already has an exam in the target slot.');
        }

        $room1 = $e1['room_id'] ? (int) $e1['room_id'] : null;
        $room2 = $e2['room_id'] ? (int) $e2['room_id'] : null;
        if ($room1 !== null && $timetableModel->isRoomBusy($sessionId, $room1, $day2, $slot2, $entryId1)) {
            Response::error('Room already in use in target slot.');
        }
        if ($room2 !== null && $timetableModel->isRoomBusy($sessionId, $room2, $day1, $slot1, $entryId2)) {
            Response::error('Room already in use in target slot.');
        }

        $this->timetableModel->update($entryId1, [
            'room_id' => $room2,
            'school_day_id' => $day2,
            'time_slot_id' => $slot2,
            'supervisor_teacher_id' => $e2['supervisor_teacher_id'] ?? null,
        ]);
        $this->timetableModel->update($entryId2, [
            'room_id' => $room1,
            'school_day_id' => $day1,
            'time_slot_id' => $slot1,
            'supervisor_teacher_id' => $e1['supervisor_teacher_id'] ?? null,
        ]);

        return [
            'entry_1' => $this->timetableModel->findById($entryId1),
            'entry_2' => $this->timetableModel->findById($entryId2),
        ];
    }

    /** Move one assessment timetable entry to a new day/slot/room. */
    public function move(int $entryId, int $schoolDayId, int $timeSlotId, ?int $roomId = null): array
    {
        $e = $this->timetableModel->findById($entryId);
        if (!$e) {
            Response::notFound('Timetable entry not found.');
        }
        $sessionId = (int) $e['assessment_session_id'];
        $subj = (new AssessmentSubject())->findById((int) $e['assessment_subject_id']);
        if (!$subj) {
            Response::notFound('Assessment subject not found.');
        }
        $classId = (int) $subj['class_id'];

        if ($this->timetableModel->isClassBusy($sessionId, $classId, $schoolDayId, $timeSlotId, $entryId)) {
            Response::error('Class already has an exam in that slot.');
        }
        if ($roomId !== null && $this->timetableModel->isRoomBusy($sessionId, $roomId, $schoolDayId, $timeSlotId, $entryId)) {
            Response::error('Room already in use in that slot.');
        }

        $session = $this->sessionModel->findById($sessionId);
        if ($session && $session['type'] === 'ca') {
            $countOnDay = $this->timetableModel->countClassExamsOnDay($sessionId, $classId, $schoolDayId, $entryId);
            if ($countOnDay >= 2) {
                Response::error('CA: max 2 exams per class per day. That day already has 2 exams for this class.');
            }
        }

        $this->timetableModel->update($entryId, [
            'room_id' => $roomId ?? $e['room_id'],
            'school_day_id' => $schoolDayId,
            'time_slot_id' => $timeSlotId,
            'supervisor_teacher_id' => $e['supervisor_teacher_id'] ?? null,
        ]);

        return ['entry' => $this->timetableModel->findById($entryId)];
    }
}
