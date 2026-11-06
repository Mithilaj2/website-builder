<?php
declare(strict_types=1);

namespace SiteRuntime;

/**
 * Builds a site's initial content from a template: the manifest's default pages and
 * sections, filled with the template's demo content (demo.json) over schema defaults.
 * The same seed is used for template previews (in memory) and new sites (inserted into the DB).
 */
final class Seeder
{
    public static function build(array $manifest, SectionTypes $types, array $demo, array $siteOverrides = []): array
    {
        $supported = (array) ($manifest['section_types'] ?? []);
        $demoPages = (array) ($demo['pages'] ?? []);
        $pages = [];
        foreach ((array) ($manifest['default_pages'] ?? []) as $i => $p) {
            $slug = (string) $p['slug'];
            $type = (string) $p['type'];
            $d = (array) ($demoPages[$slug] ?? []);
            $sections = [];
            $list = isset($d['sections']) ? (array) $d['sections']
                : array_map(static fn ($t) => ['type' => $t], (array) ($manifest['page_types'][$type]['default_sections'] ?? []));
            foreach ($list as $j => $s) {
                $st = (string) ($s['type'] ?? '');
                if (!in_array($st, $supported, true) || $types->get($st) === null) {
                    continue;
                }
                $sections[] = [
                    'section_type' => $st,
                    'label' => $s['label'] ?? null,
                    'data' => array_replace($types->defaults($st), (array) ($s['data'] ?? [])),
                    'style' => (array) ($s['style'] ?? []),
                    'is_enabled' => 1,
                    'sort_order' => $j + 1,
                ];
            }
            $pages[] = [
                'title' => (string) $p['title'],
                'slug' => $slug,
                'page_type' => $type,
                'is_home' => !empty($p['is_home']) ? 1 : 0,
                'status' => 'active',
                'show_in_menu' => array_key_exists('in_menu', $p) ? ($p['in_menu'] ? 1 : 0) : 1,
                'show_in_footer' => !empty($p['in_footer']) ? 1 : 0,
                'sort_order' => $i + 1,
                'seo_title' => $d['seo_title'] ?? null,
                'seo_description' => $d['seo_description'] ?? null,
                'seo_keywords' => $d['seo_keywords'] ?? null,
                'sections' => $sections,
            ];
        }
        $settings = (array) ($demo['settings'] ?? []);
        $settings['site'] = array_replace((array) ($settings['site'] ?? []), array_filter($siteOverrides, static fn ($v) => $v !== null && $v !== ''));
        $settings['theme'] = array_replace((array) ($manifest['theme']['defaults'] ?? []), (array) ($settings['theme'] ?? []));
        $settings['seo'] = (array) ($settings['seo'] ?? []);
        return ['settings' => $settings, 'pages' => $pages];
    }

    /** Insert a seed into an (empty) site database. */
    public static function insert(Db $db, array $seed): void
    {
        $now = date('Y-m-d H:i:s');
        $db->transaction(static function (Db $db) use ($seed, $now): void {
            foreach ($seed['settings'] as $key => $value) {
                $db->query(
                    'REPLACE INTO ' . $db->t('settings') . ' (setting_key, setting_value, updated_at) VALUES (?, ?, ?)',
                    [$key, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $now]
                );
            }
            foreach ($seed['pages'] as $p) {
                $sections = $p['sections'];
                unset($p['sections']);
                $pageId = $db->insert('pages', $p + ['created_at' => $now, 'updated_at' => $now]);
                foreach ($sections as $s) {
                    $db->insert('sections', [
                        'page_id' => $pageId,
                        'section_type' => $s['section_type'],
                        'label' => $s['label'],
                        'data' => json_encode($s['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'style' => json_encode($s['style'] ?: new \stdClass(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'is_enabled' => 1,
                        'sort_order' => $s['sort_order'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }
}
