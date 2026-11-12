<?php
class Router {
    private array $routes = [];

    public function get(string $path, callable|array $handler): void {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void {
        $this->routes[] = compact('method', 'path', 'handler');
    }

    public function dispatch(): void {
        $method  = $_SERVER['REQUEST_METHOD'];
        $uri     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
        $base    = parse_url(APP_URL, PHP_URL_PATH) ?? '';
        $baseLen = strlen($base);
        $path    = '/' . ltrim(substr($uri, $baseLen), '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            $pattern = $this->toRegex($route['path']);
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                $this->call($route['handler'], $matches);
                return;
            }
        }

        http_response_code(404);
        include ROOT . '/app/views/errors/404.php';
        exit;
    }

    private function toRegex(string $path): string {
        $pattern = preg_replace('/\{(\w+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function call(callable|array $handler, array $params): void {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $instance = new $class();
            $instance->$method(...$params);
        } else {
            $handler(...$params);
        }
    }
}
