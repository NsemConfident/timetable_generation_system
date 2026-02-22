<?php

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

class TimeSlot
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, name, start_time, end_time, slot_order, created_at FROM time_slots ORDER BY slot_order'
        );
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, start_time, end_time, slot_order, created_at FROM time_slots WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO time_slots (name, start_time, end_time, slot_order) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['start_time'],
            $data['end_time'],
            $data['slot_order'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE time_slots SET name = ?, start_time = ?, end_time = ?, slot_order = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $data['start_time'],
            $data['end_time'],
            $data['slot_order'],
            $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM time_slots WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
