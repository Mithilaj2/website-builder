<?php
// Local development only: serves generated websites like Apache would (with the same folder protections).
//   php -S 127.0.0.1:8082 -t generated_websites tools/dev-sites-router.php
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (!preg_match('#^/(site_[a-z0-9_-]+)(/.*)?$#', $path, $m) || !is_file(__DIR__ . '/../generated_websites/' . $m[1] . '/index.php')) {
    http_response_code(404);
    echo 'Not found';
    return true;
}
$rest = $m[2] ?? '/';
if (preg_match('#^/(config|core|storage|database)(/|$)|^/theme/(layouts|partials|sections)/|^/theme/[^/]+\.(json|php)$|\.\.|\.(sql|log|md|ini)$#i', $rest)) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}
$file = __DIR__ . '/../generated_websites/' . $m[1] . $rest;
if ($rest !== '/' && is_file($file) && !preg_match('/\.php\d?$/i', $file)) {
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/' . $m[1] . '/index.php';
require __DIR__ . '/../generated_websites/' . $m[1] . '/index.php';
return true;
