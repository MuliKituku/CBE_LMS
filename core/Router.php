<?php

/**
 * Router – collision-safe URL dispatcher for CBE_LMS
 *
 * URL format: controller/method/param1/param2
 *
 * Reserved segments map to explicit controller/method pairs so that
 * no segment can accidentally be parsed as a different segment.
 *
 * All controller files live in BASE_PATH/app/controllers/
 * Controller class name = Ucfirst(segment) . "Controller"
 */
class Router {

    /** Whitelist of valid controller names to prevent traversal attacks */
    private array $allowedControllers = [
        'home', 'auth', 'admin', 'dashboard',
        'student', 'parent', 'teacher', 'pathway'
    ];

    public function dispatch(): void
    {
        $url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';

        // Strip query string from url if present (e.g. path?param=val)
        if (($pos = strpos($url, '?')) !== false) {
            $url = substr($url, 0, $pos);
        }

        // Default route
        if ($url === '') {
            $url = 'home/index';
        }

        $parts = explode('/', $url);

        $controllerSlug = strtolower($parts[0] ?? 'home');
        $method         = $parts[1] ?? 'index';
        $params         = array_slice($parts, 2);

        // Security: only allow controllers in the whitelist
        if (!in_array($controllerSlug, $this->allowedControllers, true)) {
            $this->error404("Controller '$controllerSlug' not allowed.");
            return;
        }

        // Sanitise method name – only letters, numbers and underscores
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $method)) {
            $this->error404("Invalid method name.");
            return;
        }

        $controllerName = ucfirst($controllerSlug) . 'Controller';
        $controllerFile = BASE_PATH . '/app/controllers/' . $controllerName . '.php';

        if (!file_exists($controllerFile)) {
            $this->error404("Controller file not found: $controllerName");
            return;
        }

        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            $this->error404("Controller class missing: $controllerName");
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $method)) {
            $this->error404("Method '$method' not found on $controllerName");
            return;
        }

        call_user_func_array([$controller, $method], $params);
    }

    private function error404(string $msg = 'Page not found'): void
    {
        http_response_code(404);
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="UTF-8"><title>404 – CBE LMS</title>
        <style>
            body{font-family:Inter,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f1f5f9;margin:0}
            .box{text-align:center;padding:60px 40px;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08)}
            h1{font-size:5rem;margin:0;color:#4f46e5}h2{color:#1e293b}p{color:#64748b}
            a{display:inline-block;margin-top:20px;padding:10px 24px;background:#4f46e5;color:#fff;border-radius:8px;text-decoration:none}
        </style></head>
        <body>
        <div class="box">
            <h1>404</h1>
            <h2>Page Not Found</h2>
            <p>$msg</p>
            <a href="/CBE_LMS/home">Go Home</a>
        </div>
        </body></html>
        HTML;
        exit;
    }
}
