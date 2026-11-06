<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    /** Render a view inside a layout. $view is relative to app/Views, without .php. */
    public static function render(string $view, array $data = [], ?string $layout = 'main'): void
    {
        $content = self::capture($view, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        echo self::capture('layouts/' . $layout, $data + ['content' => $content]);
    }

    public static function capture(string $view, array $data = []): string
    {
        if (!preg_match('#^[a-z0-9_/\-]+$#', $view)) {
            throw new RuntimeException('Invalid view name.');
        }
        $file = APP_PATH . '/Views/' . $view . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View not found: $view");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
