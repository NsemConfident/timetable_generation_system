<?php

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

class Room
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, name, capacity, type, created_at FROM rooms ORDER BY name'
        );
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, capacity, type, created_at FROM rooms WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO rooms (name, capacity, type) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['capacity'] ?? 0,
            $data['type'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE rooms SET name = ?, capacity = ?, type = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $data['capacity'] ?? 0,
            $data['type'] ?? null,
            $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM rooms WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
