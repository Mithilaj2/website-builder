<?php
declare(strict_types=1);

namespace SiteRuntime;

/**
 * Pages and sections of one website, with all validation.
 * Used by the builder (wizard) and by the website's own admin panel, so both behave identically.
 */
final class ContentService
{
    public const RESERVED_SLUGS = ['admin', 'core', 'config', 'uploads', 'theme', 'assets', 'storage', 'database', 'vendor', 'install', 'index', 'api', 'form', 'forms', 'pay'];

    /**
     * @param callable(string):string $richtext  sanitises rich-text HTML
     * @param callable(string):bool   $imageOk   true if an image reference belongs to this site/template
     */
    public function __construct(
        private Db $db,
        private SectionTypes $types,
        private array $manifest,
        private $richtext,
        private $imageOk,
    ) {
    }

    /** Section type schemas supported by this website's template. */
    public function schemas(): array
    {
        return $this->types->only((array) ($this->manifest['section_types'] ?? []));
    }

    public function pageTypes(): array
    {
        return array_keys((array) ($this->manifest['page_types'] ?? []));
    }

    // ================================================================== pages

    public function pages(): array
    {
        $t = $this->db->t('sections');
        return $this->db->fetchAll(
            "SELECT p.*, (SELECT COUNT(*) FROM $t s WHERE s.page_id = p.id) AS sections,
                    (SELECT COUNT(*) FROM $t s WHERE s.page_id = p.id AND s.is_enabled = 1) AS enabled_sections
             FROM " . $this->db->t('pages') . ' p ORDER BY p.sort_order, p.id'
        );
    }

    public function page(int $id): array
    {
        $p = $this->db->fetch('SELECT * FROM ' . $this->db->t('pages') . ' WHERE id = ?', [$id]);
        if (!$p) {
            throw new NotFound('Page not found.');
        }
        return $p;
    }

    /** Create (page = null) or update a page. New pages start with their page type's default sections. */
    public function savePage(?array $page, array $in): int
    {
        $isHome = $page && (int) $page['is_home'] === 1;
        $title = trim(strip_tags((string) ($in['title'] ?? '')));
        $slug = strtolower(trim((string) ($in['slug'] ?? ''), "/ \t\n"));
        if ($slug === '' && !$isHome) {
            $slug = self::slugify($title);
        }
        $type = (string) ($in['page_type'] ?? '');
        $errors = [];
        if ($title === '' || mb_strlen($title) > 150) {
            $errors['title'] = 'Page title is required (up to 150 characters).';
        }
        if (!in_array($type, $this->pageTypes(), true)) {
            $errors['page_type'] = 'Choose a page layout.';
        }
        foreach (['seo_title' => 190, 'seo_description' => 320, 'seo_keywords' => 255] as $k => $max) {
            if (mb_strlen(trim((string) ($in[$k] ?? ''))) > $max) {
                $errors[$k] = "May not be longer than $max characters.";
            }
        }
        if (!$isHome || $slug !== '') {
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || strlen($slug) > 120) {
                $errors['slug'] = 'URL may contain only lowercase letters, numbers and single hyphens.';
            } elseif (in_array($slug, self::RESERVED_SLUGS, true)) {
                $errors['slug'] = "“{$slug}” is reserved by the website system. Choose another URL.";
            } elseif ((int) $this->db->value('SELECT COUNT(*) FROM ' . $this->db->t('pages') . ' WHERE slug = ? AND id <> ?', [$slug, (int) ($page['id'] ?? 0)]) > 0) {
                $errors['slug'] = 'Another page already uses this URL.';
            }
        }
        if ($errors) {
            throw new ValidationError('Please correct the highlighted fields.', $errors);
        }
        $data = [
            'title' => $title, 'slug' => $slug, 'page_type' => $type,
            'show_in_menu' => self::truthy($in['show_in_menu'] ?? null) ? 1 : 0,
            'show_in_footer' => self::truthy($in['show_in_footer'] ?? null) ? 1 : 0,
            'seo_title' => trim((string) ($in['seo_title'] ?? '')) ?: null,
            'seo_description' => trim((string) ($in['seo_description'] ?? '')) ?: null,
            'seo_keywords' => trim((string) ($in['seo_keywords'] ?? '')) ?: null,
            'updated_at' => self::now(),
        ];
        if ($page) {
            $this->db->update('pages', $data, (int) $page['id']);
            return (int) $page['id'];
        }
        $data += ['is_home' => 0, 'status' => 'active', 'created_at' => self::now(),
            'sort_order' => (int) $this->db->value('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM ' . $this->db->t('pages'))];
        $id = $this->db->insert('pages', $data);
        foreach ((array) ($this->manifest['page_types'][$type]['default_sections'] ?? []) as $i => $st) {
            if ($this->types->get($st)) {
                $defaults = $this->types->defaults($st);
                if ($st === 'page_banner') {
                    $defaults['heading'] = $title;
                }
                $this->insertSection($id, $st, $defaults, [], $i + 1);
            }
        }
        return $id;
    }

    public function setHome(int $id): array
    {
        $page = $this->page($id);
        if ($page['status'] !== 'active') {
            throw new ValidationError('Only an active page can be the homepage.');
        }
        $this->db->transaction(static function (Db $db) use ($id): void {
            $db->query('UPDATE ' . $db->t('pages') . ' SET is_home = 0');
            $db->query('UPDATE ' . $db->t('pages') . ' SET is_home = 1 WHERE id = ?', [$id]);
        });
        return $page;
    }

    public function togglePage(int $id): string
    {
        $page = $this->page($id);
        if ((int) $page['is_home'] === 1 && $page['status'] === 'active') {
            throw new ValidationError('The homepage cannot be deactivated. Set another page as the homepage first.');
        }
        $new = $page['status'] === 'active' ? 'inactive' : 'active';
        $this->db->update('pages', ['status' => $new, 'updated_at' => self::now()], $id);
        return $new;
    }

    public function reorderPages(array $ids): void
    {
        $this->db->transaction(static function (Db $db) use ($ids): void {
            foreach (array_values(array_filter(array_map('intval', $ids))) as $i => $id) {
                $db->query('UPDATE ' . $db->t('pages') . ' SET sort_order = ? WHERE id = ?', [$i + 1, $id]);
            }
        });
    }

    public function deletePage(int $id): array
    {
        $page = $this->page($id);
        if ((int) $page['is_home'] === 1) {
            throw new ValidationError('The homepage cannot be deleted. Set another page as the homepage first.');
        }
        $this->db->delete('pages', $id); // sections cascade
        $this->db->query('DELETE FROM ' . $this->db->t('menu_items') . ' WHERE page_id = ?', [$id]);
        return $page;
    }

    // ================================================================== sections

    public function sections(int $pageId): array
    {
        $this->page($pageId);
        $schemas = $this->schemas();
        $rows = (new DbSource($this->db))->sections($pageId);
        foreach ($rows as &$r) {
            $r['type_label'] = $schemas[$r['section_type']]['label'] ?? $r['section_type'];
            $r['icon'] = $schemas[$r['section_type']]['icon'] ?? 'bi-square';
            $r['summary'] = self::summary($r['data']);
            $r['supported'] = isset($schemas[$r['section_type']]);
        }
        return $rows;
    }

    public function section(int $id): array
    {
        $s = $this->db->fetch('SELECT * FROM ' . $this->db->t('sections') . ' WHERE id = ?', [$id]);
        if (!$s) {
            throw new NotFound('Section not found.');
        }
        return $s;
    }

    /** Section for the editor: decoded data/style + its schema. */
    public function sectionForEditor(int $id): array
    {
        $s = $this->section($id);
        $s['data'] = json_decode((string) $s['data'], true) ?: [];
        $s['style'] = json_decode((string) $s['style'], true) ?: [];
        return ['section' => $s, 'schema' => $this->schemas()[$s['section_type']] ?? null];
    }

    public function addSection(int $pageId, string $type, int $after = 0): int
    {
        $this->page($pageId);
        if (!isset($this->schemas()[$type])) {
            throw new ValidationError('This template does not support that section type.');
        }
        $t = $this->db->t('sections');
        $order = (int) $this->db->value("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM $t WHERE page_id = ?", [$pageId]);
        if ($after > 0) {
            $pos = $this->db->value("SELECT sort_order FROM $t WHERE id = ? AND page_id = ?", [$after, $pageId]);
            if ($pos !== null) {
                $order = (int) $pos + 1;
                $this->db->query("UPDATE $t SET sort_order = sort_order + 1 WHERE page_id = ? AND sort_order >= ?", [$pageId, $order]);
            }
        }
        return $this->insertSection($pageId, $type, $this->types->defaults($type), [], $order);
    }

    /** Validate and save a section's fields and style options. Returns the cleaned data. */
    public function updateSection(int $id, array $data, array $style, string $label = ''): array
    {
        $s = $this->section($id);
        if (!isset($this->schemas()[$s['section_type']])) {
            throw new ValidationError('This section type is not supported by the template any more.');
        }
        [$clean, $errors] = $this->types->sanitize($s['section_type'], $data, $this->richtext, $this->imageOk);
        [$cleanStyle, $styleErrors] = $this->cleanStyle($style);
        $errors += $styleErrors;
        if ($errors) {
            throw new ValidationError('Please correct the highlighted fields.', $errors);
        }
        $this->db->update('sections', [
            'data' => self::json($clean),
            'style' => self::json($cleanStyle ?: new \stdClass()),
            'label' => mb_substr(trim(strip_tags($label)), 0, 120) ?: null,
            'updated_at' => self::now(),
        ], $id);
        return $clean;
    }

    public function toggleSection(int $id): bool
    {
        $s = $this->section($id);
        $new = (int) $s['is_enabled'] ? 0 : 1;
        $this->db->update('sections', ['is_enabled' => $new, 'updated_at' => self::now()], $id);
        return (bool) $new;
    }

    public function duplicateSection(int $id): int
    {
        $s = $this->section($id);
        $t = $this->db->t('sections');
        $this->db->query("UPDATE $t SET sort_order = sort_order + 1 WHERE page_id = ? AND sort_order > ?", [(int) $s['page_id'], (int) $s['sort_order']]);
        return $this->db->insert('sections', [
            'page_id' => (int) $s['page_id'], 'section_type' => $s['section_type'],
            'label' => $s['label'] ? mb_substr($s['label'] . ' (copy)', 0, 120) : null,
            'data' => $s['data'], 'style' => $s['style'], 'is_enabled' => (int) $s['is_enabled'],
            'sort_order' => (int) $s['sort_order'] + 1, 'created_at' => self::now(), 'updated_at' => self::now(),
        ]);
    }

    public function deleteSection(int $id): array
    {
        $s = $this->section($id);
        $this->db->delete('sections', $id);
        return $s;
    }

    public function reorderSections(int $pageId, array $ids): void
    {
        $this->page($pageId);
        $this->db->transaction(static function (Db $db) use ($ids, $pageId): void {
            foreach (array_values(array_filter(array_map('intval', $ids))) as $i => $id) {
                $db->query('UPDATE ' . $db->t('sections') . ' SET sort_order = ? WHERE id = ? AND page_id = ?', [$i + 1, $id, $pageId]);
            }
        });
    }

    // ================================================================== navigation

    /** Menu items for a location; empty means "automatic from pages". */
    public function menu(string $location): array
    {
        return $this->db->fetchAll('SELECT * FROM ' . $this->db->t('menu_items') . ' WHERE location = ? ORDER BY sort_order, id', [$location]);
    }

    /**
     * Replace a menu. Each item: {label, page_id?} or {label, url, new_tab?}. An empty list
     * switches the location back to automatic (pages marked "show in menu/footer").
     */
    public function saveMenu(string $location, array $items): void
    {
        if (!in_array($location, ['header', 'footer'], true)) {
            throw new ValidationError('Unknown menu.');
        }
        if (count($items) > 30) {
            throw new ValidationError('A menu may have at most 30 links.');
        }
        $pages = array_column($this->pages(), null, 'id');
        $rows = [];
        $errors = [];
        foreach (array_values($items) as $i => $it) {
            $label = mb_substr(trim(strip_tags((string) ($it['label'] ?? ''))), 0, 60);
            $pageId = (int) ($it['page_id'] ?? 0);
            $url = trim((string) ($it['url'] ?? ''));
            if ($pageId > 0) {
                if (!isset($pages[$pageId])) {
                    $errors["items.$i"] = 'That page no longer exists.';
                    continue;
                }
                $label = $label !== '' ? $label : $pages[$pageId]['title'];
                $url = '';
            } elseif ($url === '' || !SectionTypes::validUrl($url)) {
                $errors["items.$i"] = 'Link ' . ($i + 1) . ': enter a full https:// address, #anchor, mailto: or tel:.';
                continue;
            }
            if ($label === '') {
                $errors["items.$i"] = 'Link ' . ($i + 1) . ': a label is required.';
                continue;
            }
            $rows[] = ['location' => $location, 'label' => $label, 'page_id' => $pageId ?: null, 'url' => $url ?: null,
                'new_tab' => !empty($it['new_tab']) && $url !== '' ? 1 : 0, 'sort_order' => $i + 1];
        }
        if ($errors) {
            throw new ValidationError('Please correct the menu links.', $errors);
        }
        $this->db->transaction(static function (Db $db) use ($location, $rows): void {
            $db->query('DELETE FROM ' . $db->t('menu_items') . ' WHERE location = ?', [$location]);
            foreach ($rows as $r) {
                $db->insert('menu_items', $r + ['created_at' => date('Y-m-d H:i:s')]);
            }
        });
    }

    // ================================================================== helpers

    private function insertSection(int $pageId, string $type, array $data, array $style, int $order): int
    {
        return $this->db->insert('sections', [
            'page_id' => $pageId, 'section_type' => $type, 'data' => self::json($data), 'style' => self::json($style ?: new \stdClass()),
            'is_enabled' => 1, 'sort_order' => $order, 'created_at' => self::now(), 'updated_at' => self::now(),
        ]);
    }

    /** Style options common to every section. */
    private function cleanStyle(array $in): array
    {
        $errors = [];
        $out = [];
        $bg = trim((string) ($in['bg_color'] ?? ''));
        if ($bg !== '') {
            preg_match('/^#[0-9a-fA-F]{6}$/', $bg) ? $out['bg_color'] = $bg : $errors['style.bg_color'] = 'Background colour must look like #1A2B3C.';
        }
        $img = trim((string) ($in['bg_image'] ?? ''));
        if ($img !== '') {
            ($this->imageOk)($img) ? $out['bg_image'] = $img : $errors['style.bg_image'] = 'Choose the background image from the media library.';
        }
        $overlay = (int) ($in['overlay'] ?? 0);
        if ($overlay > 0) {
            $out['overlay'] = min(90, max(0, $overlay));
        }
        if (in_array($in['padding'] ?? 'md', ['none', 'sm', 'md', 'lg'], true)) {
            $out['padding'] = $in['padding'] ?? 'md';
        }
        if (in_array($in['tone'] ?? 'auto', ['auto', 'light', 'dark'], true)) {
            $out['tone'] = $in['tone'] ?? 'auto';
        }
        $anchor = strtolower(trim((string) ($in['anchor'] ?? '')));
        if ($anchor !== '') {
            preg_match('/^[a-z][a-z0-9-]{0,40}$/', $anchor) ? $out['anchor'] = $anchor : $errors['style.anchor'] = 'Anchor must start with a letter and use only lowercase letters, numbers and hyphens.';
        }
        return [$out, $errors];
    }

    private static function summary(array $data): string
    {
        foreach (['heading', 'title', 'eyebrow', 'form_heading'] as $k) {
            if (!empty($data[$k]) && is_string($data[$k])) {
                return mb_substr($data[$k], 0, 90);
            }
        }
        return '';
    }

    public static function slugify(string $text): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($text))), '-');
    }

    private static function truthy(mixed $v): bool
    {
        return in_array($v, [true, 1, '1', 'on', 'true'], true);
    }

    private static function json(mixed $v): string
    {
        return (string) json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
