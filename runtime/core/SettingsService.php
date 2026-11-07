<?php
declare(strict_types=1);

namespace SiteRuntime;

/** Website-level settings: theme, site details, mail. Shared by the builder and the website admin. */
final class SettingsService
{
    public function __construct(private Db $db, private array $manifest, private string $fontsDir, private $imageOk)
    {
    }

    public function all(): array
    {
        return (new DbSource($this->db))->settings();
    }

    public function save(string $key, array $value): void
    {
        $this->db->query(
            'REPLACE INTO ' . $this->db->t('settings') . ' (setting_key, setting_value, updated_at) VALUES (?, ?, ?)',
            [$key, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), date('Y-m-d H:i:s')]
        );
    }

    /** Theme merged over the template defaults, for forms. */
    public function theme(): array
    {
        return Theme::resolve((array) ($this->manifest['theme']['defaults'] ?? []), (array) ($this->all()['theme'] ?? []), Theme::fonts($this->fontsDir));
    }

    public function saveTheme(array $in): void
    {
        $fonts = Theme::fonts($this->fontsDir);
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
        $headers = (array) ($this->manifest['theme']['header_styles'] ?? []);
        $footers = (array) ($this->manifest['theme']['footer_styles'] ?? []);
        if ($headers && !isset($headers[$in['header_style'] ?? ''])) {
            $errors['header_style'] = 'Choose a header style.';
        }
        if ($footers && !isset($footers[$in['footer_style'] ?? ''])) {
            $errors['footer_style'] = 'Choose a footer style.';
        }
        foreach (['logo', 'favicon'] as $img) {
            $v = trim((string) ($in[$img] ?? ''));
            if ($v !== '' && !($this->imageOk)($v)) {
                $errors[$img] = 'Choose the image from the media library.';
            }
        }
        if ($errors) {
            throw new ValidationError('Please correct the highlighted fields.', $errors);
        }
        $theme = [];
        foreach (['primary', 'secondary', 'bg', 'text', 'heading_font', 'body_font', 'button_style', 'radius', 'header_style', 'footer_style', 'logo', 'favicon'] as $k) {
            $theme[$k] = trim((string) ($in[$k] ?? ''));
        }
        foreach (['primary', 'secondary', 'bg', 'text'] as $c) {
            $theme[$c] = strtoupper($theme[$c]);
        }
        $this->save('theme', $theme);
    }

    public function saveDetails(array $in): void
    {
        $s = static fn ($k) => trim(strip_tags((string) ($in[$k] ?? '')));
        $errors = [];
        foreach (['name' => 120, 'tagline' => 150, 'address' => 400, 'hours' => 120, 'footer_about' => 400, 'footer_text' => 200, 'rating' => 40, 'rating_label' => 90, 'seo_description' => 320, 'seo_keywords' => 255] as $k => $max) {
            if (mb_strlen($s($k)) > $max) {
                $errors[$k] = "May not be longer than $max characters.";
            }
        }
        if ($s('name') === '') {
            $errors['name'] = 'Website name is required.';
        }
        if ($s('email') !== '' && !filter_var($s('email'), FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email must be a valid email address.';
        }
        foreach (['phone', 'whatsapp', 'emergency_phone'] as $p) {
            if ($s($p) !== '' && !preg_match('/^\+?[0-9 ()\-]{7,20}$/', $s($p))) {
                $errors[$p] = 'Enter a valid phone number.';
            }
        }
        $social = [];
        foreach (['facebook', 'instagram', 'linkedin', 'twitter', 'youtube'] as $n) {
            $u = $s('social_' . $n);
            if ($u !== '') {
                preg_match('#^https://#i', $u) && filter_var($u, FILTER_VALIDATE_URL) ? $social[$n] = $u : $errors['social_' . $n] = 'Enter a full https:// address.';
            }
        }
        $btn = ['text' => mb_substr($s('header_button_text'), 0, 40), 'url' => trim((string) ($in['header_button_url'] ?? ''))];
        if ($btn['url'] !== '' && !SectionTypes::validUrl($btn['url'])) {
            $errors['header_button_url'] = 'Use page:slug, a full https:// address, #anchor, mailto: or tel:.';
        }
        if ($errors) {
            throw new ValidationError('Please correct the highlighted fields.', $errors);
        }
        $site = array_merge((array) ($this->all()['site'] ?? []), [
            'name' => $s('name'), 'tagline' => $s('tagline'), 'email' => strtolower($s('email')), 'phone' => $s('phone'),
            'whatsapp' => $s('whatsapp'), 'address' => $s('address'), 'hours' => $s('hours'), 'footer_about' => $s('footer_about'),
            'footer_text' => $s('footer_text'), 'emergency_phone' => $s('emergency_phone'), 'rating' => $s('rating'), 'rating_label' => $s('rating_label'), 'social' => $social, 'header_button' => $btn,
        ]);
        if ($site['whatsapp'] !== '') {
            $site['social']['whatsapp'] = 'https://wa.me/' . preg_replace('/\D/', '', $site['whatsapp']);
        }
        $this->save('site', $site);
        $this->save('seo', ['description' => $s('seo_description'), 'keywords' => $s('seo_keywords')]);
    }
}
