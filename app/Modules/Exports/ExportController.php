<?php
declare(strict_types=1);

namespace App\Modules\Exports;

use App\Core\Activity;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Modules\Generator\Checklist;
use App\Modules\Websites\SiteService;

/** Export & deploy screen: full packages (first deployment) and update packages (deployed websites). */
final class ExportController
{
    public function page(Request $request): void
    {
        $site = $this->site($request->params['id']);
        $w = $site->website();
        $payments = $site->db()->value('SELECT setting_value FROM ' . $site->db()->t('settings') . " WHERE setting_key = 'payments'");
        $payments = json_decode((string) $payments, true) ?: [];
        $defs = \SiteRuntime\Payments\Gateways::definitions();
        $counts = [
            'enquiries' => (int) $site->db()->value('SELECT COUNT(*) FROM ' . $site->db()->t('contact_submissions') . " WHERE status <> 'spam'"),
            'live_payments' => (int) $site->db()->value('SELECT COUNT(*) FROM ' . $site->db()->t('payment_transactions') . " WHERE mode = 'live'"),
            'test_payments' => (int) $site->db()->value('SELECT COUNT(*) FROM ' . $site->db()->t('payment_transactions') . " WHERE mode = 'test'"),
            'media' => (int) $site->db()->value('SELECT COUNT(*) FROM ' . $site->db()->t('media')),
        ];
        $version = json_decode((string) @file_get_contents($site->folder() . '/VERSION'), true) ?: [];
        View::render('websites/export', [
            'title' => 'Export & deploy · ' . $w['name'], 'w' => $w, 'counts' => $counts, 'generated' => is_file($site->folder() . '/generation.json'),
            'version' => $version, 'runtimeLatest' => trim((string) @file_get_contents(RUNTIME_PATH . '/VERSION')),
            'templateLatest' => (string) Database::value('SELECT current_version FROM wb_templates WHERE id = ?', [(int) $w['template_id']]),
            'payments' => $payments, 'gatewayName' => $defs[$payments['gateway'] ?? '']['name'] ?? '',
            'checklist' => Checklist::run($site), 'prefix' => Exporter::DEFAULT_PREFIX,
            'scripts' => ['assets/js/pages/website-export.js'],
        ]);
    }

    public function history(Request $request): void
    {
        $site = $this->site($request->params['id']);
        $rows = Database::fetchAll('SELECT e.id, e.type, e.status, e.error_message, e.size_bytes, e.file_count, e.runtime_version, e.template_version, e.created_at, e.finished_at,
                e.zip_path IS NOT NULL AS has_zip, e.sql_path IS NOT NULL AS has_sql, e.state, u.name AS user
            FROM wb_exports e LEFT JOIN wb_users u ON u.id = e.created_by WHERE e.website_id = ? ORDER BY e.id DESC LIMIT 30', [$site->id()]);
        $manage = can('websites.manage');
        foreach ($rows as &$r) {
            $state = json_decode((string) $r['state'], true) ?: [];
            unset($r['state']);
            $r['install_key'] = $manage && $r['type'] === 'full' && $r['status'] === 'done' ? ($state['install_key'] ?? null) : null;
            $r['size_text'] = $r['size_bytes'] ? Exporter::mb((int) $r['size_bytes']) : '';
        }
        Response::json(['ok' => true, 'exports' => $rows]);
    }

    public function start(Request $request): void
    {
        $site = $this->site($request->params['id']);
        $type = $request->str('type') === 'update' ? 'update' : 'full';
        try {
            $id = Exporter::start($site, $type, [
                'include_enquiries' => $request->str('include_enquiries') === '1',
                'include_payments' => $request->str('include_payments') === '1',
                'prefix' => $request->str('prefix') ?: Exporter::DEFAULT_PREFIX,
            ]);
        } catch (\RuntimeException $e) {
            Response::fail($e->getMessage(), str_contains($e->getMessage(), 'prefix') ? ['prefix' => $e->getMessage()] : [], str_contains($e->getMessage(), 'already running') ? 409 : 422);
        }
        Response::ok('Export started.', ['state' => Exporter::load($id)->state()]);
    }

    public function step(Request $request): void
    {
        Response::json(['ok' => true, 'state' => $this->export($request)->step()]);
    }

    public function download(Request $request): void
    {
        $this->export($request);
        $e = Database::fetch('SELECT * FROM wb_exports WHERE id = ?', [(int) $request->params['eid']]);
        $path = $request->str('file') === 'sql' ? $e['sql_path'] : $e['zip_path'];
        $abs = $path ? realpath(BASE_PATH . '/' . $path) : false;
        if ($e['status'] !== 'done' || !$abs || !str_starts_with($abs, realpath(Exporter::dir()) . DIRECTORY_SEPARATOR) || !is_file($abs)) {
            Response::abort(404, 'This package is no longer available. Export again.');
        }
        Activity::log('website.export_downloaded', 'website', (int) $e['website_id'], basename($abs));
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/zip');
        header('Content-Length: ' . filesize($abs));
        header('Content-Disposition: attachment; filename="' . basename($abs) . '"');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        $fh = fopen($abs, 'rb');
        while (!feof($fh)) {
            echo fread($fh, 1 << 20);
            flush();
        }
        fclose($fh);
        exit;
    }

    public function destroy(Request $request): void
    {
        $this->export($request);
        $e = Database::fetch('SELECT * FROM wb_exports WHERE id = ?', [(int) $request->params['eid']]);
        if ($e['status'] === 'running' && strtotime((string) $e['created_at']) > time() - 3600) {
            Response::fail('This export is still running.', [], 409);
        }
        foreach (['zip_path', 'sql_path'] as $k) {
            if ($e[$k] && is_file(BASE_PATH . '/' . $e[$k])) {
                @unlink(BASE_PATH . '/' . $e[$k]);
            }
        }
        Database::query('DELETE FROM wb_exports WHERE id = ?', [(int) $e['id']]);
        Response::ok('Package deleted.');
    }

    private function export(Request $request): Exporter
    {
        $this->site($request->params['id']);
        $wid = (int) Database::value('SELECT website_id FROM wb_exports WHERE id = ?', [(int) $request->params['eid']]);
        if ($wid !== (int) $request->params['id']) {
            Response::abort(404, 'Export not found.');
        }
        return Exporter::load((int) $request->params['eid']);
    }

    private function site(int $id): SiteService
    {
        $s = SiteService::load($id);
        if (!$s || empty($s->website()['table_prefix'])) {
            Response::abort(404, 'Website not found.');
        }
        return $s;
    }
}
