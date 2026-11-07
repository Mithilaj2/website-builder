<?php
declare(strict_types=1);

// Global helpers for the website admin views (and the shared editor partials, which the builder also uses
// with its own functions of the same name). Only loaded inside a generated website.

if (!function_exists('e')) {
    function e(mixed $v): string
    {
        return htmlspecialchars(is_scalar($v) ? (string) $v : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e($GLOBALS['__wb_admin_csrf'] ?? '') . '">';
    }
}
