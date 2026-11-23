<?php
declare(strict_types=1);

namespace App\Modules\Generator;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Websites\SiteService;

final class GeneratorController
{
    /** Statuses from which a (re)generation may start. Deployed sites get update packages instead (Export & deploy). */
    private const CAN_GENERATE = ['draft', 'config_pending', 'generated', 'testing', 'ready', 'failed'];

    public function checklist(Request $request): void
    {
        $site = $this->site($request->params['id']);
        Response::json(['ok' => true] + Checklist::run($site));
    }

    public function start(Request $request): void
    {
        $site = $this->site($request->params['id']);
        $w = $site->website();
        if ($w['status'] === 'generating') {
            Response::fail('A generation is already in progress for this website.', [], 409);
        }
        if (!in_array($w['status'], self::CAN_GENERATE, true)) {
            Response::fail('A ' . str_replace('_', ' ', $w['status']) . ' website cannot be generated from here.');
        }
        $report = Checklist::run($site);
        if ($report['errors']) {
            Response::json(['ok' => false, 'message' => 'Fix the problems in the checklist first.', 'checklist' => $report], 422);
        }
        try {
            $runId = Generator::start($site->id(), in_array($w['status'], ['draft', 'config_pending'], true) ? 'generate' : 'regenerate');
        } catch (\RuntimeException $e) {
            Response::fail($e->getMessage(), [], 409);
        }
        Response::ok('Generation started.', ['state' => Generator::load($runId)->state()]);
    }

    /** Run the next step of a generation (called repeatedly by the browser). */
    public function step(Request $request): void
    {
        $gen = $this->run($request);
        Response::json(['ok' => true, 'state' => $gen->step()]);
    }

    public function state(Request $request): void
    {
        Response::json(['ok' => true, 'state' => $this->run($request)->state()]);
    }

    public function retry(Request $request): void
    {
        try {
            $state = $this->run($request)->retry();
        } catch (\RuntimeException $e) {
            Response::fail($e->getMessage(), [], 409);
        }
        Response::ok('Retrying from the failed step.', ['state' => $state]);
    }

    /** Generation history of a website. */
    public function history(Request $request): void
    {
        $site = $this->site($request->params['id']);
        $runs = Database::fetchAll(
            'SELECT r.id, r.type, r.status, r.current_step, r.error_message, r.started_at, r.finished_at, u.name AS user
             FROM wb_generation_runs r LEFT JOIN wb_users u ON u.id = r.triggered_by WHERE r.website_id = ? ORDER BY r.id DESC LIMIT 20',
            [$site->id()]
        );
        Response::json(['ok' => true, 'runs' => $runs, 'site_url' => QaAccess::siteUrl($site->website()), 'qa_user' => QaAccess::USER, 'qa_password' => can('websites.manage') ? QaAccess::password() : null]);
    }

    private function run(Request $request): Generator
    {
        $runId = (int) $request->params['run'];
        $websiteId = (int) Database::value('SELECT website_id FROM wb_generation_runs WHERE id = ?', [$runId]);
        if ($websiteId !== (int) $request->params['id']) {
            Response::abort(404, 'Generation run not found.');
        }
        return Generator::load($runId);
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
