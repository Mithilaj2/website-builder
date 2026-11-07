<?php
declare(strict_types=1);

/**
 * Builder bootstrap: constants, autoloader, config, error handling, session.
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('TEMPLATES_PATH', BASE_PATH . '/templates');
define('CONFIG_FILE', BASE_PATH . '/config/config.php');
define('RUNTIME_PATH', BASE_PATH . '/runtime');
define('SITES_PATH', BASE_PATH . '/generated_websites');

spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
}

require BASE_PATH . '/runtime/core/autoload.php';
require APP_PATH . '/helpers.php';

use App\Core\Config;
use App\Core\Logger;

if (!is_file(CONFIG_FILE)) {
    // Not installed yet: send the operator to the setup script.
    if (PHP_SAPI !== 'cli') {
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
        $requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        // Reached through the root .htaccess rewrite: keep /public out of the visible URL.
        if (str_ends_with($dir, '/public') && !str_contains($requestPath, '/public/')) {
            $dir = substr($dir, 0, -7);
        }
        header('Location: ' . $dir . '/setup.php');
    }
    exit;
}

Config::load(require CONFIG_FILE);
date_default_timezone_set((string) Config::get('app.timezone', 'Asia/Kolkata'));

$debug = (bool) Config::get('app.debug', false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

set_error_handler(static function (int $no, string $str, string $file, int $line): bool {
    if (!(error_reporting() & $no)) {
        return false;
    }
    throw new ErrorException($str, 0, $no, $file, $line);
});

set_exception_handler(static function (Throwable $e) use ($debug): void {
    Logger::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
    if (!headers_sent()) {
        http_response_code(500);
    }
    $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    if ($isAjax) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['ok' => false, 'message' => $debug ? $e->getMessage() : 'An unexpected error occurred. It has been logged.']);
        return;
    }
    echo '<!doctype html><meta charset="utf-8"><title>Error</title><div style="font-family:system-ui;max-width:640px;margin:10vh auto">';
    echo '<h1>Something went wrong</h1><p>The error has been logged. Please try again or contact the administrator.</p>';
    if ($debug) {
        echo '<pre style="white-space:pre-wrap;background:#f5f5f5;padding:1rem">' . e($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>';
    }
    echo '</div>';
});
