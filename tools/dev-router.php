<?php
// Local development only: php -S 127.0.0.1:8080 -t public tools/dev-router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . '/../public' . $path;
if ($path !== '/' && is_file($file)) {
    if (str_ends_with($file, '.php')) {
        require $file;
        return true;
    }
    return false; // let the built-in server serve static files
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/../public/index.php';
