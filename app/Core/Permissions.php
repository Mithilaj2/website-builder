<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Role-based access control.
 *  super_admin — everything, including users and platform settings
 *  admin       — everything except user management
 *  designer    — categories and templates; read-only elsewhere
 *  viewer      — read-only everywhere (except users and activity log)
 */
final class Permissions
{
    public const ROLES = [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'designer' => 'Designer',
        'viewer' => 'Viewer',
    ];

    private const MATRIX = [
        'super_admin' => ['*'],
        'admin' => [
            'dashboard.view',
            'categories.view', 'categories.manage',
            'templates.view', 'templates.manage',
            'clients.view', 'clients.manage',
            'websites.view', 'websites.manage',
            'activity.view',
        ],
        'designer' => [
            'dashboard.view',
            'categories.view', 'categories.manage',
            'templates.view', 'templates.manage',
            'clients.view', 'websites.view',
        ],
        'viewer' => [
            'dashboard.view', 'categories.view', 'templates.view', 'clients.view', 'websites.view',
        ],
    ];

    public static function allows(string $role, string $permission): bool
    {
        $perms = self::MATRIX[$role] ?? [];
        return in_array('*', $perms, true) || in_array($permission, $perms, true);
    }
}
