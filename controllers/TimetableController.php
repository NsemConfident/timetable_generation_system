<?php

declare(strict_types=1);

namespace Controllers;

use Services\TimetableService;
use Utils\Response;
use Utils\Validator;

class TimetableController
{
    private TimetableService $service;

    public function __construct()
    {
        $this->service = new TimetableService();
    }

    public function generate(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $v = new Validator($input);
        $v->required('term_id')->integer('term_id', 1);
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $termId = (int) $input['term_id'];
        $result = $this->service->generate($termId);
        Response::success('Timetable generated.', $result, 201);
    }

    public function index(): void
    {
        $termId = isset($_GET['term_id']) ? (int) $_GET['term_id'] : null;
        $entries = $this->service->getAll($termId);
        Response::success('Request successful.', ['timetable' => $entries]);
    }

    public function byClass(string $id): void
    {
        $classId = (int) $id;
        $termId = isset($_GET['term_id']) ? (int) $_GET['term_id'] : null;
        $entries = $this->service->getByClass($classId, $termId);
        Response::success('Request successful.', ['timetable' => $entries]);
    }

    public function byTeacher(string $id): void
    {
        $teacherId = (int) $id;
        $termId = isset($_GET['term_id']) ? (int) $_GET['term_id'] : null;
        $entries = $this->service->getByTeacher($teacherId, $termId);
        Response::success('Request successful.', ['timetable' => $entries]);
    }

    public function swap(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $v = new Validator($input);
        $v->required('entry_id_1', 'entry_id_2')->integer('entry_id_1', 1)->integer('entry_id_2', 1);
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $result = $this->service->swap((int) $input['entry_id_1'], (int) $input['entry_id_2']);
        Response::success('Entries swapped.', $result);
    }

    public function move(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $v = new Validator($input);
        $v->required('entry_id', 'school_day_id', 'time_slot_id')
          ->integer('entry_id', 1)
          ->integer('school_day_id', 1)
          ->integer('time_slot_id', 1);
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $roomId = isset($input['room_id']) ? (int) $input['room_id'] : null;
        $result = $this->service->move(
            (int) $input['entry_id'],
            (int) $input['school_day_id'],
            (int) $input['time_slot_id'],
            $roomId
        );
        Response::success('Entry moved.', $result);
    }

    public function conflicts(): void
    {
        $termId = isset($_GET['term_id']) ? (int) $_GET['term_id'] : null;
        $result = $this->service->getConflicts($termId);
        Response::success('Request successful.', $result);
    }
}
