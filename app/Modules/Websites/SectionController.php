<?php
declare(strict_types=1);

namespace App\Modules\Websites;

use App\Core\Activity;
use App\Core\Request;
use App\Core\Response;

/**
 * Sections of a page: add from the template's section types, edit fields (validated against
 * the type's schema), style options, enable/disable, duplicate, delete, drag-and-drop order.
 */
final class SectionController extends SiteController
{
    public function list(Request $request): void
    {
        $svc = $this->site($request->params['id']);
        $page = $this->page($svc, (int) $request->params['pid']);
        $schemas = $svc->schemas();
        $rows = $svc->source()->sections((int) $page['id']);
        foreach ($rows as &$r) {
            $r['type_label'] = $schemas[$r['section_type']]['label'] ?? $r['section_type'];
            $r['icon'] = $schemas[$r['section_type']]['icon'] ?? 'bi-square';
            $r['summary'] = $this->summary($r['data']);
            $r['supported'] = isset($schemas[$r['section_type']]);
        }
        Response::json(['ok' => true, 'page' => $page, 'sections' => $rows]);
    }

    public function store(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $page = $this->page($svc, (int) $request->params['pid']);
        $type = $request->str('section_type');
        if (!isset($svc->schemas()[$type])) {
            Response::fail('This template does not support that section type.');
        }
        $db = $svc->db();
        $after = $request->int('after'); // insert after this section id, or at the end
        $order = (int) $db->value('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM ' . $db->t('sections') . ' WHERE page_id = ?', [(int) $page['id']]);
        if ($after > 0) {
            $pos = $db->value('SELECT sort_order FROM ' . $db->t('sections') . ' WHERE id = ? AND page_id = ?', [$after, (int) $page['id']]);
            if ($pos !== null) {
                $order = (int) $pos + 1;
                $db->query('UPDATE ' . $db->t('sections') . ' SET sort_order = sort_order + 1 WHERE page_id = ? AND sort_order >= ?', [(int) $page['id'], $order]);
            }
        }
        $id = $db->insert('sections', [
            'page_id' => (int) $page['id'], 'section_type' => $type,
            'data' => json_encode($svc->sectionTypes()->defaults($type), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'style' => '{}', 'is_enabled' => 1, 'sort_order' => $order, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->touch($svc);
        Activity::log('website.section_added', 'website', $svc->id(), $page['title'] . ' → ' . $type);
        Response::ok('Section added. Edit it to add your content.', ['id' => $id]);
    }

    /** Section data + its schema, for the editor. */
    public function show(Request $request): void
    {
        $svc = $this->site($request->params['id']);
        $s = $this->section($svc, (int) $request->params['sid']);
        $s['data'] = json_decode((string) $s['data'], true) ?: [];
        $s['style'] = json_decode((string) $s['style'], true) ?: [];
        Response::json(['ok' => true, 'section' => $s, 'schema' => $svc->schemas()[$s['section_type']] ?? null]);
    }

    public function update(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $s = $this->section($svc, (int) $request->params['sid']);
        if (!isset($svc->schemas()[$s['section_type']])) {
            Response::fail('This section type is not supported by the template any more. Delete it or switch templates.');
        }
        [$data, $errors] = $svc->sectionTypes()->sanitize($s['section_type'], $this->jsonInput('data'), $svc->richtext(), $svc->imageValidator());
        [$style, $styleErrors] = $this->cleanStyle($this->jsonInput('style'), $svc);
        $errors += $styleErrors;
        if ($errors) {
            Response::fail('Please correct the highlighted fields.', $errors);
        }
        $svc->db()->update('sections', [
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'style' => json_encode($style ?: new \stdClass(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'label' => mb_substr($request->str('label'), 0, 120) ?: null,
            'updated_at' => now(),
        ], (int) $s['id']);
        $this->touch($svc);
        Response::ok('Section saved.', ['data' => $data]);
    }

    public function toggle(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $s = $this->section($svc, (int) $request->params['sid']);
        $new = (int) $s['is_enabled'] ? 0 : 1;
        $svc->db()->update('sections', ['is_enabled' => $new, 'updated_at' => now()], (int) $s['id']);
        $this->touch($svc);
        Response::ok($new ? 'Section enabled.' : 'Section hidden. It stays here and can be enabled again.');
    }

    public function duplicate(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $s = $this->section($svc, (int) $request->params['sid']);
        $db = $svc->db();
        $db->query('UPDATE ' . $db->t('sections') . ' SET sort_order = sort_order + 1 WHERE page_id = ? AND sort_order > ?', [(int) $s['page_id'], (int) $s['sort_order']]);
        $id = $db->insert('sections', [
            'page_id' => (int) $s['page_id'], 'section_type' => $s['section_type'], 'label' => $s['label'] ? mb_substr($s['label'] . ' (copy)', 0, 120) : null,
            'data' => $s['data'], 'style' => $s['style'], 'is_enabled' => (int) $s['is_enabled'], 'sort_order' => (int) $s['sort_order'] + 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->touch($svc);
        Response::ok('Section duplicated.', ['id' => $id]);
    }

    public function destroy(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $s = $this->section($svc, (int) $request->params['sid']);
        $svc->db()->delete('sections', (int) $s['id']);
        $this->touch($svc);
        Activity::log('website.section_deleted', 'website', $svc->id(), $s['section_type']);
        Response::ok('Section deleted.');
    }

    public function reorder(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $page = $this->page($svc, (int) $request->params['pid']);
        $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? []))));
        $db = $svc->db();
        $db->transaction(static function ($db) use ($ids, $page): void {
            foreach ($ids as $i => $id) {
                $db->query('UPDATE ' . $db->t('sections') . ' SET sort_order = ? WHERE id = ? AND page_id = ?', [$i + 1, $id, (int) $page['id']]);
            }
        });
        $this->touch($svc);
        Response::ok('Section order saved.');
    }

    /** Style options common to every section. */
    private function cleanStyle(array $in, SiteService $svc): array
    {
        $errors = [];
        $out = [];
        $bg = trim((string) ($in['bg_color'] ?? ''));
        if ($bg !== '') {
            preg_match('/^#[0-9a-fA-F]{6}$/', $bg) ? $out['bg_color'] = $bg : $errors['style.bg_color'] = 'Background colour must look like #1A2B3C.';
        }
        $img = trim((string) ($in['bg_image'] ?? ''));
        if ($img !== '') {
            ($svc->imageValidator())($img) ? $out['bg_image'] = $img : $errors['style.bg_image'] = 'Choose the background image from the media library.';
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

    private function summary(array $data): string
    {
        foreach (['heading', 'title', 'eyebrow', 'form_heading'] as $k) {
            if (!empty($data[$k]) && is_string($data[$k])) {
                return mb_substr($data[$k], 0, 90);
            }
        }
        return '';
    }

    private function page(SiteService $svc, int $pid): array
    {
        $db = $svc->db();
        $p = $db->fetch('SELECT * FROM ' . $db->t('pages') . ' WHERE id = ?', [$pid]);
        if (!$p) {
            Response::abort(404, 'Page not found.');
        }
        return $p;
    }

    private function section(SiteService $svc, int $sid): array
    {
        $db = $svc->db();
        $s = $db->fetch('SELECT * FROM ' . $db->t('sections') . ' WHERE id = ?', [$sid]);
        if (!$s) {
            Response::abort(404, 'Section not found.');
        }
        return $s;
    }
}
