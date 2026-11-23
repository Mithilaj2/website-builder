<?php
// Local testing only: serves an exported/installed website with PHP's built-in server, applying the same
// protections as its .htaccess (Apache/LiteSpeed is what clients use).
//   php -S 127.0.0.1:8084 -t /path/to/website tools/site-builtin-router.php
$root = rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/');
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (str_contains($path, '..') || preg_match('#^/(config|core|vendor|storage|database)(/|$)|^/theme/(layouts|partials|sections)/|^/theme/[^/]+\.(json|php)$|\.(sql|log|lock|md|ini|env|bak)$#i', $path)) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}
if (str_starts_with($path, '/uploads/') && preg_match('/\.(php\d?|phtml|phar)$/i', $path)) {
    http_response_code(403);
    return true;
}
if ($path === '/install.php') {
    if (!is_file("$root/install.php")) {
        http_response_code(404);
        echo 'Not found';
        return true;
    }
    $_SERVER['SCRIPT_NAME'] = '/install.php';
    require "$root/install.php";
    return true;
}
if ($path !== '/' && is_file($root . $path) && !preg_match('/\.php\d?$/i', $path)) {
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require "$root/index.php";
return true;
