<?php

declare(strict_types=1);

namespace Controllers;

use Models\TimeSlot;
use Utils\Request;
use Utils\Response;
use Utils\Validator;

class TimeSlotController
{
    private TimeSlot $model;

    public function __construct()
    {
        $this->model = new TimeSlot();
    }

    public function index(): void
    {
        $list = $this->model->all();
        Response::success('Request successful.', ['time_slots' => $list]);
    }

    public function store(): void
    {
        $input = Request::input();
        $v = new Validator($input);
        $v->required('name', 'start_time', 'end_time', 'slot_order')->integer('slot_order', 0);
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['slot_order'] = (int) $data['slot_order'];
        $id = $this->model->create($data);
        $item = $this->model->findById($id);
        Response::success('Time slot created.', ['time_slot' => $item], 201);
    }

    public function update(string $id): void
    {
        $id = (int) $id;
        $item = $this->model->findById($id);
        if (!$item) {
            Response::notFound('Time slot not found.');
        }
        $input = Request::input();
        $v = new Validator($input);
        $v->required('name', 'start_time', 'end_time', 'slot_order');
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['slot_order'] = (int) $data['slot_order'];
        $this->model->update($id, $data);
        $item = $this->model->findById($id);
        Response::success('Time slot updated.', ['time_slot' => $item]);
    }

    public function destroy(string $id): void
    {
        $id = (int) $id;
        if (!$this->model->findById($id)) {
            Response::notFound('Time slot not found.');
        }
        $this->model->delete($id);
        Response::success('Time slot deleted.');
    }
}
