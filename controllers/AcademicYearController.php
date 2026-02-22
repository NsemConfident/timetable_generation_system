<?php

declare(strict_types=1);

namespace Controllers;

use Models\AcademicYear;
use Utils\Request;
use Utils\Response;
use Utils\Validator;

class AcademicYearController
{
    private AcademicYear $model;

    public function __construct()
    {
        $this->model = new AcademicYear();
    }

    public function index(): void
    {
        $list = $this->model->all();
        Response::success('Request successful.', ['academic_years' => $list]);
    }

    public function show(string $id): void
    {
        $id = (int) $id;
        $item = $this->model->findById($id);
        if (!$item) {
            Response::notFound('Academic year not found.');
        }
        Response::success('Request successful.', ['academic_year' => $item]);
    }

    public function store(): void
    {
        $input = Request::input();
        $v = new Validator($input);
        $v->required('name', 'start_date', 'end_date');
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['is_active'] = $data['is_active'] ?? 0;
        $id = $this->model->create($data);
        $item = $this->model->findById($id);
        Response::success('Academic year created.', ['academic_year' => $item], 201);
    }

    public function update(string $id): void
    {
        $id = (int) $id;
        $item = $this->model->findById($id);
        if (!$item) {
            Response::notFound('Academic year not found.');
        }
        $input = Request::input();
        $v = new Validator($input);
        $v->required('name', 'start_date', 'end_date');
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['is_active'] = $data['is_active'] ?? 0;
        $this->model->update($id, $data);
        $item = $this->model->findById($id);
        Response::success('Academic year updated.', ['academic_year' => $item]);
    }

    public function destroy(string $id): void
    {
        $id = (int) $id;
        if (!$this->model->findById($id)) {
            Response::notFound('Academic year not found.');
        }
        $this->model->delete($id);
        Response::success('Academic year deleted.');
    }
}
