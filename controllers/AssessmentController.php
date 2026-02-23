<?php

declare(strict_types=1);

namespace Controllers;

use Models\AcademicYear;
use Models\AssessmentSession;
use Models\AssessmentSubject;
use Models\Term;
use Services\AssessmentService;
use Utils\Request;
use Utils\Response;
use Utils\Validator;

/**
 * Assessment (CA/Exam) sessions and timetable.
 * CRUD sessions, add subjects, generate timetable, list/swap/move.
 */
class AssessmentController
{
    private AssessmentSession $sessionModel;
    private AssessmentService $service;

    public function __construct()
    {
        $this->sessionModel = new AssessmentSession();
        $this->service = new AssessmentService();
    }

    public function index(): void
    {
        $termId = isset($_GET['term_id']) ? (int) $_GET['term_id'] : null;
        $type = isset($_GET['type']) ? (string) $_GET['type'] : null;
        if ($type !== null && $type !== 'ca' && $type !== 'exam') {
            $type = null;
        }
        $list = $this->sessionModel->all($termId, $type);
        Response::success('Request successful.', ['assessments' => $list]);
    }

    public function show(string $id): void
    {
        $id = (int) $id;
        $item = $this->sessionModel->findById($id);
        if (!$item) {
            Response::notFound('Assessment session not found.');
        }
        Response::success('Request successful.', ['assessment' => $item]);
    }

    public function store(): void
    {
        $input = Request::input();
        $v = new Validator($input);
        $v->required('name', 'type', 'term_id', 'academic_year_id', 'start_date', 'end_date')
          ->in('type', ['ca', 'exam'])
          ->integer('term_id', 1)
          ->integer('academic_year_id', 1);
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['term_id'] = (int) $data['term_id'];
        $data['academic_year_id'] = (int) $data['academic_year_id'];
        $data['default_duration_minutes'] = (int) ($data['default_duration_minutes'] ?? 60);

        $term = (new Term())->findById($data['term_id']);
        $ay = (new AcademicYear())->findById($data['academic_year_id']);
        if (!$ay) {
            Response::validationError('Invalid academic year.', ['academic_year_id' => ['Academic year does not exist.']]);
        }
        if (!$term) {
            Response::validationError('Invalid term.', ['term_id' => ['Term does not exist.']]);
        }
        if ((int) $term['academic_year_id'] !== $data['academic_year_id']) {
            Response::validationError('Term does not belong to the given academic year.', ['term_id' => ['Term belongs to a different academic year.']]);
        }

        try {
            $id = $this->sessionModel->create($data);
        } catch (\PDOException $e) {
            Response::error('Unable to create assessment session.', [], 422);
        }
        $item = $this->sessionModel->findById($id);
        Response::success('Assessment session created.', ['assessment' => $item], 201);
    }

    public function update(string $id): void
    {
        $id = (int) $id;
        $item = $this->sessionModel->findById($id);
        if (!$item) {
            Response::notFound('Assessment session not found.');
        }
        $input = Request::input();
        $v = new Validator($input);
        $v->required('name', 'type', 'term_id', 'academic_year_id', 'start_date', 'end_date')
          ->in('type', ['ca', 'exam']);
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['term_id'] = (int) $data['term_id'];
        $data['academic_year_id'] = (int) $data['academic_year_id'];
        $data['default_duration_minutes'] = (int) ($data['default_duration_minutes'] ?? 60);
        $this->sessionModel->update($id, $data);
        $item = $this->sessionModel->findById($id);
        Response::success('Assessment session updated.', ['assessment' => $item]);
    }

    public function destroy(string $id): void
    {
        $id = (int) $id;
        if (!$this->sessionModel->findById($id)) {
            Response::notFound('Assessment session not found.');
        }
        $this->sessionModel->delete($id);
        Response::success('Assessment session deleted.');
    }

    public function subjects(string $id): void
    {
        $id = (int) $id;
        if (!$this->sessionModel->findById($id)) {
            Response::notFound('Assessment session not found.');
        }
        $subjectModel = new AssessmentSubject();
        $list = $subjectModel->bySession($id);
        Response::success('Request successful.', ['subjects' => $list]);
    }

    public function addSubjects(string $id): void
    {
        $id = (int) $id;
        $session = $this->sessionModel->findById($id);
        if (!$session) {
            Response::notFound('Assessment session not found.');
        }
        $input = Request::input();
        if (isset($input['items']) && is_string($input['items'])) {
            $input['items'] = json_decode($input['items'], true);
            if (!is_array($input['items'])) {
                $input['items'] = [];
            }
        }
        $v = new Validator($input);
        $v->required('items');
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $items = $input['items'];
        if (!is_array($items)) {
            Response::validationError('items must be an array of {class_id, subject_id, duration_minutes?, supervisor_teacher_id?}.', []);
        }
        $subjectModel = new AssessmentSubject();
        $created = [];
        foreach ($items as $row) {
            $vr = new Validator($row);
            $vr->required('class_id', 'subject_id')->integer('class_id', 1)->integer('subject_id', 1);
            if ($vr->fails()) {
                continue;
            }
            try {
                $sid = $subjectModel->create([
                    'assessment_session_id' => $id,
                    'class_id' => (int) $row['class_id'],
                    'subject_id' => (int) $row['subject_id'],
                    'duration_minutes' => isset($row['duration_minutes']) ? (int) $row['duration_minutes'] : null,
                    'supervisor_teacher_id' => isset($row['supervisor_teacher_id']) ? (int) $row['supervisor_teacher_id'] : null,
                ]);
                $created[] = $subjectModel->findById($sid);
            } catch (\PDOException $e) {
                // duplicate or FK failure — skip
            }
        }
        $list = $subjectModel->bySession($id);
        Response::success('Subjects added.', ['subjects' => $list, 'added_count' => count($created)]);
    }

    public function generate(string $id): void
    {
        $id = (int) $id;
        $result = $this->service->generate($id);
        Response::success('Timetable generated.', $result, 201);
    }

    public function timetable(string $id): void
    {
        $id = (int) $id;
        $entries = $this->service->getTimetable($id);
        Response::success('Request successful.', ['timetable' => $entries]);
    }

    public function swap(): void
    {
        $input = Request::input();
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
        $input = Request::input();
        $v = new Validator($input);
        $v->required('entry_id', 'school_day_id', 'time_slot_id')
          ->integer('entry_id', 1)->integer('school_day_id', 1)->integer('time_slot_id', 1);
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
}
