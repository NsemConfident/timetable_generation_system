<?php

declare(strict_types=1);

namespace Controllers;

use Services\AuthService;
use Utils\Request;
use Utils\Response;

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function register(): void
    {
        $input = Request::input();
        $result = $this->authService->register($input);
        Response::success('Registration successful.', $result, 201);
    }

    public function login(): void
    {
        $input = Request::input();
        $result = $this->authService->login($input);
        Response::success('Login successful.', $result);
    }

    public function logout(): void
    {
        $this->authService->logout();
        Response::success('Logged out successfully.');
    }

    public function me(): void
    {
        $result = $this->authService->me();
        Response::success('Request successful.', $result);
    }
}
