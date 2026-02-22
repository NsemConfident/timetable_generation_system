<?php

declare(strict_types=1);

/**
 * API route definitions.
 * Pattern may include {id}, {classId}, {teacherId} etc.
 */

$authMiddleware = [
    ['class' => 'Middleware\AuthMiddleware', 'method' => 'handle'],
];

return [
    // -------------------------------------------------------------------------
    // Auth (public)
    // -------------------------------------------------------------------------
    ['pattern' => '/api/auth/register', 'methods' => ['POST'], 'handler' => ['Controllers\AuthController', 'register']],
    ['pattern' => '/api/auth/login', 'methods' => ['POST'], 'handler' => ['Controllers\AuthController', 'login']],

    // Auth (protected)
    ['pattern' => '/api/auth/logout', 'methods' => ['POST'], 'handler' => ['Controllers\AuthController', 'logout'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/auth/me', 'methods' => ['GET'], 'handler' => ['Controllers\AuthController', 'me'], 'middleware' => $authMiddleware],

    // -------------------------------------------------------------------------
    // Academic years
    // -------------------------------------------------------------------------
    ['pattern' => '/api/academic-years', 'methods' => ['GET'], 'handler' => ['Controllers\AcademicYearController', 'index'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/academic-years', 'methods' => ['POST'], 'handler' => ['Controllers\AcademicYearController', 'store'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/academic-years/{id}', 'methods' => ['GET'], 'handler' => ['Controllers\AcademicYearController', 'show'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/academic-years/{id}', 'methods' => ['PUT'], 'handler' => ['Controllers\AcademicYearController', 'update'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/academic-years/{id}', 'methods' => ['DELETE'], 'handler' => ['Controllers\AcademicYearController', 'destroy'], 'middleware' => $authMiddleware],

    // -------------------------------------------------------------------------
    // Terms
    // -------------------------------------------------------------------------
    ['pattern' => '/api/terms', 'methods' => ['GET'], 'handler' => ['Controllers\TermController', 'index'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/terms', 'methods' => ['POST'], 'handler' => ['Controllers\TermController', 'store'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/terms/{id}', 'methods' => ['GET'], 'handler' => ['Controllers\TermController', 'show'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/terms/{id}', 'methods' => ['PUT'], 'handler' => ['Controllers\TermController', 'update'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/terms/{id}', 'methods' => ['DELETE'], 'handler' => ['Controllers\TermController', 'destroy'], 'middleware' => $authMiddleware],

    // -------------------------------------------------------------------------
    // Classes
    // -------------------------------------------------------------------------
    ['pattern' => '/api/classes', 'methods' => ['GET'], 'handler' => ['Controllers\ClassController', 'index'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/classes', 'methods' => ['POST'], 'handler' => ['Controllers\ClassController', 'store'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/classes/{id}', 'methods' => ['GET'], 'handler' => ['Controllers\ClassController', 'show'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/classes/{id}', 'methods' => ['PUT'], 'handler' => ['Controllers\ClassController', 'update'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/classes/{id}', 'methods' => ['DELETE'], 'handler' => ['Controllers\ClassController', 'destroy'], 'middleware' => $authMiddleware],

    // -------------------------------------------------------------------------
    // Teachers
    // -------------------------------------------------------------------------
    ['pattern' => '/api/teachers', 'methods' => ['GET'], 'handler' => ['Controllers\TeacherController', 'index'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/teachers', 'methods' => ['POST'], 'handler' => ['Controllers\TeacherController', 'store'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/teachers/{id}', 'methods' => ['GET'], 'handler' => ['Controllers\TeacherController', 'show'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/teachers/{id}', 'methods' => ['PUT'], 'handler' => ['Controllers\TeacherController', 'update'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/teachers/{id}', 'methods' => ['DELETE'], 'handler' => ['Controllers\TeacherController', 'destroy'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/teachers/{id}/subjects', 'methods' => ['GET'], 'handler' => ['Controllers\TeacherController', 'subjects'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/teachers/{id}/subjects', 'methods' => ['POST'], 'handler' => ['Controllers\TeacherController', 'assignSubjects'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/teachers/{id}/availability', 'methods' => ['GET'], 'handler' => ['Controllers\TeacherController', 'availability'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/teachers/{id}/availability', 'methods' => ['PUT'], 'handler' => ['Controllers\TeacherController', 'updateAvailability'], 'middleware' => $authMiddleware],

    // -------------------------------------------------------------------------
    // Subjects
    // -------------------------------------------------------------------------
    ['pattern' => '/api/subjects', 'methods' => ['GET'], 'handler' => ['Controllers\SubjectController', 'index'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/subjects', 'methods' => ['POST'], 'handler' => ['Controllers\SubjectController', 'store'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/subjects/{id}', 'methods' => ['GET'], 'handler' => ['Controllers\SubjectController', 'show'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/subjects/{id}', 'methods' => ['PUT'], 'handler' => ['Controllers\SubjectController', 'update'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/subjects/{id}', 'methods' => ['DELETE'], 'handler' => ['Controllers\SubjectController', 'destroy'], 'middleware' => $authMiddleware],

    // -------------------------------------------------------------------------
    // Rooms
    // -------------------------------------------------------------------------
    ['pattern' => '/api/rooms', 'methods' => ['GET'], 'handler' => ['Controllers\RoomController', 'index'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/rooms', 'methods' => ['POST'], 'handler' => ['Controllers\RoomController', 'store'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/rooms/{id}', 'methods' => ['GET'], 'handler' => ['Controllers\RoomController', 'show'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/rooms/{id}', 'methods' => ['PUT'], 'handler' => ['Controllers\RoomController', 'update'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/rooms/{id}', 'methods' => ['DELETE'], 'handler' => ['Controllers\RoomController', 'destroy'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/rooms/{id}/availability', 'methods' => ['GET'], 'handler' => ['Controllers\RoomController', 'availability'], 'middleware' => $authMiddleware],

    // -------------------------------------------------------------------------
    // Time configuration: school days, time slots, break periods
    // -------------------------------------------------------------------------
    ['pattern' => '/api/school-days', 'methods' => ['GET'], 'handler' => ['Controllers\SchoolDayController', 'index'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/school-days', 'methods' => ['POST'], 'handler' => ['Controllers\SchoolDayController', 'store'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/school-days/{id}', 'methods' => ['PUT'], 'handler' => ['Controllers\SchoolDayController', 'update'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/time-slots', 'methods' => ['GET'], 'handler' => ['Controllers\TimeSlotController', 'index'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/time-slots', 'methods' => ['POST'], 'handler' => ['Controllers\TimeSlotController', 'store'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/time-slots/{id}', 'methods' => ['PUT'], 'handler' => ['Controllers\TimeSlotController', 'update'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/time-slots/{id}', 'methods' => ['DELETE'], 'handler' => ['Controllers\TimeSlotController', 'destroy'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/break-periods', 'methods' => ['GET'], 'handler' => ['Controllers\BreakPeriodController', 'index'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/break-periods', 'methods' => ['POST'], 'handler' => ['Controllers\BreakPeriodController', 'store'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/break-periods/{id}', 'methods' => ['DELETE'], 'handler' => ['Controllers\BreakPeriodController', 'destroy'], 'middleware' => $authMiddleware],

    // -------------------------------------------------------------------------
    // Allocations
    // -------------------------------------------------------------------------
    ['pattern' => '/api/allocations', 'methods' => ['GET'], 'handler' => ['Controllers\AllocationController', 'index'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/allocations', 'methods' => ['POST'], 'handler' => ['Controllers\AllocationController', 'store'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/allocations/class/{id}', 'methods' => ['GET'], 'handler' => ['Controllers\AllocationController', 'byClass'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/allocations/{id}', 'methods' => ['PUT'], 'handler' => ['Controllers\AllocationController', 'update'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/allocations/{id}', 'methods' => ['DELETE'], 'handler' => ['Controllers\AllocationController', 'destroy'], 'middleware' => $authMiddleware],

    // -------------------------------------------------------------------------
    // Timetable
    // -------------------------------------------------------------------------
    ['pattern' => '/api/timetable/generate', 'methods' => ['POST'], 'handler' => ['Controllers\TimetableController', 'generate'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/timetable', 'methods' => ['GET'], 'handler' => ['Controllers\TimetableController', 'index'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/timetable/class/{id}', 'methods' => ['GET'], 'handler' => ['Controllers\TimetableController', 'byClass'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/timetable/teacher/{id}', 'methods' => ['GET'], 'handler' => ['Controllers\TimetableController', 'byTeacher'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/timetable/swap', 'methods' => ['POST'], 'handler' => ['Controllers\TimetableController', 'swap'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/timetable/move', 'methods' => ['POST'], 'handler' => ['Controllers\TimetableController', 'move'], 'middleware' => $authMiddleware],
    ['pattern' => '/api/timetable/conflicts', 'methods' => ['GET'], 'handler' => ['Controllers\TimetableController', 'conflicts'], 'middleware' => $authMiddleware],
];
