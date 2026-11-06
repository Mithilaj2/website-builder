<?php
declare(strict_types=1);

use App\Core\Router;
use App\Modules\Activity\ActivityController;
use App\Modules\Auth\AuthController;
use App\Modules\Categories\CategoryController;
use App\Modules\Clients\ClientController;
use App\Modules\Dashboard\DashboardController;
use App\Modules\Templates\TemplateController;
use App\Modules\Users\ProfileController;
use App\Modules\Users\UserController;

return static function (Router $r): void {
    // Authentication (public)
    $r->get('login', [AuthController::class, 'showLogin'], null, true);
    $r->post('login', [AuthController::class, 'login'], null, true);
    $r->post('logout', [AuthController::class, 'logout']);

    // Dashboard
    $r->get('', [DashboardController::class, 'index'], 'dashboard.view');
    $r->get('dashboard/recent', [DashboardController::class, 'recent'], 'dashboard.view');

    // Categories
    $r->get('categories', [CategoryController::class, 'index'], 'categories.view');
    $r->get('categories/list', [CategoryController::class, 'list'], 'categories.view');
    $r->get('categories/{id}', [CategoryController::class, 'show'], 'categories.view');
    $r->post('categories', [CategoryController::class, 'store'], 'categories.manage');
    $r->post('categories/{id}', [CategoryController::class, 'update'], 'categories.manage');
    $r->post('categories/{id}/status', [CategoryController::class, 'toggleStatus'], 'categories.manage');
    $r->post('categories/{id}/delete', [CategoryController::class, 'destroy'], 'categories.manage');
    $r->post('categories/reorder', [CategoryController::class, 'reorder'], 'categories.manage');

    // Templates
    $r->get('templates', [TemplateController::class, 'index'], 'templates.view');
    $r->get('templates/list', [TemplateController::class, 'list'], 'templates.view');
    $r->get('templates/scan', [TemplateController::class, 'scan'], 'templates.manage');
    $r->post('templates/register', [TemplateController::class, 'register'], 'templates.manage');
    $r->post('templates/upload', [TemplateController::class, 'upload'], 'templates.manage');
    $r->get('templates/{id}', [TemplateController::class, 'show'], 'templates.view');
    $r->get('templates/{id}/json', [TemplateController::class, 'json'], 'templates.view');
    $r->post('templates/{id}', [TemplateController::class, 'update'], 'templates.manage');
    $r->post('templates/{id}/status', [TemplateController::class, 'toggleStatus'], 'templates.manage');
    $r->post('templates/{id}/validate', [TemplateController::class, 'revalidate'], 'templates.manage');
    $r->post('templates/{id}/duplicate', [TemplateController::class, 'duplicate'], 'templates.manage');
    $r->post('templates/{id}/new-version', [TemplateController::class, 'uploadVersion'], 'templates.manage');
    $r->post('templates/{id}/versions/{vid}/restore', [TemplateController::class, 'restoreVersion'], 'templates.manage');
    $r->get('templates/{id}/versions/{vid}/download', [TemplateController::class, 'downloadVersion'], 'templates.manage');
    $r->post('templates/{id}/screens', [TemplateController::class, 'addScreens'], 'templates.manage');
    $r->post('templates/{id}/screens/{sid}/delete', [TemplateController::class, 'deleteScreen'], 'templates.manage');
    $r->post('templates/{id}/delete', [TemplateController::class, 'destroy'], 'templates.manage');

    // Clients
    $r->get('clients', [ClientController::class, 'index'], 'clients.view');
    $r->get('clients/list', [ClientController::class, 'list'], 'clients.view');
    $r->get('clients/{id}', [ClientController::class, 'show'], 'clients.view');
    $r->get('clients/{id}/json', [ClientController::class, 'json'], 'clients.view');
    $r->post('clients', [ClientController::class, 'store'], 'clients.manage');
    $r->post('clients/{id}', [ClientController::class, 'update'], 'clients.manage');
    $r->post('clients/{id}/status', [ClientController::class, 'toggleStatus'], 'clients.manage');
    $r->post('clients/{id}/delete', [ClientController::class, 'destroy'], 'clients.manage');

    // Users (Super Admin only) and own profile (everyone)
    $r->get('users', [UserController::class, 'index'], 'users.manage');
    $r->get('users/list', [UserController::class, 'list'], 'users.manage');
    $r->post('users', [UserController::class, 'store'], 'users.manage');
    $r->post('users/{id}', [UserController::class, 'update'], 'users.manage');
    $r->post('users/{id}/delete', [UserController::class, 'destroy'], 'users.manage');
    $r->get('profile', [ProfileController::class, 'index']);
    $r->post('profile', [ProfileController::class, 'update']);
    $r->post('profile/password', [ProfileController::class, 'password']);

    // Activity log
    $r->get('activity', [ActivityController::class, 'index'], 'activity.view');
    $r->get('activity/list', [ActivityController::class, 'list'], 'activity.view');
};
