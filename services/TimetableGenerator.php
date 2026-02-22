<?php

declare(strict_types=1);

namespace Services;

use Models\BreakPeriod;
use Models\ClassSubjectAllocation;
use Models\Room;
use Models\SchoolDay;
use Models\Teacher;
use Models\TimeSlot;
use Models\TimetableEntry;
use Utils\Response;

/**
 * Timetable generation engine.
 * Hard constraints:
 * - Teacher cannot appear twice at same time
 * - Room cannot be double-booked
 * - Class cannot have two subjects in same period
 * - Respect teacher availability (unavailable = cannot schedule)
 */
class TimetableGenerator
{
    private ClassSubjectAllocation $allocationModel;
    private TimetableEntry $entryModel;
    private Teacher $teacherModel;
    private SchoolDay $schoolDayModel;
    private TimeSlot $timeSlotModel;
    private BreakPeriod $breakPeriodModel;
    private Room $roomModel;

    /** @var array<int, array> room_id -> [day_id => [slot_id => true]] */
    private array $roomUsed = [];
    /** @var array<int, array> teacher_id -> [day_id => [slot_id => true]] */
    private array $teacherUsed = [];
    /** @var array<int, array> class_id -> [day_id => [slot_id => true]] */
    private array $classUsed = [];
    /** @var array<int> break slot ids (cannot place lessons) */
    private array $breakSlotIds = [];
    /** @var array list of [class_id, subject_id, teacher_id, periods_per_week, academic_year_id] */
    private array $demands = [];
    /** @var array period counts left to place per demand index */
    private array $periodsLeft = [];
    /** @var array [day_id => [slot_order => slot_id]] */
    private array $daySlots = [];
    private int $termId;
    private int $academicYearId;

    public function __construct()
    {
        $this->allocationModel = new ClassSubjectAllocation();
        $this->entryModel = new TimetableEntry();
        $this->teacherModel = new Teacher();
        $this->schoolDayModel = new SchoolDay();
        $this->timeSlotModel = new TimeSlot();
        $this->breakPeriodModel = new BreakPeriod();
        $this->roomModel = new Room();
    }

    /**
     * Generate timetable for the given term.
     * Uses backtracking: try to place each demand in a valid slot; backtrack on failure.
     */
    public function generate(int $termId): array
    {
        $allocations = $this->allocationModel->getForTerm($termId);
        if (empty($allocations)) {
            Response::error('No allocations found for this term. Add class-subject-teacher allocations first.');
        }

        $first = $allocations[0];
        $this->termId = $termId;
        $this->academicYearId = (int) $first['academic_year_id'];

        $days = $this->schoolDayModel->all();
        $slots = $this->timeSlotModel->all();
        $this->breakSlotIds = $this->breakPeriodModel->getBreakSlotIds(null);

        foreach ($days as $d) {
            $this->daySlots[(int) $d['id']] = [];
            foreach ($slots as $s) {
                if (in_array((int) $s['id'], $this->breakSlotIds, true)) {
                    continue;
                }
                $this->daySlots[(int) $d['id']][(int) $s['slot_order']] = (int) $s['id'];
            }
        }

        $this->demands = [];
        foreach ($allocations as $a) {
            $ppw = (int) ($a['periods_per_week'] ?? 1);
            $this->demands[] = [
                'class_id' => (int) $a['class_id'],
                'subject_id' => (int) $a['subject_id'],
                'teacher_id' => (int) $a['teacher_id'],
                'periods_per_week' => $ppw,
                'academic_year_id' => (int) $a['academic_year_id'],
            ];
            $this->periodsLeft[] = $ppw;
        }

        $this->roomUsed = [];
        $this->teacherUsed = [];
        $this->classUsed = [];

        $rooms = $this->roomModel->all();
        $roomIds = array_column($rooms, 'id');
        $roomIds = array_map('intval', $roomIds);

        $this->entryModel->deleteByTerm($termId);

        $placed = [];
        $success = $this->backtrack(0, $placed, $roomIds);
        if (!$success) {
            Response::error(
                'Timetable generation failed: could not satisfy all constraints. ' .
                'Check teacher availability, number of periods vs available slots, and allocations.'
            );
        }

        $entries = $this->entryModel->all($termId);
        return [
            'term_id' => $termId,
            'academic_year_id' => $this->academicYearId,
            'entries_count' => count($entries),
            'entries' => $entries,
        ];
    }

    private function backtrack(int $demandIndex, array &$placed, array $roomIds): bool
    {
        if ($demandIndex >= count($this->demands)) {
            return true;
        }

        $d = $this->demands[$demandIndex];
        $toPlace = $this->periodsLeft[$demandIndex];
        if ($toPlace <= 0) {
            return $this->backtrack($demandIndex + 1, $placed, $roomIds);
        }

        $classId = $d['class_id'];
        $teacherId = $d['teacher_id'];
        $subjectId = $d['subject_id'];

        $dayIds = array_keys($this->daySlots);
        $attempts = [];
        foreach ($dayIds as $dayId) {
            $slotOrders = array_keys($this->daySlots[$dayId]);
            foreach ($slotOrders as $slotOrder) {
                $slotId = $this->daySlots[$dayId][$slotOrder];
                if ($this->canPlace($classId, $teacherId, $dayId, $slotId, null)) {
                    $attempts[] = ['day_id' => $dayId, 'slot_id' => $slotId, 'slot_order' => $slotOrder];
                }
            }
        }

        shuffle($attempts);
        foreach ($attempts as $cell) {
            $dayId = $cell['day_id'];
            $slotId = $cell['slot_id'];
            $roomId = $this->pickRoom($dayId, $slotId, $roomIds);
            if (!$this->canPlace($classId, $teacherId, $dayId, $slotId, $roomId)) {
                continue;
            }
            $this->place($classId, $teacherId, $subjectId, $roomId, $dayId, $slotId);
            $this->periodsLeft[$demandIndex]--;
            $placed[] = $demandIndex;
            if ($this->backtrack($demandIndex, $placed, $roomIds)) {
                return true;
            }
            $this->unplace($classId, $teacherId, $roomId, $dayId, $slotId);
            $this->periodsLeft[$demandIndex]++;
            array_pop($placed);
        }

        if ($this->periodsLeft[$demandIndex] === $d['periods_per_week']) {
            return $this->backtrack($demandIndex + 1, $placed, $roomIds);
        }
        return false;
    }

    private function canPlace(int $classId, int $teacherId, int $dayId, int $slotId, ?int $roomId): bool
    {
        if (isset($this->classUsed[$classId][$dayId][$slotId])) {
            return false;
        }
        if (isset($this->teacherUsed[$teacherId][$dayId][$slotId])) {
            return false;
        }
        if ($roomId !== null && isset($this->roomUsed[$roomId][$dayId][$slotId])) {
            return false;
        }
        if (!$this->teacherModel->isAvailable($teacherId, $dayId, $slotId)) {
            return false;
        }
        return true;
    }

    private function place(int $classId, int $teacherId, int $subjectId, ?int $roomId, int $dayId, int $slotId): void
    {
        $this->entryModel->create([
            'class_id' => $classId,
            'teacher_id' => $teacherId,
            'subject_id' => $subjectId,
            'room_id' => $roomId,
            'school_day_id' => $dayId,
            'time_slot_id' => $slotId,
            'academic_year_id' => $this->academicYearId,
            'term_id' => $this->termId,
        ]);
        $this->classUsed[$classId][$dayId][$slotId] = true;
        $this->teacherUsed[$teacherId][$dayId][$slotId] = true;
        if ($roomId !== null) {
            $this->roomUsed[$roomId][$dayId][$slotId] = true;
        }
    }

    private function unplace(int $classId, int $teacherId, ?int $roomId, int $dayId, int $slotId): void
    {
        $pdo = \Config\Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(
            'DELETE FROM timetable_entries WHERE class_id = ? AND teacher_id = ? AND school_day_id = ? AND time_slot_id = ? AND term_id = ? LIMIT 1'
        );
        $stmt->execute([$classId, $teacherId, $dayId, $slotId, $this->termId]);
        unset($this->classUsed[$classId][$dayId][$slotId]);
        unset($this->teacherUsed[$teacherId][$dayId][$slotId]);
        if ($roomId !== null) {
            unset($this->roomUsed[$roomId][$dayId][$slotId]);
        }
    }

    private function pickRoom(int $dayId, int $slotId, array $roomIds): ?int
    {
        foreach ($roomIds as $rid) {
            if (!isset($this->roomUsed[$rid][$dayId][$slotId])) {
                return $rid;
            }
        }
        return null;
    }
}
