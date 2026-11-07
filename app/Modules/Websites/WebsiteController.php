<?php
declare(strict_types=1);

namespace App\Modules\Websites;

use App\Core\Activity;
use App\Core\Auth;
use App\Core\Database;
use App\Core\FileSystem;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;
use SiteRuntime\Theme;

final class WebsiteController extends SiteController
{
    private const STATUSES = ['draft', 'config_pending', 'generating', 'generated', 'testing', 'ready', 'deployed', 'archived', 'failed'];
    private const SORTS = ['name' => 'w.name', 'client' => 'c.business_name', 'status' => 'w.status', 'created_at' => 'w.created_at', 'updated_at' => 'w.updated_at'];

    public function index(Request $request): void
    {
        View::render('websites/index', [
            'title' => 'Websites',
            'active' => 'websites',
            'statuses' => self::STATUSES,
            'clients' => Database::fetchAll("SELECT id, business_name FROM wb_clients WHERE status = 'active' ORDER BY business_name"),
            'categories' => Database::fetchAll("SELECT id, name FROM wb_categories WHERE status = 'active' ORDER BY sort_order, name"),
            'preselectClient' => $request->int('client'),
            'scripts' => ['assets/js/pages/websites.js'],
        ]);
    }

    public function list(Request $request): void
    {
        $where = ['1=1'];
        $params = [];
        if ($q = $request->str('q')) {
            $where[] = '(w.name LIKE :q1 OR c.business_name LIKE :q2 OR w.domain LIKE :q3 OR w.site_code LIKE :q4)';
            foreach (['q1', 'q2', 'q3', 'q4'] as $k) {
                $params[$k] = Database::like($q);
            }
        }
        if (in_array($request->str('status'), self::STATUSES, true)) {
            $where[] = 'w.status = :status';
            $params['status'] = $request->str('status');
        }
        if ($request->int('client') > 0) {
            $where[] = 'w.client_id = :client';
            $params['client'] = $request->int('client');
        }
        $sort = self::SORTS[$request->str('sort')] ?? 'w.updated_at';
        $dir = $request->str('dir') === 'asc' ? 'ASC' : 'DESC';
        Response::json(Database::paginate(
            'w.id, w.site_code, w.name, w.domain, w.status, w.created_at, w.updated_at, c.business_name AS client, cat.name AS category, t.name AS template',
            'FROM wb_websites w JOIN wb_clients c ON c.id = w.client_id JOIN wb_categories cat ON cat.id = w.category_id LEFT JOIN wb_templates t ON t.id = w.template_id WHERE ' . implode(' AND ', $where),
            $params,
            "$sort $dir, w.id DESC",
            $request->int('page', 1),
            $request->int('per_page', 15)
        ));
    }

    /** Active templates of a category (for the "new website" form). */
    public function templatesFor(Request $request): void
    {
        $rows = Database::fetchAll(
            "SELECT id, code, name, description, preview_image FROM wb_templates WHERE category_id = ? AND status = 'active' ORDER BY name",
            [$request->int('category')]
        );
        foreach ($rows as &$r) {
            $r['preview_url'] = $r['preview_image'] ? asset($r['preview_image']) : null;
            $r['preview_link'] = url('templates/' . $r['id'] . '/preview');
        }
        Response::json(['ok' => true, 'templates' => $rows]);
    }

    public function store(Request $request): void
    {
        // Step 1 may create the client inline.
        if ($request->str('client_mode') === 'new') {
            $cv = Validator::make($request->all(), [
                'new_business_name' => 'required|max:190', 'new_contact_person' => 'max:120', 'new_email' => 'email|max:190',
                'new_phone' => 'phone', 'new_address' => 'max:1000',
            ], ['new_business_name' => 'Client / business name', 'new_contact_person' => 'Contact person', 'new_email' => 'Email', 'new_phone' => 'Phone', 'new_address' => 'Address']);
            if ($cv->fails()) {
                Response::fail('Please correct the highlighted fields.', $cv->errors());
            }
            $_POST['client_id'] = (string) Database::insert('wb_clients', [
                'business_name' => $request->str('new_business_name'), 'contact_person' => $request->str('new_contact_person') ?: null,
                'email' => strtolower($request->str('new_email')) ?: null, 'phone' => $request->str('new_phone') ?: null,
                'address' => $request->str('new_address') ?: null, 'status' => 'active', 'created_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $newClientId = (int) $_POST['client_id'];
            Activity::log('client.created', 'client', $newClientId, $request->str('new_business_name') . ' (from website wizard)');
        }
        $v = Validator::make($request->all(), [
            'client_id' => 'required|integer|exists:wb_clients,id',
            'name' => 'required|max:190',
            'category_id' => 'required|integer|exists:wb_categories,id',
            'template_id' => 'required|integer',
            'domain' => 'domain|max:190',
            'description' => 'max:1000',
        ], ['client_id' => 'Client', 'category_id' => 'Category', 'template_id' => 'Template', 'name' => 'Website name']);
        $errors = $v->errors();
        $tpl = Database::fetch("SELECT * FROM wb_templates WHERE id = ? AND category_id = ? AND status = 'active'", [$request->int('template_id'), $request->int('category_id')]);
        if (!isset($errors['template_id']) && !$tpl) {
            $errors['template_id'] = 'Choose an active template from the selected category.';
        }
        if ($errors) {
            if (!empty($newClientId)) {
                Database::delete('wb_clients', $newClientId); // keep step 1 atomic: no orphan client on validation errors
            }
            Response::fail('Please correct the highlighted fields.', $errors);
        }

        $id = Database::insert('wb_websites', [
            'client_id' => $request->int('client_id'),
            'category_id' => $request->int('category_id'),
            'template_id' => (int) $tpl['id'],
            'name' => $request->str('name'),
            'slug' => slugify($request->str('name')) ?: 'site',
            'domain' => strtolower($request->str('domain')) ?: null,
            'description' => $request->str('description') ?: null,
            'status' => 'draft',
            'wizard_step' => 3,
            'runtime_version' => trim((string) @file_get_contents(RUNTIME_PATH . '/VERSION')) ?: null,
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        try {
            SiteService::provision($id);
        } catch (\Throwable $e) {
            Database::delete('wb_websites', $id);
            Response::fail('The website could not be set up: ' . $e->getMessage(), [], 500);
        }
        Activity::log('website.created', 'website', $id, $request->str('name') . ' (' . $tpl['code'] . ')');
        Database::update('wb_websites', ['wizard_step' => 3], $id);
        Activity::log('website.created', 'client', $request->int('client_id'), $request->str('name'));
        Response::ok('Website created with the template\'s default pages and content.', ['redirect' => url('websites/' . $id . '?step=3')]);
    }

    /** The website workspace: pages & sections, media, theme, site details, preview. */
    public function show(Request $request): void
    {
        $svc = $this->site($request->params['id']);
        $manifest = $svc->manifest();
        $fonts = Theme::fonts(RUNTIME_PATH . '/fonts');
        $w = $svc->website();
        $step = $request->int('step', 0);
        if ($step < 1 || $step > 8) {
            $step = in_array($w['status'], ['draft'], true) ? max(1, min(8, (int) $w['wizard_step'])) : 8;
        }
        View::render('websites/show', [
            'title' => $w['name'],
            'active' => 'websites',
            'w' => $w,
            'currentStep' => $step,
            'client' => Database::fetch('SELECT * FROM wb_clients WHERE id = ?', [$w['client_id']]) ?? [],
            'templates' => Database::fetchAll("SELECT * FROM wb_templates WHERE category_id = ? AND (status = 'active' OR id = ?) ORDER BY name", [$w['category_id'], $w['template_id']]),
            'payments' => (array) (json_decode((string) $w['features'], true)['payments'] ?? []),
            'gateways' => PaymentGateways::all(),
            'editable' => in_array($svc->website()['status'], self::EDITABLE, true) && can('websites.manage'),
            'schemas' => $svc->schemas(),
            'mediaFolders' => SiteService::MEDIA_FOLDERS,
            'fonts' => $fonts,
            'theme' => Theme::resolve((array) ($manifest['theme']['defaults'] ?? []), (array) ($svc->settings()['theme'] ?? []), $fonts),
            'themeDefaults' => (array) ($manifest['theme']['defaults'] ?? []),
            'headerStyles' => (array) ($manifest['theme']['header_styles'] ?? []),
            'footerStyles' => (array) ($manifest['theme']['footer_styles'] ?? []),
            'pageTypes' => array_keys((array) ($manifest['page_types'] ?? [])),
            'scripts' => ['assets/vendor/sortablejs/Sortable.min.js', 'assets/vendor/tinymce/tinymce.min.js', 'assets/js/pages/website-workspace.js'],
        ]);
    }

    public function update(Request $request): void
    {
        $svc = $this->site($request->params['id']);
        $v = Validator::make($request->all(), ['name' => 'required|max:190', 'domain' => 'domain|max:190', 'description' => 'max:1000'], ['name' => 'Website name']);
        if ($v->fails()) {
            Response::fail('Please correct the highlighted fields.', $v->errors());
        }
        Database::update('wb_websites', ['name' => $request->str('name'), 'domain' => strtolower($request->str('domain')) ?: null, 'description' => $request->str('description') ?: null, 'updated_at' => now()], $svc->id());
        Activity::log('website.updated', 'website', $svc->id(), $request->str('name'));
        Response::ok('Website details saved.');
    }

    /** Remember how far the wizard got; reaching the review step marks the configuration as complete. */
    public function wizardStep(Request $request): void
    {
        $svc = $this->site($request->params['id']);
        $step = max(1, min(8, $request->int('step')));
        $w = $svc->website();
        $data = ['wizard_step' => max((int) $w['wizard_step'], $step)];
        if ($step === 8 && $w['status'] === 'draft') {
            $data['status'] = 'config_pending';
        }
        Database::update('wb_websites', $data, $svc->id());
        Response::ok('', ['status' => $data['status'] ?? $w['status']]);
    }

    /** Step 7: online payment choice. Credentials are entered by the client in the website's own admin (decision 22). */
    public function payments(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $enabled = $request->str('enabled') === '1';
        $gateway = $request->str('gateway');
        $mode = $request->str('mode') === 'live' ? 'live' : 'test';
        if ($enabled && !array_key_exists($gateway, \App\Modules\Websites\PaymentGateways::all())) {
            Response::fail('Choose a payment gateway.', ['gateway' => 'Choose a payment gateway.']);
        }
        $features = json_decode((string) $svc->website()['features'], true) ?: [];
        $features['payments'] = ['enabled' => $enabled, 'gateway' => $enabled ? $gateway : null, 'mode' => $mode];
        Database::update('wb_websites', [
            'features' => json_encode($features), 'payment_gateway' => $enabled ? $gateway : null, 'payment_mode' => $enabled ? $mode : null, 'updated_at' => now(),
        ], $svc->id());
        // Keep the website's own payment settings in step (gateway keys entered in the website admin are kept).
        $db = $svc->db();
        $raw = $db->value('SELECT setting_value FROM ' . $db->t('settings') . " WHERE setting_key = 'payments'");
        $site = array_replace(\SiteRuntime\Payments\PaymentService::defaults(), is_string($raw) ? (json_decode($raw, true) ?: []) : []);
        $site['enabled'] = $enabled;
        $site['gateway'] = $enabled ? $gateway : ($site['gateway'] ?? '');
        $site['mode'] = $mode;
        $db->query('REPLACE INTO ' . $db->t('settings') . ' (setting_key, setting_value, updated_at) VALUES (?, ?, ?)', ['payments', json_encode($site, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), now()]);
        Activity::log('website.payments_saved', 'website', $svc->id(), $enabled ? "$gateway ($mode)" : 'disabled');
        Response::ok('Payment settings saved.');
    }

    /** Manual status changes after generation: Testing → Ready for deployment → Deployed, Archive / restore. */
    public function status(Request $request): void
    {
        $svc = SiteService::load($request->params['id']) ?? Response::abort(404, 'Website not found.');
        $w = $svc->website();
        $to = $request->str('status');
        $allowed = [
            'testing' => ['generated', 'ready'],
            'ready' => ['generated', 'testing'],
            'deployed' => ['ready'],
            'generated' => ['testing', 'ready'],
            'archived' => ['draft', 'config_pending', 'generated', 'testing', 'ready', 'deployed', 'failed'],
            'restore' => ['archived'],
        ];
        if (!isset($allowed[$to]) || !in_array($w['status'], $allowed[$to], true)) {
            Response::fail('That status change is not allowed from “' . str_replace('_', ' ', $w['status']) . '”.');
        }
        if ($to === 'restore') {
            $to = is_file(BASE_PATH . '/' . $w['folder_path'] . '/generation.json') ? 'generated' : 'draft';
        }
        $data = ['status' => $to, 'updated_at' => now()];
        if ($to === 'deployed') {
            $data['deployed_at'] = now();
            $data['hosting_info'] = mb_substr($request->str('hosting_info'), 0, 2000) ?: null;
        }
        Database::update('wb_websites', $data, $svc->id());
        Activity::log('website.status_' . $to, 'website', $svc->id(), $w['name']);
        $labels = ['testing' => 'Testing', 'ready' => 'Ready for deployment', 'deployed' => 'Deployed', 'generated' => 'Generated', 'archived' => 'Archived', 'draft' => 'Draft'];
        Response::ok('Status changed to ' . $labels[$to] . '.', ['redirect' => url('websites/' . $svc->id() . '?step=8')]);
    }

    /** Step 2: switch template (re-seeds content; keeps media and site details; backup first). */
    public function changeTemplate(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $w = $svc->website();
        if (!in_array($w['status'], ['draft', 'config_pending', 'failed', 'generated', 'testing'], true)) {
            Response::fail('The template cannot be changed once a website is ready for deployment.');
        }
        $tpl = Database::fetch("SELECT * FROM wb_templates WHERE id = ? AND category_id = ? AND status = 'active'", [$request->int('template_id'), (int) $w['category_id']]);
        if (!$tpl) {
            Response::fail('Choose an active template from this website\'s category.', ['template_id' => 'Choose a template.']);
        }
        if ((int) $tpl['id'] === (int) $w['template_id']) {
            Response::fail('This website already uses that template.');
        }
        if ($request->str('confirm') !== '1') {
            Response::fail('Please confirm the template change.');
        }
        try {
            $backup = $svc->changeTemplate((int) $tpl['id']);
        } catch (\Throwable $e) {
            Response::fail('The template could not be changed: ' . $e->getMessage() . ' A backup is in storage/backups/websites.', [], 500);
        }
        Activity::log('website.template_changed', 'website', $svc->id(), $w['template_code'] . ' → ' . $tpl['code'] . ' (backup: ' . basename($backup) . ')');
        Response::ok('Template changed. Pages and content were reset from the new template; media and site details were kept.', ['redirect' => url('websites/' . $svc->id() . '?step=3')]);
    }

    /** Delete a website after taking a full backup (folder + data). Deployed sites must be archived instead. */
    public function destroy(Request $request): void
    {
        $svc = SiteService::load($request->params['id']);
        if (!$svc) {
            Response::abort(404, 'Website not found.');
        }
        $w = $svc->website();
        if (in_array($w['status'], ['deployed', 'generating'], true)) {
            Response::fail('A ' . $w['status'] . ' website cannot be deleted.');
        }
        if ($request->str('confirm') !== (string) $w['site_code'] && $w['site_code'] !== null) {
            Response::fail('Type the website code ' . $w['site_code'] . ' to confirm deletion.', ['confirm' => 'Enter ' . $w['site_code'] . ' to confirm.']);
        }
        $backup = null;
        if (!empty($w['table_prefix'])) {
            $backup = $svc->backup('deleted');
            $svc->dropTables();
        }
        if (!empty($w['folder_path'])) {
            $folder = FileSystem::within(SITES_PATH, BASE_PATH . '/' . $w['folder_path']);
            if ($folder !== null && is_dir($folder)) {
                FileSystem::deleteDir($folder);
            }
        }
        Database::delete('wb_websites', $svc->id());
        Activity::log('website.deleted', 'website', $svc->id(), $w['name'] . ($backup ? ' (backup: ' . basename($backup) . ')' : ''));
        Response::ok('Website deleted. A backup was saved in storage/backups/websites.', ['redirect' => url('websites')]);
    }
}
