<?php

declare(strict_types=1);

namespace Middleware;

use Config\Database;
use Models\User;
use Utils\Response;

/**
 * Token-based authentication middleware.
 * Validates Bearer token and sets current user for the request.
 */
class AuthMiddleware
{
    public function handle(array $params): void
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if ($header === '' || !preg_match('/^Bearer\s+(.+)$/i', trim($header), $m)) {
            Response::unauthorized('Missing or invalid authorization token.');
        }
        $token = trim($m[1]);

        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(
            'SELECT user_id, expires_at FROM user_tokens WHERE token = ? AND expires_at > NOW()'
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        if (!$row) {
            Response::unauthorized('Invalid or expired token.');
        }

        $user = (new User())->findById((int) $row['user_id']);
        if (!$user) {
            Response::unauthorized('User not found.');
        }

        RequestContext::setUser($user);
    }
}
