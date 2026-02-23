<?php

declare(strict_types=1);

namespace Controllers;

use Models\BreakPeriod;
use Models\SchoolDay;
use Models\TimeSlot;
use Utils\Request;
use Utils\Response;
use Utils\Validator;

class BreakPeriodController
{
    private BreakPeriod $model;

    public function __construct()
    {
        $this->model = new BreakPeriod();
    }

    public function index(): void
    {
        $list = $this->model->all();
        Response::success('Request successful.', ['break_periods' => $list]);
    }

    public function store(): void
    {
        $input = Request::input();
        $v = new Validator($input);
        $v->required('time_slot_id')->integer('time_slot_id', 1);
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['time_slot_id'] = (int) $data['time_slot_id'];
        $data['school_day_id'] = isset($data['school_day_id']) && $data['school_day_id'] !== '' && $data['school_day_id'] !== null
            ? (int) $data['school_day_id']
            : null;
        $data['name'] = isset($data['name']) ? trim((string) $data['name']) : null;

        if (!(new TimeSlot())->findById($data['time_slot_id'])) {
            Response::validationError('Time slot does not exist.', ['time_slot_id' => ['This time slot does not exist.']]);
        }
        if ($data['school_day_id'] !== null && !(new SchoolDay())->findById($data['school_day_id'])) {
            Response::validationError('School day does not exist.', ['school_day_id' => ['This school day does not exist.']]);
        }

        try {
            $id = $this->model->create($data);
        } catch (\PDOException $e) {
            Response::error('Unable to create break period. Please check that time_slot_id and school_day_id (if set) exist.', [], 422);
        }
        $list = $this->model->all();
        $item = array_values(array_filter($list, fn($b) => (int) $b['id'] === $id))[0] ?? null;
        Response::success('Break period created.', ['break_period' => $item], 201);
    }

    public function destroy(string $id): void
    {
        $id = (int) $id;
        $this->model->delete($id);
        Response::success('Break period deleted.');
    }
}
