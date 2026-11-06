<?php
declare(strict_types=1);

/**
 * Site runtime autoloader. The runtime is self-contained: it is copied into every
 * generated website (as core/) and has no dependency on the builder.
 */
spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'SiteRuntime\\', 12) !== 0) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, 12)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
