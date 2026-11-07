<?php
declare(strict_types=1);

use App\Core\Router;
use App\Modules\Activity\ActivityController;
use App\Modules\Auth\AuthController;
use App\Modules\Categories\CategoryController;
use App\Modules\Clients\ClientController;
use App\Modules\Dashboard\DashboardController;
use App\Modules\Generator\GeneratorController;
use App\Modules\Settings\SettingsController as PlatformSettingsController;
use App\Modules\Templates\TemplateController;
use App\Modules\Users\ProfileController;
use App\Modules\Users\UserController;
use App\Modules\Websites\AdminAccountController;
use App\Modules\Websites\MediaController;
use App\Modules\Websites\PageController;
use App\Modules\Websites\PreviewController;
use App\Modules\Websites\SectionController;
use App\Modules\Websites\SettingsController;
use App\Modules\Websites\WebsiteController;

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

    // Template live preview (demo content rendered by the site runtime)
    $r->get('templates/{id}/preview', [PreviewController::class, 'template'], 'templates.view');

    // Websites (workspace: pages, sections, media, theme, details, preview)
    $r->get('websites', [WebsiteController::class, 'index'], 'websites.view');
    $r->get('websites/list', [WebsiteController::class, 'list'], 'websites.view');
    $r->get('websites/templates', [WebsiteController::class, 'templatesFor'], 'websites.view');
    $r->post('websites', [WebsiteController::class, 'store'], 'websites.manage');
    $r->get('websites/{id}', [WebsiteController::class, 'show'], 'websites.view');
    $r->post('websites/{id}', [WebsiteController::class, 'update'], 'websites.manage');
    $r->post('websites/{id}/delete', [WebsiteController::class, 'destroy'], 'websites.manage');
    $r->post('websites/{id}/wizard-step', [WebsiteController::class, 'wizardStep'], 'websites.view');
    $r->post('websites/{id}/payments', [WebsiteController::class, 'payments'], 'websites.manage');
    $r->post('websites/{id}/status', [WebsiteController::class, 'status'], 'websites.manage');
    $r->post('websites/{id}/template', [WebsiteController::class, 'changeTemplate'], 'websites.manage');

    // Generation (staged; the browser drives one step per request)
    $r->get('websites/{id}/checklist', [GeneratorController::class, 'checklist'], 'websites.view');
    $r->get('websites/{id}/generations', [GeneratorController::class, 'history'], 'websites.view');
    $r->post('websites/{id}/generate', [GeneratorController::class, 'start'], 'websites.manage');
    $r->get('websites/{id}/generations/{run}', [GeneratorController::class, 'state'], 'websites.view');
    $r->post('websites/{id}/generations/{run}/step', [GeneratorController::class, 'step'], 'websites.manage');
    $r->post('websites/{id}/generations/{run}/retry', [GeneratorController::class, 'retry'], 'websites.manage');
    $r->get('websites/{id}/preview', [PreviewController::class, 'website'], 'websites.view');
    $r->get('websites/{id}/admin-account', [AdminAccountController::class, 'show'], 'websites.view');
    $r->post('websites/{id}/admin-account', [AdminAccountController::class, 'store'], 'websites.manage');
    $r->get('websites/{id}/media/{path*}', [MediaController::class, 'serve'], 'websites.view');

    $r->get('websites/{id}/pages', [PageController::class, 'list'], 'websites.view');
    $r->post('websites/{id}/pages', [PageController::class, 'store'], 'websites.manage');
    $r->post('websites/{id}/pages/reorder', [PageController::class, 'reorder'], 'websites.manage');
    $r->post('websites/{id}/pages/{pid}', [PageController::class, 'update'], 'websites.manage');
    $r->post('websites/{id}/pages/{pid}/home', [PageController::class, 'setHome'], 'websites.manage');
    $r->post('websites/{id}/pages/{pid}/toggle', [PageController::class, 'toggle'], 'websites.manage');
    $r->post('websites/{id}/pages/{pid}/delete', [PageController::class, 'destroy'], 'websites.manage');

    $r->get('websites/{id}/pages/{pid}/sections', [SectionController::class, 'list'], 'websites.view');
    $r->post('websites/{id}/pages/{pid}/sections', [SectionController::class, 'store'], 'websites.manage');
    $r->post('websites/{id}/pages/{pid}/sections/reorder', [SectionController::class, 'reorder'], 'websites.manage');
    $r->get('websites/{id}/sections/{sid}', [SectionController::class, 'show'], 'websites.view');
    $r->post('websites/{id}/sections/{sid}', [SectionController::class, 'update'], 'websites.manage');
    $r->post('websites/{id}/sections/{sid}/toggle', [SectionController::class, 'toggle'], 'websites.manage');
    $r->post('websites/{id}/sections/{sid}/duplicate', [SectionController::class, 'duplicate'], 'websites.manage');
    $r->post('websites/{id}/sections/{sid}/delete', [SectionController::class, 'destroy'], 'websites.manage');

    $r->get('websites/{id}/media', [MediaController::class, 'list'], 'websites.view');
    $r->post('websites/{id}/media', [MediaController::class, 'upload'], 'websites.manage');
    $r->post('websites/{id}/media/{mid}', [MediaController::class, 'update'], 'websites.manage');
    $r->post('websites/{id}/media/{mid}/replace', [MediaController::class, 'replace'], 'websites.manage');
    $r->post('websites/{id}/media/{mid}/delete', [MediaController::class, 'destroy'], 'websites.manage');

    $r->get('websites/{id}/settings', [SettingsController::class, 'show'], 'websites.view');
    $r->post('websites/{id}/settings/theme', [SettingsController::class, 'theme'], 'websites.manage');
    $r->post('websites/{id}/settings/details', [SettingsController::class, 'details'], 'websites.manage');

    // Preview assets (logged-in users only)
    $r->get('preview-assets/template/{id}/{path*}', [PreviewController::class, 'templateAsset']);
    $r->get('preview-assets/runtime/fonts/{path*}', [PreviewController::class, 'runtimeFont']);
    $r->get('preview-assets/runtime/lib/{path*}', [PreviewController::class, 'runtimeLib']);

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

    // Platform settings (Super Admin)
    $r->get('settings', [PlatformSettingsController::class, 'index'], 'settings.manage');
    $r->post('settings', [PlatformSettingsController::class, 'update'], 'settings.manage');
    $r->post('settings/qa-password', [PlatformSettingsController::class, 'regeneratePassword'], 'settings.manage');

    // Activity log
    $r->get('activity', [ActivityController::class, 'index'], 'activity.view');
    $r->get('activity/list', [ActivityController::class, 'list'], 'activity.view');
};
