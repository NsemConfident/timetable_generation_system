<?php

declare(strict_types=1);

namespace Controllers;

use Models\SchoolDay;
use Utils\Response;
use Utils\Validator;

class SchoolDayController
{
    private SchoolDay $model;

    public function __construct()
    {
        $this->model = new SchoolDay();
    }

    public function index(): void
    {
        $list = $this->model->all();
        Response::success('Request successful.', ['school_days' => $list]);
    }

    public function store(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $v = new Validator($input);
        $v->required('name', 'day_order')->integer('day_order', 0);
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['day_order'] = (int) $data['day_order'];
        $id = $this->model->create($data);
        $item = $this->model->findById($id);
        Response::success('School day created.', ['school_day' => $item], 201);
    }

    public function update(string $id): void
    {
        $id = (int) $id;
        $item = $this->model->findById($id);
        if (!$item) {
            Response::notFound('School day not found.');
        }
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $v = new Validator($input);
        $v->required('name', 'day_order')->integer('day_order', 0);
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['day_order'] = (int) $data['day_order'];
        $this->model->update($id, $data);
        $item = $this->model->findById($id);
        Response::success('School day updated.', ['school_day' => $item]);
    }
}
