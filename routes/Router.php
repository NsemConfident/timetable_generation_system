<?php

declare(strict_types=1);

namespace Routes;

use Utils\Response;

/**
 * Central request router - matches URL and method to controller actions.
 */
class Router
{
    private array $routes = [];
    private array $routeParams = [];

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function dispatch(string $url, string $method): void
    {
        $url = $this->normalizeUrl($url);
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            $pattern = $route['pattern'];
            $allowedMethods = $route['methods'] ?? ['GET'];
            $handler = $route['handler'];
            $middleware = $route['middleware'] ?? [];

            if (!in_array($method, $allowedMethods, true)) {
                continue;
            }

            $params = $this->match($pattern, $url);
            if ($params !== null) {
                $this->runMiddleware($middleware, $params);
                $this->runHandler($handler, $params);
                return;
            }
        }

        Response::notFound('Endpoint not found.');
    }

    private function normalizeUrl(string $url): string
    {
        $url = '/' . trim($url, '/');
        $pos = strpos($url, '?');
        if ($pos !== false) {
            $url = substr($url, 0, $pos);
        }
        return $url === '' ? '/' : $url;
    }

    private function match(string $pattern, string $url): ?array
    {
        $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '([^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $url, $matches)) {
            array_shift($matches);
            return array_merge($this->routeParams, $matches);
        }
        return null;
    }

    private function runMiddleware(array $middleware, array $params): void
    {
        foreach ($middleware as $m) {
            $class = $m['class'];
            $method = $m['method'] ?? 'handle';
            if (!class_exists($class)) {
                continue;
            }
            $instance = new $class();
            $instance->$method($params);
        }
    }

    private function runHandler($handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }
        if (is_array($handler)) {
            [$controllerClass, $method] = $handler;
            if (!class_exists($controllerClass)) {
                Response::serverError('Controller not found.');
            }
            $controller = new $controllerClass();
            if (!method_exists($controller, $method)) {
                Response::serverError('Action not found.');
            }
            call_user_func_array([$controller, $method], $params);
            return;
        }
        Response::serverError('Invalid route handler.');
    }
}
