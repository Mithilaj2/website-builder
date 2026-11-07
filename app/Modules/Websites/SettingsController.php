<?php
declare(strict_types=1);

namespace App\Modules\Websites;

use App\Core\Activity;
use App\Core\Request;
use App\Core\Response;

/** Theme customisation and site details (wizard step 6). Logic lives in SiteRuntime\SettingsService, shared with the website admin. */
final class SettingsController extends SiteController
{
    public function theme(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $this->call(fn () => $svc->settingsService()->saveTheme($_POST));
        $this->touch($svc);
        Activity::log('website.theme_saved', 'website', $svc->id());
        Response::ok('Theme saved.');
    }

    public function details(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $this->call(fn () => $svc->settingsService()->saveDetails($_POST));
        $this->touch($svc);
        Activity::log('website.details_saved', 'website', $svc->id());
        Response::ok('Site details saved.');
    }

    /** Current theme + site details for the forms. */
    public function show(Request $request): void
    {
        $svc = $this->site($request->params['id']);
        $ss = $svc->settingsService();
        $all = $ss->all();
        $theme = $ss->theme();
        $mediaUrl = fn ($p) => is_string($p) && str_starts_with($p, 'uploads/') ? url('websites/' . $svc->id() . '/media/' . substr($p, 8)) : null;
        Response::json(['ok' => true, 'theme' => $theme + ['logo_url' => $mediaUrl($theme['logo'] ?? ''), 'favicon_url' => $mediaUrl($theme['favicon'] ?? '')], 'site' => (array) ($all['site'] ?? []), 'seo' => (array) ($all['seo'] ?? [])]);
    }
}
