<?php
declare(strict_types=1);

namespace SiteRuntime\Shop;

use SiteRuntime\Db;
use SiteRuntime\NotFound;
use SiteRuntime\ValidationError;

/**
 * The website's online shop: settings, catalogue (categories, products, variants), coupons and orders.
 * Money is always an integer number of paise. Used by the public shop pages and the website admin.
 */
final class ShopService
{
    public const GST_RATES = ['0', '0.25', '3', '5', '12', '18', '28'];
    public const ORDER_STATUSES = ['pending_payment' => 'Awaiting payment', 'placed' => 'Placed', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];
    public const STATES = ['Andaman and Nicobar Islands', 'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chandigarh', 'Chhattisgarh', 'Dadra and Nagar Haveli and Daman and Diu', 'Delhi', 'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jammu and Kashmir', 'Jharkhand', 'Karnataka', 'Kerala', 'Ladakh', 'Lakshadweep', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Puducherry', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal'];
    private const ORDER_LIMIT = 10;     // orders per IP …
    private const ORDER_WINDOW = 3600;  // … per hour

    private ?array $settings = null;

    /** @param ?callable(string):bool $imageOk  checks an image reference (uploads/… in the media library, or theme:…) */
    public function __construct(private Db $db, private $imageOk = null, private $richtext = null)
    {
    }

    public function db(): Db
    {
        return $this->db;
    }

    // ================================================================== settings

    public static function defaults(): array
    {
        return [
            'enabled' => false,
            'legal_name' => '', 'gstin' => '', 'store_state' => 'Kerala', 'invoice_address' => '',
            'prices_include_tax' => true,
            'order_prefix' => 'OD', 'invoice_prefix' => 'INV-',
            'shipping_flat' => 0, 'free_shipping_above' => 0, 'shipping_note' => 'Delivered in 3–7 working days.',
            'cod_enabled' => true, 'cod_fee' => 0, 'cod_max' => 1000000,
            'min_order' => 0, 'low_stock' => 5,
            'checkout_note' => '', 'return_policy' => '',
        ];
    }

    public function settings(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }
        $raw = $this->db->value('SELECT setting_value FROM ' . $this->db->t('settings') . ' WHERE setting_key = ?', ['shop']);
        $saved = is_string($raw) ? (json_decode($raw, true) ?: []) : [];
        return $this->settings = array_replace(self::defaults(), array_intersect_key($saved, self::defaults()));
    }

    public function enabled(): bool
    {
        return (bool) $this->settings()['enabled'];
    }

    public function saveSettings(array $in): void
    {
        $errors = [];
        $s = $this->settings();
        $str = static fn (string $k, int $max): string => mb_substr(trim(strip_tags((string) ($in[$k] ?? ''))), 0, $max);
        $out = [
            'enabled' => ($in['enabled'] ?? '') === '1',
            'legal_name' => $str('legal_name', 150),
            'gstin' => strtoupper($str('gstin', 15)),
            'store_state' => (string) ($in['store_state'] ?? ''),
            'invoice_address' => $str('invoice_address', 400),
            'prices_include_tax' => ($in['prices_include_tax'] ?? '') === '1',
            'order_prefix' => strtoupper($str('order_prefix', 6)),
            'invoice_prefix' => strtoupper($str('invoice_prefix', 12)),
            'shipping_note' => $str('shipping_note', 200),
            'cod_enabled' => ($in['cod_enabled'] ?? '') === '1',
            'checkout_note' => $str('checkout_note', 400),
            'return_policy' => $str('return_policy', 600),
            'low_stock' => max(0, min(1000, (int) ($in['low_stock'] ?? 5))),
        ];
        foreach (['shipping_flat' => 'Shipping charge', 'free_shipping_above' => 'Free shipping above', 'cod_fee' => 'Cash on delivery fee', 'cod_max' => 'Cash on delivery limit', 'min_order' => 'Minimum order'] as $k => $label) {
            $v = self::parseMoney($in[$k] ?? '0');
            if ($v === null || $v > 100_000_000) {
                $errors[$k] = "$label must be an amount in rupees.";
            }
            $out[$k] = (int) $v;
        }
        if ($out['gstin'] !== '' && !preg_match('/^\d{2}[A-Z]{5}\d{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $out['gstin'])) {
            $errors['gstin'] = 'Enter a valid 15-character GSTIN, or leave it empty.';
        }
        if (!in_array($out['store_state'], self::STATES, true)) {
            $errors['store_state'] = 'Choose the state the shop ships from.';
        }
        if (!preg_match('/^[A-Z]{1,6}$/', $out['order_prefix'])) {
            $errors['order_prefix'] = 'Use 1–6 capital letters.';
        }
        if (!preg_match('/^[A-Z0-9\/-]{1,12}$/', $out['invoice_prefix'])) {
            $errors['invoice_prefix'] = 'Use up to 12 capital letters, digits, / or -.';
        }
        if ($errors) {
            throw new ValidationError('Please correct the highlighted fields.', $errors);
        }
        $this->db->query('REPLACE INTO ' . $this->db->t('settings') . ' (setting_key, setting_value, updated_at) VALUES (?, ?, ?)',
            ['shop', json_encode(array_replace($s, $out), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), date('Y-m-d H:i:s')]);
        $this->settings = null;
    }

    // ================================================================== catalogue (public)

    public function categories(bool $activeOnly = true): array
    {
        $t = $this->db->t('product_categories');
        $p = $this->db->t('products');
        return $this->db->fetchAll("SELECT c.*, (SELECT COUNT(*) FROM $p WHERE category_id = c.id AND status = 'active') AS product_count FROM $t c"
            . ($activeOnly ? " WHERE c.status = 'active'" : '') . ' ORDER BY c.sort_order, c.name');
    }

    public function categoryBySlug(string $slug): ?array
    {
        return $this->db->fetch('SELECT * FROM ' . $this->db->t('product_categories') . " WHERE slug = ? AND status = 'active'", [$slug]);
    }

    /**
     * Active products for listings. Filters: category (id), featured, q, sort (featured|new|price_asc|price_desc|name),
     * ids, exclude, limit, offset. Returns ['rows' => …, 'total' => …].
     */
    public function products(array $f = []): array
    {
        $p = $this->db->t('products');
        $v = $this->db->t('product_variants');
        $c = $this->db->t('product_categories');
        $where = ["p.status = 'active'"];
        $params = [];
        if (!empty($f['category'])) {
            $where[] = 'p.category_id = ?';
            $params[] = (int) $f['category'];
        }
        if (!empty($f['featured'])) {
            $where[] = 'p.is_featured = 1';
        }
        if (!empty($f['ids'])) {
            $ids = array_values(array_filter(array_map('intval', (array) $f['ids'])));
            $where[] = $ids ? 'p.id IN (' . implode(',', $ids) . ')' : '1=0';
        }
        if (!empty($f['exclude'])) {
            $where[] = 'p.id <> ?';
            $params[] = (int) $f['exclude'];
        }
        $q = trim((string) ($f['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(p.name LIKE ? OR p.short_description LIKE ? OR p.sku LIKE ?)';
            $like = '%' . addcslashes(mb_substr($q, 0, 60), '%_\\') . '%';
            array_push($params, $like, $like, $like);
        }
        $order = match ((string) ($f['sort'] ?? '')) {
            'new' => 'p.created_at DESC, p.id DESC',
            'price_asc' => 'eff_price ASC, p.id',
            'price_desc' => 'eff_price DESC, p.id',
            'name' => 'p.name',
            default => 'p.is_featured DESC, p.sort_order, p.id DESC',
        };
        $w = implode(' AND ', $where);
        $total = (int) $this->db->value("SELECT COUNT(*) FROM $p p WHERE $w", $params);
        $limit = max(1, min(60, (int) ($f['limit'] ?? 12)));
        $offset = max(0, (int) ($f['offset'] ?? 0));
        $rows = $this->db->fetchAll("SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                (SELECT MIN(COALESCE(x.price, p.price)) FROM $v x WHERE x.product_id = p.id) AS from_price,
                (SELECT MAX(COALESCE(x.price, p.price)) FROM $v x WHERE x.product_id = p.id) AS to_price,
                (SELECT COUNT(*) FROM $v x WHERE x.product_id = p.id) AS variant_count,
                (SELECT COALESCE(SUM(GREATEST(x.stock, 0)), 0) FROM $v x WHERE x.product_id = p.id) AS variant_stock,
                COALESCE((SELECT MIN(COALESCE(x.price, p.price)) FROM $v x WHERE x.product_id = p.id), p.price) AS eff_price
            FROM $p p LEFT JOIN $c c ON c.id = p.category_id WHERE $w ORDER BY $order LIMIT $limit OFFSET $offset", $params);
        return ['rows' => array_map([self::class, 'decorate'], $rows), 'total' => $total];
    }

    public function productBySlug(string $slug, bool $activeOnly = true): ?array
    {
        $row = $this->db->fetch('SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM ' . $this->db->t('products') . ' p LEFT JOIN ' . $this->db->t('product_categories')
            . ' c ON c.id = p.category_id WHERE p.slug = ?' . ($activeOnly ? " AND p.status = 'active'" : ''), [$slug]);
        if (!$row) {
            return null;
        }
        $row['variants'] = $this->variants((int) $row['id']);
        $row['variant_count'] = count($row['variants']);
        $row['variant_stock'] = array_sum(array_map(static fn ($x) => max(0, (int) $x['stock']), $row['variants']));
        $row['from_price'] = $row['variants'] ? min(array_map(static fn ($x) => (int) ($x['price'] ?? $row['price']), $row['variants'])) : null;
        $row['to_price'] = $row['variants'] ? max(array_map(static fn ($x) => (int) ($x['price'] ?? $row['price']), $row['variants'])) : null;
        return self::decorate($row);
    }

    public function variants(int $productId): array
    {
        return $this->db->fetchAll('SELECT * FROM ' . $this->db->t('product_variants') . ' WHERE product_id = ? ORDER BY sort_order, id', [$productId]);
    }

    /** Derived fields every product row carries: gallery, in_stock, display price, discount %. */
    public static function decorate(array $p): array
    {
        $p['gallery'] = array_values(array_filter((array) (json_decode((string) ($p['gallery_json'] ?? ''), true) ?: []), 'is_string'));
        $hasVariants = (int) ($p['variant_count'] ?? 0) > 0;
        $p['in_stock'] = !(int) $p['track_stock'] || ($hasVariants ? (int) $p['variant_stock'] > 0 : (int) $p['stock'] > 0);
        $p['display_price'] = $hasVariants && $p['from_price'] !== null ? (int) $p['from_price'] : (int) $p['price'];
        $p['price_from'] = $hasVariants && ($p['from_price'] ?? null) !== null && (int) $p['from_price'] !== (int) ($p['to_price'] ?? $p['from_price']);
        $cmp = (int) ($p['compare_price'] ?? 0);
        $p['discount_pct'] = $cmp > $p['display_price'] ? (int) floor(($cmp - $p['display_price']) * 100 / $cmp) : 0;
        return $p;
    }

    // ================================================================== admin: categories

    public function saveCategory(array $in, ?int $id = null): int
    {
        $name = trim(strip_tags((string) ($in['name'] ?? '')));
        $errors = [];
        if ($name === '' || mb_strlen($name) > 80) {
            $errors['name'] = 'Enter a category name (up to 80 characters).';
        }
        $image = trim((string) ($in['image'] ?? ''));
        if ($image !== '' && !$this->imageOk($image)) {
            $errors['image'] = 'Choose an image from the media library.';
        }
        if ($errors) {
            throw new ValidationError('Please correct the highlighted fields.', $errors);
        }
        $data = [
            'name' => $name,
            'description' => mb_substr(trim(strip_tags((string) ($in['description'] ?? ''))), 0, 400) ?: null,
            'image' => $image ?: null,
            'status' => ($in['status'] ?? 'active') === 'hidden' ? 'hidden' : 'active',
            'sort_order' => (int) ($in['sort_order'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($id) {
            $this->category($id);
            $this->db->update('product_categories', $data, $id);
            return $id;
        }
        $data['slug'] = $this->uniqueSlug('product_categories', $name);
        return $this->db->insert('product_categories', $data + ['created_at' => date('Y-m-d H:i:s')]);
    }

    public function category(int $id): array
    {
        return $this->db->fetch('SELECT * FROM ' . $this->db->t('product_categories') . ' WHERE id = ?', [$id]) ?? throw new NotFound('Category not found.');
    }

    public function deleteCategory(int $id): void
    {
        $this->category($id);
        $this->db->query('UPDATE ' . $this->db->t('products') . ' SET category_id = NULL WHERE category_id = ?', [$id]);
        $this->db->delete('product_categories', $id);
    }

    // ================================================================== admin: products

    public function adminProducts(array $f, int $page, int $perPage): array
    {
        $p = $this->db->t('products');
        $where = ['1=1'];
        $params = [];
        if (in_array($f['status'] ?? '', ['active', 'draft'], true)) {
            $where[] = 'p.status = ?';
            $params[] = $f['status'];
        }
        if (($f['category'] ?? '') !== '') {
            $where[] = 'p.category_id ' . ((int) $f['category'] ? '= ' . (int) $f['category'] : 'IS NULL');
        }
        if (($f['stock'] ?? '') === 'low') {
            $low = (int) $this->settings()['low_stock'];
            $where[] = "p.track_stock = 1 AND ((p.stock <= $low AND NOT EXISTS (SELECT 1 FROM " . $this->db->t('product_variants') . ' v WHERE v.product_id = p.id)) OR EXISTS (SELECT 1 FROM ' . $this->db->t('product_variants') . " v WHERE v.product_id = p.id AND v.stock <= $low))";
        }
        $q = trim((string) ($f['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(p.name LIKE ? OR p.sku LIKE ?)';
            $like = '%' . addcslashes($q, '%_\\') . '%';
            array_push($params, $like, $like);
        }
        $w = implode(' AND ', $where);
        $total = (int) $this->db->value("SELECT COUNT(*) FROM $p p WHERE $w", $params);
        $perPage = max(1, min(100, $perPage));
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $rows = $this->db->fetchAll("SELECT p.id, p.name, p.slug, p.sku, p.price, p.compare_price, p.stock, p.track_stock, p.status, p.is_featured, p.image, p.gst_rate, c.name AS category_name,
                (SELECT COUNT(*) FROM " . $this->db->t('product_variants') . " v WHERE v.product_id = p.id) AS variant_count,
                (SELECT COALESCE(SUM(v.stock), 0) FROM " . $this->db->t('product_variants') . " v WHERE v.product_id = p.id) AS variant_stock
            FROM $p p LEFT JOIN " . $this->db->t('product_categories') . " c ON c.id = p.category_id WHERE $w ORDER BY p.sort_order, p.id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage), $params);
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    public function product(int $id): array
    {
        $p = $this->db->fetch('SELECT * FROM ' . $this->db->t('products') . ' WHERE id = ?', [$id]) ?? throw new NotFound('Product not found.');
        $p['variants'] = $this->variants($id);
        $p['gallery'] = array_values(array_filter((array) (json_decode((string) ($p['gallery_json'] ?? ''), true) ?: []), 'is_string'));
        return $p;
    }

    /** Create or update a product from the admin form (prices in rupees, variants as rows). */
    public function saveProduct(array $in, ?int $id = null): int
    {
        $errors = [];
        $name = trim(strip_tags((string) ($in['name'] ?? '')));
        if ($name === '' || mb_strlen($name) > 150) {
            $errors['name'] = 'Enter a product name (up to 150 characters).';
        }
        $price = self::parseMoney($in['price'] ?? '');
        if ($price === null || $price < 100 || $price > 100_000_000) {
            $errors['price'] = 'Enter a price between ₹1 and ₹10,00,000.';
        }
        $compare = trim((string) ($in['compare_price'] ?? '')) === '' ? null : self::parseMoney($in['compare_price']);
        if ($compare !== null && ($price === null || $compare <= $price)) {
            $errors['compare_price'] = 'The “was” price must be higher than the price (or leave it empty).';
        }
        if (trim((string) ($in['compare_price'] ?? '')) !== '' && $compare === null) {
            $errors['compare_price'] = 'Enter an amount in rupees.';
        }
        $gst = (string) ($in['gst_rate'] ?? '0');
        if (!in_array($gst, self::GST_RATES, true)) {
            $errors['gst_rate'] = 'Choose a GST rate.';
        }
        $hsn = trim((string) ($in['hsn'] ?? ''));
        if ($hsn !== '' && !preg_match('/^\d{4,8}$/', $hsn)) {
            $errors['hsn'] = 'HSN code: 4 to 8 digits.';
        }
        $sku = mb_substr(trim(strip_tags((string) ($in['sku'] ?? ''))), 0, 60);
        $stock = (string) ($in['stock'] ?? '0');
        if (!preg_match('/^\d{1,6}$/', $stock)) {
            $errors['stock'] = 'Stock: a whole number.';
        }
        $catId = (int) ($in['category_id'] ?? 0);
        if ($catId && !$this->db->value('SELECT id FROM ' . $this->db->t('product_categories') . ' WHERE id = ?', [$catId])) {
            $errors['category_id'] = 'Choose a category.';
        }
        $image = trim((string) ($in['image'] ?? ''));
        if ($image !== '' && !$this->imageOk($image)) {
            $errors['image'] = 'Choose the image from the media library.';
        }
        $gallery = [];
        foreach (array_slice((array) ($in['gallery'] ?? []), 0, 8) as $g) {
            $g = trim((string) $g);
            if ($g === '') {
                continue;
            }
            if (!$this->imageOk($g)) {
                $errors['gallery'] = 'Gallery images must come from the media library.';
                break;
            }
            $gallery[] = $g;
        }
        $optionName = mb_substr(trim(strip_tags((string) ($in['option_name'] ?? ''))), 0, 40);
        $variants = [];
        foreach (array_slice(array_values((array) ($in['variants'] ?? [])), 0, 40) as $i => $v) {
            if (!is_array($v)) {
                continue;
            }
            $label = mb_substr(trim(strip_tags((string) ($v['label'] ?? ''))), 0, 60);
            if ($label === '') {
                continue;
            }
            $vp = trim((string) ($v['price'] ?? '')) === '' ? null : self::parseMoney($v['price']);
            if (trim((string) ($v['price'] ?? '')) !== '' && ($vp === null || $vp < 100)) {
                $errors["variants[$i][price]"] = 'Enter a price, or leave it empty to use the product price.';
            }
            if (!preg_match('/^\d{1,6}$/', (string) ($v['stock'] ?? '0'))) {
                $errors["variants[$i][stock]"] = 'Stock: a whole number.';
            }
            $variants[] = ['id' => (int) ($v['id'] ?? 0), 'label' => $label, 'price' => $vp, 'stock' => (int) ($v['stock'] ?? 0), 'sku' => mb_substr(trim(strip_tags((string) ($v['sku'] ?? ''))), 0, 60) ?: null, 'sort_order' => $i + 1];
        }
        if (count(array_unique(array_map(static fn ($v) => mb_strtolower($v['label']), $variants))) !== count($variants)) {
            $errors['variants'] = 'Each option needs a different name.';
        }
        if ($variants && $optionName === '') {
            $errors['option_name'] = 'Name the option (e.g. Size or Colour).';
        }
        if ($errors) {
            throw new ValidationError('Please correct the highlighted fields.', $errors);
        }
        $desc = (string) ($in['description'] ?? '');
        $data = [
            'category_id' => $catId ?: null,
            'name' => $name,
            'sku' => $sku ?: null,
            'short_description' => mb_substr(trim(strip_tags((string) ($in['short_description'] ?? ''))), 0, 400) ?: null,
            'description' => $this->richtext ? ($this->richtext)($desc) : htmlspecialchars(strip_tags($desc), ENT_QUOTES, 'UTF-8'),
            'price' => $price, 'compare_price' => $compare, 'gst_rate' => $gst, 'hsn' => $hsn ?: null,
            'track_stock' => ($in['track_stock'] ?? '') === '1' ? 1 : 0, 'stock' => (int) $stock,
            'option_name' => $variants ? $optionName : null,
            'image' => $image ?: null, 'gallery_json' => $gallery ? json_encode($gallery, JSON_UNESCAPED_SLASHES) : null,
            'badge' => mb_substr(trim(strip_tags((string) ($in['badge'] ?? ''))), 0, 30) ?: null,
            'is_featured' => ($in['is_featured'] ?? '') === '1' ? 1 : 0,
            'status' => ($in['status'] ?? 'active') === 'draft' ? 'draft' : 'active',
            'sort_order' => (int) ($in['sort_order'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        return $this->db->transaction(function (Db $db) use ($data, $variants, $id, $name): int {
            if ($id) {
                $this->product($id);
                $db->update('products', $data, $id);
            } else {
                $id = $db->insert('products', $data + ['slug' => $this->uniqueSlug('products', $name), 'created_at' => date('Y-m-d H:i:s')]);
            }
            $keep = [];
            $existing = array_column($this->variants($id), null, 'id');
            foreach ($variants as $v) {
                $vid = $v['id'];
                unset($v['id']);
                if ($vid && isset($existing[$vid])) {
                    $db->update('product_variants', $v, $vid);
                    $keep[] = $vid;
                } else {
                    $keep[] = $db->insert('product_variants', $v + ['product_id' => $id]);
                }
            }
            foreach (array_keys($existing) as $vid) {
                if (!in_array($vid, $keep, true)) {
                    $db->delete('product_variants', (int) $vid);
                }
            }
            return $id;
        });
    }

    public function deleteProduct(int $id): void
    {
        $this->product($id);
        $this->db->query('DELETE FROM ' . $this->db->t('product_variants') . ' WHERE product_id = ?', [$id]);
        $this->db->delete('products', $id);
    }

    // ================================================================== admin: coupons

    public function coupons(): array
    {
        return $this->db->fetchAll('SELECT * FROM ' . $this->db->t('coupons') . ' ORDER BY status, id DESC');
    }

    public function saveCoupon(array $in, ?int $id = null): int
    {
        $errors = [];
        $code = strtoupper(trim((string) ($in['code'] ?? '')));
        if (!preg_match('/^[A-Z0-9_-]{3,30}$/', $code)) {
            $errors['code'] = 'Use 3–30 letters, digits, - or _.';
        } elseif ((int) $this->db->value('SELECT COUNT(*) FROM ' . $this->db->t('coupons') . ' WHERE code = ? AND id <> ?', [$code, (int) $id]) > 0) {
            $errors['code'] = 'This code already exists.';
        }
        $type = in_array($in['type'] ?? '', ['percent', 'fixed', 'free_shipping'], true) ? $in['type'] : 'percent';
        $value = 0;
        if ($type === 'percent') {
            $value = (int) ($in['value'] ?? 0);
            if ($value < 1 || $value > 90 || !preg_match('/^\d+$/', (string) ($in['value'] ?? ''))) {
                $errors['value'] = 'Enter a percentage from 1 to 90.';
            }
        } elseif ($type === 'fixed') {
            $value = (int) self::parseMoney($in['value'] ?? '');
            if ($value < 100) {
                $errors['value'] = 'Enter a discount amount in rupees.';
            }
        }
        $min = self::parseMoney($in['min_order'] ?? '0');
        $max = trim((string) ($in['max_discount'] ?? '')) === '' ? null : self::parseMoney($in['max_discount']);
        if ($min === null) {
            $errors['min_order'] = 'Enter an amount in rupees.';
        }
        if (trim((string) ($in['max_discount'] ?? '')) !== '' && ($max === null || $max < 100)) {
            $errors['max_discount'] = 'Enter an amount in rupees, or leave it empty.';
        }
        $dates = [];
        foreach (['starts_at', 'ends_at'] as $k) {
            $d = trim((string) ($in[$k] ?? ''));
            if ($d !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) || !checkdate((int) substr($d, 5, 2), (int) substr($d, 8, 2), (int) substr($d, 0, 4)))) {
                $errors[$k] = 'Choose a date.';
            }
            $dates[$k] = $d ?: null;
        }
        if ($dates['starts_at'] && $dates['ends_at'] && $dates['ends_at'] < $dates['starts_at']) {
            $errors['ends_at'] = 'The end date is before the start date.';
        }
        $limit = trim((string) ($in['usage_limit'] ?? ''));
        if ($limit !== '' && !preg_match('/^[1-9]\d{0,6}$/', $limit)) {
            $errors['usage_limit'] = 'A whole number, or empty for unlimited.';
        }
        if ($errors) {
            throw new ValidationError('Please correct the highlighted fields.', $errors);
        }
        $data = ['code' => $code, 'description' => mb_substr(trim(strip_tags((string) ($in['description'] ?? ''))), 0, 160) ?: null, 'type' => $type, 'value' => $value,
            'min_order' => (int) $min, 'max_discount' => $type === 'percent' ? $max : null, 'starts_at' => $dates['starts_at'], 'ends_at' => $dates['ends_at'],
            'usage_limit' => $limit === '' ? null : (int) $limit, 'status' => ($in['status'] ?? 'active') === 'paused' ? 'paused' : 'active', 'updated_at' => date('Y-m-d H:i:s')];
        if ($id) {
            $this->coupon($id);
            $this->db->update('coupons', $data, $id);
            return $id;
        }
        return $this->db->insert('coupons', $data + ['created_at' => date('Y-m-d H:i:s')]);
    }

    public function coupon(int $id): array
    {
        return $this->db->fetch('SELECT * FROM ' . $this->db->t('coupons') . ' WHERE id = ?', [$id]) ?? throw new NotFound('Coupon not found.');
    }

    public function deleteCoupon(int $id): void
    {
        $this->coupon($id);
        $this->db->delete('coupons', $id);
    }

    /** A coupon a customer typed, if it can be used now (min order is checked when pricing). */
    public function couponByCode(string $code): ?array
    {
        $code = strtoupper(trim($code));
        if (!preg_match('/^[A-Z0-9_-]{3,30}$/', $code)) {
            return null;
        }
        $c = $this->db->fetch('SELECT * FROM ' . $this->db->t('coupons') . " WHERE code = ? AND status = 'active'", [$code]);
        $today = date('Y-m-d');
        if (!$c || ($c['starts_at'] && $c['starts_at'] > $today) || ($c['ends_at'] && $c['ends_at'] < $today)
            || ($c['usage_limit'] !== null && (int) $c['used_count'] >= (int) $c['usage_limit'])) {
            return null;
        }
        return $c;
    }

    // ================================================================== orders

    /**
     * Place an order from a priced cart. Cash on delivery orders are placed at once (stock and coupon
     * are taken, an invoice number assigned); online orders wait for the payment.
     */
    public function createOrder(array $quote, array $customer, string $method, string $ip): array
    {
        $recent = (int) $this->db->value('SELECT COUNT(*) FROM ' . $this->db->t('orders') . ' WHERE ip = ? AND created_at >= ?', [$ip, date('Y-m-d H:i:s', time() - self::ORDER_WINDOW)]);
        if ($recent >= self::ORDER_LIMIT) {
            throw new ValidationError('Too many orders were placed from your connection. Please try again later, or contact us.');
        }
        $s = $this->settings();
        $now = date('Y-m-d H:i:s');
        $id = $this->db->transaction(function (Db $db) use ($quote, $customer, $method, $ip, $s, $now): int {
            for ($i = 0; $i < 5; $i++) {
                $no = $s['order_prefix'] . date('ymd') . strtoupper(substr(str_replace(['0', 'O', '1', 'I'], '', base_convert(bin2hex(random_bytes(6)), 16, 36)), 0, 5));
                if (!$db->value('SELECT id FROM ' . $db->t('orders') . ' WHERE order_no = ?', [$no])) {
                    break;
                }
            }
            $orderId = $db->insert('orders', [
                'order_no' => $no, 'status' => 'pending_payment', 'payment_method' => $method, 'payment_status' => 'unpaid',
                'customer_name' => $customer['name'], 'customer_email' => $customer['email'], 'customer_phone' => $customer['phone'],
                'address' => $customer['address'], 'city' => $customer['city'], 'state' => $customer['state'], 'pincode' => $customer['pincode'],
                'customer_gstin' => $customer['gstin'] ?: null, 'notes' => $customer['notes'] ?: null,
                'subtotal' => $quote['subtotal'], 'discount' => $quote['discount'], 'coupon_code' => $quote['coupon']['code'] ?? null,
                'shipping' => $quote['shipping'], 'cod_fee' => $quote['cod_fee'], 'tax' => $quote['tax'], 'total' => $quote['total'],
                'prices_include_tax' => $quote['prices_include_tax'] ? 1 : 0, 'inter_state' => $quote['inter_state'] ? 1 : 0,
                'ip' => $ip, 'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach ($quote['lines'] as $l) {
                $db->insert('order_items', [
                    'order_id' => $orderId, 'product_id' => $l['product_id'], 'variant_id' => $l['variant_id'] ?: null, 'name' => $l['name'],
                    'variant_label' => $l['variant_label'] ?: null, 'sku' => $l['sku'] ?: null, 'hsn' => $l['hsn'] ?: null,
                    'unit_price' => $l['unit_price'], 'qty' => $l['qty'], 'gst_rate' => $l['gst_rate'], 'discount' => $l['discount'],
                    'taxable' => $l['taxable'], 'tax' => $l['tax'], 'line_total' => $l['line_total'],
                ]);
            }
            $this->event($orderId, 'created', ($method === 'cod' ? 'Cash on delivery' : 'Online payment') . ' · ₹' . self::money($quote['total']), $customer['name']);
            return $orderId;
        });
        if ($method === 'cod') {
            $this->place($id, 'Order placed (cash on delivery).');
        }
        return $this->order($id);
    }

    /** Move an order to "placed": take stock, count the coupon, assign the invoice number. Safe to call twice. */
    public function place(int $id, string $detail): bool
    {
        return $this->db->transaction(function (Db $db) use ($id, $detail): bool {
            $o = $db->fetch('SELECT * FROM ' . $db->t('orders') . ' WHERE id = ? FOR UPDATE', [$id]);
            if (!$o || (int) $o['stock_reserved'] === 1) {
                return false;
            }
            $short = [];
            foreach ($db->fetchAll('SELECT * FROM ' . $db->t('order_items') . ' WHERE order_id = ?', [$id]) as $it) {
                if ($it['variant_id']) {
                    $row = $db->fetch('SELECT v.stock, p.track_stock FROM ' . $db->t('product_variants') . ' v JOIN ' . $db->t('products') . ' p ON p.id = v.product_id WHERE v.id = ? FOR UPDATE', [$it['variant_id']]);
                    if ($row && (int) $row['track_stock']) {
                        if ((int) $row['stock'] < (int) $it['qty']) {
                            $short[] = $it['name'] . ' (' . $it['variant_label'] . ')';
                        }
                        $db->query('UPDATE ' . $db->t('product_variants') . ' SET stock = GREATEST(stock - ?, 0) WHERE id = ?', [(int) $it['qty'], $it['variant_id']]);
                    }
                } elseif ($it['product_id']) {
                    $row = $db->fetch('SELECT stock, track_stock FROM ' . $db->t('products') . ' WHERE id = ? FOR UPDATE', [$it['product_id']]);
                    if ($row && (int) $row['track_stock']) {
                        if ((int) $row['stock'] < (int) $it['qty']) {
                            $short[] = $it['name'];
                        }
                        $db->query('UPDATE ' . $db->t('products') . ' SET stock = GREATEST(stock - ?, 0) WHERE id = ?', [(int) $it['qty'], $it['product_id']]);
                    }
                }
            }
            if ($o['coupon_code']) {
                $db->query('UPDATE ' . $db->t('coupons') . ' SET used_count = used_count + 1 WHERE code = ?', [$o['coupon_code']]);
            }
            $seq = (int) $db->value('SELECT COALESCE(MAX(invoice_seq), 0) + 1 FROM ' . $db->t('orders') . ' FOR UPDATE');
            $s = $this->settings();
            $fy = (int) date('n') >= 4 ? date('y') . '-' . date('y', strtotime('+1 year')) : date('y', strtotime('-1 year')) . '-' . date('y');
            $now = date('Y-m-d H:i:s');
            $db->query('UPDATE ' . $db->t('orders') . " SET status = IF(status = 'pending_payment', 'placed', status), stock_reserved = 1, invoice_seq = ?, invoice_no = ?, invoiced_at = ?, updated_at = ? WHERE id = ?",
                [$seq, $s['invoice_prefix'] . $fy . '/' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT), $now, $now, $id]);
            $this->event($id, 'placed', $detail);
            if ($short) {
                $this->event($id, 'stock', 'Not enough stock for: ' . implode(', ', $short) . '. Stock was set to 0; please check before shipping.');
            }
            return true;
        });
    }

    /** A successful online payment for an order (from the payment pages or a webhook). Returns true the first time. */
    public function markPaid(int $orderId, array $tx): bool
    {
        $o = $this->order($orderId);
        if ($o['payment_status'] === 'paid' || (int) $tx['amount'] !== (int) $o['total']) {
            if ((int) $tx['amount'] !== (int) $o['total']) {
                $this->event($orderId, 'payment', 'Payment ' . $tx['reference'] . ' amount ₹' . self::money((int) $tx['amount']) . ' does not match the order total. Not marked as paid.');
            }
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $changed = $this->db->query('UPDATE ' . $this->db->t('orders') . " SET payment_status = 'paid', transaction_id = ?, paid_at = ?, updated_at = ? WHERE id = ? AND payment_status <> 'paid'", [(int) $tx['id'], $now, $now, $orderId])->rowCount() === 1;
        if ($changed) {
            $this->event($orderId, 'paid', 'Paid online · ' . $tx['reference'] . ($tx['gateway_payment_id'] ? ' · ' . $tx['gateway_payment_id'] : '') . ($tx['mode'] === 'test' ? ' (test mode)' : ''));
            if ($o['status'] === 'cancelled') {
                $this->event($orderId, 'payment', 'Payment received for a cancelled order: refund it, or reopen the order.');
            } else {
                $this->place($orderId, 'Order placed (paid online).');
            }
        }
        return $changed;
    }

    public function paymentFailed(int $orderId, array $tx): void
    {
        $this->db->query('UPDATE ' . $this->db->t('orders') . " SET payment_status = 'failed', transaction_id = ?, updated_at = ? WHERE id = ? AND payment_status = 'unpaid'", [(int) $tx['id'], date('Y-m-d H:i:s'), $orderId]);
        $this->event($orderId, 'payment', 'Online payment ' . $tx['reference'] . ' ' . $tx['status'] . ($tx['message'] ? ': ' . $tx['message'] : '') . '.');
    }

    public function order(int $id): array
    {
        $o = $this->db->fetch('SELECT * FROM ' . $this->db->t('orders') . ' WHERE id = ?', [$id]) ?? throw new NotFound('Order not found.');
        $o['items'] = $this->db->fetchAll('SELECT * FROM ' . $this->db->t('order_items') . ' WHERE order_id = ? ORDER BY id', [$id]);
        return $o;
    }

    public function orderByNo(string $no): ?array
    {
        if (!preg_match('/^[A-Z0-9]{6,20}$/', $no)) {
            return null;
        }
        $id = $this->db->value('SELECT id FROM ' . $this->db->t('orders') . ' WHERE order_no = ?', [$no]);
        return $id ? $this->order((int) $id) : null;
    }

    public function events(int $orderId): array
    {
        return $this->db->fetchAll('SELECT event, detail, actor, created_at FROM ' . $this->db->t('order_events') . ' WHERE order_id = ? ORDER BY id', [$orderId]);
    }

    public function event(int $orderId, string $event, ?string $detail, ?string $actor = null): void
    {
        $this->db->insert('order_events', ['order_id' => $orderId, 'event' => $event, 'detail' => $detail !== null ? mb_substr($detail, 0, 500) : null, 'actor' => $actor ? mb_substr($actor, 0, 120) : null, 'created_at' => date('Y-m-d H:i:s')]);
    }

    /** Admin: change status / tracking / note. Returns the updated order. */
    public function updateOrder(int $id, array $in, string $actor): array
    {
        $o = $this->order($id);
        $status = (string) ($in['status'] ?? $o['status']);
        $errors = [];
        if (!isset(self::ORDER_STATUSES[$status])) {
            $errors['status'] = 'Choose a status.';
        }
        $allowed = [
            'pending_payment' => ['pending_payment', 'cancelled'],
            'placed' => ['placed', 'processing', 'shipped', 'delivered', 'cancelled'],
            'processing' => ['processing', 'shipped', 'delivered', 'cancelled'],
            'shipped' => ['shipped', 'delivered', 'cancelled'],
            'delivered' => ['delivered'],
            'cancelled' => ['cancelled'],
        ];
        if (!$errors && !in_array($status, $allowed[$o['status']], true)) {
            $errors['status'] = 'An order that is “' . self::ORDER_STATUSES[$o['status']] . '” cannot be changed to “' . self::ORDER_STATUSES[$status] . '”.';
        }
        $tracking = mb_substr(trim(strip_tags((string) ($in['tracking_no'] ?? ''))), 0, 80);
        $courier = mb_substr(trim(strip_tags((string) ($in['courier'] ?? ''))), 0, 60);
        $turl = trim((string) ($in['tracking_url'] ?? ''));
        if ($turl !== '' && (!preg_match('#^https://#i', $turl) || !filter_var($turl, FILTER_VALIDATE_URL))) {
            $errors['tracking_url'] = 'Enter a full https:// address.';
        }
        $paid = ($in['payment_status'] ?? '') === 'paid' && $o['payment_method'] === 'cod' && $o['payment_status'] !== 'paid';
        if ($errors) {
            throw new ValidationError('Please correct the highlighted fields.', $errors);
        }
        $now = date('Y-m-d H:i:s');
        $data = ['status' => $status, 'courier' => $courier ?: null, 'tracking_no' => $tracking ?: null, 'tracking_url' => $turl ?: null,
            'admin_note' => mb_substr(trim(strip_tags((string) ($in['admin_note'] ?? ''))), 0, 500) ?: null, 'updated_at' => $now];
        if ($paid) {
            $data += ['payment_status' => 'paid', 'paid_at' => $now];
        }
        $this->db->update('orders', $data, $id);
        if ($status !== $o['status']) {
            $this->event($id, 'status', self::ORDER_STATUSES[$o['status']] . ' → ' . self::ORDER_STATUSES[$status] . ($status === 'shipped' && $tracking ? " · $courier $tracking" : ''), $actor);
            if ($status === 'cancelled' && (int) $o['stock_reserved'] === 1) {
                $this->restock($id);
            }
        }
        if ($paid) {
            $this->event($id, 'paid', 'Cash received (marked paid by ' . $actor . ').', $actor);
        }
        return $this->order($id);
    }

    private function restock(int $id): void
    {
        $this->db->transaction(function (Db $db) use ($id): void {
            foreach ($db->fetchAll('SELECT * FROM ' . $db->t('order_items') . ' WHERE order_id = ?', [$id]) as $it) {
                if ($it['variant_id']) {
                    $db->query('UPDATE ' . $db->t('product_variants') . ' SET stock = stock + ? WHERE id = ?', [(int) $it['qty'], $it['variant_id']]);
                } elseif ($it['product_id']) {
                    $db->query('UPDATE ' . $db->t('products') . ' SET stock = stock + ? WHERE id = ? AND track_stock = 1', [(int) $it['qty'], $it['product_id']]);
                }
            }
            $db->query('UPDATE ' . $db->t('orders') . ' SET stock_reserved = 0 WHERE id = ?', [$id]);
        });
        $this->event($id, 'stock', 'Items returned to stock.');
    }

    public function adminOrders(array $f, int $page, int $perPage): array
    {
        [$where, $params] = $this->orderFilters($f);
        $t = $this->db->t('orders');
        $perPage = max(1, min(100, $perPage));
        $total = (int) $this->db->value("SELECT COUNT(*) FROM $t WHERE $where", $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $rows = $this->db->fetchAll("SELECT id, order_no, status, payment_method, payment_status, customer_name, customer_email, customer_phone, city, total, created_at,
            (SELECT SUM(qty) FROM " . $this->db->t('order_items') . " i WHERE i.order_id = o.id) AS items FROM $t o WHERE $where ORDER BY id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage), $params);
        $sum = (int) $this->db->value("SELECT COALESCE(SUM(total), 0) FROM $t WHERE $where AND status NOT IN ('cancelled', 'pending_payment')", $params);
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage, 'sales_total' => $sum];
    }

    public function exportOrders(array $f): array
    {
        [$where, $params] = $this->orderFilters($f);
        return $this->db->fetchAll('SELECT order_no, created_at, status, payment_method, payment_status, invoice_no, customer_name, customer_email, customer_phone, address, city, state, pincode, customer_gstin, subtotal, discount, coupon_code, shipping, cod_fee, tax, total, courier, tracking_no FROM '
            . $this->db->t('orders') . " WHERE $where ORDER BY id DESC", $params);
    }

    private function orderFilters(array $f): array
    {
        $where = ['1=1'];
        $params = [];
        if (isset(self::ORDER_STATUSES[$f['status'] ?? ''])) {
            $where[] = 'status = ?';
            $params[] = $f['status'];
        } elseif (($f['status'] ?? '') === 'open') {
            $where[] = "status IN ('placed', 'processing')";
        } elseif (($f['status'] ?? '') === '') {
            $where[] = "status <> 'pending_payment'";
        }
        if (in_array($f['payment'] ?? '', ['online', 'cod'], true)) {
            $where[] = 'payment_method = ?';
            $params[] = $f['payment'];
        }
        $q = trim((string) ($f['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(order_no LIKE ? OR customer_name LIKE ? OR customer_email LIKE ? OR customer_phone LIKE ? OR invoice_no LIKE ?)';
            $like = '%' . addcslashes($q, '%_\\') . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        foreach (['from' => '>=', 'to' => '<='] as $k => $op) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($f[$k] ?? ''))) {
                $where[] = "created_at $op ?";
                $params[] = $f[$k] . ($k === 'from' ? ' 00:00:00' : ' 23:59:59');
            }
        }
        return [implode(' AND ', $where), $params];
    }

    /** Dashboard numbers. */
    public function summary(): array
    {
        $t = $this->db->t('orders');
        return [
            'open' => (int) $this->db->value("SELECT COUNT(*) FROM $t WHERE status IN ('placed', 'processing')"),
            'month_total' => (int) $this->db->value("SELECT COALESCE(SUM(total), 0) FROM $t WHERE status NOT IN ('cancelled', 'pending_payment') AND created_at >= ?", [date('Y-m-01 00:00:00')]),
            'month_count' => (int) $this->db->value("SELECT COUNT(*) FROM $t WHERE status NOT IN ('cancelled', 'pending_payment') AND created_at >= ?", [date('Y-m-01 00:00:00')]),
        ];
    }

    /** Unguessable token for the customer's order page and invoice link. */
    public static function orderToken(string $orderNo, string $appKey): string
    {
        return substr(hash_hmac('sha256', 'order|' . $orderNo, $appKey), 0, 32);
    }

    // ================================================================== seeding (demo catalogue)

    /** Insert a template's demo shop (settings, categories, products with variants, coupons). */
    public static function seed(Db $db, array $shop): void
    {
        $now = date('Y-m-d H:i:s');
        if (isset($shop['settings'])) {
            $db->query('REPLACE INTO ' . $db->t('settings') . ' (setting_key, setting_value, updated_at) VALUES (?, ?, ?)',
                ['shop', json_encode(array_replace(self::defaults(), array_intersect_key((array) $shop['settings'], self::defaults())), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $now]);
        }
        $catIds = [];
        foreach ((array) ($shop['categories'] ?? []) as $i => $c) {
            $catIds[(string) $c['slug']] = $db->insert('product_categories', ['name' => (string) $c['name'], 'slug' => (string) $c['slug'], 'description' => $c['description'] ?? null,
                'image' => $c['image'] ?? null, 'status' => 'active', 'sort_order' => $i + 1, 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach ((array) ($shop['products'] ?? []) as $i => $p) {
            $created = date('Y-m-d H:i:s', time() - (count((array) $shop['products']) - $i) * 3600);
            $pid = $db->insert('products', [
                'category_id' => $catIds[(string) ($p['category'] ?? '')] ?? null, 'name' => (string) $p['name'], 'slug' => (string) $p['slug'], 'sku' => $p['sku'] ?? null,
                'short_description' => $p['short_description'] ?? null, 'description' => $p['description'] ?? null,
                'price' => (int) $p['price'], 'compare_price' => isset($p['compare_price']) ? (int) $p['compare_price'] : null, 'gst_rate' => (string) ($p['gst_rate'] ?? '0'), 'hsn' => $p['hsn'] ?? null,
                'track_stock' => isset($p['track_stock']) ? (int) (bool) $p['track_stock'] : 1, 'stock' => (int) ($p['stock'] ?? 0), 'option_name' => $p['option_name'] ?? null,
                'image' => $p['image'] ?? null, 'gallery_json' => !empty($p['gallery']) ? json_encode(array_values((array) $p['gallery']), JSON_UNESCAPED_SLASHES) : null,
                'badge' => $p['badge'] ?? null, 'is_featured' => !empty($p['featured']) ? 1 : 0, 'status' => 'active', 'sort_order' => $i + 1,
                'created_at' => $created, 'updated_at' => $created,
            ]);
            foreach ((array) ($p['variants'] ?? []) as $j => $v) {
                $db->insert('product_variants', ['product_id' => $pid, 'label' => (string) $v['label'], 'sku' => $v['sku'] ?? null, 'price' => isset($v['price']) ? (int) $v['price'] : null, 'stock' => (int) ($v['stock'] ?? 0), 'sort_order' => $j + 1]);
            }
        }
        foreach ((array) ($shop['coupons'] ?? []) as $c) {
            $db->insert('coupons', ['code' => strtoupper((string) $c['code']), 'description' => $c['description'] ?? null, 'type' => $c['type'] ?? 'percent', 'value' => (int) ($c['value'] ?? 0),
                'min_order' => (int) ($c['min_order'] ?? 0), 'max_discount' => isset($c['max_discount']) ? (int) $c['max_discount'] : null, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    // ================================================================== helpers

    /** "1,299.50" / "₹ 1299" → 129950 paise; null if not an amount. */
    public static function parseMoney(mixed $v): ?int
    {
        $s = str_replace([',', '₹', ' ', 'Rs.', 'Rs'], '', trim((string) $v));
        if ($s === '') {
            return 0;
        }
        return preg_match('/^\d{1,9}(\.\d{1,2})?$/', $s) ? (int) round(((float) $s) * 100) : null;
    }

    /** ₹ amount text without the symbol, Indian grouping, paise only when present (1,299 / 1,299.50). */
    public static function money(int $paise): string
    {
        return \SiteRuntime\Payments\PaymentService::inr($paise, false);
    }

    private function imageOk(string $ref): bool
    {
        return $this->imageOk ? (bool) ($this->imageOk)($ref) : false;
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
