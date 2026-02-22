<?php

declare(strict_types=1);

namespace Controllers;

use Models\Subject;
use Utils\Response;
use Utils\Validator;

class SubjectController
{
    private Subject $model;

    public function __construct()
    {
        $this->model = new Subject();
    }

    public function index(): void
    {
        $list = $this->model->all();
        Response::success('Request successful.', ['subjects' => $list]);
    }

    public function show(string $id): void
    {
        $id = (int) $id;
        $item = $this->model->findById($id);
        if (!$item) {
            Response::notFound('Subject not found.');
        }
        Response::success('Request successful.', ['subject' => $item]);
    }

    public function store(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $v = new Validator($input);
        $v->required('name')->maxLength('name', 150)->maxLength('code', 20);
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $id = $this->model->create($data);
        $item = $this->model->findById($id);
        Response::success('Subject created.', ['subject' => $item], 201);
    }

    public function update(string $id): void
    {
        $id = (int) $id;
        $item = $this->model->findById($id);
        if (!$item) {
            Response::notFound('Subject not found.');
        }
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $v = new Validator($input);
        $v->required('name');
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['code'] = $data['code'] ?? $item['code'];
        $this->model->update($id, $data);
        $item = $this->model->findById($id);
        Response::success('Subject updated.', ['subject' => $item]);
    }

    public function destroy(string $id): void
    {
        $id = (int) $id;
        if (!$this->model->findById($id)) {
            Response::notFound('Subject not found.');
        }
        $this->model->delete($id);
        Response::success('Subject deleted.');
    }
}
