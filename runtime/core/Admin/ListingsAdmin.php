<?php
declare(strict_types=1);

namespace SiteRuntime\Admin;

use SiteRuntime\Listings\ListingService;

/** Website admin screens for listings (properties, packages, rooms…) and their categories. */
final class ListingsAdmin
{
    public function __construct(private App $app, private ListingService $svc, private string $base, private array $config)
    {
    }

    private function cfg(): array
    {
        return $this->svc->config();
    }

    private function publicUrl(string $slug = ''): string
    {
        return $this->base . (!empty($this->config['clean_urls']) ? '' : '/index.php') . '/' . $this->cfg()['path'] . ($slug !== '' ? '/' . $slug : '');
    }

    public function listPage(): void
    {
        $c = $this->cfg();
        $this->app->page('listings', ['title' => $c['plural'], 'active' => 'listings', 'cfg' => $c, 'categories' => $this->svc->categories(false), 'publicUrl' => $this->publicUrl(),
            'scripts' => ['js/admin-listings.js']]);
    }

    public function apiList(): void
    {
        $r = $this->svc->adminList($_GET, (int) ($_GET['page'] ?? 1), (int) ($_GET['per_page'] ?? 20));
        foreach ($r['rows'] as &$row) {
            $row['price_text'] = $row['price'] !== null ? ListingService::price((int) $row['price'], $this->cfg()['price_format']) . ($row['price_note'] ? ' ' . $row['price_note'] : '') : 'On request';
            $row['image_url'] = $this->imageUrl((string) $row['image']);
            $row['url'] = $this->publicUrl((string) $row['slug']);
        }
        $this->app->respond($r);
    }

    public function form(array $p = []): void
    {
        $item = isset($p['lid']) ? $this->svc->find($p['lid']) : null;
        $images = [];
        if ($item) {
            foreach (array_values(array_filter(array_merge([(string) $item['image']], $item['gallery']))) as $ref) {
                $images[] = ['ref' => $ref, 'url' => $this->imageUrl($ref)];
            }
        }
        $c = $this->cfg();
        $this->app->page('listing-form', ['title' => ($item ? 'Edit ' : 'Add ') . strtolower($c['singular']), 'active' => 'listings', 'item' => $item, 'images' => $images, 'cfg' => $c,
            'categories' => $this->svc->categories(false), 'config' => $this->app->editorConfig(), 'publicUrl' => $this->publicUrl(), 'mediaFolder' => 'listings', 'mediaFolderLabel' => 'Listings',
            'scripts' => ['vendor/tinymce/tinymce.min.js', 'js/admin-media-picker.js', 'js/admin-listings.js']]);
    }

    public function apiSave(array $p = []): void
    {
        $id = $this->svc->save($_POST, $p['lid'] ?? null);
        $this->app->respondOk($this->cfg()['singular'] . (isset($p['lid']) ? ' saved.' : ' added.'), ['id' => $id, 'redirect' => isset($p['lid']) ? null : $this->app->url('listings/' . $id)]);
    }

    public function apiDelete(array $p): void
    {
        $this->svc->delete($p['lid']);
        $this->app->respondOk($this->cfg()['singular'] . ' deleted.', ['redirect' => ($_GET['back'] ?? '') === '1' ? $this->app->url('listings') : null]);
    }

    public function categoriesPage(): void
    {
        $c = $this->cfg();
        $this->app->page('listing-categories', ['title' => $c['category_label'] . ' list', 'active' => 'listings', 'cfg' => $c, 'config' => $this->app->editorConfig(),
            'mediaFolder' => 'listings', 'mediaFolderLabel' => 'Listings', 'scripts' => ['js/admin-media-picker.js', 'js/admin-listings.js']]);
    }

    public function apiCategories(): void
    {
        $rows = $this->svc->categories(false);
        foreach ($rows as &$r) {
            $r['image_url'] = $this->imageUrl((string) $r['image']);
            $r['url'] = $this->publicUrl() . '?c=' . rawurlencode((string) $r['slug']);
        }
        $this->app->respond(['rows' => $rows, 'total' => count($rows), 'page' => 1, 'pages' => 1, 'per_page' => max(1, count($rows))]);
    }

    public function apiCategorySave(array $p = []): void
    {
        $this->svc->saveCategory($_POST, $p['cid'] ?? null);
        $this->app->respondOk('Saved.');
    }

    public function apiCategoryDelete(array $p): void
    {
        $this->svc->deleteCategory($p['cid']);
        $this->app->respondOk('Deleted. Its ' . strtolower($this->cfg()['plural']) . ' are kept, without a ' . strtolower($this->cfg()['category_label']) . '.');
    }

    private function imageUrl(string $ref): string
    {
        if (str_starts_with($ref, 'uploads/') && !str_contains($ref, '..')) {
            return $this->base . '/' . $ref;
        }
        if (str_starts_with($ref, 'theme:') && !str_contains($ref, '..')) {
            return $this->base . '/theme/assets/' . substr($ref, 6);
        }
        return '';
    }
}
