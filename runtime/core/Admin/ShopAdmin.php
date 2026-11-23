<?php
declare(strict_types=1);

namespace SiteRuntime\Admin;

use SiteRuntime\Db;
use SiteRuntime\NotFound;
use SiteRuntime\Shop\Invoice;
use SiteRuntime\Shop\ShopService;

/**
 * Website admin screens for the shop: orders, products, categories, coupons and shop settings.
 * Owners and editors manage orders and products; coupons and settings are owner-only (see App routes).
 */
final class ShopAdmin
{
    public function __construct(private App $app, private Db $db, private array $config, private string $base, private string $root)
    {
    }

    private function shop(): ShopService
    {
        return $this->app->shop();
    }

    private function actor(): string
    {
        $u = $this->app->currentUser();
        return (string) ($u['name'] ?? $u['email'] ?? 'Admin');
    }

    private function shopUrl(string $sub = ''): string
    {
        return $this->base . (!empty($this->config['clean_urls']) ? '/shop' : '/index.php/shop') . ($sub !== '' ? '/' . $sub : '');
    }

    private function money(int $p): string
    {
        return '₹' . ShopService::money($p);
    }

    // ================================================================== orders

    public function ordersPage(): void
    {
        $this->app->page('shop-orders', ['title' => 'Orders', 'active' => 'orders', 'shopEnabled' => $this->shop()->enabled(), 'summary' => $this->shop()->summary(),
            'statuses' => ShopService::ORDER_STATUSES, 'shopUrl' => $this->shopUrl(), 'scripts' => ['js/admin-shop.js']]);
    }

    public function apiOrders(): void
    {
        $r = $this->shop()->adminOrders($_GET, (int) ($_GET['page'] ?? 1), (int) ($_GET['per_page'] ?? 20));
        foreach ($r['rows'] as &$row) {
            $row['total_text'] = $this->money((int) $row['total']);
            $row['status_text'] = ShopService::ORDER_STATUSES[$row['status']] ?? $row['status'];
        }
        $r['sales_total_text'] = $this->money((int) $r['sales_total']);
        $this->app->respond($r);
    }

    public function orderPage(array $p): void
    {
        $o = $this->shop()->order($p['oid']);
        $tx = $o['transaction_id'] ? $this->app->paymentService()->find((int) $o['transaction_id']) : $this->app->paymentService()->latestForOrder((int) $o['id']);
        $this->app->page('shop-order', ['title' => 'Order ' . $o['order_no'], 'active' => 'orders', 'order' => $o, 'events' => $this->shop()->events((int) $o['id']), 'tx' => $tx,
            'statuses' => ShopService::ORDER_STATUSES, 'customerUrl' => $this->shopUrl('order/' . $o['order_no']) . '?t=' . ShopService::orderToken($o['order_no'], (string) $this->config['key']),
            'scripts' => ['js/admin-shop.js']]);
    }

    public function apiOrderUpdate(array $p): void
    {
        $before = $this->shop()->order($p['oid']);
        $o = $this->shop()->updateOrder($p['oid'], $_POST, $this->actor());
        $msg = 'Order updated.';
        if (($_POST['notify'] ?? '') === '1' && $o['status'] !== $before['status'] && in_array($o['status'], ['shipped', 'delivered', 'cancelled'], true)) {
            $msg .= $this->notifyCustomer($o) ? ' The customer was emailed.' : ' The email to the customer could not be sent (check Settings → Email).';
        }
        $this->app->respondOk($msg, ['status' => $o['status']]);
    }

    public function apiOrderResend(array $p): void
    {
        $o = $this->shop()->order($p['oid']);
        if ($o['status'] === 'pending_payment') {
            $this->app->respondFail('This order is not paid yet.');
        }
        $this->app->respondOk($this->notifyCustomer($o, true) ? 'Order confirmation sent to ' . $o['customer_email'] . '.' : 'The email could not be sent. Check Settings → Email.');
    }

    /** Status email to the customer (shipped / delivered / cancelled), or the confirmation again. */
    private function notifyCustomer(array $o, bool $confirmation = false): bool
    {
        $e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $site = (string) ($this->app->siteSettings()['site']['name'] ?? 'our shop');
        $link = $this->absolute($this->shopUrl('order/' . $o['order_no']) . '?t=' . ShopService::orderToken($o['order_no'], (string) $this->config['key']));
        $lines = '';
        foreach ($o['items'] as $it) {
            $lines .= '<li>' . $e($it['name'] . ($it['variant_label'] ? ' (' . $it['variant_label'] . ')' : '')) . ' × ' . (int) $it['qty'] . '</li>';
        }
        [$subject, $intro] = match (true) {
            $confirmation => ['Order ' . $o['order_no'] . ' confirmed — ' . $site, 'We have received your order ' . $e($o['order_no']) . ' (' . $e($this->money((int) $o['total'])) . ').'],
            $o['status'] === 'shipped' => ['Your order ' . $o['order_no'] . ' has shipped', 'Good news: your order ' . $e($o['order_no']) . ' is on its way.'
                . ($o['tracking_no'] ? ' ' . ($o['courier'] ? $e($o['courier']) . ' tracking' : 'Tracking') . ' number: <strong>' . $e($o['tracking_no']) . '</strong>.' : '')
                . ($o['tracking_url'] ? ' <a href="' . $e($o['tracking_url']) . '">Track your parcel</a>.' : '')],
            $o['status'] === 'delivered' => ['Your order ' . $o['order_no'] . ' was delivered', 'Your order ' . $e($o['order_no']) . ' has been delivered. We hope you enjoy it!'],
            default => ['Your order ' . $o['order_no'] . ' was cancelled', 'Your order ' . $e($o['order_no']) . ' has been cancelled.' . ($o['payment_status'] === 'paid' ? ' Your payment will be refunded to the original payment method.' : '')],
        };
        try {
            $this->app->sendMail([$o['customer_email']], $subject, '<p>Dear ' . $e($o['customer_name']) . ',</p><p>' . $intro . '</p><ul>' . $lines . '</ul>'
                . '<p><a href="' . $e($link) . '" style="background:#1f2937;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;display:inline-block">View your order</a></p>',
                strip_tags(str_replace('<br>', "\n", $intro)) . "\n\nView your order: $link");
            $this->shop()->event((int) $o['id'], 'email', 'Email to the customer: ' . $subject, $this->actor());
            return true;
        } catch (\Throwable $ex) {
            $this->app->writeLog('Order ' . $o['order_no'] . ': customer email failed: ' . $ex->getMessage());
            return false;
        }
    }

    public function invoice(array $p): void
    {
        $o = $this->shop()->order($p['oid']);
        if (!$o['invoice_no']) {
            throw new NotFound('This order has no invoice yet (it is not paid or placed).');
        }
        $pdf = Invoice::pdf($o, $this->shop()->settings(), (array) ($this->app->siteSettings()['site'] ?? []));
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="invoice-' . preg_replace('/[^A-Za-z0-9-]/', '-', (string) $o['invoice_no']) . '.pdf"');
        echo $pdf;
        exit;
    }

    public function apiOrdersExport(): void
    {
        $rows = $this->shop()->exportOrders($_GET);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="orders-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Order', 'Date', 'Status', 'Payment', 'Payment status', 'Invoice', 'Name', 'Email', 'Phone', 'Address', 'City', 'State', 'PIN', 'GSTIN',
            'Items (INR)', 'Discount (INR)', 'Coupon', 'Shipping (INR)', 'COD fee (INR)', 'GST (INR)', 'Total (INR)', 'Courier', 'Tracking']);
        foreach ($rows as $r) {
            foreach (['subtotal', 'discount', 'shipping', 'cod_fee', 'tax', 'total'] as $k) {
                $r[$k] = number_format(((int) $r[$k]) / 100, 2, '.', '');
            }
            fputcsv($out, array_map(static fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : (string) $v, array_values($r)));
        }
        fclose($out);
        exit;
    }

    // ================================================================== products

    public function productsPage(): void
    {
        $this->app->page('shop-products', ['title' => 'Products', 'active' => 'products', 'categories' => $this->shop()->categories(false), 'shopEnabled' => $this->shop()->enabled(),
            'shopUrl' => $this->shopUrl(), 'lowStock' => (int) $this->shop()->settings()['low_stock'], 'scripts' => ['js/admin-shop.js']]);
    }

    public function apiProducts(): void
    {
        $r = $this->shop()->adminProducts($_GET, (int) ($_GET['page'] ?? 1), (int) ($_GET['per_page'] ?? 20));
        foreach ($r['rows'] as &$row) {
            $row['price_text'] = $this->money((int) $row['price']);
            $row['image_url'] = $this->imageUrl((string) $row['image']);
            $row['url'] = $this->shopUrl('product/' . $row['slug']);
        }
        $this->app->respond($r);
    }

    public function productForm(array $p = []): void
    {
        $product = isset($p['pid']) ? $this->shop()->product($p['pid']) : null;
        $images = [];
        if ($product) {
            foreach (array_values(array_filter(array_merge([(string) $product['image']], $product['gallery']))) as $ref) {
                $images[] = ['ref' => $ref, 'url' => $this->imageUrl($ref)];
            }
        }
        $this->app->page('shop-product-form', ['title' => $product ? 'Edit product' : 'Add product', 'active' => 'products', 'product' => $product, 'images' => $images,
            'categories' => $this->shop()->categories(false), 'gstRates' => ShopService::GST_RATES, 'config' => $this->app->editorConfig(),
            'shopUrl' => $this->shopUrl(), 'scripts' => ['vendor/tinymce/tinymce.min.js', 'js/admin-shop.js']]);
    }

    public function apiProductSave(array $p = []): void
    {
        $in = $_POST;
        $refs = array_values(array_filter(array_map('strval', (array) ($in['images'] ?? []))));
        $in['image'] = $refs[0] ?? '';
        $in['gallery'] = array_slice($refs, 1);
        $id = $this->shop()->saveProduct($in, $p['pid'] ?? null);
        $this->app->respondOk(isset($p['pid']) ? 'Product saved.' : 'Product added.', ['id' => $id, 'redirect' => isset($p['pid']) ? null : $this->app->url('products/' . $id)]);
    }

    public function apiProductDelete(array $p): void
    {
        $this->shop()->deleteProduct($p['pid']);
        $this->app->respondOk('Product deleted. Past orders keep their details.', ['redirect' => ($_GET['back'] ?? $_POST['back'] ?? '') === '1' ? $this->app->url('products') : null]);
    }

    /** Quick stock change from the list. */
    public function apiProductStock(array $p): void
    {
        $prod = $this->shop()->product($p['pid']);
        $stock = (string) ($_POST['stock'] ?? '');
        if ($prod['variants'] || !preg_match('/^\d{1,6}$/', $stock)) {
            $this->app->respondFail($prod['variants'] ? 'Edit the stock of each option on the product page.' : 'Stock must be a whole number.');
        }
        $this->db->update('products', ['stock' => (int) $stock, 'updated_at' => date('Y-m-d H:i:s')], $p['pid']);
        $this->app->respondOk('Stock updated.');
    }

    // ================================================================== categories

    public function categoriesPage(): void
    {
        $this->app->page('shop-categories', ['title' => 'Categories', 'active' => 'products', 'config' => $this->app->editorConfig(), 'scripts' => ['js/admin-shop.js']]);
    }

    public function apiCategories(): void
    {
        $rows = $this->shop()->categories(false);
        foreach ($rows as &$r) {
            $r['image_url'] = $this->imageUrl((string) $r['image']);
            $r['url'] = $this->shopUrl() . '?c=' . rawurlencode((string) $r['slug']);
        }
        $this->app->respond(['rows' => $rows, 'total' => count($rows), 'page' => 1, 'pages' => 1, 'per_page' => max(1, count($rows))]);
    }

    public function apiCategorySave(array $p = []): void
    {
        $this->shop()->saveCategory($_POST, $p['cid'] ?? null);
        $this->app->respondOk('Category saved.');
    }

    public function apiCategoryDelete(array $p): void
    {
        $this->shop()->deleteCategory($p['cid']);
        $this->app->respondOk('Category deleted. Its products are now uncategorised.');
    }

    // ================================================================== coupons

    public function couponsPage(): void
    {
        $this->app->page('shop-coupons', ['title' => 'Coupons', 'active' => 'coupons', 'scripts' => ['js/admin-shop.js']]);
    }

    public function apiCoupons(): void
    {
        $rows = $this->shop()->coupons();
        foreach ($rows as &$c) {
            $c['value_text'] = match ($c['type']) {
                'percent' => $c['value'] . '% off' . ($c['max_discount'] !== null ? ' (up to ' . $this->money((int) $c['max_discount']) . ')' : ''),
                'fixed' => $this->money((int) $c['value']) . ' off',
                default => 'Free delivery',
            };
            $c['min_order_text'] = (int) $c['min_order'] > 0 ? $this->money((int) $c['min_order']) : '';
            $c['value_input'] = $c['type'] === 'fixed' ? number_format(((int) $c['value']) / 100, 2, '.', '') : (string) $c['value'];
            $c['min_order_input'] = (int) $c['min_order'] ? number_format(((int) $c['min_order']) / 100, 2, '.', '') : '';
            $c['max_discount_input'] = $c['max_discount'] !== null ? number_format(((int) $c['max_discount']) / 100, 2, '.', '') : '';
        }
        $this->app->respond(['rows' => $rows, 'total' => count($rows), 'page' => 1, 'pages' => 1, 'per_page' => max(1, count($rows))]);
    }

    public function apiCouponSave(array $p = []): void
    {
        $this->shop()->saveCoupon($_POST, $p['cid'] ?? null);
        $this->app->respondOk('Coupon saved.');
    }

    public function apiCouponDelete(array $p): void
    {
        $this->shop()->deleteCoupon($p['cid']);
        $this->app->respondOk('Coupon deleted.');
    }

    // ================================================================== settings

    public function settingsPage(): void
    {
        $pay = $this->app->paymentService();
        $this->app->page('shop-settings', ['title' => 'Shop settings', 'active' => 'products', 'settings' => $this->shop()->settings(), 'states' => ShopService::STATES,
            'paymentsReady' => $pay->ready(), 'paymentMode' => $pay->settings()['mode'], 'shopUrl' => $this->shopUrl(), 'scripts' => ['js/admin-shop.js']]);
    }

    public function saveSettings(): void
    {
        $this->shop()->saveSettings($_POST);
        $this->app->respondOk('Shop settings saved.');
    }

    // ================================================================== helpers

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

    private function absolute(string $path): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        $host = preg_replace('/[^a-z0-9.\-:\[\]]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        return ($https ? 'https' : 'http') . '://' . $host . $path;
    }
}
