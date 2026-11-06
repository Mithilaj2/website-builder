<?php
declare(strict_types=1);

namespace App\Modules\Websites;

use App\Core\Activity;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;

/** Pages of one website: add, rename, slug, homepage, order, status, menu visibility, SEO. */
final class PageController extends SiteController
{
    private const RESERVED = ['admin', 'core', 'config', 'uploads', 'theme', 'assets', 'storage', 'database', 'vendor', 'install', 'index', 'api', 'form', 'forms'];

    public function list(Request $request): void
    {
        $svc = $this->site($request->params['id']);
        $db = $svc->db();
        $pages = $db->fetchAll(
            'SELECT p.*, (SELECT COUNT(*) FROM ' . $db->t('sections') . ' s WHERE s.page_id = p.id) AS sections,
                    (SELECT COUNT(*) FROM ' . $db->t('sections') . ' s WHERE s.page_id = p.id AND s.is_enabled = 1) AS enabled_sections
             FROM ' . $db->t('pages') . ' p ORDER BY p.sort_order, p.id'
        );
        Response::json(['ok' => true, 'pages' => $pages]);
    }

    public function store(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $data = $this->validated($request, $svc, null);
        $db = $svc->db();
        $data['sort_order'] = (int) $db->value('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM ' . $db->t('pages'));
        $data['created_at'] = $data['updated_at'] = now();
        $data['is_home'] = 0;
        $pageId = $db->insert('pages', $data);

        // Start the page with the template's default sections for its type.
        $types = $svc->sectionTypes();
        foreach ((array) ($svc->manifest()['page_types'][$data['page_type']]['default_sections'] ?? []) as $i => $type) {
            if ($types->get($type)) {
                $defaults = $types->defaults($type);
                if ($type === 'page_banner') {
                    $defaults['heading'] = $data['title'];
                }
                $db->insert('sections', ['page_id' => $pageId, 'section_type' => $type, 'data' => json_encode($defaults, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'style' => '{}', 'is_enabled' => 1, 'sort_order' => $i + 1, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        $this->touch($svc);
        Activity::log('website.page_added', 'website', $svc->id(), $data['title']);
        Response::ok('Page added with the template\'s default sections.', ['id' => $pageId]);
    }

    public function update(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $page = $this->page($svc, (int) $request->params['pid']);
        $data = $this->validated($request, $svc, $page);
        $data['updated_at'] = now();
        $svc->db()->update('pages', $data, (int) $page['id']);
        $this->touch($svc);
        Activity::log('website.page_updated', 'website', $svc->id(), $data['title']);
        Response::ok('Page saved.');
    }

    public function setHome(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $page = $this->page($svc, (int) $request->params['pid']);
        if ($page['status'] !== 'active') {
            Response::fail('Only an active page can be the homepage.');
        }
        $db = $svc->db();
        $db->transaction(static function ($db) use ($page): void {
            $db->query('UPDATE ' . $db->t('pages') . ' SET is_home = 0');
            $db->query('UPDATE ' . $db->t('pages') . ' SET is_home = 1 WHERE id = ?', [(int) $page['id']]);
        });
        $this->touch($svc);
        Activity::log('website.homepage_changed', 'website', $svc->id(), $page['title']);
        Response::ok('“' . $page['title'] . '” is now the homepage.');
    }

    public function toggle(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $page = $this->page($svc, (int) $request->params['pid']);
        if ((int) $page['is_home'] === 1 && $page['status'] === 'active') {
            Response::fail('The homepage cannot be deactivated. Set another page as the homepage first.');
        }
        $new = $page['status'] === 'active' ? 'inactive' : 'active';
        $svc->db()->update('pages', ['status' => $new, 'updated_at' => now()], (int) $page['id']);
        $this->touch($svc);
        Response::ok('Page ' . ($new === 'active' ? 'activated.' : 'deactivated.'));
    }

    public function reorder(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? []))));
        $db = $svc->db();
        $db->transaction(static function ($db) use ($ids): void {
            foreach ($ids as $i => $id) {
                $db->query('UPDATE ' . $db->t('pages') . ' SET sort_order = ? WHERE id = ?', [$i + 1, $id]);
            }
        });
        $this->touch($svc);
        Response::ok('Page order saved. The menu follows this order.');
    }

    public function destroy(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $page = $this->page($svc, (int) $request->params['pid']);
        if ((int) $page['is_home'] === 1) {
            Response::fail('The homepage cannot be deleted. Set another page as the homepage first.');
        }
        $svc->db()->delete('pages', (int) $page['id']); // sections cascade
        $this->touch($svc);
        Activity::log('website.page_deleted', 'website', $svc->id(), $page['title']);
        Response::ok('Page deleted.');
    }

    private function validated(Request $request, SiteService $svc, ?array $page): array
    {
        $input = $request->all();
        $isHome = $page && (int) $page['is_home'] === 1;
        $input['slug'] = strtolower(trim($request->str('slug'), '/ '));
        if ($input['slug'] === '' && !$isHome) {
            $input['slug'] = slugify($request->str('title'));
        }
        $types = array_keys((array) ($svc->manifest()['page_types'] ?? []));
        $v = Validator::make($input, [
            'title' => 'required|max:150',
            'page_type' => 'required|in:' . implode(',', $types),
            'seo_title' => 'max:190',
            'seo_description' => 'max:320',
            'seo_keywords' => 'max:255',
        ], ['page_type' => 'Page layout', 'seo_title' => 'SEO title', 'seo_description' => 'SEO description', 'seo_keywords' => 'SEO keywords']);
        $errors = $v->errors();
        $slug = $input['slug'];
        if (!$isHome || $slug !== '') {
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || strlen($slug) > 120) {
                $errors['slug'] = 'URL may contain only lowercase letters, numbers and single hyphens.';
            } elseif (in_array($slug, self::RESERVED, true)) {
                $errors['slug'] = "“{$slug}” is reserved by the website system. Choose another URL.";
            } else {
                $db = $svc->db();
                $exists = (int) $db->value('SELECT COUNT(*) FROM ' . $db->t('pages') . ' WHERE slug = ? AND id <> ?', [$slug, (int) ($page['id'] ?? 0)]);
                if ($exists) {
                    $errors['slug'] = 'Another page already uses this URL.';
                }
            }
        }
        if ($errors) {
            Response::fail('Please correct the highlighted fields.', $errors);
        }
        return [
            'title' => $request->str('title'),
            'slug' => $slug,
            'page_type' => $request->str('page_type'),
            'show_in_menu' => $request->str('show_in_menu') === '1' ? 1 : 0,
            'show_in_footer' => $request->str('show_in_footer') === '1' ? 1 : 0,
            'seo_title' => $request->str('seo_title') ?: null,
            'seo_description' => $request->str('seo_description') ?: null,
            'seo_keywords' => $request->str('seo_keywords') ?: null,
        ];
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
}
