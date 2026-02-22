<?php

declare(strict_types=1);

namespace Utils;

/**
 * Centralized JSON API response helper.
 */
final class Response
{
    public static function json(
        bool $success,
        string $message,
        array $data = [],
        int $statusCode = 200
    ): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(string $message = 'Request successful', array $data = [], int $statusCode = 200): void
    {
        self::json(true, $message, $data, $statusCode);
    }

    public static function error(string $message, array $data = [], int $statusCode = 400): void
    {
        self::json(false, $message, $data, $statusCode);
    }

    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::json(false, $message, [], 401);
    }

    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::json(false, $message, [], 403);
    }

    public static function notFound(string $message = 'Resource not found'): void
    {
        self::json(false, $message, [], 404);
    }

    public static function validationError(string $message, array $errors = []): void
    {
        self::json(false, $message, ['errors' => $errors], 422);
    }

    public static function serverError(string $message = 'Internal server error'): void
    {
        self::json(false, $message, [], 500);
    }
}
