<?php
declare(strict_types=1);

namespace App\Modules\Settings;

use App\Core\Activity;
use App\Core\Request;
use App\Core\Response;
use App\Core\Settings;
use App\Core\View;
use App\Modules\Generator\QaAccess;

/** Platform settings (Super Admin): QA access to generated websites. */
final class SettingsController
{
    public function index(Request $request): void
    {
        View::render('settings/index', [
            'title' => 'Settings',
            'active' => 'settings',
            'qaUrl' => (string) Settings::get('qa_sites_url', ''),
            'qaDefault' => rtrim(url('generated_websites'), '/'),
            'qaUser' => QaAccess::USER,
            'qaPassword' => QaAccess::password(),
            'scripts' => [],
        ]);
    }

    public function update(Request $request): void
    {
        $url = rtrim($request->str('qa_sites_url'), '/');
        if ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#', $url))) {
            Response::fail('Enter a full URL such as https://qa.builder.example.com', ['qa_sites_url' => 'Enter a full http(s) URL, or leave empty for the default.']);
        }
        Settings::set('qa_sites_url', $url ?: null);
        QaAccess::writeFiles();
        Activity::log('settings.updated', null, null, 'QA sites URL: ' . ($url ?: 'default'));
        Response::ok('Settings saved.');
    }

    public function regeneratePassword(Request $request): void
    {
        QaAccess::regenerate();
        Activity::log('settings.qa_password_regenerated');
        Response::ok('A new QA password was generated.', ['redirect' => url('settings')]);
    }
}
