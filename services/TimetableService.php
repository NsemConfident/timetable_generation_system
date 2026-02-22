<?php

declare(strict_types=1);

namespace Services;

use Models\TimetableEntry;
use Utils\Response;

class TimetableService
{
    private TimetableEntry $entryModel;
    private TimetableGenerator $generator;

    public function __construct()
    {
        $this->entryModel = new TimetableEntry();
        $this->generator = new TimetableGenerator();
    }

    public function generate(int $termId): array
    {
        return $this->generator->generate($termId);
    }

    public function getAll(?int $termId = null): array
    {
        return $this->entryModel->all($termId);
    }

    public function getByClass(int $classId, ?int $termId = null): array
    {
        return $this->entryModel->byClass($classId, $termId);
    }

    public function getByTeacher(int $teacherId, ?int $termId = null): array
    {
        return $this->entryModel->byTeacher($teacherId, $termId);
    }

    /**
     * Swap two timetable entries (by id).
     * Revalidates conflicts after swap.
     */
    public function swap(int $entryId1, int $entryId2): array
    {
        $e1 = $this->entryModel->findById($entryId1);
        $e2 = $this->entryModel->findById($entryId2);
        if (!$e1 || !$e2) {
            Response::notFound('One or both timetable entries not found.');
        }
        if ((int) $e1['term_id'] !== (int) $e2['term_id']) {
            Response::error('Cannot swap entries from different terms.');
        }

        $day1 = (int) $e1['school_day_id'];
        $slot1 = (int) $e1['time_slot_id'];
        $day2 = (int) $e2['school_day_id'];
        $slot2 = (int) $e2['time_slot_id'];
        $termId = (int) $e1['term_id'];

        // Swap only time slots (day, slot, room); keep class/teacher/subject per entry
        $e1New = [
            'class_id' => (int) $e1['class_id'],
            'teacher_id' => (int) $e1['teacher_id'],
            'subject_id' => (int) $e1['subject_id'],
            'room_id' => $e2['room_id'] ? (int) $e2['room_id'] : null,
            'school_day_id' => $day2,
            'time_slot_id' => $slot2,
            'academic_year_id' => (int) $e1['academic_year_id'],
            'term_id' => $termId,
        ];
        $e2New = [
            'class_id' => (int) $e2['class_id'],
            'teacher_id' => (int) $e2['teacher_id'],
            'subject_id' => (int) $e2['subject_id'],
            'room_id' => $e1['room_id'] ? (int) $e1['room_id'] : null,
            'school_day_id' => $day1,
            'time_slot_id' => $slot1,
            'academic_year_id' => (int) $e2['academic_year_id'],
            'term_id' => $termId,
        ];

        $this->entryModel->update($entryId1, $e1New);
        $this->entryModel->update($entryId2, $e2New);

        $conflicts = $this->detectConflicts($termId);
        return [
            'entry_1' => $this->entryModel->findById($entryId1),
            'entry_2' => $this->entryModel->findById($entryId2),
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Move one entry to a new day/slot. Room can be optional.
     * Revalidates conflicts.
     */
    public function move(int $entryId, int $schoolDayId, int $timeSlotId, ?int $roomId = null): array
    {
        $e = $this->entryModel->findById($entryId);
        if (!$e) {
            Response::notFound('Timetable entry not found.');
        }
        $termId = (int) $e['term_id'];
        $classId = (int) $e['class_id'];
        $teacherId = (int) $e['teacher_id'];

        if ($this->entryModel->isSlotTaken($classId, $schoolDayId, $timeSlotId, $termId, $entryId)) {
            Response::error('Class already has a lesson in that slot.');
        }
        if ($this->entryModel->isTeacherBusy($teacherId, $schoolDayId, $timeSlotId, $termId, $entryId)) {
            Response::error('Teacher is already busy in that slot.');
        }
        if ($roomId !== null && $this->entryModel->isRoomBusy($roomId, $schoolDayId, $timeSlotId, $termId, $entryId)) {
            Response::error('Room is already in use in that slot.');
        }

        $this->entryModel->update($entryId, [
            'class_id' => $classId,
            'teacher_id' => $teacherId,
            'subject_id' => (int) $e['subject_id'],
            'room_id' => $roomId ?? $e['room_id'],
            'school_day_id' => $schoolDayId,
            'time_slot_id' => $timeSlotId,
            'academic_year_id' => (int) $e['academic_year_id'],
            'term_id' => $termId,
        ]);

        $conflicts = $this->detectConflicts($termId);
        return [
            'entry' => $this->entryModel->findById($entryId),
            'conflicts' => $conflicts,
        ];
    }

    public function getConflicts(?int $termId = null): array
    {
        if ($termId === null) {
            $termId = isset($_GET['term_id']) ? (int) $_GET['term_id'] : null;
        }
        if ($termId === null) {
            Response::error('term_id is required.');
        }
        return $this->detectConflicts($termId);
    }

    private function detectConflicts(int $termId): array
    {
        $entries = $this->entryModel->all($termId);
        $teacherConflicts = [];
        $roomConflicts = [];
        $classConflicts = [];

        $byTeacherSlot = [];
        $byRoomSlot = [];
        $byClassSlot = [];

        foreach ($entries as $e) {
            $key = (int) $e['teacher_id'] . '-' . (int) $e['school_day_id'] . '-' . (int) $e['time_slot_id'];
            $byTeacherSlot[$key] = ($byTeacherSlot[$key] ?? []);
            $byTeacherSlot[$key][] = $e;
        }
        foreach ($entries as $e) {
            if (!empty($e['room_id'])) {
                $key = (int) $e['room_id'] . '-' . (int) $e['school_day_id'] . '-' . (int) $e['time_slot_id'];
                $byRoomSlot[$key] = ($byRoomSlot[$key] ?? []);
                $byRoomSlot[$key][] = $e;
            }
        }
        foreach ($entries as $e) {
            $key = (int) $e['class_id'] . '-' . (int) $e['school_day_id'] . '-' . (int) $e['time_slot_id'];
            $byClassSlot[$key] = ($byClassSlot[$key] ?? []);
            $byClassSlot[$key][] = $e;
        }

        foreach ($byTeacherSlot as $list) {
            if (count($list) > 1) {
                $teacherConflicts[] = $list;
            }
        }
        foreach ($byRoomSlot as $list) {
            if (count($list) > 1) {
                $roomConflicts[] = $list;
            }
        }
        foreach ($byClassSlot as $list) {
            if (count($list) > 1) {
                $classConflicts[] = $list;
            }
        }

        return [
            'teacher_conflicts' => $teacherConflicts,
            'room_conflicts' => $roomConflicts,
            'class_conflicts' => $classConflicts,
        ];
    }
}
