<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal router. Each route declares:
 *  - method + path pattern ({id} placeholders, digits only)
 *  - handler [ControllerClass, 'method']
 *  - permission (null = any logged-in user), or public => true for guest routes
 * Every POST is CSRF-checked before the handler runs.
 */
final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler, ?string $permission = null, bool $public = false): void
    {
        $this->add('GET', $path, $handler, $permission, $public);
    }

    public function post(string $path, array $handler, ?string $permission = null, bool $public = false): void
    {
        $this->add('POST', $path, $handler, $permission, $public);
    }

    private function add(string $method, string $path, array $handler, ?string $permission, bool $public): void
    {
        // {id} matches digits; {path*} matches the rest of the URL (used for asset paths).
        $pattern = preg_replace('#\{([a-z_]+)\*\}#', '(?P<$1>.+)', trim($path, '/'));
        $regex = '#^' . preg_replace('#\{([a-z_]+)\}#', '(?P<$1>\d+)', (string) $pattern) . '$#';
        $this->routes[] = compact('method', 'regex', 'handler', 'permission', 'public');
    }

    public function dispatch(Request $request): void
    {
        $path = $request->path();
        $method = $request->method();
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $m)) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $method) {
                continue;
            }

            if ($method === 'POST' && !Csrf::verify()) {
                // 403, not 419: Apache rewrites unknown status codes such as 419 into 500.
                Response::abort(403, 'Your session has expired. Please reload the page and try again.');
            }

            if (!$route['public']) {
                if (!Auth::check()) {
                    if ($request->isAjax()) {
                        Response::json(['ok' => false, 'message' => 'Please log in again.', 'redirect' => url('login')], 401);
                    }
                    Response::redirect('login');
                }
                if ($route['permission'] !== null && !can($route['permission'])) {
                    Response::abort(403, 'You do not have permission to do this.');
                }
            }

            $request->params = array_map(
                static fn ($v) => ctype_digit((string) $v) ? (int) $v : (string) $v,
                array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY)
            );
            [$class, $action] = $route['handler'];
            (new $class())->$action($request);
            return;
        }

        Response::abort($pathMatched ? 405 : 404, $pathMatched ? 'Method not allowed.' : 'The page you requested does not exist.');
    }
}
