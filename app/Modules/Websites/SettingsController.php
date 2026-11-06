<?php
declare(strict_types=1);

namespace App\Modules\Websites;

use App\Core\Activity;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use SiteRuntime\SectionTypes;
use SiteRuntime\Theme;

/** Website-level settings: theme customisation (Step 6) and site details used by headers/footers. */
final class SettingsController extends SiteController
{
    public function theme(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $manifest = $svc->manifest();
        $fonts = Theme::fonts(RUNTIME_PATH . '/fonts');
        $in = $request->all();
        $errors = [];
        foreach (['primary', 'secondary', 'bg', 'text'] as $c) {
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($in[$c] ?? ''))) {
                $errors[$c] = 'Choose a colour like #1A2B3C.';
            }
        }
        foreach (['heading_font', 'body_font'] as $f) {
            if (!isset($fonts[$in[$f] ?? ''])) {
                $errors[$f] = 'Choose one of the bundled fonts.';
            }
        }
        if (!isset(Theme::BUTTON_STYLES[$in['button_style'] ?? ''])) {
            $errors['button_style'] = 'Choose a button style.';
        }
        if (!isset(Theme::RADII[$in['radius'] ?? ''])) {
            $errors['radius'] = 'Choose a corner style.';
        }
        $headers = (array) ($manifest['theme']['header_styles'] ?? []);
        $footers = (array) ($manifest['theme']['footer_styles'] ?? []);
        if ($headers && !isset($headers[$in['header_style'] ?? ''])) {
            $errors['header_style'] = 'Choose a header style.';
        }
        if ($footers && !isset($footers[$in['footer_style'] ?? ''])) {
            $errors['footer_style'] = 'Choose a footer style.';
        }
        $imageOk = $svc->imageValidator();
        foreach (['logo', 'favicon'] as $img) {
            $v = trim((string) ($in[$img] ?? ''));
            if ($v !== '' && !$imageOk($v)) {
                $errors[$img] = 'Choose the image from the media library.';
            }
        }
        if ($errors) {
            Response::fail('Please correct the highlighted fields.', $errors);
        }
        $theme = [];
        foreach (['primary', 'secondary', 'bg', 'text', 'heading_font', 'body_font', 'button_style', 'radius', 'header_style', 'footer_style', 'logo', 'favicon'] as $k) {
            $theme[$k] = trim((string) ($in[$k] ?? ''));
        }
        $theme['primary'] = strtoupper($theme['primary']);
        $svc->saveSetting('theme', $theme);
        $this->touch($svc);
        Activity::log('website.theme_saved', 'website', $svc->id());
        Response::ok('Theme saved.');
    }

    public function details(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $in = $request->all();
        $v = Validator::make($in, [
            'name' => 'required|max:120', 'tagline' => 'max:150', 'email' => 'email|max:190', 'phone' => 'phone',
            'whatsapp' => 'phone', 'address' => 'max:400', 'hours' => 'max:120', 'footer_about' => 'max:400', 'footer_text' => 'max:200',
            'seo_description' => 'max:320', 'seo_keywords' => 'max:255',
        ], ['name' => 'Website name', 'seo_description' => 'Default meta description']);
        $errors = $v->errors();
        $social = [];
        foreach (['facebook', 'instagram', 'linkedin', 'twitter', 'youtube'] as $n) {
            $u = trim((string) ($in['social_' . $n] ?? ''));
            if ($u !== '') {
                preg_match('#^https://#i', $u) && filter_var($u, FILTER_VALIDATE_URL) ? $social[$n] = $u : $errors['social_' . $n] = 'Enter a full https:// address.';
            }
        }
        $btn = ['text' => mb_substr(trim(strip_tags((string) ($in['header_button_text'] ?? ''))), 0, 40), 'url' => trim((string) ($in['header_button_url'] ?? ''))];
        if ($btn['url'] !== '' && !SectionTypes::validUrl($btn['url'])) {
            $errors['header_button_url'] = 'Use page:slug, a full https:// address, #anchor, mailto: or tel:.';
        }
        if ($errors) {
            Response::fail('Please correct the highlighted fields.', $errors);
        }
        $current = (array) ($svc->settings()['site'] ?? []);
        $site = array_merge($current, [
            'name' => $request->str('name'), 'tagline' => $request->str('tagline'), 'email' => strtolower($request->str('email')),
            'phone' => $request->str('phone'), 'whatsapp' => $request->str('whatsapp'), 'address' => $request->str('address'),
            'hours' => $request->str('hours'), 'footer_about' => $request->str('footer_about'), 'footer_text' => $request->str('footer_text'),
            'social' => $social, 'header_button' => $btn,
        ]);
        if ($site['whatsapp'] !== '') {
            $site['social']['whatsapp'] = 'https://wa.me/' . preg_replace('/\D/', '', $site['whatsapp']);
        }
        $svc->saveSetting('site', $site);
        $svc->saveSetting('seo', ['description' => $request->str('seo_description'), 'keywords' => $request->str('seo_keywords')]);
        $this->touch($svc);
        Activity::log('website.details_saved', 'website', $svc->id());
        Response::ok('Site details saved.');
    }

    /** Current theme + site details for the workspace forms. */
    public function show(Request $request): void
    {
        $svc = $this->site($request->params['id']);
        $s = $svc->settings();
        $theme = Theme::resolve((array) ($svc->manifest()['theme']['defaults'] ?? []), (array) ($s['theme'] ?? []), Theme::fonts(RUNTIME_PATH . '/fonts'));
        $mediaUrl = fn ($p) => is_string($p) && str_starts_with($p, 'uploads/') ? url('websites/' . $svc->id() . '/media/' . substr($p, 8)) : null;
        Response::json(['ok' => true, 'theme' => $theme + ['logo_url' => $mediaUrl($theme['logo'] ?? ''), 'favicon_url' => $mediaUrl($theme['favicon'] ?? '')], 'site' => (array) ($s['site'] ?? []), 'seo' => (array) ($s['seo'] ?? [])]);
    }
}
