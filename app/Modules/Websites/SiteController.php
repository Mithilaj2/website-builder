<?php
declare(strict_types=1);

namespace App\Modules\Websites;

use App\Core\Response;

/** Shared helpers for controllers that work on one website. */
abstract class SiteController
{
    /** Statuses in which the builder may still change a site's content (decision 7: builder owns content until Deployed). */
    protected const EDITABLE = ['draft', 'config_pending', 'generated', 'testing', 'ready', 'failed'];

    protected function site(int $id, bool $forEdit = false): SiteService
    {
        $svc = SiteService::load($id);
        if (!$svc) {
            Response::abort(404, 'Website not found.');
        }
        if (empty($svc->website()['table_prefix'])) {
            Response::abort(409, 'This website has not been set up yet.');
        }
        if ($forEdit && !in_array($svc->website()['status'], self::EDITABLE, true)) {
            Response::fail('This website is ' . str_replace('_', ' ', $svc->website()['status']) . ' and its content can no longer be changed from the builder.', [], 409);
        }
        return $svc;
    }

    /** Decode a JSON form field into an array. */
    protected function jsonInput(string $key): array
    {
        $raw = $_POST[$key] ?? '';
        if (!is_string($raw) || strlen($raw) > 2_000_000) {
            Response::fail('Invalid data.');
        }
        $v = json_decode($raw, true);
        if (!is_array($v)) {
            Response::fail('Invalid data.');
        }
        return $v;
    }

    protected function touch(SiteService $svc): void
    {
        \App\Core\Database::update('wb_websites', ['updated_at' => now()], $svc->id());
    }
}
