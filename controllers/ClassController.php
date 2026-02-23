<?php

declare(strict_types=1);

namespace Controllers;

use Models\SchoolClass;
use Utils\Request;
use Utils\Response;
use Utils\Validator;

class ClassController
{
    private SchoolClass $model;

    public function __construct()
    {
        $this->model = new SchoolClass();
    }

    public function index(): void
    {
        $termId = isset($_GET['term_id']) ? (int) $_GET['term_id'] : null;
        $academicYearId = isset($_GET['academic_year_id']) ? (int) $_GET['academic_year_id'] : null;
        $list = $this->model->all($termId, $academicYearId);
        Response::success('Request successful.', ['classes' => $list]);
    }

    public function show(string $id): void
    {
        $id = (int) $id;
        $item = $this->model->findById($id);
        if (!$item) {
            Response::notFound('Class not found.');
        }
        Response::success('Request successful.', ['class' => $item]);
    }

    public function store(): void
    {
        $input = Request::input();
        $v = new Validator($input);
        $v->required('academic_year_id', 'term_id', 'name')
          ->integer('academic_year_id', 1)
          ->integer('term_id', 1);
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['academic_year_id'] = (int) $data['academic_year_id'];
        $data['term_id'] = (int) $data['term_id'];
        $data['name'] = trim((string) ($data['name'] ?? ''));
        try {
            $id = $this->model->create($data);
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            $code = (string) $e->getCode();
            if ($code === '23000' || strpos($msg, 'foreign key') !== false || strpos($msg, '1452') !== false) {
                Response::validationError(
                    'Invalid academic year or term. Please ensure the academic year and term exist.',
                    ['academic_year_id' => ['Check that this academic year exists.'], 'term_id' => ['Check that this term exists and belongs to the academic year.']]
                );
            }
            if (strpos($msg, 'Duplicate entry') !== false || strpos($msg, '1062') !== false) {
                Response::validationError('A class with this name may already exist for this term.', ['name' => ['Class name must be unique per term.']]);
            }
            Response::error('Unable to create class. Please check academic year and term exist.', [], 422);
        }
        $item = $this->model->findById($id);
        Response::success('Class created.', ['class' => $item], 201);
    }

    public function update(string $id): void
    {
        $id = (int) $id;
        $item = $this->model->findById($id);
        if (!$item) {
            Response::notFound('Class not found.');
        }
        $input = Request::input();
        $v = new Validator($input);
        $v->required('academic_year_id', 'term_id', 'name');
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['academic_year_id'] = (int) $data['academic_year_id'];
        $data['term_id'] = (int) $data['term_id'];
        $this->model->update($id, $data);
        $item = $this->model->findById($id);
        Response::success('Class updated.', ['class' => $item]);
    }

    public function destroy(string $id): void
    {
        $id = (int) $id;
        if (!$this->model->findById($id)) {
            Response::notFound('Class not found.');
        }
        $this->model->delete($id);
        Response::success('Class deleted.');
    }
}
