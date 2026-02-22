<?php

declare(strict_types=1);

namespace Controllers;

use Models\Teacher;
use Utils\Request;
use Utils\Response;
use Utils\Validator;

class TeacherController
{
    private Teacher $model;

    public function __construct()
    {
        $this->model = new Teacher();
    }

    public function index(): void
    {
        $list = $this->model->all();
        Response::success('Request successful.', ['teachers' => $list]);
    }

    public function show(string $id): void
    {
        $id = (int) $id;
        $item = $this->model->findById($id);
        if (!$item) {
            Response::notFound('Teacher not found.');
        }
        Response::success('Request successful.', ['teacher' => $item]);
    }

    public function store(): void
    {
        $input = Request::input();
        $v = new Validator($input);
        $v->required('name')->maxLength('name', 255);
        if (isset($input['email'])) {
            $v->email('email');
        }
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['user_id'] = $data['user_id'] ?? null;
        $id = $this->model->create($data);
        $item = $this->model->findById($id);
        Response::success('Teacher created.', ['teacher' => $item], 201);
    }

    public function update(string $id): void
    {
        $id = (int) $id;
        $item = $this->model->findById($id);
        if (!$item) {
            Response::notFound('Teacher not found.');
        }
        $input = Request::input();
        $v = new Validator($input);
        if (isset($input['name'])) {
            $v->maxLength('name', 255);
        }
        if (isset($input['email'])) {
            $v->email('email');
        }
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = array_intersect_key($input, array_flip(['user_id', 'name', 'email']));
        $this->model->update($id, $data);
        $item = $this->model->findById($id);
        Response::success('Teacher updated.', ['teacher' => $item]);
    }

    public function destroy(string $id): void
    {
        $id = (int) $id;
        if (!$this->model->findById($id)) {
            Response::notFound('Teacher not found.');
        }
        $this->model->delete($id);
        Response::success('Teacher deleted.');
    }

    public function subjects(string $id): void
    {
        $id = (int) $id;
        if (!$this->model->findById($id)) {
            Response::notFound('Teacher not found.');
        }
        $list = $this->model->getSubjects($id);
        Response::success('Request successful.', ['subjects' => $list]);
    }

    public function assignSubjects(string $id): void
    {
        $id = (int) $id;
        if (!$this->model->findById($id)) {
            Response::notFound('Teacher not found.');
        }
        $input = Request::inputWithArrays(['subject_ids']);
        $v = new Validator($input);
        $v->required('subject_ids');
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $subjectIds = is_array($input['subject_ids']) ? array_map('intval', $input['subject_ids']) : [];
        $this->model->setSubjects($id, $subjectIds);
        $list = $this->model->getSubjects($id);
        Response::success('Subjects assigned.', ['subjects' => $list]);
    }

    public function availability(string $id): void
    {
        $id = (int) $id;
        if (!$this->model->findById($id)) {
            Response::notFound('Teacher not found.');
        }
        $list = $this->model->getAvailability($id);
        Response::success('Request successful.', ['availability' => $list]);
    }

    public function updateAvailability(string $id): void
    {
        $id = (int) $id;
        if (!$this->model->findById($id)) {
            Response::notFound('Teacher not found.');
        }
        $input = Request::input();
        if (isset($input['slots']) && is_string($input['slots'])) {
            $input['slots'] = json_decode($input['slots'], true) ?: [];
        }
        $v = new Validator($input);
        $v->required('slots');
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $slots = $input['slots'];
        $this->model->setAvailability($id, $slots);
        $list = $this->model->getAvailability($id);
        Response::success('Availability updated.', ['availability' => $list]);
    }
}
