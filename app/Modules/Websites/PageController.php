<?php
declare(strict_types=1);

namespace App\Modules\Websites;

use App\Core\Activity;
use App\Core\Request;
use App\Core\Response;

/** Pages of one website (wizard step 3). Logic lives in SiteRuntime\ContentService, shared with the website admin. */
final class PageController extends SiteController
{
    public function list(Request $request): void
    {
        $svc = $this->site($request->params['id']);
        Response::json(['ok' => true, 'pages' => $svc->content()->pages()]);
    }

    public function store(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $id = $this->call(fn () => $svc->content()->savePage(null, $_POST));
        $this->touch($svc);
        Activity::log('website.page_added', 'website', $svc->id(), $request->str('title'));
        Response::ok('Page added with the template\'s default sections.', ['id' => $id]);
    }

    public function update(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $c = $svc->content();
        $this->call(fn () => $c->savePage($c->page((int) $request->params['pid']), $_POST));
        $this->touch($svc);
        Activity::log('website.page_updated', 'website', $svc->id(), $request->str('title'));
        Response::ok('Page saved.');
    }

    public function setHome(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $page = $this->call(fn () => $svc->content()->setHome((int) $request->params['pid']));
        $this->touch($svc);
        Activity::log('website.homepage_changed', 'website', $svc->id(), $page['title']);
        Response::ok('“' . $page['title'] . '” is now the homepage.');
    }

    public function toggle(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $new = $this->call(fn () => $svc->content()->togglePage((int) $request->params['pid']));
        $this->touch($svc);
        Response::ok('Page ' . ($new === 'active' ? 'activated.' : 'deactivated.'));
    }

    public function reorder(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $svc->content()->reorderPages((array) ($_POST['ids'] ?? []));
        $this->touch($svc);
        Response::ok('Page order saved. The menu follows this order.');
    }

    public function destroy(Request $request): void
    {
        $svc = $this->site($request->params['id'], true);
        $page = $this->call(fn () => $svc->content()->deletePage((int) $request->params['pid']));
        $this->touch($svc);
        Activity::log('website.page_deleted', 'website', $svc->id(), $page['title']);
        Response::ok('Page deleted.');
    }
}
