<?php
declare(strict_types=1);

namespace App\Modules\Categories;

use App\Core\Activity;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Uploader;
use App\Core\UploadException;
use App\Core\Validator;
use App\Core\View;

final class CategoryController
{
    private const SORTS = ['name' => 'c.name', 'sort_order' => 'c.sort_order', 'status' => 'c.status', 'templates' => 'templates', 'created_at' => 'c.created_at'];

    public function index(Request $request): void
    {
        View::render('categories/index', [
            'title' => 'Categories',
            'active' => 'categories',
            'scripts' => ['assets/vendor/sortablejs/Sortable.min.js', 'assets/js/pages/categories.js'],
        ]);
    }

    public function list(Request $request): void
    {
        $where = ['1=1'];
        $params = [];
        if ($q = $request->str('q')) {
            $where[] = '(c.name LIKE :q OR c.slug LIKE :q2 OR c.description LIKE :q3)';
            $params += ['q' => Database::like($q), 'q2' => Database::like($q), 'q3' => Database::like($q)];
        }
        if (in_array($request->str('status'), ['active', 'inactive'], true)) {
            $where[] = 'c.status = :status';
            $params['status'] = $request->str('status');
        }
        $sort = self::SORTS[$request->str('sort')] ?? 'c.sort_order';
        $dir = $request->str('dir') === 'desc' ? 'DESC' : 'ASC';

        $result = Database::paginate(
            'c.*, (SELECT COUNT(*) FROM wb_templates t WHERE t.category_id = c.id) AS templates,
                  (SELECT COUNT(*) FROM wb_websites w WHERE w.category_id = c.id) AS websites',
            'FROM wb_categories c WHERE ' . implode(' AND ', $where),
            $params,
            "$sort $dir, c.id ASC",
            $request->int('page', 1),
            $request->int('per_page', 50)
        );
        foreach ($result['rows'] as &$row) {
            $row['image_url'] = $row['image'] ? asset($row['image']) : null;
        }
        Response::json($result);
    }

    public function show(Request $request): void
    {
        $cat = $this->find($request->params['id']);
        $cat['image_url'] = $cat['image'] ? asset($cat['image']) : null;
        Response::json(['ok' => true, 'data' => $cat]);
    }

    public function store(Request $request): void
    {
        $data = $this->validated($request, null);
        $file = $request->file('image');
        try {
            $data['image'] = $file ? Uploader::image($file, 'categories') : null;
        } catch (UploadException $e) {
            Response::fail($e->getMessage(), ['image' => $e->getMessage()]);
        }
        $data['sort_order'] = $request->str('sort_order') !== '' ? $request->int('sort_order')
            : (int) Database::value('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM wb_categories');
        $data['created_at'] = $data['updated_at'] = now();
        $id = Database::insert('wb_categories', $data);
        $this->ensureTemplateFolder($data['slug']);
        Activity::log('category.created', 'category', $id, $data['name']);
        Response::ok('Category created.', ['id' => $id]);
    }

    public function update(Request $request): void
    {
        $cat = $this->find($request->params['id']);
        $data = $this->validated($request, (int) $cat['id']);

        $templateCount = (int) Database::value('SELECT COUNT(*) FROM wb_templates WHERE category_id = ?', [$cat['id']]);
        if ($data['slug'] !== $cat['slug'] && $templateCount > 0) {
            Response::fail('The slug cannot change while templates exist in this category (it is their folder name).', ['slug' => 'Slug is locked because this category has templates.']);
        }

        $file = $request->file('image');
        try {
            if ($file) {
                $data['image'] = Uploader::image($file, 'categories');
                Uploader::delete($cat['image']);
            } elseif ($request->str('remove_image') === '1') {
                $data['image'] = null;
                Uploader::delete($cat['image']);
            }
        } catch (UploadException $e) {
            Response::fail($e->getMessage(), ['image' => $e->getMessage()]);
        }
        if ($request->str('sort_order') !== '') {
            $data['sort_order'] = $request->int('sort_order');
        }
        $data['updated_at'] = now();
        Database::update('wb_categories', $data, (int) $cat['id']);
        if ($data['slug'] !== $cat['slug']) {
            $this->ensureTemplateFolder($data['slug']);
        }
        Activity::log('category.updated', 'category', (int) $cat['id'], $data['name']);
        Response::ok('Category updated.');
    }

    public function toggleStatus(Request $request): void
    {
        $cat = $this->find($request->params['id']);
        $new = $cat['status'] === 'active' ? 'inactive' : 'active';
        Database::update('wb_categories', ['status' => $new, 'updated_at' => now()], (int) $cat['id']);
        Activity::log('category.' . ($new === 'active' ? 'activated' : 'deactivated'), 'category', (int) $cat['id'], $cat['name']);
        Response::ok('Category ' . ($new === 'active' ? 'activated.' : 'deactivated.'), ['status' => $new]);
    }

    public function destroy(Request $request): void
    {
        $cat = $this->find($request->params['id']);
        $templates = (int) Database::value('SELECT COUNT(*) FROM wb_templates WHERE category_id = ?', [$cat['id']]);
        $websites = (int) Database::value('SELECT COUNT(*) FROM wb_websites WHERE category_id = ?', [$cat['id']]);
        if ($templates > 0 || $websites > 0) {
            Response::fail("This category cannot be deleted: it has $templates template(s) and $websites website(s). Move or delete them first, or deactivate the category instead.");
        }
        Database::delete('wb_categories', (int) $cat['id']);
        Uploader::delete($cat['image']);
        // Remove the empty template folder for this category (only if empty).
        $dir = TEMPLATES_PATH . '/' . $cat['slug'];
        if (is_dir($dir) && count(array_diff((array) scandir($dir), ['.', '..', '.gitkeep'])) === 0) {
            @unlink($dir . '/.gitkeep');
            @rmdir($dir);
        }
        Activity::log('category.deleted', 'category', (int) $cat['id'], $cat['name']);
        Response::ok('Category deleted.');
    }

    /** Drag-and-drop ordering: receives ids[] in the new order. */
    public function reorder(Request $request): void
    {
        $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? []))));
        if (!$ids) {
            Response::fail('Nothing to reorder.');
        }
        Database::transaction(static function () use ($ids): void {
            foreach ($ids as $i => $id) {
                Database::query('UPDATE wb_categories SET sort_order = ?, updated_at = ? WHERE id = ?', [$i + 1, now(), $id]);
            }
        });
        Activity::log('category.reordered', 'category', null, implode(',', $ids));
        Response::ok('Order saved.');
    }

    private function validated(Request $request, ?int $ignoreId): array
    {
        $input = $request->all();
        $input['slug'] = $request->str('slug') !== '' ? strtolower($request->str('slug')) : slugify($request->str('name'));
        $v = Validator::make($input, [
            'name' => 'required|max:120|unique:wb_categories,name' . ($ignoreId ? ",$ignoreId" : ''),
            // Slug is derived from the name when left blank, so only demand it once a name exists.
            'slug' => ($request->str('name') !== '' ? 'required|' : '') . 'slug|max:60|unique:wb_categories,slug' . ($ignoreId ? ",$ignoreId" : ''),
            'description' => 'max:2000',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'integer',
        ]);
        if ($v->fails()) {
            Response::fail('Please correct the highlighted fields.', $v->errors());
        }
        return [
            'name' => $request->str('name'),
            'slug' => $input['slug'],
            'description' => $request->str('description') ?: null,
            'status' => $request->str('status'),
        ];
    }

    private function find(int $id): array
    {
        $cat = Database::fetch('SELECT * FROM wb_categories WHERE id = ?', [$id]);
        if (!$cat) {
            Response::abort(404, 'Category not found.');
        }
        return $cat;
    }

    private function ensureTemplateFolder(string $slug): void
    {
        $dir = TEMPLATES_PATH . '/' . $slug;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
            @touch($dir . '/.gitkeep');
        }
    }
}
