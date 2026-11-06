<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Permissions;

/** HTML-escape a value for output. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

/** Absolute URL for a builder route, e.g. url('categories'). */
function url(string $path = ''): string
{
    return rtrim((string) Config::get('app.base_url', ''), '/') . '/' . ltrim($path, '/');
}

/** URL for a file under public/ (assets, uploads), with cache-busting for local files. */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = PUBLIC_PATH . '/' . $path;
    $v = is_file($file) ? '?v=' . filemtime($file) : '';
    return url($path) . $v;
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function auth_user(): ?array
{
    return Auth::user();
}

function can(string $permission): bool
{
    $user = Auth::user();
    return $user !== null && Permissions::allows($user['role'], $permission);
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function format_date(?string $value, string $format = 'd M Y'): string
{
    if (!$value) {
        return '—';
    }
    $ts = strtotime($value);
    return $ts ? date($format, $ts) : '—';
}

function slugify(string $text, string $sep = '-'): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', $sep, $text) ?? '';
    return trim($text, $sep);
}

function role_label(string $role): string
{
    return Permissions::ROLES[$role] ?? ucfirst($role);
}

/** Bootstrap badge class for status values used across modules. */
function status_badge(string $status): string
{
    $map = [
        'active' => 'success', 'inactive' => 'secondary',
        'draft' => 'secondary', 'config_pending' => 'warning', 'generating' => 'info',
        'generated' => 'primary', 'testing' => 'info', 'ready' => 'primary',
        'deployed' => 'success', 'archived' => 'dark', 'failed' => 'danger',
    ];
    $cls = $map[$status] ?? 'secondary';
    $label = ucwords(str_replace('_', ' ', $status));
    return '<span class="badge text-bg-' . $cls . '">' . e($label) . '</span>';
}
