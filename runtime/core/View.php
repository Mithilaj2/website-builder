<?php
declare(strict_types=1);

namespace SiteRuntime;

/**
 * The API available to template files as $view.
 * Templates never touch the database, request data or the filesystem directly:
 * everything they need comes through this object.
 */
final class View
{
    public array $page = [];
    public array $theme = [];
    /** Result of the last form submission, keyed by form name: ['status' => 'sent'|'error', 'message' => '…'] */
    public array $flash = [];

    /**
     * @param array{page:string,home:string,theme_asset:string,upload:string,font:string,lib:string} $urls URL patterns
     * @param array{fonts:string,lib:string} $dirs runtime folders (bundled fonts, front-end libraries)
     */
    public function __construct(
        private DataSource $source,
        private string $themeDir,
        private array $manifest,
        private array $urls,
        private array $dirs,
        private bool $preview = false,
    ) {
        $settings = $source->settings();
        $this->theme = Theme::resolve((array) ($manifest['theme']['defaults'] ?? []), (array) ($settings['theme'] ?? []), Theme::fonts($dirs['fonts']));
    }

    // ---------------------------------------------------------------- output helpers

    public function e(mixed $v): string
    {
        return htmlspecialchars(is_scalar($v) ? (string) $v : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Escaped text with line breaks preserved. */
    public function text(mixed $v): string
    {
        return nl2br($this->e($v), false);
    }

    /** Rich text: already sanitised when saved, output as HTML. */
    public function rich(mixed $html): string
    {
        return is_string($html) ? $html : '';
    }

    public function icon(?string $name, string $class = ''): string
    {
        if (!$name || !preg_match('/^bi-[a-z0-9-]+$/', $name)) {
            return '';
        }
        return '<i class="bi ' . $name . ($class !== '' ? ' ' . $this->e($class) : '') . '" aria-hidden="true"></i>';
    }

    // ---------------------------------------------------------------- data

    public function site(?string $key = null, mixed $default = ''): mixed
    {
        $site = (array) ($this->source->settings()['site'] ?? []);
        if ($key === null) {
            return $site;
        }
        $v = $site[$key] ?? null;
        return ($v === null || $v === '') ? $default : $v;
    }

    public function setting(string $group, string $key, mixed $default = ''): mixed
    {
        $g = (array) ($this->source->settings()[$group] ?? []);
        return ($g[$key] ?? '') === '' ? $default : $g[$key];
    }

    public function social(): array
    {
        $out = [];
        $icons = ['facebook' => 'bi-facebook', 'instagram' => 'bi-instagram', 'linkedin' => 'bi-linkedin', 'twitter' => 'bi-twitter-x', 'youtube' => 'bi-youtube', 'whatsapp' => 'bi-whatsapp'];
        foreach ((array) ($this->site('social', [])) as $k => $url) {
            if (isset($icons[$k]) && is_string($url) && SectionTypes::validUrl($url)) {
                $out[] = ['network' => $k, 'url' => $url, 'icon' => $icons[$k]];
            }
        }
        return $out;
    }

    /**
     * Menu links for 'header' or 'footer': the custom menu if one was built in the admin,
     * otherwise automatic (active pages marked "show in menu" / "show in footer", in page order).
     * Each item: title, url, active, slug, external.
     */
    public function menu(string $location = 'header'): array
    {
        $pages = [];
        foreach ($this->source->pages() as $p) {
            $pages[(int) $p['id']] = $p;
        }
        $custom = $this->source->menuItems($location);
        $items = [];
        if ($custom) {
            foreach ($custom as $m) {
                if ($m['page_id']) {
                    $p = $pages[(int) $m['page_id']] ?? null;
                    if (!$p || ($p['status'] ?? 'active') !== 'active') {
                        continue;
                    }
                    $items[] = ['title' => $m['label'], 'url' => $this->url((string) $p['slug']), 'active' => ($this->page['id'] ?? null) == $p['id'], 'slug' => $p['slug'], 'external' => false];
                } elseif (SectionTypes::validUrl((string) $m['url'])) {
                    $items[] = ['title' => $m['label'], 'url' => str_starts_with((string) $m['url'], 'page:') ? $this->url(substr((string) $m['url'], 5)) : (string) $m['url'], 'active' => false, 'slug' => null, 'external' => (bool) $m['new_tab']];
                }
            }
            return $items;
        }
        $col = $location === 'footer' ? 'show_in_footer' : 'show_in_menu';
        foreach ($pages as $p) {
            if (($p['status'] ?? 'active') !== 'active' || empty($p[$col])) {
                continue;
            }
            $items[] = ['title' => $p['title'], 'url' => $this->url((string) $p['slug']), 'active' => ($this->page['id'] ?? null) == $p['id'], 'slug' => $p['slug'], 'external' => false];
        }
        return $items;
    }

    // ---------------------------------------------------------------- URLs

    public function url(string $slug = ''): string
    {
        $home = $this->source->homePage();
        if ($slug === '' || ($home && $home['slug'] === $slug)) {
            return $this->urls['home'];
        }
        return str_replace('{slug}', rawurlencode($slug), $this->urls['page']);
    }

    /** Resolve a link field {text,url} or url string to an href. */
    public function href(mixed $link): string
    {
        $url = is_array($link) ? (string) ($link['url'] ?? '') : (string) $link;
        if ($url === '') {
            return '#';
        }
        if (str_starts_with($url, 'page:')) {
            return $this->e($this->url(substr($url, 5)));
        }
        return SectionTypes::validUrl($url) ? $this->e($url) : '#';
    }

    /** True if a link field has both text and a URL. */
    public function hasLink(mixed $link): bool
    {
        return is_array($link) && trim((string) ($link['text'] ?? '')) !== '' && trim((string) ($link['url'] ?? '')) !== '';
    }

    /** target/rel attributes for external links. */
    public function linkAttrs(mixed $link): string
    {
        $url = is_array($link) ? (string) ($link['url'] ?? '') : (string) $link;
        return preg_match('#^https?://#i', $url) ? ' target="_blank" rel="noopener"' : '';
    }

    /** URL of a file in the template's assets/ folder. */
    public function asset(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (str_contains($path, '..')) {
            return '';
        }
        $file = $this->themeDir . '/assets/' . $path;
        $v = is_file($file) ? '?v=' . substr(md5((string) filemtime($file)), 0, 8) : '';
        return $this->e(str_replace('{path}', $path, $this->urls['theme_asset']) . $v);
    }

    /** URL of a runtime-provided library file (Bootstrap, Bootstrap Icons) declared in manifest "libraries". */
    public function lib(string $path): string
    {
        $path = ltrim($path, '/');
        return str_contains($path, '..') ? '' : $this->e(str_replace('{path}', $path, $this->urls['lib']));
    }

    /** <link> tags for the CSS of every library the template declares. */
    public function libStyles(): string
    {
        $out = '';
        foreach ($this->libraries() as $lib) {
            foreach ((array) ($lib['css'] ?? []) as $css) {
                $out .= '<link rel="stylesheet" href="' . $this->lib($css) . '">';
            }
        }
        return $out;
    }

    /** <script> tags for the JS of every library the template declares. */
    public function libScripts(): string
    {
        $out = '';
        foreach ($this->libraries() as $lib) {
            foreach ((array) ($lib['js'] ?? []) as $js) {
                $out .= '<script src="' . $this->lib($js) . '"></script>';
            }
        }
        return $out;
    }

    private function libraries(): array
    {
        $all = json_decode((string) @file_get_contents($this->libDir() . '/libraries.json'), true) ?: [];
        return array_intersect_key($all, array_flip((array) ($this->manifest['libraries'] ?? [])));
    }

    private function libDir(): string
    {
        return $this->dirs['lib'];
    }

    // ---------------------------------------------------------------- shop

    /** True when this website's shop is switched on (and the shop pages exist here, i.e. not in a builder preview). */
    public function shopEnabled(): bool
    {
        return !empty($this->source->settings()['shop']['enabled']);
    }

    /** URL of a shop page: '' (all products), 'cart', 'checkout', 'product/{slug}'; optional query (e.g. ['c' => 'lamps']). Not escaped. */
    public function shopUrl(string $sub = '', array $query = []): string
    {
        $base = (string) ($this->urls['shop'] ?? '');
        if ($base === '') {
            return '#';
        }
        return $base . ($sub !== '' ? '/' . ltrim($sub, '/') : '') . ($query ? '?' . http_build_query($query) : '');
    }

    public function productUrl(array $product): string
    {
        return $this->shopUrl('product/' . rawurlencode((string) ($product['slug'] ?? '')));
    }

    public function categoryUrl(array $category): string
    {
        return $this->shopUrl('', ['c' => (string) ($category['slug'] ?? '')]);
    }

    /** ₹ amount from paise, Indian digit grouping: ₹1,299 or ₹1,299.50 */
    public function money(mixed $paise): string
    {
        return '₹' . Shop\ShopService::money((int) $paise);
    }

    /** Hidden-field value for forms that post to the cart (add to cart buttons). Empty in previews. */
    public function shopToken(): string
    {
        $key = (string) ($this->urls['form_key'] ?? '');
        if ($key === '' || $this->preview) {
            return '';
        }
        $ts = (string) time();
        return $ts . '.' . hash_hmac('sha256', 'shop|' . $ts, $key);
    }

    /** Number of items in the visitor's cart (0 in previews). */
    public function cartCount(): int
    {
        return (int) ($this->urls['cart_count'] ?? 0);
    }

    /**
     * Products for a section. Options: category (slug), featured (bool), sort (featured|new|price_asc|price_desc), limit (1–60).
     * Each product: id, name, slug, image, gallery, price, compare_price, display_price, price_from, discount_pct, in_stock, badge, category_name …
     */
    public function products(array $opts = []): array
    {
        return $this->source->products(array_intersect_key($opts, array_flip(['category', 'featured', 'sort', 'limit'])));
    }

    /** Active product categories (id, name, slug, description, image, product_count). */
    public function productCategories(): array
    {
        return $this->source->productCategories();
    }

    /** False in the builder preview: forms are shown but submissions only work on the published site. */
    public function formsEnabled(): bool
    {
        return !$this->preview && ($this->urls['form'] ?? '') !== '';
    }

    public function formAction(string $form = 'contact'): string
    {
        return $this->e(str_replace('{form}', rawurlencode($form), (string) ($this->urls['form'] ?? '')));
    }

    /**
     * Hidden fields every public form must include: a signed timestamp token (stateless CSRF +
     * "submitted too fast" trap), a honeypot field, and the page to return to.
     */
    public function formGuard(): string
    {
        $key = (string) ($this->urls['form_key'] ?? '');
        if ($key === '' || !$this->formsEnabled()) {
            return '';
        }
        $ts = (string) time();
        $token = $ts . '.' . hash_hmac('sha256', 'form|' . $ts, $key);
        return '<input type="hidden" name="_t" value="' . $this->e($token) . '">'
            . '<input type="hidden" name="_back" value="' . $this->e($this->page['slug'] ?? '') . '">'
            . '<div style="position:absolute;left:-9999px" aria-hidden="true"><label>Leave this empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>';
    }

    /** Status of the last submission of a form (after the redirect), or null. */
    public function formStatus(string $form = 'contact'): ?array
    {
        return $this->flash[$form] ?? null;
    }

    /**
     * URL for an image reference: "uploads/…" (site media) or "theme:images/…" (template asset).
     * Returns $fallback (a theme: reference or '') when empty.
     */
    public function img(mixed $ref, string $fallback = ''): string
    {
        $ref = is_string($ref) && $ref !== '' ? $ref : $fallback;
        if ($ref === '' || str_contains($ref, '..')) {
            return '';
        }
        if (str_starts_with($ref, 'theme:')) {
            return $this->asset(substr($ref, 6));
        }
        if (str_starts_with($ref, 'uploads/')) {
            return $this->e(str_replace('{path}', substr($ref, 8), $this->urls['upload']));
        }
        return '';
    }

    /** Alt text recorded in the media library for an uploaded image, or $default. */
    public function alt(mixed $ref, string $default = ''): string
    {
        $alts = $this->source->mediaAlt();
        return $this->e(is_string($ref) && isset($alts[$ref]) && $alts[$ref] !== '' ? $alts[$ref] : $default);
    }

    // ---------------------------------------------------------------- composition

    public function partial(string $name, array $vars = []): string
    {
        if (!preg_match('/^[a-z0-9_\-]+$/', $name)) {
            return '';
        }
        $file = $this->themeDir . '/partials/' . $name . '.php';
        return is_file($file) ? $this->capture($file, $vars) : '';
    }

    /**
     * Attributes for a section's root element: anchor id, classes and background styles.
     * Usage in a section view: <section <?= $view->attrs($section, 'about') ?>>
     */
    public function attrs(array $section, string $class = ''): string
    {
        $style = (array) ($section['style'] ?? []);
        $classes = ['wb-section', 'wb-' . preg_replace('/[^a-z0-9_]/', '', (string) ($section['section_type'] ?? ''))];
        if ($class !== '') {
            $classes[] = $class;
        }
        $pad = $style['padding'] ?? 'md';
        if (in_array($pad, ['none', 'sm', 'md', 'lg'], true)) {
            $classes[] = 'wb-pad-' . $pad;
        }
        $tone = $style['tone'] ?? 'auto';
        if (in_array($tone, ['light', 'dark'], true)) {
            $classes[] = 'wb-tone-' . $tone;
        }
        $css = [];
        if (!empty($style['bg_color']) && preg_match('/^#[0-9a-fA-F]{6}$/', $style['bg_color'])) {
            $css[] = 'background-color:' . $style['bg_color'];
            $classes[] = 'wb-has-bg';
        }
        if (!empty($style['bg_image']) && ($url = $this->img($style['bg_image'])) !== '') {
            $css[] = "background-image:url('" . $url . "')";
            $css[] = 'background-size:cover;background-position:center';
            $classes[] = 'wb-has-bg-image';
        }
        $overlay = (int) ($style['overlay'] ?? 0);
        if ($overlay > 0) {
            $css[] = '--wb-overlay:' . min(90, $overlay) / 100;
            $classes[] = 'wb-has-overlay';
        }
        $anchor = (string) ($style['anchor'] ?? '');
        $id = preg_match('/^[a-z][a-z0-9-]{0,40}$/', $anchor) ? $anchor : 'section-' . (int) ($section['id'] ?? 0);
        $out = 'id="' . $this->e($id) . '" class="' . $this->e(implode(' ', $classes)) . '"';
        if ($css) {
            $out .= ' style="' . $this->e(implode(';', $css)) . '"';
        }
        if ($this->preview) {
            $out .= ' data-wb-section="' . (int) ($section['id'] ?? 0) . '"';
        }
        return $out;
    }

    /** <head> contents: title, meta, favicon and the theme stylesheet (fonts + CSS variables). */
    public function head(): string
    {
        $seo = (array) ($this->source->settings()['seo'] ?? []);
        $siteName = (string) $this->site('name', 'Website');
        $title = trim((string) ($this->page['seo_title'] ?? ''));
        if ($title === '') {
            $title = !empty($this->page['is_home'])
                ? $siteName . ((string) $this->site('tagline') !== '' ? ' – ' . $this->site('tagline') : '')
                : ($this->page['title'] ?? '') . ' | ' . $siteName;
        }
        $desc = trim((string) ($this->page['seo_description'] ?? '')) ?: (string) ($seo['description'] ?? '');
        $keywords = trim((string) ($this->page['seo_keywords'] ?? '')) ?: (string) ($seo['keywords'] ?? '');
        $out = '<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
        $out .= '<title>' . $this->e($title) . '</title>';
        if ($desc !== '') {
            $out .= '<meta name="description" content="' . $this->e($desc) . '">';
        }
        if ($keywords !== '') {
            $out .= '<meta name="keywords" content="' . $this->e($keywords) . '">';
        }
        $out .= '<meta property="og:title" content="' . $this->e($title) . '"><meta property="og:type" content="website">';
        if ($this->preview) {
            $out .= '<meta name="robots" content="noindex, nofollow">';
        }
        $fav = $this->img($this->theme['favicon'] ?? '');
        if ($fav !== '') {
            $out .= '<link rel="icon" href="' . $fav . '">';
        }
        $out .= '<style id="wb-theme">' . Theme::css($this->theme, Theme::fonts($this->dirs['fonts']), $this->urls['font']) . '</style>';
        return $out;
    }

    /** Classes for <body>: button, header and footer styles chosen in the theme customiser. */
    public function bodyClass(string $extra = ''): string
    {
        $c = ['wb-btn-' . $this->theme['button_style']];
        foreach (['header_style' => 'wb-header-', 'footer_style' => 'wb-footer-'] as $k => $prefix) {
            $v = (string) ($this->theme[$k] ?? '');
            if (preg_match('/^[a-z0-9-]+$/', $v)) {
                $c[] = $prefix . $v;
            }
        }
        if ($extra !== '') {
            $c[] = $extra;
        }
        return $this->e(implode(' ', $c));
    }

    public function logo(): string
    {
        return $this->img($this->theme['logo'] ?? '');
    }

    public function isPreview(): bool
    {
        return $this->preview;
    }

    public function year(): string
    {
        return date('Y');
    }

    /** Include a template file in an isolated scope with $view and the given variables. */
    public function capture(string $file, array $vars): string
    {
        $vars['view'] = $this;
        $vars['site'] ??= $this->site();
        $vars['page'] ??= $this->page;
        ob_start();
        try {
            (static function (string $__file, array $__vars): void {
                extract($__vars, EXTR_SKIP);
                include $__file;
            })($file, $vars);
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
