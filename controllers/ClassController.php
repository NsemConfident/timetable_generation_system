<?php

declare(strict_types=1);

namespace Controllers;

use Models\AcademicYear;
use Models\SchoolClass;
use Models\Term;
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
        if ($data['name'] === '') {
            Response::validationError('Validation failed.', ['name' => ['Class name is required and cannot be empty.']]);
        }
        $academicYear = (new AcademicYear())->findById($data['academic_year_id']);
        $term = (new Term())->findById($data['term_id']);
        if (!$academicYear) {
            Response::validationError('Invalid academic year.', ['academic_year_id' => ['This academic year does not exist.']]);
        }
        if (!$term) {
            Response::validationError('Invalid term.', ['term_id' => ['This term does not exist.']]);
        }
        if ((int) $term['academic_year_id'] !== (int) $data['academic_year_id']) {
            Response::validationError('Term does not belong to the given academic year.', ['term_id' => ['This term belongs to a different academic year.']]);
        }
        try {
            $id = $this->model->create($data);
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'Duplicate entry') !== false || strpos($msg, '1062') !== false) {
                Response::validationError('A class with this name may already exist for this term.', ['name' => ['Class name must be unique per term.']]);
            }
            if (strpos($msg, 'foreign key') !== false || strpos($msg, '1452') !== false || strpos($msg, 'Cannot add or update a child row') !== false) {
                Response::validationError(
                    'Invalid academic year or term. Please ensure the academic year and term exist.',
                    ['academic_year_id' => ['Check that this academic year exists.'], 'term_id' => ['Check that this term exists and belongs to the academic year.']]
                );
            }
            if (stripos($msg, 'cannot be null') !== false || (stripos($msg, 'Column') !== false && stripos($msg, 'null') !== false)) {
                Response::validationError('Invalid data. Please check all required fields are provided.', []);
            }
            Response::error('Unable to create class. Please try again or check your data.', [], 422);
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
