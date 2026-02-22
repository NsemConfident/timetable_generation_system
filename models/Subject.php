<?php

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

class Subject
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, name, code, created_at FROM subjects ORDER BY name'
        );
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, code, created_at FROM subjects WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO subjects (name, code) VALUES (?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['code'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE subjects SET name = ?, code = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $data['code'] ?? null,
            $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM subjects WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
