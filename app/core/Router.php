<?php

class Router
{
    private array $routes = [];

    public function get(
        string $path,
        array $handler,
        array $middlewares = []
    ): void {
        $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(
        string $path,
        array $handler,
        array $middlewares = []
    ): void {
        $this->add('POST', $path, $handler, $middlewares);
    }

    public function put(
        string $path,
        array $handler,
        array $middlewares = []
    ): void {
        $this->add('PUT', $path, $handler, $middlewares);
    }

    public function patch(
        string $path,
        array $handler,
        array $middlewares = []
    ): void {
        $this->add('PATCH', $path, $handler, $middlewares);
    }

    public function delete(
        string $path,
        array $handler,
        array $middlewares = []
    ): void {
        $this->add('DELETE', $path, $handler, $middlewares);
    }

    private function add(
        string $method,
        string $path,
        array $handler,
        array $middlewares = []
    ): void {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    public function dispatch(mysqli $db): void
    {
        $method = $_SERVER['REQUEST_METHOD'];

        $uri = parse_url(
            $_SERVER['REQUEST_URI'],
            PHP_URL_PATH
        );

        $uri = rtrim($uri, '/');

        if ($uri === '') {
            $uri = '/';
        }

        foreach ($this->routes as $route) {

            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->convertToRegex(
                $route['path']
            );

            if (!preg_match(
                $pattern,
                $uri,
                $matches
            )) {
                continue;
            }

            array_shift($matches);

            $this->runMiddlewares(
                $route['middlewares'],
                $db
            );

            $controllerClass =
                $route['handler'][0];

            $action =
                $route['handler'][1];

            $classBasename =
                basename(
                    str_replace(
                        '\\',
                        '/',
                        $controllerClass
                    )
                );

            $controllerFile =
                __DIR__
                . '/../controllers/'
                . $classBasename
                . '.php';

            if (file_exists($controllerFile)) {
                require_once $controllerFile;
            }

            if (!class_exists($controllerClass)) {
                $this->serverError(
                    "Class {$controllerClass} not found"
                );
                return;
            }

            $controller =
                new $controllerClass($db);

            if (!method_exists(
                $controller,
                $action
            )) {
                $this->serverError(
                    "Method {$action} not found in {$controllerClass}"
                );
                return;
            }

            $matches = array_map(
                function ($value) {
                    return is_numeric($value)
                        ? (int) $value
                        : $value;
                },
                $matches
            );

            call_user_func_array(
                [$controller, $action],
                $matches
            );

            return;
        }

        $this->notFound();
    }

    private function runMiddlewares(
        array $middlewares,
        mysqli $db
    ): void {
        $userId = null;

        foreach ($middlewares as $middleware) {

            if ($middleware === 'auth') {

                $authFile =
                    __DIR__
                    . '/../middleware/AuthMiddleware.php';

                if (file_exists($authFile)) {
                    require_once $authFile;
                }

                $authClass =
                    class_exists(
                        'App\\Middleware\\AuthMiddleware'
                    )
                        ? 'App\\Middleware\\AuthMiddleware'
                        : 'AuthMiddleware';

                if (!class_exists($authClass)) {
                    $this->serverError(
                        'AuthMiddleware class not found'
                    );
                    return;
                }

                $auth =
                    new $authClass();

                if (!method_exists(
                    $auth,
                    'handle'
                )) {
                    $this->serverError(
                        'AuthMiddleware::handle() not found'
                    );
                    return;
                }

                $user = $auth->handle();

                if (!is_object($user)) {

                    http_response_code(401);

                    echo json_encode([
                        'success' => false,
                        'message' => 'Unauthenticated',
                        'data' => null
                    ]);

                    exit;
                }

                $userId = isset($user->sub)
                    ? (int) $user->sub
                    : null;

                if (!$userId) {

                    http_response_code(401);

                    echo json_encode([
                        'success' => false,
                        'message' => 'User ID not found in token',
                        'data' => null
                    ]);

                    exit;
                }

                $_REQUEST['user_id'] = $userId;
                $_REQUEST['auth_user'] = $user;
            }

            if (
                str_starts_with(
                    $middleware,
                    'permission:'
                )
            ) {

                $permission = substr(
                    $middleware,
                    strlen('permission:')
                );

                if (!$userId) {

                    http_response_code(401);

                    echo json_encode([
                        'success' => false,
                        'message' => 'Unauthenticated',
                        'data' => null
                    ]);

                    exit;
                }

                $middlewareFile =
                    __DIR__
                    . '/../middleware/PermissionMiddleware.php';

                if (file_exists($middlewareFile)) {
                    require_once $middlewareFile;
                }

                $permClass =
                    class_exists(
                        'App\\Middleware\\PermissionMiddleware'
                    )
                        ? 'App\\Middleware\\PermissionMiddleware'
                        : 'PermissionMiddleware';

                if (!class_exists($permClass)) {
                    $this->serverError(
                        'PermissionMiddleware class not found'
                    );
                    return;
                }

                $permissionMiddleware =
                    new $permClass($db);

                if (!method_exists(
                    $permissionMiddleware,
                    'handle'
                )) {
                    $this->serverError(
                        'PermissionMiddleware::handle() not found'
                    );
                    return;
                }

                $permissionMiddleware->handle(
                    $userId,
                    $permission
                );
            }
        }
    }

    private function convertToRegex(
        string $path
    ): string {
        $pattern = preg_replace(
            '#\{[^}]+\}#',
            '([^/]+)',
            $path
        );

        return '#^' . $pattern . '/?$#';
    }

    private function notFound(): void
    {
        http_response_code(404);

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        echo json_encode([
            'success' => false,
            'message' => 'Route not found',
            'data' => null
        ]);
    }

    private function serverError(
        string $message
    ): void {
        http_response_code(500);

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        echo json_encode([
            'success' => false,
            'message' => $message,
            'data' => null
        ]);
    }
}