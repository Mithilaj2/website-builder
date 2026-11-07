<?php
declare(strict_types=1);

namespace App\Modules\Websites;

use App\Core\Activity;
use App\Core\FileSystem;
use App\Core\Request;
use App\Core\Response;

/**
 * Media library of one website (wizard step 5). Logic lives in SiteRuntime\MediaService, shared with the
 * website admin. Files live in that website's own uploads/ folder, so one client's images never reach another site.
 */
final class MediaController extends SiteController
{
    public function list(Request $request): void
    {
        $svc = $this->site($request->params['id']);
        Response::json($svc->media()->list($request->str('folder'), $request->str('q'), $request->int('page', 1), $request->int('per_page', 24)));
    }

    public function upload(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        [$saved, $skipped] = $this->call(fn () => $svc->media()->upload($request->files('files'), $request->str('folder')));
        $this->touch($svc);
        Activity::log('website.media_uploaded', 'website', $svc->id(), count($saved) . ' file(s)');
        Response::ok(count($saved) . ' image(s) uploaded.' . ($skipped ? ' Skipped: ' . implode(' ', $skipped) : ''), ['items' => $saved, 'skipped' => $skipped]);
    }

    public function update(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $this->call(fn () => $svc->media()->update((int) $request->params['mid'], $request->str('alt_text'), $request->str('folder')));
        Response::ok('Image details saved.');
    }

    public function replace(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $url = $this->call(fn () => $svc->media()->replace((int) $request->params['mid'], $request->file('file')));
        $this->touch($svc);
        Response::ok('Image replaced everywhere it is used.', ['url' => $url]);
    }

    public function destroy(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $m = $this->call(fn () => $svc->media()->delete((int) $request->params['mid'], $request->str('force') === '1'));
        $this->touch($svc);
        Activity::log('website.media_deleted', 'website', $svc->id(), $m['original_name'] ?: $m['path']);
        Response::ok('Image deleted.');
    }

    /** Serve a file from the website's uploads folder (builder preview only; login required). */
    public function serve(Request $request): void
    {
        $svc = $this->site($request->params['id']);
        $rel = (string) $request->params['path'];
        $file = FileSystem::within($svc->uploadsDir(), $svc->uploadsDir() . '/' . $rel);
        if ($file === null || !is_file($file) || str_contains($rel, '/.')) {
            Response::abort(404, 'File not found.');
        }
        AssetStreamer::send($file);
    }
}
