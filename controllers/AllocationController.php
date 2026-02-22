<?php

declare(strict_types=1);

namespace Controllers;

use Models\ClassSubjectAllocation;
use Utils\Response;
use Utils\Validator;

class AllocationController
{
    private ClassSubjectAllocation $model;

    public function __construct()
    {
        $this->model = new ClassSubjectAllocation();
    }

    public function index(): void
    {
        $termId = isset($_GET['term_id']) ? (int) $_GET['term_id'] : null;
        $list = $this->model->all($termId);
        Response::success('Request successful.', ['allocations' => $list]);
    }

    public function byClass(string $id): void
    {
        $classId = (int) $id;
        $termId = isset($_GET['term_id']) ? (int) $_GET['term_id'] : null;
        $list = $this->model->byClass($classId, $termId);
        Response::success('Request successful.', ['allocations' => $list]);
    }

    public function store(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $v = new Validator($input);
        $v->required('class_id', 'subject_id', 'teacher_id', 'academic_year_id', 'term_id')
          ->integer('class_id', 1)
          ->integer('subject_id', 1)
          ->integer('teacher_id', 1)
          ->integer('academic_year_id', 1)
          ->integer('term_id', 1)
          ->integer('periods_per_week', 1);
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['periods_per_week'] = (int) ($data['periods_per_week'] ?? 1);
        $id = $this->model->create($data);
        $item = $this->model->findById($id);
        Response::success('Allocation created.', ['allocation' => $item], 201);
    }

    public function update(string $id): void
    {
        $id = (int) $id;
        $item = $this->model->findById($id);
        if (!$item) {
            Response::notFound('Allocation not found.');
        }
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $v = new Validator($input);
        $v->required('class_id', 'subject_id', 'teacher_id', 'academic_year_id', 'term_id');
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['periods_per_week'] = (int) ($data['periods_per_week'] ?? 1);
        $this->model->update($id, $data);
        $item = $this->model->findById($id);
        Response::success('Allocation updated.', ['allocation' => $item]);
    }

    public function destroy(string $id): void
    {
        $id = (int) $id;
        if (!$this->model->findById($id)) {
            Response::notFound('Allocation not found.');
        }
        $this->model->delete($id);
        Response::success('Allocation deleted.');
    }
}
