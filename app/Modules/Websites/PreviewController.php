<?php
declare(strict_types=1);

namespace App\Modules\Websites;

use App\Core\Database;
use App\Core\FileSystem;
use App\Core\Request;
use App\Core\Response;
use SiteRuntime\ArraySource;
use SiteRuntime\Renderer;
use SiteRuntime\SectionTypes;
use SiteRuntime\Seeder;

/**
 * Live previews rendered by the real site runtime:
 *  - a website with its own data (websites/{id}/preview?page=slug)
 *  - a template with its demo content (templates/{id}/preview?page=slug)
 * plus the static files those previews need.
 */
final class PreviewController extends SiteController
{
    public function website(Request $request): void
    {
        $svc = $this->site($request->params['id']);
        $out = $svc->renderer()->render($request->str('page'));
        $this->output($out);
    }

    public function template(Request $request): void
    {
        $tpl = Database::fetch('SELECT * FROM wb_templates WHERE id = ?', [$request->params['id']]);
        $dir = $tpl ? FileSystem::within(TEMPLATES_PATH, BASE_PATH . '/' . $tpl['folder_path']) : null;
        if (!$tpl || $dir === null || !is_file("$dir/manifest.json")) {
            Response::abort(404, 'Template not found.');
        }
        $manifest = json_decode((string) file_get_contents("$dir/manifest.json"), true) ?: [];
        $demo = is_file("$dir/demo.json") ? (json_decode((string) file_get_contents("$dir/demo.json"), true) ?: []) : [];
        $seed = Seeder::build($manifest, new SectionTypes(RUNTIME_PATH . '/section-types', $dir, $manifest), $demo);
        $id = (int) $tpl['id'];
        $renderer = new Renderer(ArraySource::fromSeed($seed), $dir, $manifest, SiteService::previewUrls("templates/$id/preview", $id, "templates/$id/preview-media"), ['fonts' => RUNTIME_PATH . '/fonts', 'lib' => RUNTIME_PATH . '/assets/lib'], true);
        $this->output($renderer->render($request->str('page')));
    }

    /** Files from a template's assets/ folder. */
    public function templateAsset(Request $request): void
    {
        $tpl = Database::fetch('SELECT folder_path FROM wb_templates WHERE id = ?', [$request->params['id']]);
        if (!$tpl) {
            Response::abort(404, 'File not found.');
        }
        $base = BASE_PATH . '/' . $tpl['folder_path'] . '/assets';
        $file = is_dir($base) ? FileSystem::within($base, $base . '/' . $request->params['path']) : null;
        if ($file === null || !is_file($file)) {
            Response::abort(404, 'File not found.');
        }
        AssetStreamer::send($file);
    }

    public function runtimeFont(Request $request): void
    {
        $this->runtimeAsset(RUNTIME_PATH . '/fonts', (string) $request->params['path']);
    }

    public function runtimeLib(Request $request): void
    {
        $this->runtimeAsset(RUNTIME_PATH . '/assets/lib', (string) $request->params['path']);
    }

    /** Runtime-provided fonts and libraries (Bootstrap, Bootstrap Icons). */
    private function runtimeAsset(string $base, string $path): void
    {
        $file = FileSystem::within($base, $base . '/' . $path);
        if ($file === null || !is_file($file)) {
            Response::abort(404, 'File not found.');
        }
        AssetStreamer::send($file);
    }

    private function output(array $out): never
    {
        http_response_code($out['status']);
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
        echo $out['html'];
        exit;
    }
}
