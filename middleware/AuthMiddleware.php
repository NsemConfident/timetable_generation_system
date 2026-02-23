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
        $token = $this->extractToken();
        if ($token === '') {
            Response::unauthorized('Missing or invalid authorization token.');
        }

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

    /**
     * Extract Bearer token from Authorization header or X-Auth-Token (fallback when server strips Authorization).
     */
    private function extractToken(): string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? $_SERVER['HTTP_X_AUTH_TOKEN']
            ?? '';
        $header = trim((string) $header);
        if ($header !== '' && preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
            return trim($m[1]);
        }
        if ($header !== '' && !preg_match('/^Bearer\s+/i', $header)) {
            return $header;
        }
        return '';
    }
}
