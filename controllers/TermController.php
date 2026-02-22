<?php

declare(strict_types=1);

namespace Controllers;

use Models\Term;
use Utils\Request;
use Utils\Response;
use Utils\Validator;

class TermController
{
    private Term $model;

    public function __construct()
    {
        $this->model = new Term();
    }

    public function index(): void
    {
        $academicYearId = isset($_GET['academic_year_id']) ? (int) $_GET['academic_year_id'] : null;
        $list = $this->model->all($academicYearId);
        Response::success('Request successful.', ['terms' => $list]);
    }

    public function show(string $id): void
    {
        $id = (int) $id;
        $item = $this->model->findById($id);
        if (!$item) {
            Response::notFound('Term not found.');
        }
        Response::success('Request successful.', ['term' => $item]);
    }

    public function store(): void
    {
        $input = Request::input();
        $v = new Validator($input);
        $v->required('academic_year_id', 'name', 'start_date', 'end_date')
          ->integer('academic_year_id', 1);
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['academic_year_id'] = (int) $data['academic_year_id'];
        $id = $this->model->create($data);
        $item = $this->model->findById($id);
        Response::success('Term created.', ['term' => $item], 201);
    }

    public function update(string $id): void
    {
        $id = (int) $id;
        $item = $this->model->findById($id);
        if (!$item) {
            Response::notFound('Term not found.');
        }
        $input = Request::input();
        $v = new Validator($input);
        $v->required('academic_year_id', 'name', 'start_date', 'end_date');
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['academic_year_id'] = (int) $data['academic_year_id'];
        $this->model->update($id, $data);
        $item = $this->model->findById($id);
        Response::success('Term updated.', ['term' => $item]);
    }

    public function destroy(string $id): void
    {
        $id = (int) $id;
        if (!$this->model->findById($id)) {
            Response::notFound('Term not found.');
        }
        $this->model->delete($id);
        Response::success('Term deleted.');
    }
}
