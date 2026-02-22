<?php

declare(strict_types=1);

namespace Services;

use Models\User;
use Utils\Response;
use Utils\Validator;

class AuthService
{
    public function register(array $input): array
    {
        $v = new Validator($input);
        $v->required('email', 'password', 'name')
          ->email('email')
          ->minLength('password', 6)
          ->maxLength('email', 255)
          ->maxLength('name', 255)
          ->in('role', ['admin', 'head_teacher', 'teacher']);
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();

        $user = new User();
        $existing = $user->findByEmail($data['email']);
        if ($existing) {
            Response::validationError('Email already registered.', ['email' => ['Email already in use.']]);
        }

        $user->create([
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'name' => $data['name'],
            'role' => $data['role'] ?? 'teacher',
        ]);
        $token = $user->createToken(86400 * 7); // 7 days

        return [
            'user' => $user->toArray(),
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 86400 * 7,
        ];
    }

    public function login(array $input): array
    {
        $v = new Validator($input);
        $v->required('email', 'password')->email('email');
        if ($v->fails()) {
            Response::validationError('Validation failed.', $v->getErrors());
        }
        $data = $v->getData();

        $user = new User();
        $user = $user->findByEmail($data['email']);
        if (!$user || !password_verify($data['password'], $this->getPasswordHash($data['email']))) {
            Response::error('Invalid email or password.', [], 401);
        }
        $token = $user->createToken(86400 * 7);

        return [
            'user' => $user->toArray(),
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 86400 * 7,
        ];
    }

    public function logout(): void
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $m)) {
            $user = \Middleware\RequestContext::getUser();
            if ($user) {
                $user->revokeToken(trim($m[1]));
            }
        }
    }

    public function me(): array
    {
        $user = \Middleware\RequestContext::getUser();
        if (!$user) {
            Response::unauthorized();
        }
        return ['user' => $user->toArray()];
    }

    private function getPasswordHash(string $email): string
    {
        $pdo = \Config\Database::getInstance()->getConnection();
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ? $row['password_hash'] : '';
    }
}
