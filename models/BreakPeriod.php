<?php

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

class BreakPeriod
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT bp.id, bp.time_slot_id, bp.school_day_id, bp.name, bp.created_at,
                    ts.name AS slot_name, ts.start_time, ts.end_time,
                    sd.name AS day_name
             FROM break_periods bp
             LEFT JOIN time_slots ts ON ts.id = bp.time_slot_id
             LEFT JOIN school_days sd ON sd.id = bp.school_day_id
             ORDER BY bp.id'
        );
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO break_periods (time_slot_id, school_day_id, name) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $data['time_slot_id'],
            $data['school_day_id'] ?? null,
            $data['name'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM break_periods WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /** Get time_slot_ids that are breaks (optionally for a day) */
    public function getBreakSlotIds(?int $schoolDayId = null): array
    {
        $sql = 'SELECT DISTINCT time_slot_id FROM break_periods WHERE 1=1';
        $params = [];
        if ($schoolDayId !== null) {
            $sql .= ' AND (school_day_id IS NULL OR school_day_id = ?)';
            $params[] = $schoolDayId;
        }
        $stmt = $params ? $this->pdo->prepare($sql) : $this->pdo->query($sql);
        if ($params) {
            $stmt->execute($params);
        }
        return array_column($stmt->fetchAll(), 'time_slot_id');
    }
}
