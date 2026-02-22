<?php

declare(strict_types=1);

namespace Controllers;

use Models\Room;
use Models\TimetableEntry;
use Utils\Request;
use Utils\Response;
use Utils\Validator;

class RoomController
{
    private Room $model;
    private TimetableEntry $timetableModel;

    public function __construct()
    {
        $this->model = new Room();
        $this->timetableModel = new TimetableEntry();
    }

    public function index(): void
    {
        $list = $this->model->all();
        Response::success('Request successful.', ['rooms' => $list]);
    }

    public function show(string $id): void
    {
        $id = (int) $id;
        $item = $this->model->findById($id);
        if (!$item) {
            Response::notFound('Room not found.');
        }
        Response::success('Request successful.', ['room' => $item]);
    }

    public function store(): void
    {
        $input = Request::input();
        $v = new Validator($input);
        $v->required('name')->integer('capacity', 0);
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['capacity'] = (int) ($data['capacity'] ?? 0);
        $id = $this->model->create($data);
        $item = $this->model->findById($id);
        Response::success('Room created.', ['room' => $item], 201);
    }

    public function update(string $id): void
    {
        $id = (int) $id;
        $item = $this->model->findById($id);
        if (!$item) {
            Response::notFound('Room not found.');
        }
        $input = Request::input();
        $v = new Validator($input);
        $v->required('name');
        if (isset($input['capacity'])) {
            $v->integer('capacity', 0);
        }
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();
        $data['capacity'] = array_key_exists('capacity', $input) ? (int) $input['capacity'] : (int) $item['capacity'];
        $data['type'] = $input['type'] ?? $item['type'];
        $this->model->update($id, $data);
        $item = $this->model->findById($id);
        Response::success('Room updated.', ['room' => $item]);
    }

    public function destroy(string $id): void
    {
        $id = (int) $id;
        if (!$this->model->findById($id)) {
            Response::notFound('Room not found.');
        }
        $this->model->delete($id);
        Response::success('Room deleted.');
    }

    public function availability(string $id): void
    {
        $id = (int) $id;
        $room = $this->model->findById($id);
        if (!$room) {
            Response::notFound('Room not found.');
        }
        $termId = isset($_GET['term_id']) ? (int) $_GET['term_id'] : null;
        if (!$termId) {
            Response::error('term_id is required.');
        }
        $entries = $this->timetableModel->all($termId);
        $roomSlots = array_filter($entries, fn($e) => isset($e['room_id']) && (int) $e['room_id'] === $id);
        Response::success('Request successful.', ['room' => $room, 'allocated_slots' => array_values($roomSlots)]);
    }
}
