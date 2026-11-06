<?php
declare(strict_types=1);

namespace App\Modules\Generator;

use App\Core\Database;
use App\Modules\Websites\SiteService;

/**
 * Pre-generation checks (wizard step 8 and generation step 1).
 * Errors block generation; warnings are shown but do not block.
 */
final class Checklist
{
    /** @return array{errors: list<string>, warnings: list<string>, items: list<array>} */
    public static function run(SiteService $site): array
    {
        $items = [];
        $add = static function (string $status, string $label, string $detail = '', int $step = 0) use (&$items): void {
            $items[] = compact('status', 'label', 'detail', 'step');
        };
        $db = $site->db();
        $w = $site->website();
        $settings = $site->settings();
        $siteInfo = (array) ($settings['site'] ?? []);
        $theme = (array) ($settings['theme'] ?? []);

        // Template
        $tplStatus = Database::value('SELECT status FROM wb_templates WHERE id = ?', [$w['template_id']]);
        try {
            $site->themeDir();
            $tplStatus === 'active'
                ? $add('ok', 'Template is available', $w['template_name'] . ' v' . $w['template_version'], 2)
                : $add('warn', 'Template is inactive', 'It still works for this website, but cannot be chosen for new ones.', 2);
        } catch (\Throwable) {
            $add('error', 'Template folder is missing', 'Restore the template before generating.', 2);
        }

        // Pages
        $pages = $db->fetchAll('SELECT p.id, p.title, p.slug, p.status, p.is_home, p.seo_description, (SELECT COUNT(*) FROM ' . $db->t('sections') . ' s WHERE s.page_id = p.id AND s.is_enabled = 1) AS visible FROM ' . $db->t('pages') . ' p ORDER BY p.sort_order');
        $active = array_filter($pages, static fn ($p) => $p['status'] === 'active');
        $home = array_filter($active, static fn ($p) => (int) $p['is_home'] === 1);
        $home ? $add('ok', 'Homepage is set', current($home)['title'], 3) : $add('error', 'No active homepage', 'Set an active page as the homepage.', 3);
        $add('ok', count($active) . ' active page(s)', implode(', ', array_column($active, 'title')), 3);
        foreach ($active as $p) {
            if ((int) $p['visible'] === 0) {
                $add('error', "“{$p['title']}” has no visible sections", 'Add or enable at least one section, or deactivate the page.', 4);
            }
        }
        $noSeo = array_filter($active, static fn ($p) => trim((string) $p['seo_description']) === '');
        if ($noSeo && trim((string) ($settings['seo']['description'] ?? '')) === '') {
            $add('warn', count($noSeo) . ' page(s) without a meta description', 'Add SEO descriptions to pages, or a default one in Site details.', 3);
        }

        // Content references
        $sections = $db->fetchAll('SELECT s.id, s.section_type, s.data, s.style, p.title FROM ' . $db->t('sections') . ' s JOIN ' . $db->t('pages') . ' p ON p.id = s.page_id WHERE s.is_enabled = 1 AND p.status = \'active\'');
        $media = array_flip(array_column($db->fetchAll('SELECT path FROM ' . $db->t('media')), 'path'));
        $slugs = array_flip(array_map('strval', array_column($active, 'slug')));
        $missingImages = [];
        $brokenLinks = [];
        $demoImages = 0;
        $blob = json_encode($theme) . json_encode($siteInfo);
        foreach ($sections as $s) {
            $blob .= $s['data'] . $s['style'];
            $json = $s['data'] . $s['style'];
            preg_match_all('#"(uploads/[^"]+)"#', $json, $m);
            foreach ($m[1] as $ref) {
                $ref = stripslashes($ref);
                if (!isset($media[$ref]) || !is_file($site->folder() . '/' . $ref)) {
                    $missingImages[] = $s['title'];
                }
            }
            $demoImages += preg_match_all('#"theme:images/#', $json);
            preg_match_all('#"page:([a-z0-9-]*)"#', $json, $l);
            foreach ($l[1] as $slug) {
                if (!isset($slugs[$slug]) && !($slug === '' && $home)) {
                    $brokenLinks[] = "{$s['title']} → page:$slug";
                }
            }
        }
        $hb = (string) ($siteInfo['header_button']['url'] ?? '');
        if (str_starts_with($hb, 'page:') && !isset($slugs[substr($hb, 5)]) && substr($hb, 5) !== '') {
            $brokenLinks[] = 'Header button → ' . $hb;
        }
        $missingImages
            ? $add('error', 'Missing images', 'Used on: ' . implode(', ', array_unique($missingImages)) . '. Choose them again from the media library.', 4)
            : $add('ok', 'All images are in the media library', count($media) . ' uploaded file(s).', 5);
        if ($brokenLinks) {
            $add('warn', count($brokenLinks) . ' link(s) to missing or inactive pages', implode('; ', array_slice(array_unique($brokenLinks), 0, 5)), 4);
        }
        if ($demoImages > 0) {
            $add('warn', "$demoImages template demo image(s) still in use", 'Replace them with the client\'s own images before going live.', 5);
        }

        // Site details & branding
        trim((string) ($siteInfo['name'] ?? '')) !== ''
            ? $add('ok', 'Website name', (string) $siteInfo['name'], 6)
            : $add('error', 'Website name is empty', 'Set it in Site details.', 6);
        $email = (string) ($siteInfo['email'] ?? '');
        if ($email === '' && trim((string) ($siteInfo['phone'] ?? '')) === '') {
            $add('warn', 'No email or phone number', 'Visitors will have no way to contact the business.', 6);
        } elseif (preg_match('/\.example$/i', $email) || str_contains($blob, '.example"')) {
            $add('warn', 'Demo contact details are still present', 'Replace the template\'s example email/phone/address with the client\'s.', 6);
        } else {
            $add('ok', 'Contact details', trim($email . ' ' . ($siteInfo['phone'] ?? '')), 6);
        }
        empty($theme['logo']) ? $add('warn', 'No logo uploaded', 'The website name is shown instead.', 6) : $add('ok', 'Logo', '', 6);
        if (empty($theme['favicon'])) {
            $add('warn', 'No favicon', 'Browsers will show a blank tab icon.', 6);
        }

        // Payments
        $payments = json_decode((string) $w['features'], true)['payments'] ?? null;
        if (!empty($payments['enabled'])) {
            empty($payments['gateway'])
                ? $add('error', 'Online payment is enabled but no gateway is selected', '', 7)
                : $add('ok', 'Payment gateway: ' . ucfirst((string) $payments['gateway']) . ' (' . ($payments['mode'] ?? 'test') . ' mode)', 'Credentials are entered by the client in their website admin after deployment.', 7);
        } else {
            $add('ok', 'Online payment: off', '', 7);
        }

        $errors = array_values(array_map(static fn ($i) => $i['label'] . ($i['detail'] ? ' — ' . $i['detail'] : '') . '.', array_filter($items, static fn ($i) => $i['status'] === 'error')));
        $warnings = array_values(array_map(static fn ($i) => $i['label'] . ($i['detail'] ? ' — ' . $i['detail'] : ''), array_filter($items, static fn ($i) => $i['status'] === 'warn')));
        return ['errors' => $errors, 'warnings' => $warnings, 'items' => $items];
    }
}
