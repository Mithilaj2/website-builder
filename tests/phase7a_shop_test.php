<?php
declare(strict_types=1);

/**
 * Phase 7a (part 2) end-to-end test: the online shop on a website generated from E-commerce 01 (Terra Market).
 *
 *   php tests/phase7a_shop_test.php <builder-url> <email> <password> <website-id> <emulator-url> <mail-dir> <site-db-dsn> [template-id]
 *
 * The website must be freshly generated from ecommerce_template_01 demo content (it changes stock, coupons and settings).
 * Needs the payment emulator (php -S 127.0.0.1:8090 tools/payment-emulator.php, 'payment_emulator' in config) and tools/mailsink.py.
 */

require __DIR__ . '/support.php';

[$_, $base, $email, $password, $wid, $emu, $mailDir, $dsn, $tplId] = $argv + [null, 'http://127.0.0.1:8080', 'info@channelsoftech.com', '', '0', 'http://127.0.0.1:8090', '/tmp/mailsink', '', '0'];
$base = rtrim((string) $base, '/');
$wid = (int) $wid;
$sfx = substr(bin2hex(random_bytes(3)), 0, 6);
$pass = $fail = 0;
$started = microtime(true);

function mails(string $dir, string $rcpt, float $since, int $wait = 8): array
{
    for ($i = 0; $i < $wait * 4; $i++) {
        $out = [];
        foreach (glob(rtrim($dir, '/') . '/*.eml') ?: [] as $f) {
            if (filemtime($f) + 1 < (int) $since) {
                continue;
            }
            $raw = (string) file_get_contents($f);
            if (stripos(strtok($raw, "\r\n"), $rcpt) === false) {
                continue;
            }
            $text = quoted_printable_decode($raw);
            if (preg_match_all('/Content-Transfer-Encoding: base64\r?\n(?:[^\r\n]+\r?\n)*\r?\n([A-Za-z0-9+\/=\r\n]+)/', $raw, $m)) {
                foreach ($m[1] as $b) {
                    $text .= "\n" . base64_decode(preg_replace('/\s+/', '', $b));
                }
            }
            $out[] = $text;
        }
        if ($out) {
            return $out;
        }
        usleep(250000);
    }
    return [];
}
function formOf(string $html): array
{
    preg_match('#<form[^>]*action="([^"]*)"[^>]*>(.*?)</form>#s', $html, $m);
    $fields = [];
    preg_match_all('#<input[^>]*name="([^"]+)"[^>]*value="([^"]*)"#', $m[2] ?? '', $mm, PREG_SET_ORDER);
    foreach ($mm as $x) {
        $fields[html_entity_decode($x[1])] = html_entity_decode($x[2], ENT_QUOTES);
    }
    return [html_entity_decode($m[1] ?? ''), $fields];
}
function optionsOf(string $html): array
{
    preg_match('#data-options="([^"]+)"#', $html, $m);
    return json_decode(html_entity_decode($m[1] ?? '', ENT_QUOTES), true) ?: [];
}
function token(string $html): string
{
    preg_match('/name="_t" value="([^"]+)"/', $html, $m);
    return html_entity_decode($m[1] ?? '');
}

[$d, $du, $dp] = explode('|', (string) $dsn) + ['', '', ''];
$pdo = new PDO($d, $du, $dp, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$prefix = (string) $pdo->query('SELECT table_prefix FROM wb_websites WHERE id = ' . $wid)->fetchColumn();
$t = static fn (string $n): string => $prefix . $n;
$pdo->exec("DELETE FROM {$t('login_attempts')} WHERE ip IN ('127.0.0.1', '::1')");
$pdo->exec("UPDATE {$t('orders')} SET created_at = created_at - INTERVAL 2 HOUR WHERE ip IN ('127.0.0.1', '::1')");
$pdo->exec("UPDATE {$t('payment_transactions')} SET created_at = created_at - INTERVAL 15 MINUTE WHERE ip IN ('127.0.0.1', '::1')");
$pid = static fn (string $slug): int => (int) $pdo->query("SELECT id FROM {$t('products')} WHERE slug = " . $pdo->quote($slug))->fetchColumn();
$vid = static fn (string $slug, string $label): int => (int) $pdo->query("SELECT v.id FROM {$t('product_variants')} v JOIN {$t('products')} p ON p.id = v.product_id WHERE p.slug = " . $pdo->quote($slug) . ' AND v.label = ' . $pdo->quote($label))->fetchColumn();
$stockOf = static fn (string $slug, string $label = ''): int => (int) $pdo->query($label === '' ? "SELECT stock FROM {$t('products')} WHERE slug = " . $pdo->quote($slug)
    : "SELECT v.stock FROM {$t('product_variants')} v JOIN {$t('products')} p ON p.id = v.product_id WHERE p.slug = " . $pdo->quote($slug) . ' AND v.label = ' . $pdo->quote($label))->fetchColumn();

$b = new Client($base);
check('Builder login', ($b->login($email, $password)['json']['ok'] ?? false) === true);
preg_match('/font-monospace" value="([A-Za-z0-9]+)"/', $b->req('GET', 'settings', [], false)['body'], $m);
$qa = 'qa:' . ($m[1] ?? '');
$owner = "shopowner$sfx@example.com";
$r = $b->req('POST', "websites/$wid/admin-account", ['email' => $owner, 'name' => 'Shop Owner']);
$ownerPw = (string) ($r['json']['password'] ?? '');
$adminUrl = rtrim((string) ($r['json']['url'] ?? ''), '/');
$siteUrl = substr($adminUrl, 0, -strlen('admin'));
$host = (string) preg_replace('#^(https?://[^/]+).*$#', '$1', $siteUrl);
$o = new Client($adminUrl);
$o->basic = $qa;
check('Website owner signs in', ($o->login($owner, $ownerPw)['json']['ok'] ?? false) === true);
$notify = "orders$sfx@example.com";
$o->req('POST', 'settings/mail', ['notify_to' => $notify, 'from_email' => "shop$sfx@example.com", 'from_name' => 'Terra Market', 'smtp_enabled' => '1', 'smtp_host' => '127.0.0.1', 'smtp_port' => '2525', 'smtp_encryption' => 'none']);
$page = ['title' => 'Pay online', 'custom_purpose' => '1', 'min_amount' => '1', 'max_amount' => '500000'];
$rz = ['key_id' => "rzp_test_$sfx", 'key_secret' => "rzpsecret$sfx", 'webhook_secret' => "rzpwh$sfx"];
$o->req('POST', 'payments/settings', ['enabled' => '0', 'gateway' => '', 'mode' => 'test', 'page' => $page]);
$abs = static fn (string $loc): string => preg_match('#^https?://#', $loc) ? $loc : $host . $loc;
$pub = new Client($siteUrl);
$pub->basic = $qa;
$em = new Client(rtrim((string) $emu, '/'));

echo "\n== Catalogue ==\n";
$home = $pub->req('GET', '', [], false)['body'];
check('Homepage: 4 category tiles and 8 bestsellers (featured products) + 4 new arrivals', substr_count($home, 'class="trm-cat"') === 4 && substr_count($home, '<article class="trm-card') === 12);
check('Header: Shop menu with categories, cart icon; announcement bar from shop settings', str_contains($home, 'trm-shop-nav') && str_contains($home, 'aria-label="Cart, 0 items"') && str_contains($home, 'Free delivery on orders above ₹999') && str_contains($home, 'Cash on delivery available'));
check('Simple products have a quick "Add to cart" form; products with options link to the product', str_contains($home, 'name="product_id" value="' . $pid('terracotta-bud-vase') . '"') && str_contains($home, 'Choose options'));
$shop = $pub->req('GET', 'shop', [], false);
check('/shop lists all 14 products inside the template', $shop['code'] === 200 && str_contains($shop['body'], '14 products') && substr_count($shop['body'], '<article class="trm-card') === 12 && str_contains($shop['body'], 'class="trm '));
check('Pagination (12 per page) and page 2', str_contains($shop['body'], 'wbshop-pager') && substr_count($pub->req('GET', 'shop?p=2', [], false)['body'], '<article class="trm-card') === 2);
$light = $pub->req('GET', 'shop?c=lighting', [], false)['body'];
check('Category filter: Lighting has 1 product', str_contains($light, '1 product<') && str_contains($light, 'Pebble Table Lamp') && !str_contains($light, 'Sage Ripple Vase'));
check('Search: "vase" finds both vases', substr_count($pub->req('GET', 'shop?q=vase', [], false)['body'], '<article class="trm-card') === 2);
$asc = $pub->req('GET', 'shop?sort=price_asc', [], false)['body'];
check('Sort by price (low to high) starts with the cheapest (Soy candle ₹649)', strpos($asc, 'Soy Wax Jar Candle') < strpos($asc, 'Indigo Dinner Plate') && strpos($asc, 'Indigo Dinner Plate') < strpos($asc, 'Terracotta Bud Vase'));
check('"From" price for options with different prices only', str_contains($asc, '<small>From</small><strong>₹999</strong>') && !preg_match('#From</small><strong>₹649#', $asc));
check('Unknown category → 404', $pub->req('GET', 'shop?c=no-such-thing', [], false)['code'] === 404);
$pp = $pub->req('GET', 'shop/product/sage-ripple-vase', [], false);
check('Product page: options with prices, quantity, add-to-cart and buy-now', $pp['code'] === 200 && substr_count($pp['body'], 'name="variant_id"') === 2 && str_contains($pp['body'], 'data-price="₹2,150"') && str_contains($pp['body'], 'value="checkout"'));
check('Product page: GST note, SKU, returns, related products, description', str_contains($pp['body'], 'Inclusive of all taxes') && str_contains($pp['body'], 'TM-CER-002') && str_contains($pp['body'], 'You may also like') && str_contains($pp['body'], 'Made in Khurja'));
check('Sold-out product shows "sold out" and no add-to-cart form', str_contains($rug = $pub->req('GET', 'shop/product/round-jute-rug', [], false)['body'], 'Currently sold out') && !str_contains($rug, 'data-wbshop-buy'));
check('Unknown product → 404', $pub->req('GET', 'shop/product/nothing-here', [], false)['code'] === 404);
check('"shop" is a reserved page address', isset($o->req('POST', 'api/pages', ['title' => 'Shop', 'slug' => 'shop', 'page_type' => 'generic'])['json']['errors']['slug']));
if ((int) $tplId > 0) {
    $prev = $b->req('GET', "templates/$tplId/preview", [], false)['body'];
    check('Template preview in the builder shows demo products (no database)', str_contains($prev, 'Terracotta Bud Vase') && str_contains($prev, 'Shop by category'));
}

echo "\n== Cart ==\n";
$tok = token($pp['body']);
$r = $pub->req('POST', 'shop/cart', ['_t' => 'x', 'action' => 'add', 'product_id' => $pid('terracotta-bud-vase'), 'qty' => 1], false);
check('Cart change without a valid token is refused', $r['code'] === 200 && str_contains($r['body'], 'session expired') && str_contains($r['body'], 'Your cart is empty'));
$r = $pub->req('POST', 'shop/cart', ['_t' => $tok, 'action' => 'add', 'product_id' => $pid('sage-ripple-vase'), 'qty' => 1], false);
check('Product with options needs an option (back to the product page)', $r['code'] === 303 && str_contains($r['location'], 'shop/product/sage-ripple-vase?need_option=1'));
$r = $pub->req('POST', 'shop/cart', ['_t' => $tok, 'action' => 'add', 'product_id' => $pid('terracotta-bud-vase'), 'qty' => 2, 'then' => 'product'], false);
check('Add to cart (qty 2) → back to the product with a confirmation', $r['code'] === 303 && str_contains($r['location'], '?added=1') && str_contains(implode(' ', $r['headers']['set-cookie'] ?? []), 'HttpOnly'));
$pub->req('POST', 'shop/cart', ['_t' => $tok, 'action' => 'add', 'product_id' => $pid('pebble-table-lamp'), 'variant_id' => $vid('pebble-table-lamp', 'Sage base'), 'qty' => 1], false);
$cart = $pub->req('GET', 'shop/cart', [], false)['body'];
check('Cart shows both lines, option label and subtotal ₹6,399', substr_count($cart, 'class="wbshop-line"') === 2 && str_contains($cart, 'Base: Sage base') && str_contains($cart, '<dt>Items (3)</dt><dd>₹6,399</dd>'));
check('Header cart count updates', str_contains($pub->req('GET', '', [], false)['body'], 'aria-label="Cart, 3 items"'));
$tok = token($cart);
$r = $pub->req('POST', 'shop/cart', ['_t' => $tok, 'action' => 'add', 'product_id' => $pid('pebble-table-lamp'), 'variant_id' => $vid('pebble-table-lamp', 'Sage base'), 'qty' => 5], false);
check('Quantity above stock is reduced to the stock (2 left of Sage base), with a message', str_contains($r['body'], 'Only 2 left of Pebble Table Lamp (Sage base)') && str_contains($r['body'], 'name="qty[1]" value="2"'));
$pub->req('POST', 'shop/cart', ['_t' => $tok, 'action' => 'update', 'qty' => [0 => 1, 1 => 1]], false);
$cart = $pub->req('GET', 'shop/cart', [], false)['body'];
check('Update quantities', str_contains($cart, '<dt>Items (2)</dt><dd>₹4,949</dd>'));
// a tampered cookie is ignored
$jar = (string) file_get_contents($pub->jar);
$tampered = preg_replace_callback('/(wbcart_[a-f0-9]+\t)([^\t\r\n]+)$/m', static fn ($m) => $m[1] . str_replace('.', 'X.', $m[2]), $jar);
$evil = new Client($siteUrl);
$evil->basic = $qa;
file_put_contents($evil->jar, (string) $tampered);
check('A tampered cart cookie is ignored (signature check)', str_contains($evil->req('GET', 'shop/cart', [], false)['body'], 'Your cart is empty'));

echo "\n== Coupons & pricing ==\n";
$r = $pub->req('POST', 'shop/cart', ['_t' => $tok, 'action' => 'coupon', 'coupon' => 'NOPE99'], false);
check('Unknown coupon refused with a message', str_contains($r['body'], 'The code NOPE99 is not valid'));
$pdo->exec("UPDATE {$t('coupons')} SET min_order = 600000 WHERE code = 'WELCOME10'");
$r = $pub->req('POST', 'shop/cart', ['_t' => $tok, 'action' => 'coupon', 'coupon' => 'welcome10'], false);
check('Coupon below its minimum order refused', str_contains($r['body'], 'needs an order of at least ₹6,000'));
$pdo->exec("UPDATE {$t('coupons')} SET min_order = 99900 WHERE code = 'WELCOME10'");
$r = $pub->req('POST', 'shop/cart', ['_t' => $tok, 'action' => 'coupon', 'coupon' => 'welcome10'], false);
$cart = $pub->req('GET', 'shop/cart', [], false)['body'];
check('WELCOME10: 10% capped at ₹300; delivery free above ₹999', str_contains($cart, 'WELCOME10</strong> applied') && str_contains($cart, '<dd>− ₹300</dd>') && str_contains($cart, '<dt>Delivery</dt><dd>Free</dd>') && str_contains($cart, '<dt>Total</dt><dd>₹4,649</dd>'));
// GST included: vase ₹1,450 @12% and lamp ₹3,499 @18%, ₹300 discount shared by value → tax ₹145.94 + ₹501.39
check('GST worked out per line after the discount (included in prices): ₹647.33', str_contains($cart, '<dt>Includes GST</dt><dd>₹647.33</dd>'));
$pdo->exec("UPDATE {$t('coupons')} SET status = 'paused' WHERE code = 'WELCOME10'");
$cart = $pub->req('GET', 'shop/cart', [], false)['body'];
check('A coupon paused later is dropped from the cart', !str_contains($cart, 'WELCOME10</strong> applied') && str_contains($cart, '<dt>Total</dt><dd>₹4,949</dd>'));
$pdo->exec("UPDATE {$t('coupons')} SET status = 'active' WHERE code = 'WELCOME10'");
$pub->req('POST', 'shop/cart', ['_t' => $tok, 'action' => 'coupon', 'coupon' => 'WELCOME10'], false);

echo "\n== Checkout: cash on delivery ==\n";
$co = $pub->req('GET', 'shop/checkout', [], false);
check('Checkout page: COD offered (online payment switched off), order summary', $co['code'] === 200 && str_contains($co['body'], 'value="cod"') && !str_contains($co['body'], 'value="online"') && str_contains($co['body'], 'Place order'));
$ctok = token($co['body']);
sleep(3);
$r = $pub->req('POST', 'shop/checkout', ['_t' => $ctok, 'name' => '', 'email' => 'bad', 'phone' => '12345', 'address' => 'x', 'city' => '', 'state' => 'Atlantis', 'pincode' => '012345', 'gstin' => 'ABC', 'payment' => 'cod'], false);
check('Validation: name, email, mobile, address, city, state, PIN, GSTIN', $r['code'] === 422 && substr_count($r['body'], 'class="wbshop-err"') === 8, (string) substr_count($r['body'], 'class="wbshop-err"'));
$addr = ['name' => "Meera $sfx", 'email' => "meera$sfx@example.com", 'phone' => '+91 98470 12345', 'address' => '12 Temple Road, Thevara', 'city' => 'Kochi', 'state' => 'Kerala', 'pincode' => '682013', 'gstin' => '', 'notes' => 'Please call before delivery <b>x</b>'];
$r = $pub->req('POST', 'shop/checkout', ['_t' => $ctok, 'payment' => 'cod'] + $addr, false);
check('Order placed → redirected to the order page (token link)', $r['code'] === 303 && preg_match('#/shop/order/(TM\d{6}[A-Z0-9]{3,5})\?t=[a-f0-9]{32}$#', $r['location'], $om) === 1, $r['code'] . ' ' . $r['location']);
$orderNo = $om[1] ?? '';
$orderUrl = $abs((string) $r['location']);
$op = $pub->req('GET', $orderUrl, [], false);
check('Order page: thank you, status steps, totals with COD fee, invoice link, noindex', str_contains($op['body'], 'Thank you, Meera') && str_contains($op['body'], 'wbshop-steps') && str_contains($op['body'], '<dt>Cash on delivery fee</dt><dd>₹49</dd>')
    && str_contains($op['body'], '<dt>Total</dt><dd>₹4,698</dd>') && str_contains($op['body'], 'Download invoice') && str_contains(implode(',', $op['headers']['x-robots-tag'] ?? []), 'noindex'));
check('Order page needs its token', $pub->req('GET', 'shop/order/' . $orderNo, [], false)['code'] === 404 && $pub->req('GET', 'shop/order/' . $orderNo . '?t=' . str_repeat('0', 32), [], false)['code'] === 404);
check('Cart emptied after the order', str_contains($pub->req('GET', 'shop/cart', [], false)['body'], 'Your cart is empty'));
$ord = $pdo->query("SELECT * FROM {$t('orders')} WHERE order_no = " . $pdo->quote($orderNo))->fetch();
check('Stored: placed, COD unpaid, subtotal/discount/shipping/fee/tax/total in paise', $ord && $ord['status'] === 'placed' && $ord['payment_method'] === 'cod' && $ord['payment_status'] === 'unpaid'
    && (int) $ord['subtotal'] === 494900 && (int) $ord['discount'] === 30000 && (int) $ord['shipping'] === 0 && (int) $ord['cod_fee'] === 4900 && (int) $ord['tax'] === 64733 && (int) $ord['total'] === 469800 && $ord['coupon_code'] === 'WELCOME10', json_encode($ord));
check('Invoice number assigned in the financial-year series (TM/yy-yy/0001)', (bool) preg_match('#^TM/\d{2}-\d{2}/0001$#', (string) $ord['invoice_no']), (string) $ord['invoice_no']);
check('Customer note stored as plain text', $ord['notes'] === 'Please call before delivery x');
check('Stock taken: vase 14 → 13, lamp (Sage base) 2 → 1; coupon used once', $stockOf('terracotta-bud-vase') === 13 && $stockOf('pebble-table-lamp', 'Sage base') === 1
    && (int) $pdo->query("SELECT used_count FROM {$t('coupons')} WHERE code = 'WELCOME10'")->fetchColumn() === 1);
$items = $pdo->query("SELECT * FROM {$t('order_items')} WHERE order_id = " . (int) $ord['id'] . ' ORDER BY id')->fetchAll();
check('Order lines keep name, option, HSN, GST rate, discount share and tax', count($items) === 2 && $items[1]['variant_label'] === 'Sage base' && $items[0]['hsn'] === '6913' && (float) $items[1]['gst_rate'] === 18.0
    && (int) $items[0]['discount'] + (int) $items[1]['discount'] === 30000 && (int) $items[0]['tax'] === 14594 && (int) $items[1]['tax'] === 50139);
$cm = array_values(array_filter(mails($mailDir, "meera$sfx@example.com", $started), fn ($x) => str_contains($x, $orderNo)));
check('Customer gets an order confirmation (items, total, COD note, order link)', count($cm) === 1 && str_contains($cm[0], 'Terracotta Bud Vase') && str_contains($cm[0], '4,698') && str_contains($cm[0], 'Cash on delivery') && str_contains($cm[0], '/shop/order/' . $orderNo . '?t='));
$nm = array_values(array_filter(mails($mailDir, $notify, $started), fn ($x) => str_contains($x, $orderNo)));
check('Shop owner notified of the new order (Reply-To the customer)', count($nm) === 1 && str_contains($nm[0], 'New order ' . $orderNo) && preg_match("/Reply-To: .*meera$sfx@example\\.com/i", $nm[0]) === 1);

echo "\n== Invoice ==\n";
preg_match('#href="([^"]*/shop/invoice/[^"]+)"#', $op['body'], $im);
$inv = $pub->req('GET', $host . html_entity_decode($im[1] ?? ''), [], false);
check('Invoice PDF (A4) for the customer', $inv['code'] === 200 && str_starts_with($inv['body'], '%PDF-1.4') && str_contains($inv['headers']['content-type'][0] ?? '', 'application/pdf') && str_contains($inv['body'], '/MediaBox [0 0 595.28 841.89]'));
check('Invoice shows seller, invoice no., buyer, items, GST split (CGST + SGST within Kerala) and total', str_contains($inv['body'], '(Terra Market Crafts)') && str_contains($inv['body'], '(' . $ord['invoice_no'] . ')')
    && str_contains($inv['body'], "(Meera $sfx)") && str_contains($inv['body'], 'Terracotta Bud Vase') && str_contains($inv['body'], 'CGST 9%') && str_contains($inv['body'], '(Rs. 4,698)'));
check('Invoice needs the order token', $pub->req('GET', 'shop/invoice/' . $orderNo . '?t=bad', [], false)['code'] === 404);
$ai = $o->req('GET', 'orders/' . $ord['id'] . '/invoice', [], false);
check('Admin can download the same invoice', $ai['code'] === 200 && str_starts_with($ai['body'], '%PDF'));
check('Invoice is not shown to the public without login (admin route)', (new Client($adminUrl))->req('GET', 'orders/' . $ord['id'] . '/invoice', [], false)['code'] !== 200);

echo "\n== Online payment (Razorpay via the emulator) ==\n";
$r = $o->req('POST', 'payments/settings', ['enabled' => '1', 'gateway' => 'razorpay', 'mode' => 'test', 'keys' => ['test' => ['razorpay' => $rz]], 'page' => $page]);
check('Online payments switched on (test mode)', ($r['json']['ok'] ?? false) === true, $r['body']);
/** Put one item in the cart and check out online; returns the response of the checkout POST. */
$checkoutOnline = function (array $addr, string $slug = 'indigo-dinner-plate', int $qty = 1) use ($pub, $pid): array {
    $p = $pub->req('GET', 'shop/product/' . $slug, [], false);
    $pub->req('POST', 'shop/cart', ['_t' => token($p['body']), 'action' => 'add', 'product_id' => $pid($slug), 'qty' => $qty], false);
    $co = $pub->req('GET', 'shop/checkout', [], false);
    sleep(3);
    return [$co, $pub->req('POST', 'shop/checkout', ['_t' => token($co['body']), 'payment' => 'online'] + $addr, false)];
};
$addr2 = ['name' => "Arjun $sfx", 'email' => "arjun$sfx@example.com", 'phone' => '9847099999', 'address' => '4 MG Road, Indiranagar', 'city' => 'Bengaluru', 'state' => 'Karnataka', 'pincode' => '560038', 'gstin' => '29ABCDE1234F1Z5', 'notes' => ''];
[$co, $r] = $checkoutOnline($addr2);
check('Checkout offers online payment and shows test mode', str_contains($co['body'], 'value="online"') && str_contains($co['body'], 'test mode'));
$opt = optionsOf($r['body']);
check('Online order → Razorpay checkout opens for the order total (₹699 + ₹99 delivery)', $r['code'] === 200 && ($opt['amount'] ?? 0) === 79800 && str_contains($r['body'], 'Complete your payment'), json_encode($opt));
$o2 = $pdo->query("SELECT * FROM {$t('orders')} WHERE customer_email = " . $pdo->quote("arjun$sfx@example.com"))->fetch();
check('Order waits for payment: no invoice, stock not taken yet', $o2 && $o2['status'] === 'pending_payment' && $o2['invoice_no'] === null && $stockOf('indigo-dinner-plate') === 30 && (int) $o2['inter_state'] === 1);
$g = $em->req('POST', 'razorpay/pay', ['order_id' => $opt['order_id'] ?? '', 'callback_url' => $opt['callback_url'] ?? '', 'outcome' => 'failure'], false);
[$act, $f] = formOf($g['body']);
$back = $pub->req('POST', $act, $f, false);
check('Failed payment → back to the order page (not the receipt)', $back['code'] === 303 && str_contains($back['location'], '/shop/order/' . $o2['order_no'] . '?t='), $back['location'] ?? '');
$op2 = $pub->req('GET', $abs((string) $back['location']), [], false)['body'];
check('Order page: "Payment not completed" with a Pay now button', str_contains($op2, 'Payment not completed') && str_contains($op2, 'Pay ₹798 now'));
check('Order marked payment failed; no confirmation email', $pdo->query("SELECT payment_status FROM {$t('orders')} WHERE id = " . (int) $o2['id'])->fetchColumn() === 'failed'
    && !array_filter(mails($mailDir, "arjun$sfx@example.com", $started, 2), fn ($x) => str_contains($x, $o2['order_no'])));
[$payAct, $pf] = formOf(substr($op2, strpos($op2, 'wbshop-hero')));
sleep(1);
$r = $pub->req('POST', $host . $payAct, $pf, false);
$opt = optionsOf($r['body']);
check('Retry: a new payment for the same order', ($opt['amount'] ?? 0) === 79800);
$g = $em->req('POST', 'razorpay/pay', ['order_id' => $opt['order_id'] ?? '', 'callback_url' => $opt['callback_url'] ?? '', 'outcome' => 'success'], false);
[$act, $f] = formOf($g['body']);
$back = $pub->req('POST', $act, $f, false);
$o2 = $pdo->query("SELECT * FROM {$t('orders')} WHERE id = " . (int) $o2['id'])->fetch();
check('Paid → order placed, invoice assigned, stock taken, payment linked to the order', $o2['status'] === 'placed' && $o2['payment_status'] === 'paid' && preg_match('#/0002$#', (string) $o2['invoice_no']) === 1 && $stockOf('indigo-dinner-plate') === 29
    && (int) $pdo->query("SELECT order_id FROM {$t('payment_transactions')} WHERE id = " . (int) $o2['transaction_id'])->fetchColumn() === (int) $o2['id']);
check('Customer lands on the confirmed order page', str_contains($pub->req('GET', $abs((string) $back['location']), [], false)['body'], 'Thank you, Arjun'));
$cm = array_values(array_filter(mails($mailDir, "arjun$sfx@example.com", $started), fn ($x) => str_contains($x, $o2['order_no'])));
check('Exactly one order confirmation (no separate payment receipt)', count($cm) === 1 && str_contains($cm[0], 'Paid online'), (string) count($cm));
$inv2 = $pub->req('GET', 'shop/invoice/' . $o2['order_no'] . '?t=' . substr(parse_url($back['location'], PHP_URL_QUERY) ?: '', 2), [], false)['body'];
check('Out-of-state order: IGST on the invoice, buyer GSTIN printed', str_contains($inv2, 'IGST 12%') && str_contains($inv2, 'GSTIN: 29ABCDE1234F1Z5'));

echo "\n== Orders admin ==\n";
$list = $o->req('GET', 'api/orders', ['q' => $sfx])['json'];
check('Orders list (search) with totals', ($list['total'] ?? 0) === 2 && ($list['sales_total_text'] ?? '') === '₹5,496');
check('Awaiting-payment orders hidden from the default list', !in_array('pending_payment', array_column($o->req('GET', 'api/orders')['json']['rows'] ?? [], 'status'), true));
check('Order page in the admin', str_contains($o->req('GET', 'orders/' . $ord['id'], [], false)['body'], $orderNo));
$r = $o->req('POST', 'api/orders/' . $ord['id'], ['status' => 'shipped', 'courier' => 'India Post', 'tracking_no' => "EK$sfx", 'tracking_url' => 'javascript:alert(1)']);
check('Unsafe tracking link refused', isset($r['json']['errors']['tracking_url']));
$r = $o->req('POST', 'api/orders/' . $ord['id'], ['status' => 'shipped', 'courier' => 'India Post', 'tracking_no' => "EK$sfx", 'tracking_url' => 'https://www.indiapost.gov.in/', 'notify' => '1']);
check('Mark shipped with tracking; customer emailed', ($r['json']['ok'] ?? false) && str_contains($r['json']['message'] ?? '', 'customer was emailed'), $r['body']);
check('Shipping email has the tracking number', (bool) array_filter(mails($mailDir, "meera$sfx@example.com", $started), fn ($x) => str_contains($x, 'has shipped') && str_contains($x, "EK$sfx")));
check('Customer order page shows the tracking number', str_contains($pub->req('GET', $orderUrl, [], false)['body'], "Tracking number <strong>EK$sfx</strong>"));
check('Shipped orders cannot go back to "placed"', isset($o->req('POST', 'api/orders/' . $ord['id'], ['status' => 'placed'])['json']['errors']['status']));
$r = $o->req('POST', 'api/orders/' . $ord['id'], ['status' => 'delivered', 'payment_status' => 'paid', 'courier' => 'India Post', 'tracking_no' => "EK$sfx"]);
check('Delivered and cash received (COD marked paid)', ($r['json']['ok'] ?? false) && $pdo->query("SELECT CONCAT(status, '/', payment_status) FROM {$t('orders')} WHERE id = " . (int) $ord['id'])->fetchColumn() === 'delivered/paid');
$r = $o->req('POST', 'api/orders/' . $o2['id'], ['status' => 'cancelled', 'notify' => '1']);
check('Cancel an order: stock returned (plate 29 → 30)', ($r['json']['ok'] ?? false) && $stockOf('indigo-dinner-plate') === 30);
check('Cancellation email mentions the refund for a paid order', (bool) array_filter(mails($mailDir, "arjun$sfx@example.com", $started), fn ($x) => str_contains($x, 'cancelled') && str_contains($x, 'refunded')));
$ev = $o->req('GET', 'orders/' . $o2['id'], [], false)['body'];
check('Order history records payment attempts, status changes and emails', str_contains($ev, 'failed') && str_contains($ev, 'Paid online') && str_contains($ev, 'Placed → Cancelled') && str_contains($ev, 'Items returned to stock'));
$csv = $o->req('GET', 'api/orders/export', ['q' => $sfx], false);
check('Orders CSV (UTF-8, amounts in rupees)', str_starts_with($csv['body'], "\xEF\xBB\xBF") && str_contains($csv['body'], $orderNo) && str_contains($csv['body'], ',4698.00,'));

echo "\n== Products, categories, coupons, settings (admin) ==\n";
check('Admin menu has Orders, Products and Coupons', preg_match_all('#/admin/(orders|products|coupons)"#', $o->req('GET', '', [], false)['body']) >= 3);
$r = $o->req('POST', 'api/products', ['name' => '', 'price' => 'abc', 'compare_price' => '10', 'gst_rate' => '7', 'hsn' => '12', 'stock' => '-1', 'images' => ['uploads/products/not-mine.jpg']]);
check('Product validation: name, price, GST rate, HSN, stock, image from the media library', count(array_intersect(array_keys((array) ($r['json']['errors'] ?? [])), ['name', 'price', 'gst_rate', 'hsn', 'stock', 'image'])) === 6, $r['body']);
$catId = (int) $pdo->query("SELECT id FROM {$t('product_categories')} WHERE slug = 'decor'")->fetchColumn();
$r = $o->req('POST', 'api/products', ['name' => "Brass Bell \"Om\" & <b>Co</b> $sfx", 'price' => '1,250', 'compare_price' => '1500', 'gst_rate' => '12', 'hsn' => '7418', 'stock' => '0', 'track_stock' => '1',
    'category_id' => $catId, 'status' => 'active', 'badge' => 'New', 'description' => '<p>Hand <b>cast</b></p><script>alert(1)</script><img src=x onerror=alert(1)>', 'images' => ['theme:images/candle-holders.svg'],
    'option_name' => 'Finish', 'variants' => [['label' => 'Antique', 'price' => '', 'stock' => '3'], ['label' => 'Polished', 'price' => '1350', 'stock' => '2']]]);
$newId = (int) ($r['json']['id'] ?? 0);
check('Add a product with two options', ($r['json']['ok'] ?? false) && $newId > 0, $r['body']);
$np = $pdo->query("SELECT * FROM {$t('products')} WHERE id = $newId")->fetch();
check('Price stored in paise, description cleaned, slug from the name', (int) $np['price'] === 125000 && (int) $np['compare_price'] === 150000 && !str_contains((string) $np['description'], '<script') && !str_contains((string) $np['description'], 'onerror') && str_starts_with($np['slug'], 'brass-bell-om-co-') && $np['name'] === "Brass Bell \"Om\" & Co $sfx");
$npPage = $pub->req('GET', 'shop/product/' . $np['slug'], [], false)['body'];
check('New product live in the shop, name escaped', str_contains($npPage, 'Brass Bell &quot;Om&quot; &amp; Co') && substr_count($npPage, 'name="variant_id"') === 2);
$vars = $pdo->query("SELECT id, label FROM {$t('product_variants')} WHERE product_id = $newId ORDER BY sort_order")->fetchAll();
$r = $o->req('POST', "api/products/$newId", ['name' => "Brass Bell $sfx", 'price' => '1250', 'gst_rate' => '12', 'stock' => '0', 'track_stock' => '1', 'status' => 'draft', 'images' => ['theme:images/candle-holders.svg'],
    'option_name' => 'Finish', 'variants' => [['id' => $vars[1]['id'], 'label' => 'Polished', 'price' => '1350', 'stock' => '9']]]);
check('Edit: keep one option (updated, same id), remove the other, set to draft', ($r['json']['ok'] ?? false) && $pdo->query("SELECT GROUP_CONCAT(CONCAT(id, ':', stock)) FROM {$t('product_variants')} WHERE product_id = $newId")->fetchColumn() === $vars[1]['id'] . ':9');
check('Draft products are hidden from the shop', $pub->req('GET', 'shop/product/' . $np['slug'], [], false)['code'] === 404);
check('Duplicate option names refused', isset($o->req('POST', "api/products/$newId", ['name' => 'X', 'price' => '100', 'gst_rate' => '0', 'stock' => '0', 'option_name' => 'Size', 'variants' => [['label' => 'M', 'stock' => '1'], ['label' => 'm', 'stock' => '1']]])['json']['errors']['variants']));
check('Quick stock edit from the list', ($o->req('POST', 'api/products/' . $pid('indigo-dinner-plate') . '/stock', ['stock' => '42'])['json']['ok'] ?? false) && $stockOf('indigo-dinner-plate') === 42);
check('Low-stock filter', in_array('Pebble Table Lamp', array_column($o->req('GET', 'api/products', ['stock' => 'low'])['json']['rows'] ?? [], 'name'), true));
check('Delete product (past orders keep their lines)', ($o->req('POST', "api/products/$newId/delete")['json']['ok'] ?? false) && (int) $pdo->query("SELECT COUNT(*) FROM {$t('products')} WHERE id = $newId")->fetchColumn() === 0);
$r = $o->req('POST', 'api/categories', ['name' => "Gifts $sfx", 'description' => 'Gift sets', 'status' => 'active', 'sort_order' => '9', 'image' => 'theme:images/cat-decor.svg']);
$cid = (int) $pdo->query("SELECT id FROM {$t('product_categories')} WHERE name = " . $pdo->quote("Gifts $sfx"))->fetchColumn();
check('Add a category', ($r['json']['ok'] ?? false) && $cid > 0 && str_contains($pub->req('GET', 'shop', [], false)['body'], "Gifts $sfx"));
check('Delete a category', ($o->req('POST', "api/categories/$cid/delete")['json']['ok'] ?? false));
$r = $o->req('POST', 'api/coupons', ['code' => 'ab', 'type' => 'percent', 'value' => '95']);
check('Coupon validation (code, percentage 1–90)', isset($r['json']['errors']['code'], $r['json']['errors']['value']));
$r = $o->req('POST', 'api/coupons', ['code' => "flat$sfx", 'type' => 'fixed', 'value' => '200', 'min_order' => '500', 'ends_at' => date('Y-m-d', strtotime('-1 day')), 'status' => 'active']);
check('Add a fixed-amount coupon (₹200)', ($r['json']['ok'] ?? false) && (int) $pdo->query("SELECT value FROM {$t('coupons')} WHERE code = " . $pdo->quote(strtoupper("flat$sfx")))->fetchColumn() === 20000);
$ptok = token($pub->req('GET', 'shop/product/terracotta-bud-vase', [], false)['body']);
$pub->req('POST', 'shop/cart', ['_t' => $ptok, 'action' => 'add', 'product_id' => $pid('terracotta-bud-vase'), 'qty' => 1], false);
check('Expired coupon refused in the cart', str_contains($pub->req('POST', 'shop/cart', ['_t' => $ptok, 'action' => 'coupon', 'coupon' => "FLAT$sfx"], false)['body'], 'not valid or has expired'));
check('Duplicate coupon code refused', isset($o->req('POST', 'api/coupons', ['code' => "FLAT$sfx", 'type' => 'free_shipping'])['json']['errors']['code']));
$r = $o->req('POST', 'shop-settings', ['enabled' => '1', 'gstin' => 'NOTAGSTIN', 'store_state' => 'Kerala', 'order_prefix' => 'TM', 'invoice_prefix' => 'TM/', 'shipping_flat' => 'abc']);
check('Shop settings validation (GSTIN, amounts)', isset($r['json']['errors']['gstin'], $r['json']['errors']['shipping_flat']));
$settings = ['enabled' => '0', 'legal_name' => 'Terra Market Crafts', 'gstin' => '32ABCDE1234F1Z5', 'store_state' => 'Kerala', 'invoice_address' => 'Kochi', 'prices_include_tax' => '1', 'order_prefix' => 'TM', 'invoice_prefix' => 'TM/',
    'shipping_flat' => '99', 'free_shipping_above' => '999', 'cod_enabled' => '1', 'cod_fee' => '49', 'cod_max' => '10000', 'min_order' => '0', 'low_stock' => '5', 'shipping_note' => 'Ships in 2–3 days', 'return_policy' => '7 days', 'checkout_note' => ''];
check('Switch the shop off', ($o->req('POST', 'shop-settings', $settings)['json']['ok'] ?? false) === true);
check('Shop closed: /shop is 404, header has no cart', $pub->req('GET', 'shop', [], false)['code'] === 404 && !str_contains($pub->req('GET', '', [], false)['body'], 'aria-label="Cart'));
$settings['enabled'] = '1';
$o->req('POST', 'shop-settings', $settings);
check('Shop open again; GSTIN makes it a "TAX INVOICE"', $pub->req('GET', 'shop', [], false)['code'] === 200 && str_contains($o->req('GET', 'orders/' . $ord['id'] . '/invoice', [], false)['body'], '(TAX INVOICE)'));
$e = $o->req('GET', 'coupons', [], false);
check('Coupons page loads', $e['code'] === 200 && str_contains($e['body'], 'couponTable'));
foreach (['products', 'products/new', 'products/' . $pid('sage-ripple-vase'), 'categories', 'shop-settings', 'orders'] as $pg) {
    $r = $o->req('GET', $pg, [], false);
    check("Admin page /admin/$pg", $r['code'] === 200 && !preg_match('/(Fatal error|Warning:|Notice:|Deprecated:)/', $r['body']));
}
$o->req('POST', 'payments/settings', ['enabled' => '0', 'gateway' => 'razorpay', 'mode' => 'test', 'page' => $page]);

echo "\n" . str_repeat('-', 50) . "\nPhase 7a shop: $pass passed, $fail failed\n";
exit($fail ? 1 : 0);
