<?php
declare(strict_types=1);

namespace App\Modules\Templates;

use App\Core\Activity;
use App\Core\Database;
use App\Core\FileSystem;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Uploader;
use App\Core\UploadException;
use App\Core\Validator;
use App\Core\View;

final class TemplateController
{
    private const SORTS = ['name' => 't.name', 'code' => 't.code', 'category' => 'cat.name', 'status' => 't.status', 'updated_at' => 't.updated_at', 'websites' => 'websites'];

    private TemplateService $svc;

    public function __construct()
    {
        $this->svc = new TemplateService();
    }

    public function index(Request $request): void
    {
        View::render('templates/index', [
            'title' => 'Templates',
            'active' => 'templates',
            'categories' => Database::fetchAll('SELECT id, name, slug FROM wb_categories ORDER BY sort_order, name'),
            'selectedCategory' => $request->int('category'),
            'scripts' => ['assets/js/pages/templates.js'],
        ]);
    }

    public function list(Request $request): void
    {
        $where = ['1=1'];
        $params = [];
        if ($q = $request->str('q')) {
            $where[] = '(t.name LIKE :q1 OR t.code LIKE :q2 OR t.description LIKE :q3)';
            $params += ['q1' => Database::like($q), 'q2' => Database::like($q), 'q3' => Database::like($q)];
        }
        if ($request->int('category') > 0) {
            $where[] = 't.category_id = :cat';
            $params['cat'] = $request->int('category');
        }
        if (in_array($request->str('status'), ['active', 'inactive'], true)) {
            $where[] = 't.status = :status';
            $params['status'] = $request->str('status');
        }
        $sort = self::SORTS[$request->str('sort')] ?? 't.updated_at';
        $dir = $request->str('dir') === 'asc' ? 'ASC' : 'DESC';
        $result = Database::paginate(
            't.id, t.code, t.name, t.description, t.preview_image, t.status, t.current_version, t.updated_at, cat.name AS category,
             (SELECT COUNT(*) FROM wb_websites w WHERE w.template_id = t.id) AS websites,
             (SELECT COUNT(*) FROM wb_template_versions v WHERE v.template_id = t.id) AS versions',
            'FROM wb_templates t JOIN wb_categories cat ON cat.id = t.category_id WHERE ' . implode(' AND ', $where),
            $params,
            "$sort $dir, t.id DESC",
            $request->int('page', 1),
            $request->int('per_page', 15)
        );
        foreach ($result['rows'] as &$r) {
            $r['preview_url'] = $r['preview_image'] ? asset($r['preview_image']) : null;
        }
        Response::json($result);
    }

    public function show(Request $request): void
    {
        $tpl = $this->find($request->params['id']);
        $folder = BASE_PATH . '/' . $tpl['folder_path'];
        $folderExists = is_dir($folder);
        $report = $folderExists
            ? $this->svc->validate($folder, $tpl['code'], $tpl['category_slug'])
            : null;
        View::render('templates/show', [
            'title' => $tpl['name'],
            'active' => 'templates',
            'tpl' => $tpl,
            'folderExists' => $folderExists,
            'report' => $report?->report() ?? ['ok' => false, 'errors' => ['Template folder is missing: ' . $tpl['folder_path']], 'warnings' => []],
            'manifest' => $report?->manifest(),
            'versions' => Database::fetchAll('SELECT v.*, u.name AS user FROM wb_template_versions v LEFT JOIN wb_users u ON u.id = v.created_by WHERE v.template_id = ? ORDER BY v.id DESC', [$tpl['id']]),
            'screens' => Database::fetchAll('SELECT * FROM wb_template_screens WHERE template_id = ? ORDER BY sort_order, id', [$tpl['id']]),
            'websites' => (int) Database::value('SELECT COUNT(*) FROM wb_websites WHERE template_id = ?', [$tpl['id']]),
            'categories' => Database::fetchAll('SELECT id, name FROM wb_categories ORDER BY sort_order, name'),
            'scripts' => ['assets/js/pages/templates.js'],
        ]);
    }

    public function json(Request $request): void
    {
        $tpl = $this->find($request->params['id']);
        $tpl['preview_url'] = $tpl['preview_image'] ? asset($tpl['preview_image']) : null;
        Response::json(['ok' => true, 'data' => $tpl]);
    }

    /** Upload a brand-new template as a ZIP. */
    public function upload(Request $request): void
    {
        $file = $request->file('package');
        if (!$file) {
            Response::fail('Choose a template ZIP to upload.', ['package' => 'Choose a ZIP file.']);
        }
        $tmp = null;
        try {
            [$tmp, $root] = $this->svc->extractUpload($file);
            $v = $this->svc->validate($root);
            if (!$v->ok()) {
                Response::json(['ok' => false, 'message' => 'The template did not pass validation.', 'report' => $v->report()], 422);
            }
            $m = $v->manifest();
            if (Database::value('SELECT id FROM wb_templates WHERE code = ?', [$m['id']])) {
                Response::fail("A template with the id \"{$m['id']}\" already exists. To update it, open the template and upload a new version.");
            }
            $target = $this->svc->folderFor($m['category'], $m['id']);
            if (is_dir($target)) {
                Response::fail("The folder templates/{$m['category']}/{$m['id']} already exists on the server. Use “Scan folders” to register it instead.");
            }
            $this->svc->move($root, $target);
            $id = $this->svc->register($m, $target);
            Activity::log('template.uploaded', 'template', $id, $m['id'] . ' v' . $m['version']);
            Response::ok('Template uploaded and registered as Inactive. Review it, then activate.', ['redirect' => url('templates/' . $id), 'report' => $v->report()]);
        } catch (UploadException $e) {
            Response::fail($e->getMessage(), ['package' => $e->getMessage()]);
        } finally {
            $this->svc->cleanup($tmp);
        }
    }

    /** List template folders on disk that are not registered yet (e.g. uploaded via FTP / File Manager). */
    public function scan(Request $request): void
    {
        $registered = array_column(Database::fetchAll('SELECT folder_path FROM wb_templates'), 'folder_path');
        $found = [];
        foreach ($this->svc->categorySlugs() as $slug) {
            $catDir = TEMPLATES_PATH . '/' . $slug;
            if (!is_dir($catDir)) {
                continue;
            }
            foreach ((array) scandir($catDir) as $entry) {
                if ($entry === '.' || $entry === '..' || !is_dir("$catDir/$entry") || is_link("$catDir/$entry")) {
                    continue;
                }
                $rel = "templates/$slug/$entry";
                if (in_array($rel, $registered, true)) {
                    continue;
                }
                $v = $this->svc->validate("$catDir/$entry", $entry, $slug);
                $found[] = ['category' => $slug, 'folder' => $entry, 'path' => $rel, 'name' => $v->manifest()['name'] ?? $entry, 'version' => $v->manifest()['version'] ?? '—', 'report' => $v->report()];
            }
        }
        Response::json(['ok' => true, 'items' => $found]);
    }

    public function register(Request $request): void
    {
        $cat = $request->str('category');
        $folder = $request->str('folder');
        if (!preg_match(ManifestValidator::ID_PATTERN, $folder) || !in_array($cat, $this->svc->categorySlugs(), true)) {
            Response::fail('Invalid template folder.');
        }
        $dir = FileSystem::within(TEMPLATES_PATH, TEMPLATES_PATH . "/$cat/$folder");
        if ($dir === null || !is_dir($dir)) {
            Response::fail('Template folder not found.');
        }
        if (Database::value('SELECT id FROM wb_templates WHERE code = ?', [$folder])) {
            Response::fail("A template with the id \"$folder\" is already registered.");
        }
        $v = $this->svc->validate($dir, $folder, $cat);
        if (!$v->ok()) {
            Response::json(['ok' => false, 'message' => 'The template did not pass validation.', 'report' => $v->report()], 422);
        }
        $id = $this->svc->register($v->manifest(), $dir);
        Activity::log('template.registered', 'template', $id, $folder);
        Response::ok('Template registered as Inactive.', ['redirect' => url('templates/' . $id)]);
    }

    public function update(Request $request): void
    {
        $tpl = $this->find($request->params['id']);
        $v = Validator::make($request->all(), [
            'name' => 'required|max:150',
            'description' => 'max:5000',
            'category_id' => 'required|integer|exists:wb_categories,id',
        ]);
        if ($v->fails()) {
            Response::fail('Please correct the highlighted fields.', $v->errors());
        }
        $data = ['name' => $request->str('name'), 'description' => $request->str('description') ?: null, 'updated_at' => now()];

        // Moving to another category moves the folder and rewrites manifest.category.
        $newCatId = $request->int('category_id');
        if ($newCatId !== (int) $tpl['category_id']) {
            if ((int) Database::value('SELECT COUNT(*) FROM wb_websites WHERE template_id = ?', [$tpl['id']]) > 0) {
                Response::fail('This template is used by websites, so its category cannot change.', ['category_id' => 'Locked: template in use.']);
            }
            $newCat = Database::fetch('SELECT * FROM wb_categories WHERE id = ?', [$newCatId]);
            $from = $this->svc->absoluteFolder($tpl);
            $to = $this->svc->folderFor($newCat['slug'], $tpl['code']);
            if (is_dir($to)) {
                Response::fail('A folder with this template id already exists in the target category.');
            }
            $this->svc->move($from, $to);
            $this->svc->updateManifest($to, ['category' => $newCat['slug']]);
            $data['category_id'] = $newCatId;
            $data['folder_path'] = $this->svc->relativeFolder($to);
        }

        $file = $request->file('preview_image');
        try {
            if ($file) {
                $data['preview_image'] = Uploader::image($file, 'templates', 8_388_608, 6000);
                Uploader::delete($tpl['preview_image']);
            }
        } catch (UploadException $e) {
            Response::fail($e->getMessage(), ['preview_image' => $e->getMessage()]);
        }
        Database::update('wb_templates', $data, (int) $tpl['id']);
        Activity::log('template.updated', 'template', (int) $tpl['id'], $data['name']);
        Response::ok('Template updated.');
    }

    public function toggleStatus(Request $request): void
    {
        $tpl = $this->find($request->params['id']);
        $new = $tpl['status'] === 'active' ? 'inactive' : 'active';
        if ($new === 'active') {
            $v = $this->svc->validate($this->svc->absoluteFolder($tpl), $tpl['code'], $tpl['category_slug']);
            if (!$v->ok()) {
                Response::json(['ok' => false, 'message' => 'Fix the validation errors before activating this template.', 'report' => $v->report()], 422);
            }
        }
        Database::update('wb_templates', ['status' => $new, 'updated_at' => now()], (int) $tpl['id']);
        Activity::log('template.' . ($new === 'active' ? 'activated' : 'deactivated'), 'template', (int) $tpl['id'], $tpl['code']);
        Response::ok('Template ' . ($new === 'active' ? 'activated.' : 'deactivated.'));
    }

    public function revalidate(Request $request): void
    {
        $tpl = $this->find($request->params['id']);
        $v = $this->svc->validate($this->svc->absoluteFolder($tpl), $tpl['code'], $tpl['category_slug']);
        Response::json(['ok' => true, 'message' => $v->ok() ? 'Template is valid.' : 'Template has validation errors.', 'report' => $v->report()]);
    }

    public function duplicate(Request $request): void
    {
        $tpl = $this->find($request->params['id']);
        $code = strtolower($request->str('code'));
        $v = Validator::make(['code' => $code, 'name' => $request->str('name')], [
            'code' => 'required|max:80|unique:wb_templates,code',
            'name' => 'required|max:150',
        ], ['code' => 'Template id']);
        $errors = $v->errors();
        if (!isset($errors['code']) && !preg_match(ManifestValidator::ID_PATTERN, $code)) {
            $errors['code'] = 'Use lowercase letters, numbers and underscores, e.g. business_template_02.';
        }
        if ($errors) {
            Response::fail('Please correct the highlighted fields.', $errors);
        }
        $src = $this->svc->absoluteFolder($tpl);
        $target = $this->svc->folderFor($tpl['category_slug'], $code);
        if (is_dir($target)) {
            Response::fail('A folder with this id already exists.', ['code' => 'Folder already exists.']);
        }
        $staging = $this->svc->tempPath('dup');
        try {
            FileSystem::copyDir($src, $staging);
            $this->svc->updateManifest($staging, ['id' => $code, 'name' => $request->str('name'), 'version' => '1.0.0']);
            $check = $this->svc->validate($staging, $code, $tpl['category_slug']);
            if (!$check->ok()) {
                Response::json(['ok' => false, 'message' => 'The copy did not pass validation.', 'report' => $check->report()], 422);
            }
            $this->svc->move($staging, $target);
            $staging = null;
            $newId = $this->svc->register($check->manifest(), $target);
            Database::update('wb_templates', ['description' => $tpl['description']], $newId);
            // Copy screenshots
            foreach (Database::fetchAll('SELECT * FROM wb_template_screens WHERE template_id = ?', [$tpl['id']]) as $s) {
                $copy = $this->copyPublicImage($s['image'], 'templates/screens');
                if ($copy) {
                    Database::insert('wb_template_screens', ['template_id' => $newId, 'image' => $copy, 'caption' => $s['caption'], 'sort_order' => $s['sort_order'], 'created_at' => now()]);
                }
            }
            if (!Database::value('SELECT preview_image FROM wb_templates WHERE id = ?', [$newId]) && $tpl['preview_image']) {
                Database::update('wb_templates', ['preview_image' => $this->copyPublicImage($tpl['preview_image'], 'templates')], $newId);
            }
        } finally {
            if ($staging) {
                $this->svc->cleanup($staging);
            }
        }
        Activity::log('template.duplicated', 'template', $newId, $tpl['code'] . ' → ' . $code);
        Response::ok('Template duplicated as Inactive.', ['redirect' => url('templates/' . $newId)]);
    }

    /** Upload a new version of an existing template (same id, higher version number). */
    public function uploadVersion(Request $request): void
    {
        $tpl = $this->find($request->params['id']);
        $file = $request->file('package');
        if (!$file) {
            Response::fail('Choose a ZIP file.', ['package' => 'Choose a ZIP file.']);
        }
        $tmp = null;
        try {
            [$tmp, $root] = $this->svc->extractUpload($file);
            $v = $this->svc->validate($root, $tpl['code'], $tpl['category_slug']);
            if (!$v->ok()) {
                Response::json(['ok' => false, 'message' => 'The new version did not pass validation.', 'report' => $v->report()], 422);
            }
            $m = $v->manifest();
            if (version_compare($m['version'], $tpl['current_version'], '<=')) {
                Response::fail("The new version ({$m['version']}) must be higher than the current version ({$tpl['current_version']}). Update \"version\" in manifest.json.");
            }
            $this->svc->swapFolder($this->svc->absoluteFolder($tpl), $root);
            Database::update('wb_templates', ['current_version' => $m['version'], 'updated_at' => now(),
                'features' => json_encode(['features' => $m['features'] ?? [], 'optional' => $m['optional_features'] ?? []])], (int) $tpl['id']);
            $this->svc->snapshot((int) $tpl['id'], $m['version'], $request->str('changelog') ?: null);
            Activity::log('template.version_uploaded', 'template', (int) $tpl['id'], $tpl['code'] . ' v' . $m['version']);
            Response::ok("Version {$m['version']} is now current.", ['report' => $v->report()]);
        } catch (UploadException $e) {
            Response::fail($e->getMessage(), ['package' => $e->getMessage()]);
        } finally {
            $this->svc->cleanup($tmp);
        }
    }

    public function restoreVersion(Request $request): void
    {
        $tpl = $this->find($request->params['id']);
        $ver = Database::fetch('SELECT * FROM wb_template_versions WHERE id = ? AND template_id = ?', [$request->params['vid'], $tpl['id']]);
        if (!$ver || !$ver['archive_path']) {
            Response::abort(404, 'Version not found.');
        }
        $zip = FileSystem::within(TemplateService::BACKUP_DIR, BASE_PATH . '/' . $ver['archive_path']);
        if ($zip === null || !is_file($zip)) {
            Response::fail('The archive for this version is missing from storage/backups.');
        }
        $tmp = $this->svc->tempPath('rst');
        try {
            FileSystem::safeExtract($zip, $tmp);
            // The snapshot may predate a category move; the template's current category wins.
            $old = json_decode((string) @file_get_contents("$tmp/manifest.json"), true);
            if (is_array($old) && ($old['category'] ?? null) !== $tpl['category_slug']) {
                $this->svc->updateManifest($tmp, ['category' => $tpl['category_slug']]);
            }
            $v = $this->svc->validate($tmp, $tpl['code'], $tpl['category_slug']);
            if (!$v->ok()) {
                Response::json(['ok' => false, 'message' => 'That version no longer passes validation and cannot be restored.', 'report' => $v->report()], 422);
            }
            $this->svc->swapFolder($this->svc->absoluteFolder($tpl), $tmp);
            $tmp = null;
            Database::update('wb_templates', ['current_version' => $ver['version'], 'updated_at' => now()], (int) $tpl['id']);
        } finally {
            $this->svc->cleanup($tmp);
        }
        Activity::log('template.version_restored', 'template', (int) $tpl['id'], $tpl['code'] . ' v' . $ver['version']);
        Response::ok("Version {$ver['version']} restored as the current version.", ['redirect' => url('templates/' . $tpl['id'])]);
    }

    public function downloadVersion(Request $request): void
    {
        $tpl = $this->find($request->params['id']);
        $ver = Database::fetch('SELECT * FROM wb_template_versions WHERE id = ? AND template_id = ?', [$request->params['vid'], $tpl['id']]);
        $zip = $ver && $ver['archive_path'] ? FileSystem::within(TemplateService::BACKUP_DIR, BASE_PATH . '/' . $ver['archive_path']) : null;
        if ($zip === null || !is_file($zip)) {
            Response::abort(404, 'Version archive not found.');
        }
        Activity::log('template.version_downloaded', 'template', (int) $tpl['id'], $tpl['code'] . ' v' . $ver['version']);
        header('Content-Type: application/zip');
        header('Content-Length: ' . filesize($zip));
        header('Content-Disposition: attachment; filename="' . $tpl['code'] . '-v' . $ver['version'] . '.zip"');
        header('Cache-Control: no-store');
        readfile($zip);
        exit;
    }

    public function addScreens(Request $request): void
    {
        $tpl = $this->find($request->params['id']);
        $files = $request->files('screens');
        if (!$files) {
            Response::fail('Choose one or more screenshots.', ['screens' => 'Choose at least one image.']);
        }
        if (count($files) > 20) {
            Response::fail('Upload at most 20 screenshots at a time.');
        }
        $order = (int) Database::value('SELECT COALESCE(MAX(sort_order), 0) FROM wb_template_screens WHERE template_id = ?', [$tpl['id']]);
        $saved = 0;
        $errors = [];
        foreach ($files as $f) {
            try {
                $path = Uploader::image($f, 'templates/screens', 8_388_608, 8000);
                Database::insert('wb_template_screens', ['template_id' => $tpl['id'], 'image' => $path, 'caption' => mb_substr($request->str('caption'), 0, 190) ?: null, 'sort_order' => ++$order, 'created_at' => now()]);
                $saved++;
            } catch (UploadException $e) {
                $errors[] = $f['name'] . ': ' . $e->getMessage();
            }
        }
        Activity::log('template.screens_added', 'template', (int) $tpl['id'], "$saved screenshot(s)");
        if (!$saved) {
            Response::fail(implode(' ', $errors), ['screens' => implode(' ', $errors)]);
        }
        Response::ok("$saved screenshot(s) added." . ($errors ? ' Skipped: ' . implode(' ', $errors) : ''), ['redirect' => url('templates/' . $tpl['id'])]);
    }

    public function deleteScreen(Request $request): void
    {
        $tpl = $this->find($request->params['id']);
        $s = Database::fetch('SELECT * FROM wb_template_screens WHERE id = ? AND template_id = ?', [$request->params['sid'], $tpl['id']]);
        if (!$s) {
            Response::abort(404, 'Screenshot not found.');
        }
        Database::delete('wb_template_screens', (int) $s['id']);
        Uploader::delete($s['image']);
        Response::ok('Screenshot removed.', ['redirect' => url('templates/' . $tpl['id'])]);
    }

    /** Delete a template. Its folder is archived to storage/backups before removal. */
    public function destroy(Request $request): void
    {
        $tpl = $this->find($request->params['id']);
        $sites = (int) Database::value('SELECT COUNT(*) FROM wb_websites WHERE template_id = ?', [$tpl['id']]);
        if ($sites > 0) {
            Response::fail("This template is used by $sites website(s) and cannot be deleted. Deactivate it instead.");
        }
        $folder = BASE_PATH . '/' . $tpl['folder_path'];
        if (is_dir($folder)) {
            $dir = TemplateService::BACKUP_DIR . '/deleted';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            FileSystem::zipDir($folder, $dir . '/' . $tpl['code'] . '-' . date('Ymd-His') . '.zip');
            FileSystem::deleteDir($this->svc->absoluteFolder($tpl));
        }
        foreach (Database::fetchAll('SELECT image FROM wb_template_screens WHERE template_id = ?', [$tpl['id']]) as $s) {
            Uploader::delete($s['image']);
        }
        Uploader::delete($tpl['preview_image']);
        Database::delete('wb_templates', (int) $tpl['id']);
        Activity::log('template.deleted', 'template', (int) $tpl['id'], $tpl['code'] . ' (folder archived to storage/backups/templates/deleted)');
        Logger::info('Template deleted', ['code' => $tpl['code']]);
        Response::ok('Template deleted. A backup ZIP of its folder was kept in storage/backups/templates/deleted.', ['redirect' => $request->str('from') === 'show' ? url('templates') : null]);
    }

    private function copyPublicImage(?string $relative, string $folder): ?string
    {
        if (!$relative) {
            return null;
        }
        $src = FileSystem::within(PUBLIC_PATH . '/uploads', PUBLIC_PATH . '/' . $relative);
        if ($src === null || !is_file($src)) {
            return null;
        }
        $dir = PUBLIC_PATH . '/uploads/' . $folder;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = bin2hex(random_bytes(12)) . '.' . pathinfo($src, PATHINFO_EXTENSION);
        return copy($src, "$dir/$name") ? "uploads/$folder/$name" : null;
    }

    private function find(int $id): array
    {
        $t = Database::fetch('SELECT t.*, cat.slug AS category_slug, cat.name AS category_name FROM wb_templates t JOIN wb_categories cat ON cat.id = t.category_id WHERE t.id = ?', [$id]);
        if (!$t) {
            Response::abort(404, 'Template not found.');
        }
        return $t;
    }
}
