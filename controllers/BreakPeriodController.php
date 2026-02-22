<?php

declare(strict_types=1);

namespace Controllers;

use Models\BreakPeriod;
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
        $data['school_day_id'] = isset($data['school_day_id']) ? (int) $data['school_day_id'] : null;
        $id = $this->model->create($data);
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
