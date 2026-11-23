<?php
declare(strict_types=1);

namespace SiteRuntime\Listings;

use SiteRuntime\Db;
use SiteRuntime\NotFound;
use SiteRuntime\ValidationError;

/**
 * Listings: the main items of a template's category — properties, tour packages, rooms, projects.
 * What a listing has (bedrooms, duration, amenities…) is declared by the template manifest under "listings";
 * values are stored as JSON per listing. Money is an integer number of paise.
 */
final class ListingService
{
    public const PATHS = ['properties', 'packages', 'tours', 'rooms', 'projects', 'portfolio', 'listings', 'destinations', 'vehicles', 'services', 'work'];
    public const TYPES = ['number', 'text', 'select', 'toggle', 'list'];

    private ?array $config;

    /** @param ?array $manifestListings the manifest's "listings" block (null = this template has no listings) */
    public function __construct(private Db $db, ?array $manifestListings, private $imageOk = null, private $richtext = null)
    {
        $this->config = $manifestListings ? self::normalise($manifestListings) : null;
    }

    public function db(): Db
    {
        return $this->db;
    }

    public function enabled(): bool
    {
        return $this->config !== null;
    }

    public function config(): array
    {
        return $this->config ?? throw new NotFound('This website has no listings.');
    }

    /** Defaults + validation of a manifest "listings" block. Throws \InvalidArgumentException with a readable message. */
    public static function normalise(array $m): array
    {
        $path = (string) ($m['path'] ?? '');
        if (!in_array($path, self::PATHS, true)) {
            throw new \InvalidArgumentException('listings.path must be one of: ' . implode(', ', self::PATHS));
        }
        $attrs = [];
        foreach ((array) ($m['attributes'] ?? []) as $a) {
            $key = (string) ($a['key'] ?? '');
            $type = (string) ($a['type'] ?? 'text');
            if (!preg_match('/^[a-z][a-z0-9_]{0,30}$/', $key) || isset($attrs[$key])) {
                throw new \InvalidArgumentException("listings.attributes: invalid or duplicate key \"$key\"");
            }
            if (!in_array($type, self::TYPES, true)) {
                throw new \InvalidArgumentException("listings.attributes.$key: unknown type \"$type\"");
            }
            $options = array_values(array_filter(array_map('strval', (array) ($a['options'] ?? []))));
            if ($type === 'select' && !$options) {
                throw new \InvalidArgumentException("listings.attributes.$key: a select needs options");
            }
            $attrs[$key] = [
                'key' => $key, 'type' => $type, 'label' => (string) ($a['label'] ?? ucfirst($key)), 'options' => $options,
                'icon' => preg_match('/^bi-[a-z0-9-]+$/', (string) ($a['icon'] ?? '')) ? $a['icon'] : '', 'suffix' => (string) ($a['suffix'] ?? ''),
                'filter' => !empty($a['filter']) && in_array($type, ['number', 'select', 'toggle'], true), 'card' => !empty($a['card']), 'help' => (string) ($a['help'] ?? ''),
            ];
        }
        if (count($attrs) > 20) {
            throw new \InvalidArgumentException('listings.attributes: at most 20');
        }
        $e = (array) ($m['enquiry'] ?? []);
        return [
            'path' => $path,
            'singular' => (string) ($m['singular'] ?? 'Listing'), 'plural' => (string) ($m['plural'] ?? 'Listings'),
            'menu_label' => (string) ($m['menu_label'] ?? ($m['plural'] ?? 'Listings')),
            'icon' => preg_match('/^bi-[a-z0-9-]+$/', (string) ($m['icon'] ?? '')) ? $m['icon'] : 'bi-collection',
            'category_label' => (string) ($m['category_label'] ?? 'Category'),
            'location_label' => (string) ($m['location_label'] ?? 'Location'),
            'location_filter' => (bool) ($m['location_filter'] ?? true),
            'price_label' => (string) ($m['price_label'] ?? 'Price'),
            'price_format' => in_array($m['price_format'] ?? '', ['inr', 'lakh', 'none'], true) ? $m['price_format'] : 'inr',
            'price_notes' => array_values(array_map('strval', (array) ($m['price_notes'] ?? []))),
            'closed_label' => (string) ($m['closed_label'] ?? 'Closed'),
            'attributes' => $attrs,
            'enquiry' => [
                'heading' => (string) ($e['heading'] ?? 'Send an enquiry'),
                'button' => (string) ($e['button'] ?? 'Send enquiry'),
                'date_label' => (string) ($e['date_label'] ?? ''),
                'select_label' => (string) ($e['select_label'] ?? ''),
                'select_options' => array_values(array_map('strval', (array) ($e['select_options'] ?? []))),
                'message_label' => (string) ($e['message_label'] ?? 'Message (optional)'),
                'success' => (string) ($e['success'] ?? 'Thank you! We will contact you shortly.'),
            ],
        ];
    }

    // ================================================================== public queries

    public function categories(bool $activeOnly = true): array
    {
        $c = $this->db->t('listing_categories');
        $l = $this->db->t('listings');
        return $this->db->fetchAll("SELECT c.*, (SELECT COUNT(*) FROM $l WHERE category_id = c.id AND status <> 'draft') AS listing_count FROM $c c"
            . ($activeOnly ? " WHERE c.status = 'active'" : '') . ' ORDER BY c.sort_order, c.name');
    }

    public function categoryBySlug(string $slug): ?array
    {
        return $this->db->fetch('SELECT * FROM ' . $this->db->t('listing_categories') . " WHERE slug = ? AND status = 'active'", [$slug]);
    }

    /** Distinct locations of visible listings (for the location filter). */
    public function locations(): array
    {
        return array_map('strval', array_column($this->db->fetchAll('SELECT DISTINCT location FROM ' . $this->db->t('listings')
            . " WHERE status <> 'draft' AND location IS NOT NULL AND location <> '' ORDER BY location"), 'location'));
    }

    /**
     * Visible listings. Filters: category (id), featured, q, location, min_price, max_price (paise), attr[key] (select = equals,
     * number = at least, toggle = yes), sort (featured|new|price_asc|price_desc), include_closed (default true), limit, offset.
     */
    public function search(array $f = []): array
    {
        $l = $this->db->t('listings');
        $where = $f['include_closed'] ?? true ? ["l.status IN ('active', 'closed')"] : ["l.status = 'active'"];
        $params = [];
        if (!empty($f['category'])) {
            $where[] = 'l.category_id = ?';
            $params[] = (int) $f['category'];
        }
        if (!empty($f['featured'])) {
            $where[] = 'l.is_featured = 1';
        }
        if (!empty($f['exclude'])) {
            $where[] = 'l.id <> ?';
            $params[] = (int) $f['exclude'];
        }
        $q = trim((string) ($f['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(l.title LIKE ? OR l.location LIKE ? OR l.short_description LIKE ?)';
            $like = '%' . addcslashes(mb_substr($q, 0, 60), '%_\\') . '%';
            array_push($params, $like, $like, $like);
        }
        if (($f['location'] ?? '') !== '') {
            $where[] = 'l.location = ?';
            $params[] = mb_substr((string) $f['location'], 0, 120);
        }
        foreach (['min_price' => '>=', 'max_price' => '<='] as $k => $op) {
            if (isset($f[$k]) && $f[$k] !== '' && $f[$k] !== null) {
                $where[] = "l.price IS NOT NULL AND l.price $op ?";
                $params[] = (int) $f[$k];
            }
        }
        foreach ((array) ($f['attr'] ?? []) as $key => $val) {
            $a = $this->config['attributes'][$key] ?? null;
            if (!$a || !$a['filter'] || $val === '' || $val === null) {
                continue;
            }
            $expr = "JSON_UNQUOTE(JSON_EXTRACT(l.attributes_json, '$.\"$key\"'))";
            if ($a['type'] === 'number') {
                $where[] = "CAST($expr AS DECIMAL(14,2)) >= ?";
                $params[] = (float) $val;
            } elseif ($a['type'] === 'toggle') {
                $where[] = "$expr = 'true'";
            } elseif (in_array((string) $val, $a['options'], true)) {
                $where[] = "$expr = ?";
                $params[] = (string) $val;
            }
        }
        $order = match ((string) ($f['sort'] ?? '')) {
            'new' => 'l.created_at DESC, l.id DESC',
            'price_asc' => 'l.price IS NULL, l.price ASC, l.id',
            'price_desc' => 'l.price IS NULL, l.price DESC, l.id',
            default => 'l.is_featured DESC, l.sort_order, l.id DESC',
        };
        $w = implode(' AND ', $where);
        $total = (int) $this->db->value("SELECT COUNT(*) FROM $l l WHERE $w", $params);
        $limit = max(1, min(60, (int) ($f['limit'] ?? 12)));
        $offset = max(0, (int) ($f['offset'] ?? 0));
        $rows = $this->db->fetchAll("SELECT l.*, c.name AS category_name, c.slug AS category_slug FROM $l l LEFT JOIN " . $this->db->t('listing_categories')
            . " c ON c.id = l.category_id WHERE $w ORDER BY (l.status = 'closed'), $order LIMIT $limit OFFSET $offset", $params);
        return ['rows' => array_map(fn ($r) => $this->decorate($r), $rows), 'total' => $total];
    }

    public function bySlug(string $slug): ?array
    {
        $r = $this->db->fetch('SELECT l.*, c.name AS category_name, c.slug AS category_slug FROM ' . $this->db->t('listings') . ' l LEFT JOIN ' . $this->db->t('listing_categories')
            . " c ON c.id = l.category_id WHERE l.slug = ? AND l.status IN ('active', 'closed')", [$slug]);
        return $r ? $this->decorate($r) : null;
    }

    /** Adds gallery, attrs (values by key), facts (labelled values for display), price_text. */
    public function decorate(array $r): array
    {
        return self::decorateWith($r, $this->config());
    }

    public static function decorateWith(array $r, array $cfg): array
    {
        $r['gallery'] = array_values(array_filter((array) (json_decode((string) ($r['gallery_json'] ?? ''), true) ?: []), 'is_string'));
        $vals = (array) (json_decode((string) ($r['attributes_json'] ?? ''), true) ?: []);
        $r['attrs'] = $vals;
        $facts = [];
        $lists = [];
        foreach ($cfg['attributes'] as $key => $a) {
            $v = $vals[$key] ?? null;
            if ($v === null || $v === '' || $v === [] || ($a['type'] === 'toggle' && $v !== true)) {
                continue;
            }
            if ($a['type'] === 'list') {
                $lists[] = ['key' => $key, 'label' => $a['label'], 'icon' => $a['icon'], 'items' => (array) $v];
                continue;
            }
            $text = $a['type'] === 'toggle' ? 'Yes' : ($a['type'] === 'number' ? self::number($v) : (string) $v);
            $facts[] = ['key' => $key, 'label' => $a['label'], 'icon' => $a['icon'], 'value' => $text . ($a['suffix'] !== '' && $a['type'] === 'number' ? ' ' . $a['suffix'] : ''), 'card' => $a['card']];
        }
        $r['facts'] = $facts;
        $r['lists'] = $lists;
        $r['price_text'] = $cfg['price_format'] !== 'none' && $r['price'] !== null && $r['price'] !== '' ? self::price((int) $r['price'], $cfg['price_format']) : '';
        $r['is_closed'] = ($r['status'] ?? '') === 'closed';
        return $r;
    }

    /** ₹ price; "lakh" format shortens large amounts: ₹85 Lakh, ₹1.25 Cr. */
    public static function price(int $paise, string $format = 'inr'): string
    {
        $rupees = intdiv($paise, 100);
        if ($format === 'lakh' && $rupees >= 100000) {
            if ($rupees >= 10000000) {
                return '₹' . rtrim(rtrim(number_format($rupees / 10000000, 2, '.', ''), '0'), '.') . ' Cr';
            }
            return '₹' . rtrim(rtrim(number_format($rupees / 100000, 2, '.', ''), '0'), '.') . ' Lakh';
        }
        return '₹' . \SiteRuntime\Payments\PaymentService::inr($paise, false);
    }

    private static function number(mixed $v): string
    {
        $f = (float) $v;
        return floor($f) == $f ? \SiteRuntime\Payments\PaymentService::inr((int) $f * 100, false) : rtrim(rtrim(number_format($f, 2, '.', ''), '0'), '.');
    }

    // ================================================================== admin

    public function adminList(array $f, int $page, int $perPage): array
    {
        $l = $this->db->t('listings');
        $where = ['1=1'];
        $params = [];
        if (in_array($f['status'] ?? '', ['active', 'closed', 'draft'], true)) {
            $where[] = 'l.status = ?';
            $params[] = $f['status'];
        }
        if (($f['category'] ?? '') !== '') {
            $where[] = 'l.category_id ' . ((int) $f['category'] ? '= ' . (int) $f['category'] : 'IS NULL');
        }
        $q = trim((string) ($f['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(l.title LIKE ? OR l.location LIKE ?)';
            $like = '%' . addcslashes($q, '%_\\') . '%';
            array_push($params, $like, $like);
        }
        $w = implode(' AND ', $where);
        $total = (int) $this->db->value("SELECT COUNT(*) FROM $l l WHERE $w", $params);
        $perPage = max(1, min(100, $perPage));
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $rows = $this->db->fetchAll("SELECT l.id, l.title, l.slug, l.location, l.price, l.price_note, l.status, l.is_featured, l.image, l.updated_at, c.name AS category_name FROM $l l LEFT JOIN "
            . $this->db->t('listing_categories') . " c ON c.id = l.category_id WHERE $w ORDER BY l.sort_order, l.id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage), $params);
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    public function find(int $id): array
    {
        $r = $this->db->fetch('SELECT * FROM ' . $this->db->t('listings') . ' WHERE id = ?', [$id]) ?? throw new NotFound('Not found.');
        $r['gallery'] = array_values(array_filter((array) (json_decode((string) ($r['gallery_json'] ?? ''), true) ?: []), 'is_string'));
        $r['attrs'] = (array) (json_decode((string) ($r['attributes_json'] ?? ''), true) ?: []);
        return $r;
    }

    /** Create or update from the admin form (price in rupees; attributes posted as attr[key]). */
    public function save(array $in, ?int $id = null): int
    {
        $cfg = $this->config();
        $errors = [];
        $title = trim(strip_tags((string) ($in['title'] ?? '')));
        if ($title === '' || mb_strlen($title) > 150) {
            $errors['title'] = 'Enter a title (up to 150 characters).';
        }
        $priceIn = $cfg['price_format'] === 'none' ? '' : trim(str_replace([',', '₹', ' '], '', (string) ($in['price'] ?? '')));
        $price = null;
        if ($priceIn !== '') {
            if (!preg_match('/^\d{1,12}(\.\d{1,2})?$/', $priceIn)) {
                $errors['price'] = 'Enter the amount in rupees (e.g. 8500000), or leave it empty for “price on request”.';
            } else {
                $price = (int) round((float) $priceIn * 100);
            }
        }
        $catId = (int) ($in['category_id'] ?? 0);
        if ($catId && !$this->db->value('SELECT id FROM ' . $this->db->t('listing_categories') . ' WHERE id = ?', [$catId])) {
            $errors['category_id'] = 'Choose a ' . strtolower($cfg['category_label']) . '.';
        }
        $images = [];
        foreach (array_slice(array_values(array_filter(array_map('strval', (array) ($in['images'] ?? [])))), 0, 12) as $ref) {
            if (!$this->imageOk || !($this->imageOk)($ref)) {
                $errors['image'] = 'Images must come from the media library.';
                break;
            }
            $images[] = $ref;
        }
        $attrs = [];
        foreach ($cfg['attributes'] as $key => $a) {
            $v = $in['attr'][$key] ?? null;
            $field = "attr[$key]";
            switch ($a['type']) {
                case 'number':
                    $v = trim(str_replace(',', '', (string) $v));
                    if ($v === '') {
                        break;
                    }
                    if (!preg_match('/^\d{1,9}(\.\d{1,2})?$/', $v)) {
                        $errors[$field] = $a['label'] . ': enter a number.';
                    } else {
                        $attrs[$key] = str_contains($v, '.') ? (float) $v : (int) $v;
                    }
                    break;
                case 'select':
                    $v = (string) $v;
                    if ($v !== '' && !in_array($v, $a['options'], true)) {
                        $errors[$field] = $a['label'] . ': choose an option.';
                    } elseif ($v !== '') {
                        $attrs[$key] = $v;
                    }
                    break;
                case 'toggle':
                    if ((string) $v === '1') {
                        $attrs[$key] = true;
                    }
                    break;
                case 'list':
                    $items = array_slice(array_values(array_filter(array_map(static fn ($x) => mb_substr(trim(strip_tags($x)), 0, 120), preg_split('/\R/', (string) $v) ?: []))), 0, 40);
                    if ($items) {
                        $attrs[$key] = $items;
                    }
                    break;
                default:
                    $v = mb_substr(trim(strip_tags((string) $v)), 0, 120);
                    if ($v !== '') {
                        $attrs[$key] = $v;
                    }
            }
        }
        if ($errors) {
            throw new ValidationError('Please correct the highlighted fields.', $errors);
        }
        $desc = (string) ($in['description'] ?? '');
        $data = [
            'category_id' => $catId ?: null, 'title' => $title,
            'location' => mb_substr(trim(strip_tags((string) ($in['location'] ?? ''))), 0, 120) ?: null,
            'price' => $price, 'price_note' => mb_substr(trim(strip_tags((string) ($in['price_note'] ?? ''))), 0, 40) ?: null,
            'short_description' => mb_substr(trim(strip_tags((string) ($in['short_description'] ?? ''))), 0, 400) ?: null,
            'description' => $this->richtext ? ($this->richtext)($desc) : htmlspecialchars(strip_tags($desc), ENT_QUOTES, 'UTF-8'),
            'image' => $images[0] ?? null, 'gallery_json' => count($images) > 1 ? json_encode(array_slice($images, 1), JSON_UNESCAPED_SLASHES) : null,
            'attributes_json' => $attrs ? json_encode($attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'badge' => mb_substr(trim(strip_tags((string) ($in['badge'] ?? ''))), 0, 30) ?: null,
            'is_featured' => ($in['is_featured'] ?? '') === '1' ? 1 : 0,
            'status' => in_array($in['status'] ?? '', ['active', 'closed', 'draft'], true) ? $in['status'] : 'active',
            'sort_order' => (int) ($in['sort_order'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($id) {
            $this->find($id);
            $this->db->update('listings', $data, $id);
            return $id;
        }
        return $this->db->insert('listings', $data + ['slug' => $this->uniqueSlug('listings', $title), 'created_at' => date('Y-m-d H:i:s')]);
    }

    public function delete(int $id): void
    {
        $this->find($id);
        $this->db->delete('listings', $id);
    }

    public function saveCategory(array $in, ?int $id = null): int
    {
        $name = trim(strip_tags((string) ($in['name'] ?? '')));
        $errors = [];
        if ($name === '' || mb_strlen($name) > 80) {
            $errors['name'] = 'Enter a name (up to 80 characters).';
        }
        $image = trim((string) ($in['image'] ?? ''));
        if ($image !== '' && (!$this->imageOk || !($this->imageOk)($image))) {
            $errors['image'] = 'Choose an image from the media library.';
        }
        if ($errors) {
            throw new ValidationError('Please correct the highlighted fields.', $errors);
        }
        $data = ['name' => $name, 'description' => mb_substr(trim(strip_tags((string) ($in['description'] ?? ''))), 0, 400) ?: null, 'image' => $image ?: null,
            'status' => ($in['status'] ?? '') === 'hidden' ? 'hidden' : 'active', 'sort_order' => (int) ($in['sort_order'] ?? 0), 'updated_at' => date('Y-m-d H:i:s')];
        if ($id) {
            $this->db->fetch('SELECT id FROM ' . $this->db->t('listing_categories') . ' WHERE id = ?', [$id]) ?? throw new NotFound('Not found.');
            $this->db->update('listing_categories', $data, $id);
            return $id;
        }
        return $this->db->insert('listing_categories', $data + ['slug' => $this->uniqueSlug('listing_categories', $name), 'created_at' => date('Y-m-d H:i:s')]);
    }

    public function deleteCategory(int $id): void
    {
        $this->db->fetch('SELECT id FROM ' . $this->db->t('listing_categories') . ' WHERE id = ?', [$id]) ?? throw new NotFound('Not found.');
        $this->db->query('UPDATE ' . $this->db->t('listings') . ' SET category_id = NULL WHERE category_id = ?', [$id]);
        $this->db->delete('listing_categories', $id);
    }

    /** Insert a template's demo listings: ['categories' => [...], 'items' => [...]] with prices in paise. */
    public static function seed(Db $db, array $demo): void
    {
        $now = date('Y-m-d H:i:s');
        $cats = [];
        foreach ((array) ($demo['categories'] ?? []) as $i => $c) {
            $cats[(string) $c['slug']] = $db->insert('listing_categories', ['name' => (string) $c['name'], 'slug' => (string) $c['slug'], 'description' => $c['description'] ?? null,
                'image' => $c['image'] ?? null, 'status' => 'active', 'sort_order' => $i + 1, 'created_at' => $now, 'updated_at' => $now]);
        }
        $items = (array) ($demo['items'] ?? []);
        foreach ($items as $i => $it) {
            $created = date('Y-m-d H:i:s', time() - (count($items) - $i) * 3600);
            $db->insert('listings', [
                'category_id' => $cats[(string) ($it['category'] ?? '')] ?? null, 'title' => (string) $it['title'], 'slug' => (string) $it['slug'],
                'location' => $it['location'] ?? null, 'price' => isset($it['price']) ? (int) $it['price'] : null, 'price_note' => $it['price_note'] ?? null,
                'short_description' => $it['short_description'] ?? null, 'description' => $it['description'] ?? null, 'image' => $it['image'] ?? null,
                'gallery_json' => !empty($it['gallery']) ? json_encode(array_values((array) $it['gallery']), JSON_UNESCAPED_SLASHES) : null,
                'attributes_json' => !empty($it['attributes']) ? json_encode((array) $it['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'badge' => $it['badge'] ?? null, 'is_featured' => !empty($it['featured']) ? 1 : 0, 'status' => in_array($it['status'] ?? '', ['active', 'closed'], true) ? $it['status'] : 'active',
                'sort_order' => $i + 1, 'created_at' => $created, 'updated_at' => $created,
            ]);
        }
    }

    private function uniqueSlug(string $table, string $name): string
    {
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) (function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) : $name))), '-') ?: 'item';
        $base = substr($base, 0, 80);
        $slug = $base;
        for ($i = 2; (int) $this->db->value('SELECT COUNT(*) FROM ' . $this->db->t($table) . ' WHERE slug = ?', [$slug]) > 0; $i++) {
            $slug = $base . '-' . $i;
        }
        return $slug;
    }
}
