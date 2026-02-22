<?php

declare(strict_types=1);

namespace Middleware;

use Models\User;

/**
 * Holds current request user set by AuthMiddleware.
 */
final class RequestContext
{
    private static ?User $user = null;

    public static function setUser(User $user): void
    {
        self::$user = $user;
    }

    public static function getUser(): ?User
    {
        return self::$user;
    }

    public static function clear(): void
    {
        self::$user = null;
    }
}
