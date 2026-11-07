<?php
declare(strict_types=1);

namespace SiteRuntime\Shop;

use SiteRuntime\Payments\GatewayError;
use SiteRuntime\Payments\Gateways;
use SiteRuntime\Payments\PaymentService;
use SiteRuntime\Renderer;
use SiteRuntime\ValidationError;

/**
 * Public shop pages of a website:
 *   GET  /shop                       products (?c=category&q=search&sort=…&p=page)
 *   GET  /shop/product/{slug}        product page
 *   GET  /shop/cart     POST /shop/cart      cart (add, update, remove, coupon)
 *   GET  /shop/checkout POST /shop/checkout  address, delivery and payment → order
 *   GET  /shop/order/{no}?t=…        order status (token-protected); POST …/pay retries an online payment
 *   GET  /shop/invoice/{no}?t=…      GST invoice (PDF)
 *
 * Views come from the template (theme/shop/{name}.php) when it has them, otherwise from core/Shop/views.
 */
final class ShopPage
{
    private const CHECKOUT_MIN_SECONDS = 2;
    private const TOKEN_MAX_AGE = 14400;
    private const PER_PAGE = 12;

    /**
     * @param array{key:string, abs_base:string, url:callable(string):string, asset:callable(string):string, theme_dir:string, site_name:string, color:string, pay_url:callable(string):string} $ctx
     * @param \Closure(array):void $onPlaced  order emails (customer + owner), best effort
     * @param \Closure(string,string):void $log
     */
    public function __construct(private ShopService $shop, private Pricing $pricing, private Cart $cart, private PaymentService $payments,
        private Renderer $renderer, private array $ctx, private \Closure $onPlaced, private \Closure $log)
    {
    }

    public function handle(string $sub, string $method): void
    {
        $sub = trim($sub, '/');
        if (!$this->shop->enabled()) {
            $this->notFound();
            return;
        }
        if ($sub === '' && $method === 'GET') {
            $this->listing();
        } elseif (preg_match('#^product/([a-z0-9-]{1,160})$#', $sub, $m) && $method === 'GET') {
            $this->product($m[1]);
        } elseif ($sub === 'cart' && $method === 'GET') {
            $this->cartPage();
        } elseif ($sub === 'cart' && $method === 'POST') {
            $this->cartAction();
        } elseif ($sub === 'checkout' && $method === 'GET') {
            $this->checkout();
        } elseif ($sub === 'checkout' && $method === 'POST') {
            $this->placeOrder();
        } elseif (preg_match('#^order/([A-Z0-9]{6,20})$#', $sub, $m) && $method === 'GET') {
            $this->orderPage($m[1]);
        } elseif (preg_match('#^order/([A-Z0-9]{6,20})/pay$#', $sub, $m) && $method === 'POST') {
            $this->retryPayment($m[1]);
        } elseif (preg_match('#^invoice/([A-Z0-9]{6,20})$#', $sub, $m) && $method === 'GET') {
            $this->invoice($m[1]);
        } else {
            $this->notFound();
        }
    }

    // ------------------------------------------------------------------ catalogue

    private function listing(): void
    {
        $cat = null;
        $c = (string) ($_GET['c'] ?? '');
        if ($c !== '' && preg_match('/^[a-z0-9-]{1,90}$/', $c)) {
            $cat = $this->shop->categoryBySlug($c);
            if (!$cat) {
                $this->notFound();
                return;
            }
        }
        $q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 60);
        $sort = in_array($_GET['sort'] ?? '', ['new', 'price_asc', 'price_desc', 'name'], true) ? (string) $_GET['sort'] : '';
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $r = $this->shop->products(['category' => $cat['id'] ?? null, 'q' => $q, 'sort' => $sort, 'limit' => self::PER_PAGE, 'offset' => ($page - 1) * self::PER_PAGE]);
        $pages = max(1, (int) ceil($r['total'] / self::PER_PAGE));
        $title = $cat ? (string) $cat['name'] : ($q !== '' ? 'Search: ' . $q : 'Shop');
        $this->render('list', $title, [
            'products' => $r['rows'], 'total' => $r['total'], 'category' => $cat, 'categories' => $this->shop->categories(),
            'q' => $q, 'sort' => $sort, 'pageNo' => min($page, $pages), 'pages' => $pages,
        ], $cat ? (string) ($cat['description'] ?? '') : '', $q !== '' || $page > 1);
    }

    private function product(string $slug): void
    {
        $p = $this->shop->productBySlug($slug);
        if (!$p) {
            $this->notFound();
            return;
        }
        $related = $this->shop->products(['category' => $p['category_id'], 'exclude' => $p['id'], 'limit' => 4])['rows'];
        $this->render('product', (string) $p['name'], ['product' => $p, 'related' => $related, 'added' => ($_GET['added'] ?? '') === '1'],
            (string) ($p['short_description'] ?? ''));
    }

    // ------------------------------------------------------------------ cart

    private function cartPage(array $extraNotices = []): void
    {
        $quote = $this->pricing->quote($this->cart->items(), $this->cart->coupon());
        $this->syncCart($quote);
        $notices = array_merge($extraNotices, $quote['notices']);
        $flash = (string) ($_GET['msg'] ?? '');
        $messages = ['added' => 'Added to your cart.', 'updated' => 'Cart updated.', 'removed' => 'Item removed.', 'coupon' => 'Coupon applied.', 'coupon_removed' => 'Coupon removed.'];
        $this->render('cart', 'Your cart', ['quote' => $quote, 'notices' => $notices, 'message' => $messages[$flash] ?? '', 'settings' => $this->shop->settings(), 'token' => $this->token()], '', true);
    }

    private function cartAction(): void
    {
        if ($this->tokenAge((string) ($_POST['_t'] ?? '')) === null) {
            $this->cartPage(['Your session expired. Please try again.']);
            return;
        }
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'update' && isset($_POST['line'])) {
            $action = 'remove'; // the × button of a line submits the cart form with line=<index>
        }
        $msg = 'updated';
        if ($action === 'add') {
            $pid = (int) ($_POST['product_id'] ?? 0);
            $vid = (int) ($_POST['variant_id'] ?? 0);
            $qty = max(1, min(Cart::MAX_QTY, (int) ($_POST['qty'] ?? 1)));
            $p = $pid ? $this->shop->db()->fetch('SELECT id, slug FROM ' . $this->shop->db()->t('products') . " WHERE id = ? AND status = 'active'", [$pid]) : null;
            if (!$p) {
                $this->cartPage(['That product is no longer available.']);
                return;
            }
            $variants = $this->shop->variants($pid);
            if ($variants && !in_array($vid, array_map('intval', array_column($variants, 'id')), true)) {
                $this->redirect(($this->ctx['url'])('product/' . $p['slug']) . '?need_option=1#buy');
                return;
            }
            $this->cart->add($pid, $variants ? $vid : 0, $qty);
            $quote = $this->pricing->quote($this->cart->items(), $this->cart->coupon());
            $this->syncCart($quote);
            $this->cart->save();
            if ($quote['notices']) {
                $this->cartPage($quote['notices']); // e.g. "Only 2 left": show the cart instead of redirecting, so the message is seen
                return;
            }
            if (($_POST['then'] ?? '') === 'checkout') {
                $this->redirect(($this->ctx['url'])('checkout'));
            } elseif (($_POST['then'] ?? '') === 'product') {
                $this->redirect(($this->ctx['url'])('product/' . $p['slug']) . '?added=1#buy');
            } else {
                $this->redirect(($this->ctx['url'])('cart') . '?msg=added');
            }
            return;
        }
        if ($action === 'update') {
            $items = $this->cart->items();
            $qtys = (array) ($_POST['qty'] ?? []);
            foreach (array_keys($items) as $i) {
                if (isset($qtys[$i])) {
                    $this->cart->set($i, (int) $qtys[$i]);
                }
            }
        } elseif ($action === 'remove') {
            $this->cart->set((int) ($_POST['line'] ?? -1), 0);
            $msg = 'removed';
        } elseif ($action === 'coupon') {
            $code = strtoupper(trim((string) ($_POST['coupon'] ?? '')));
            if ($code === '') {
                $this->cart->setCoupon('');
                $msg = 'coupon_removed';
            } else {
                $quote = $this->pricing->quote($this->cart->items(), $code);
                if ($quote['coupon'] === null) {
                    $this->cartPage([$quote['coupon_error'] ?: 'The code ' . $code . ' is not valid.']);
                    return;
                }
                $this->cart->setCoupon($code);
                $msg = 'coupon';
            }
        } elseif ($action === 'clear') {
            $this->cart->clear();
        }
        $this->cart->save();
        $this->redirect(($this->ctx['url'])('cart') . '?msg=' . $msg);
    }

    /** Drop or cap lines the pricing could not honour, and forget a coupon that no longer applies. */
    private function syncCart(array $quote): void
    {
        $changed = $quote['items'] !== $this->cart->items();
        if ($changed) {
            $this->cart->replace($quote['items']);
        }
        if ($this->cart->coupon() !== '' && $quote['lines'] && $quote['coupon'] === null) {
            $this->cart->setCoupon('');
            $changed = true;
        }
        if ($changed) {
            $this->cart->save();
        }
    }

    // ------------------------------------------------------------------ checkout

    private function checkout(array $values = [], array $errors = [], string $alert = '', ?string $token = null, int $status = 200): void
    {
        $method = in_array($values['payment'] ?? '', ['online', 'cod'], true) ? $values['payment'] : '';
        $quote = $this->pricing->quote($this->cart->items(), $this->cart->coupon(), $method ?: 'online', (string) ($values['state'] ?? ''));
        $this->syncCart($quote);
        if (!$quote['lines']) {
            $this->redirect(($this->ctx['url'])('cart'));
            return;
        }
        $online = $this->payments->ready();
        $methods = [];
        if ($online) {
            $methods['online'] = 'Pay online (' . Gateways::ALL[$this->payments->settings()['gateway']]::name() . ': UPI, cards, net banking)';
        }
        if ($quote['cod_available']) {
            $methods['cod'] = 'Cash on delivery' . ((int) $this->shop->settings()['cod_fee'] > 0 ? ' (+ ₹' . ShopService::money((int) $this->shop->settings()['cod_fee']) . ')' : '');
        }
        $values['payment'] = $method !== '' && isset($methods[$method]) ? $method : (string) array_key_first($methods);
        // Totals for each payment method (cash on delivery may add a fee); the page shows the one selected.
        $quotes = [];
        foreach (array_keys($methods) as $m) {
            $quotes[$m] = $this->pricing->quote($this->cart->items(), $this->cart->coupon(), $m, (string) ($values['state'] ?? ''));
        }
        $this->render('checkout', 'Checkout', [
            'quote' => $quotes[$values['payment']] ?? $quote, 'quotes' => $quotes, 'values' => $values, 'errors' => $errors, 'alert' => $alert, 'methods' => $methods,
            'states' => ShopService::STATES, 'settings' => $this->shop->settings(), 'token' => $token ?? $this->token(),
            'testMode' => $online && $this->payments->settings()['mode'] === 'test',
        ], '', true, $status);
    }

    private function placeOrder(): void
    {
        $fields = ['name', 'email', 'phone', 'address', 'city', 'state', 'pincode', 'gstin', 'notes', 'payment'];
        $v = [];
        foreach ($fields as $f) {
            $v[$f] = is_string($_POST[$f] ?? null) ? trim((string) $_POST[$f]) : '';
        }
        if (trim((string) ($_POST['website'] ?? '')) !== '') {
            $this->redirect(($this->ctx['url'])(''));
            return;
        }
        $token = (string) ($_POST['_t'] ?? '');
        $age = $this->tokenAge($token);
        if ($age === null) {
            $this->checkout($v, [], 'The page expired. Please check your details and place the order again.', null, 422);
            return;
        }
        if ($age < self::CHECKOUT_MIN_SECONDS) {
            $this->checkout($v, [], 'Please check your details and press the button again.', $token, 422);
            return;
        }
        $errors = [];
        $name = preg_replace('/\s+/', ' ', strip_tags($v['name'])) ?? '';
        if ($name === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'Enter your full name.';
        }
        $email = strtolower($v['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors['email'] = 'Enter a valid email address for your order confirmation.';
        }
        $digits = preg_replace('/\D/', '', $v['phone']) ?? '';
        if (str_starts_with($digits, '91') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }
        if (!preg_match('/^[6-9]\d{9}$/', $digits)) {
            $errors['phone'] = 'Enter a 10-digit mobile number.';
        }
        $address = mb_substr(preg_replace('/\s+/', ' ', strip_tags($v['address'])) ?? '', 0, 300);
        if (mb_strlen($address) < 8) {
            $errors['address'] = 'Enter your house number, street and area.';
        }
        $city = mb_substr(strip_tags($v['city']), 0, 80);
        if ($city === '') {
            $errors['city'] = 'Enter your town or city.';
        }
        if (!in_array($v['state'], ShopService::STATES, true)) {
            $errors['state'] = 'Choose your state.';
        }
        if (!preg_match('/^[1-9]\d{5}$/', $v['pincode'])) {
            $errors['pincode'] = 'Enter a 6-digit PIN code.';
        }
        $gstin = strtoupper(preg_replace('/\s+/', '', $v['gstin']) ?? '');
        if ($gstin !== '' && !preg_match('/^\d{2}[A-Z]{5}\d{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $gstin)) {
            $errors['gstin'] = 'Enter a valid GSTIN, or leave it empty.';
        }
        $method = $v['payment'];
        $quote = $this->pricing->quote($this->cart->items(), $this->cart->coupon(), $method, $v['state']);
        if (!$quote['lines']) {
            $this->redirect(($this->ctx['url'])('cart'));
            return;
        }
        if ($quote['notices'] || $quote['items'] !== $this->cart->items()) {
            $this->syncCart($quote);
            $this->checkout($v, $errors, implode(' ', $quote['notices']) . ' Please check your order and place it again.', $token, 409);
            return;
        }
        if ($method === 'online' && !$this->payments->ready()) {
            $errors['payment'] = 'Online payment is not available at the moment. Please choose another option.';
        } elseif ($method === 'cod' && !$quote['cod_available']) {
            $errors['payment'] = 'Cash on delivery is not available for this order.';
        } elseif (!in_array($method, ['online', 'cod'], true)) {
            $errors['payment'] = 'Choose how you would like to pay.';
        }
        if (!$quote['min_order_ok']) {
            $errors['_'] = 'The minimum order is ₹' . ShopService::money((int) $this->shop->settings()['min_order']) . '.';
        }
        if ($errors) {
            $this->checkout($v, $errors, $errors['_'] ?? 'Please correct the highlighted fields.', $token, 422);
            return;
        }
        try {
            $order = $this->shop->createOrder($quote, ['name' => $name, 'email' => $email, 'phone' => $digits, 'address' => $address, 'city' => $city, 'state' => $v['state'],
                'pincode' => $v['pincode'], 'gstin' => $gstin, 'notes' => mb_substr(strip_tags($v['notes']), 0, 500)], $method, $this->ip());
        } catch (ValidationError $e) {
            $this->checkout($v, $e->errors, $e->getMessage(), $token, 429);
            return;
        }
        $this->cart->clear();
        $this->cart->save();
        if ($method === 'cod') {
            ($this->onPlaced)($this->shop->order((int) $order['id']));
            $this->redirect($this->orderUrl($order['order_no']));
            return;
        }
        $this->startPayment($order);
    }

    private function startPayment(array $order): void
    {
        try {
            $tx = $this->payments->createForOrder($order, $this->ip());
            $action = $this->payments->start($tx, [
                'return_url' => $this->ctx['abs_base'] . ($this->ctx['pay_url'])('return/' . $tx['reference']),
                'cancel_url' => $this->ctx['abs_base'] . ($this->ctx['pay_url'])('cancel/' . $tx['reference']),
                'webhook_url' => $this->ctx['abs_base'] . ($this->ctx['pay_url'])('webhook/' . $tx['gateway']),
                'site_name' => $this->ctx['site_name'], 'color' => $this->ctx['color'],
            ]);
        } catch (GatewayError | ValidationError $e) {
            ($this->log)('ERROR', 'Order ' . $order['order_no'] . ': payment could not be started: ' . $e->getMessage());
            $this->shop->event((int) $order['id'], 'payment', 'Online payment could not be started: ' . $e->getMessage());
            $this->redirect($this->orderUrl($order['order_no']) . '&pay=error');
            return;
        }
        if ($action['type'] === 'redirect') {
            $this->redirect($action['url']);
            return;
        }
        $e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $inr = static fn (int $p): string => '₹' . PaymentService::inr($p);
        $cancel = $this->ctx['abs_base'] . ($this->ctx['pay_url'])('cancel/' . $tx['reference']);
        $gatewayName = Gateways::ALL[$tx['gateway']]::name();
        ob_start();
        include dirname(__DIR__) . '/Payments/views/redirect.php';
        $body = (string) ob_get_clean();
        $content = '<link rel="stylesheet" href="' . $e(($this->ctx['asset'])('pay/pay.css')) . '"><section class="wb-section wbpay"><div class="wbpay-wrap">' . $body . '</div></section>'
            . '<script src="' . $e(($this->ctx['asset'])('pay/pay.js')) . '"></script>';
        $this->output('Complete your payment', $content, 200, true);
    }

    // ------------------------------------------------------------------ order

    private function orderPage(string $no): void
    {
        $order = $this->authorisedOrder($no);
        if (!$order) {
            $this->notFound();
            return;
        }
        $tx = $this->payments->latestForOrder((int) $order['id']);
        $this->render('order', 'Order ' . $order['order_no'], [
            'order' => $order, 'tx' => $tx, 'statuses' => ShopService::ORDER_STATUSES, 'token' => $this->token(),
            'invoiceUrl' => $order['invoice_no'] ? ($this->ctx['url'])('invoice/' . $order['order_no']) . '?t=' . ShopService::orderToken($order['order_no'], $this->ctx['key']) : '',
            'payUrl' => ($this->ctx['url'])('order/' . $order['order_no'] . '/pay') . '?t=' . ShopService::orderToken($order['order_no'], $this->ctx['key']),
            'canPay' => $order['status'] === 'pending_payment' && $this->payments->ready(),
            'payError' => ($_GET['pay'] ?? '') === 'error', 'settings' => $this->shop->settings(),
        ], '', true);
    }

    private function retryPayment(string $no): void
    {
        $order = $this->authorisedOrder($no);
        if (!$order || $this->tokenAge((string) ($_POST['_t'] ?? '')) === null) {
            $this->notFound();
            return;
        }
        if ($order['status'] !== 'pending_payment' || $order['payment_status'] === 'paid' || !$this->payments->ready()) {
            $this->redirect($this->orderUrl($no));
            return;
        }
        $quote = $this->pricing->quote(array_map(static fn ($i) => [(int) $i['product_id'], (int) $i['variant_id'], (int) $i['qty']], $order['items']), (string) $order['coupon_code'], 'online', (string) $order['state']);
        if ($quote['notices']) {
            $this->shop->event((int) $order['id'], 'payment', 'Payment retry blocked: ' . implode(' ', $quote['notices']));
            $this->redirect($this->orderUrl($no) . '&pay=stock');
            return;
        }
        $this->startPayment($order);
    }

    private function invoice(string $no): void
    {
        $order = $this->authorisedOrder($no);
        if (!$order || !$order['invoice_no']) {
            $this->notFound();
            return;
        }
        $pdf = Invoice::pdf($order, $this->shop->settings(), $this->siteSettings());
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="invoice-' . preg_replace('/[^A-Za-z0-9-]/', '-', (string) $order['invoice_no']) . '.pdf"');
        header('Cache-Control: private, no-store');
        header('X-Robots-Tag: noindex');
        echo $pdf;
    }

    private function authorisedOrder(string $no): ?array
    {
        $order = $this->shop->orderByNo($no);
        if (!$order || !hash_equals(ShopService::orderToken($no, $this->ctx['key']), (string) ($_GET['t'] ?? ''))) {
            return null;
        }
        return $order;
    }

    public function orderUrl(string $no): string
    {
        return ($this->ctx['url'])('order/' . $no) . '?t=' . ShopService::orderToken($no, $this->ctx['key']);
    }

    // ------------------------------------------------------------------ rendering

    private function render(string $view, string $title, array $vars, string $description = '', bool $noindex = false, int $status = 200): void
    {
        $page = ['id' => 0, 'title' => $title, 'slug' => 'shop', 'page_type' => 'shop', 'is_home' => 0, 'status' => 'active',
            'seo_title' => $title, 'seo_description' => mb_substr($description, 0, 300), 'seo_keywords' => '', 'robots' => $noindex ? 'noindex' : null];
        $v = $this->renderer->view($page);
        $custom = $this->ctx['theme_dir'] . '/shop/' . $view . '.php';
        $file = is_file($custom) ? $custom : __DIR__ . '/views/' . $view . '.php';
        $body = $v->capture($file, $vars);
        $e = static fn ($s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        $content = '<link rel="stylesheet" href="' . $e(($this->ctx['asset'])('shop/shop.css')) . '">' . $body
            . '<script src="' . $e(($this->ctx['asset'])('shop/shop.js')) . '"></script>';
        $this->output($title, $content, $status, $noindex, $page);
    }

    private function output(string $title, string $content, int $status, bool $noindex, ?array $page = null): void
    {
        $page ??= ['id' => 0, 'title' => $title, 'slug' => 'shop', 'page_type' => 'shop', 'is_home' => 0, 'status' => 'active', 'seo_title' => $title, 'seo_description' => '', 'seo_keywords' => '', 'robots' => $noindex ? 'noindex' : null];
        $out = $this->renderer->renderContent($page, $content, $status);
        if ($noindex) {
            header('X-Robots-Tag: noindex');
            header('Cache-Control: no-store');
        }
        http_response_code($out['status']);
        header('Content-Type: text/html; charset=utf-8');
        echo $out['html'];
    }

    private function notFound(): void
    {
        $this->output('Page not found', '<section class="wb-section" style="padding:6rem 1rem;text-align:center"><div class="container"><h1>Page not found</h1><p>The product or page you are looking for is not available.</p><p><a href="'
            . htmlspecialchars(($this->ctx['url'])(''), ENT_QUOTES, 'UTF-8') . '">Back to the shop</a></p></div></section>', 404, true);
    }

    private function siteSettings(): array
    {
        $raw = $this->shop->db()->value('SELECT setting_value FROM ' . $this->shop->db()->t('settings') . " WHERE setting_key = 'site'");
        return is_string($raw) ? (json_decode($raw, true) ?: []) : [];
    }

    private function token(): string
    {
        $ts = (string) time();
        return $ts . '.' . hash_hmac('sha256', 'shop|' . $ts, $this->ctx['key']);
    }

    private function tokenAge(string $token): ?int
    {
        if (!preg_match('/^(\d{9,11})\.([a-f0-9]{64})$/', $token, $m) || !hash_equals(hash_hmac('sha256', 'shop|' . $m[1], $this->ctx['key']), $m[2])) {
            return null;
        }
        $age = time() - (int) $m[1];
        return $age <= self::TOKEN_MAX_AGE ? $age : null;
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url, true, 303);
    }

    private function ip(): string
    {
        return filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: '0.0.0.0';
    }
}
