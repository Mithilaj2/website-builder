<?php
declare(strict_types=1);

namespace SiteRuntime;

/**
 * Renders a page: finds it by slug, renders each enabled section through the template's
 * section view in order, and wraps the result in the page type's layout.
 */
final class Renderer
{
    public function __construct(
        private DataSource $source,
        private string $themeDir,
        private array $manifest,
        private array $urls,
        private array $dirs,
        private bool $preview = false,
        private array $flash = [],
    ) {
    }

    /** @return array{status:int, html:string} */
    public function render(string $slug): array
    {
        $slug = trim($slug, '/');
        $page = $slug === '' ? $this->source->homePage() : $this->source->pageBySlug($slug);
        if ($page === null || ($page['status'] ?? 'active') !== 'active') {
            return ['status' => 404, 'html' => $this->notFound()];
        }

        $view = $this->view($page);
        $content = '';
        foreach ($this->source->sections((int) $page['id']) as $section) {
            if (empty($section['is_enabled'])) {
                continue;
            }
            $content .= $this->section($view, $section);
        }
        $layout = $this->layoutFor((string) ($page['page_type'] ?? ''));
        return ['status' => 200, 'html' => $view->capture($layout, ['content' => $content])];
    }

    /** Render one section on its own (used for the section editor's live preview). */
    public function section(View $view, array $section): string
    {
        $type = (string) ($section['section_type'] ?? '');
        if (!preg_match('/^[a-z0-9_]+$/', $type) || !in_array($type, (array) ($this->manifest['section_types'] ?? []), true)) {
            return '';
        }
        $file = $this->themeDir . '/sections/' . $type . '.php';
        if (!is_file($file)) {
            return '';
        }
        return $view->capture($file, ['data' => (array) ($section['data'] ?? []), 'section' => $section]);
    }

    public function view(array $page): View
    {
        $view = new View($this->source, $this->themeDir, $this->manifest, $this->urls, $this->dirs, $this->preview);
        $view->page = $page;
        $view->flash = $this->flash;
        return $view;
    }

    private function layoutFor(string $pageType): string
    {
        $name = (string) ($this->manifest['page_types'][$pageType]['layout'] ?? 'default');
        $file = $this->themeDir . '/layouts/' . basename($name) . '.php';
        return is_file($file) ? $file : $this->themeDir . '/layouts/default.php';
    }

    private function notFound(): string
    {
        $page = ['id' => 0, 'title' => 'Page not found', 'slug' => '', 'page_type' => '404', 'is_home' => 0, 'seo_title' => 'Page not found'];
        $view = $this->view($page);
        $custom = $view->partial('404');
        $content = $custom !== '' ? $custom
            : '<section class="wb-section wb-404" style="padding:6rem 1rem;text-align:center"><h1>Page not found</h1><p>The page you are looking for does not exist.</p><p><a href="' . $view->e($view->url('')) . '">Go to the homepage</a></p></section>';
        return $view->capture($this->themeDir . '/layouts/default.php', ['content' => $content]);
    }
}
