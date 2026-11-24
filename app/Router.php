<?php

declare(strict_types=1);

namespace App;

final class Router
{
    /** @var list<array{method:string,regex:string,handler:callable|array,middleware:list<string>}> */
    private array $routes = [];

    public function get(string $pattern, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    public function add(string $method, string $pattern, callable|array $handler, array $middleware = []): void
    {
        [$regex, $params] = $this->compile($pattern);

        $this->routes[] = [
            'method' => strtoupper($method),
            'regex' => $regex,
            'params' => $params,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        $path = '/'.trim($path, '/');

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $requestMethod = strtoupper($method);
        $method = $requestMethod;

        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper((string) $_POST['_method']);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (! preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            if ($requestMethod === 'POST' && ! Csrf::verify()) {
                abort(419);
            }

            foreach ($route['middleware'] as $middleware) {
                match ($middleware) {
                    'auth' => Auth::requireLogin(),
                    'guest' => Auth::requireGuest(),
                    default => null,
                };
            }

            array_shift($matches);
            $params = [];

            foreach ($matches as $index => $value) {
                $name = $route['params'][$index] ?? '';
                $value = urldecode($value);
                $params[] = in_array($name, ['event', 'registration'], true) ? (int) $value : $value;
            }

            $handler = $route['handler'];

            if (is_array($handler)) {
                [$class, $action] = $handler;
                (new $class)->{$action}(...$params);
            } else {
                $handler(...$params);
            }

            return;
        }

        abort(404);
    }

    private function compile(string $pattern): array
    {
        $regex = '';
        $names = [];
        $offset = 0;

        if (preg_match_all('/\{(\w+)\}/', $pattern, $placeholders, PREG_OFFSET_CAPTURE)) {
            foreach ($placeholders[0] as $match) {
                [$placeholder, $position] = $match;
                $regex .= preg_quote(substr($pattern, $offset, $position - $offset), '#');
                $regex .= '([^/]+)';
                $names[] = trim($placeholder, '{}');
                $offset = $position + strlen($placeholder);
            }
        }

        $regex .= preg_quote(substr($pattern, $offset), '#');

        return ['#^'.$regex.'$#', $names];
    }
}
