<?php
declare(strict_types=1);

namespace App\Modules\Websites;

use App\Core\Activity;
use App\Core\Request;
use App\Core\Response;

/** Sections of a page (wizard step 4). Logic lives in SiteRuntime\ContentService, shared with the website admin. */
final class SectionController extends SiteController
{
    public function list(Request $request): void
    {
        $svc = $this->site($request->params['id']);
        $c = $svc->content();
        $pid = (int) $request->params['pid'];
        $sections = $this->call(fn () => $c->sections($pid));
        Response::json(['ok' => true, 'page' => $c->page($pid), 'sections' => $sections]);
    }

    public function store(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $id = $this->call(fn () => $svc->content()->addSection((int) $request->params['pid'], $request->str('section_type'), $request->int('after')));
        $this->touch($svc);
        Activity::log('website.section_added', 'website', $svc->id(), $request->str('section_type'));
        Response::ok('Section added. Edit it to add your content.', ['id' => $id]);
    }

    public function show(Request $request): void
    {
        $svc = $this->site($request->params['id']);
        Response::json(['ok' => true] + $this->call(fn () => $svc->content()->sectionForEditor((int) $request->params['sid'])));
    }

    public function update(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $data = $this->call(fn () => $svc->content()->updateSection((int) $request->params['sid'], $this->jsonInput('data'), $this->jsonInput('style'), $request->str('label')));
        $this->touch($svc);
        Response::ok('Section saved.', ['data' => $data]);
    }

    public function toggle(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $on = $this->call(fn () => $svc->content()->toggleSection((int) $request->params['sid']));
        $this->touch($svc);
        Response::ok($on ? 'Section enabled.' : 'Section hidden. It stays here and can be enabled again.');
    }

    public function duplicate(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $id = $this->call(fn () => $svc->content()->duplicateSection((int) $request->params['sid']));
        $this->touch($svc);
        Response::ok('Section duplicated.', ['id' => $id]);
    }

    public function destroy(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $s = $this->call(fn () => $svc->content()->deleteSection((int) $request->params['sid']));
        $this->touch($svc);
        Activity::log('website.section_deleted', 'website', $svc->id(), $s['section_type']);
        Response::ok('Section deleted.');
    }

    public function reorder(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $this->call(fn () => $svc->content()->reorderSections((int) $request->params['pid'], (array) ($_POST['ids'] ?? [])));
        $this->touch($svc);
        Response::ok('Section order saved.');
    }
}
