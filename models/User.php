<?php

declare(strict_types=1);

namespace Models;

use Config\Database;
use PDO;

class User
{
    public ?int $id = null;
    public ?string $email = null;
    public ?string $name = null;
    public ?string $role = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findById(int $id): ?self
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, email, name, role, created_at, updated_at FROM users WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function findByEmail(string $email): ?self
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, email, name, role, created_at, updated_at FROM users WHERE email = ?'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function create(array $data): self
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['email'],
            $data['password_hash'],
            $data['name'],
            $data['role'] ?? 'teacher',
        ]);
        $this->id = (int) $this->pdo->lastInsertId();
        $this->email = $data['email'];
        $this->name = $data['name'];
        $this->role = $data['role'] ?? 'teacher';
        return $this;
    }

    public function createToken(int $ttlSeconds = 86400): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + $ttlSeconds);
        $stmt = $this->pdo->prepare(
            'INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)'
        );
        $stmt->execute([$this->id, $token, $expiresAt]);
        return $token;
    }

    public function revokeToken(string $token): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM user_tokens WHERE token = ?');
        $stmt->execute([$token]);
        return $stmt->rowCount() > 0;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'role' => $this->role,
            'created_at' => $this->created_at,
        ];
    }

    private function hydrate(array $row): self
    {
        $this->id = (int) $row['id'];
        $this->email = $row['email'];
        $this->name = $row['name'];
        $this->role = $row['role'];
        $this->created_at = $row['created_at'] ?? null;
        $this->updated_at = $row['updated_at'] ?? null;
        return $this;
    }
}
