<?php

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

class SchoolDay
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, name, day_order, created_at FROM school_days ORDER BY day_order'
        );
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, day_order, created_at FROM school_days WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO school_days (name, day_order) VALUES (?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['day_order'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE school_days SET name = ?, day_order = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $data['day_order'],
            $id,
        ]);
        return $stmt->rowCount() > 0;
    }
}
