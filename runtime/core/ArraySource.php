<?php
declare(strict_types=1);

namespace SiteRuntime;

/** In-memory content, used for template previews (demo content without a database). */
final class ArraySource implements DataSource
{
    /**
     * @param array $settings
     * @param list<array> $pages each with id, slug, is_home, ...
     * @param array<int, list<array>> $sections keyed by page id
     */
    public function __construct(private array $settings, private array $pages, private array $sections, private array $shop = [], private array $listings = [])
    {
    }

    public static function fromSeed(array $seed): self
    {
        $pages = [];
        $sections = [];
        foreach ($seed['pages'] as $i => $p) {
            $id = $i + 1;
            $pages[] = $p + ['id' => $id];
            $sections[$id] = [];
            foreach ($p['sections'] as $j => $s) {
                $sections[$id][] = $s + ['id' => $id * 100 + $j, 'page_id' => $id];
            }
        }
        return new self($seed['settings'], $pages, $sections, (array) ($seed['shop'] ?? []), (array) ($seed['listings'] ?? []));
    }

    public function settings(): array
    {
        return $this->settings;
    }

    public function pages(): array
    {
        return $this->pages;
    }

    public function pageBySlug(string $slug): ?array
    {
        foreach ($this->pages as $p) {
            if ($p['slug'] === $slug) {
                return $p;
            }
        }
        return null;
    }

    public function homePage(): ?array
    {
        foreach ($this->pages as $p) {
            if (!empty($p['is_home'])) {
                return $p;
            }
        }
        return null;
    }

    public function sections(int $pageId): array
    {
        return $this->sections[$pageId] ?? [];
    }

    public function mediaAlt(): array
    {
        return [];
    }

    public function menuItems(string $location): array
    {
        return [];
    }

    /** Demo products (template previews): same fields as the database rows, ids by position. */
    public function products(array $f): array
    {
        $cats = [];
        foreach ($this->productCategories() as $c) {
            $cats[$c['slug']] = $c;
        }
        $rows = [];
        foreach ((array) ($this->shop['products'] ?? []) as $i => $p) {
            $variants = (array) ($p['variants'] ?? []);
            $c = $cats[(string) ($p['category'] ?? '')] ?? null;
            $prices = array_map(static fn ($v) => (int) ($v['price'] ?? $p['price']), $variants);
            $rows[] = Shop\ShopService::decorate([
                'id' => $i + 1, 'name' => (string) $p['name'], 'slug' => (string) $p['slug'], 'sku' => $p['sku'] ?? null, 'short_description' => $p['short_description'] ?? null,
                'price' => (int) $p['price'], 'compare_price' => $p['compare_price'] ?? null, 'gst_rate' => (string) ($p['gst_rate'] ?? '0'), 'track_stock' => (int) ($p['track_stock'] ?? 1),
                'stock' => (int) ($p['stock'] ?? 0), 'image' => $p['image'] ?? null, 'gallery_json' => json_encode((array) ($p['gallery'] ?? [])), 'badge' => $p['badge'] ?? null,
                'is_featured' => !empty($p['featured']) ? 1 : 0, 'category_id' => $c['id'] ?? null, 'category_name' => $c['name'] ?? null, 'category_slug' => $c['slug'] ?? null,
                'variant_count' => count($variants), 'variant_stock' => array_sum(array_map(static fn ($v) => (int) ($v['stock'] ?? 0), $variants)),
                'from_price' => $prices ? min($prices) : null, 'to_price' => $prices ? max($prices) : null, 'created_order' => $i,
            ]);
        }
        if (!empty($f['category'])) {
            $rows = array_values(array_filter($rows, static fn ($r) => $r['category_slug'] === $f['category']));
        }
        if (!empty($f['featured'])) {
            $rows = array_values(array_filter($rows, static fn ($r) => (int) $r['is_featured'] === 1));
        }
        match ((string) ($f['sort'] ?? '')) {
            'new' => usort($rows, static fn ($a, $b) => $b['created_order'] <=> $a['created_order']),
            'price_asc' => usort($rows, static fn ($a, $b) => $a['display_price'] <=> $b['display_price']),
            'price_desc' => usort($rows, static fn ($a, $b) => $b['display_price'] <=> $a['display_price']),
            default => usort($rows, static fn ($a, $b) => [$b['is_featured'], $a['created_order']] <=> [$a['is_featured'], $b['created_order']]),
        };
        return array_slice($rows, 0, max(1, min(60, (int) ($f['limit'] ?? 12))));
    }

    public function productCategories(): array
    {
        $out = [];
        foreach ((array) ($this->shop['categories'] ?? []) as $i => $c) {
            $count = count(array_filter((array) ($this->shop['products'] ?? []), static fn ($p) => ($p['category'] ?? '') === $c['slug']));
            $out[] = ['id' => $i + 1, 'name' => (string) $c['name'], 'slug' => (string) $c['slug'], 'description' => $c['description'] ?? null, 'image' => $c['image'] ?? null, 'product_count' => $count];
        }
        return $out;
    }

    /** Demo listings (template previews), decorated like database rows. */
    public function listings(array $f, array $config): array
    {
        try {
            $cfg = Listings\ListingService::normalise($config);
        } catch (\InvalidArgumentException) {
            return [];
        }
        $cats = [];
        foreach ($this->listingCategories() as $c) {
            $cats[$c['slug']] = $c;
        }
        $rows = [];
        foreach ((array) ($this->listings['items'] ?? []) as $i => $it) {
            $c = $cats[(string) ($it['category'] ?? '')] ?? null;
            $rows[] = Listings\ListingService::decorateWith([
                'id' => $i + 1, 'title' => (string) $it['title'], 'slug' => (string) $it['slug'], 'location' => $it['location'] ?? null, 'price' => $it['price'] ?? null,
                'price_note' => $it['price_note'] ?? null, 'short_description' => $it['short_description'] ?? null, 'image' => $it['image'] ?? null,
                'gallery_json' => json_encode((array) ($it['gallery'] ?? [])), 'attributes_json' => json_encode((array) ($it['attributes'] ?? [])), 'badge' => $it['badge'] ?? null,
                'is_featured' => !empty($it['featured']) ? 1 : 0, 'status' => ($it['status'] ?? 'active') === 'closed' ? 'closed' : 'active',
                'category_id' => $c['id'] ?? null, 'category_name' => $c['name'] ?? null, 'category_slug' => $c['slug'] ?? null, 'created_order' => $i,
            ], $cfg);
        }
        if (!empty($f['category'])) {
            $rows = array_values(array_filter($rows, static fn ($r) => $r['category_slug'] === $f['category']));
        }
        if (!empty($f['featured'])) {
            $rows = array_values(array_filter($rows, static fn ($r) => (int) $r['is_featured'] === 1));
        }
        if (isset($f['include_closed']) && !$f['include_closed']) {
            $rows = array_values(array_filter($rows, static fn ($r) => !$r['is_closed']));
        }
        match ((string) ($f['sort'] ?? '')) {
            'new' => usort($rows, static fn ($a, $b) => $b['created_order'] <=> $a['created_order']),
            'price_asc' => usort($rows, static fn ($a, $b) => (int) $a['price'] <=> (int) $b['price']),
            'price_desc' => usort($rows, static fn ($a, $b) => (int) $b['price'] <=> (int) $a['price']),
            default => usort($rows, static fn ($a, $b) => [(int) $a['is_closed'], $b['is_featured'], $a['created_order']] <=> [(int) $b['is_closed'], $a['is_featured'], $b['created_order']]),
        };
        return array_slice($rows, 0, max(1, min(60, (int) ($f['limit'] ?? 12))));
    }

    public function listingCategories(): array
    {
        $out = [];
        foreach ((array) ($this->listings['categories'] ?? []) as $i => $c) {
            $count = count(array_filter((array) ($this->listings['items'] ?? []), static fn ($it) => ($it['category'] ?? '') === $c['slug']));
            $out[] = ['id' => $i + 1, 'name' => (string) $c['name'], 'slug' => (string) $c['slug'], 'description' => $c['description'] ?? null, 'image' => $c['image'] ?? null, 'listing_count' => $count];
        }
        return $out;
    }

    public function listingLocations(): array
    {
        $l = array_values(array_unique(array_filter(array_map(static fn ($it) => (string) ($it['location'] ?? ''), (array) ($this->listings['items'] ?? [])))));
        sort($l);
        return $l;
    }
}
