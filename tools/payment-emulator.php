<?php
// Local development and automated tests only: an imitation of the five payment gateways' sandbox APIs
// and hosted payment pages, following each gateway's published API (requests, responses, signatures).
//
//   php -S 127.0.0.1:8090 tools/payment-emulator.php
//
// Then set 'payment_emulator' => 'http://127.0.0.1:8090' in the builder's config/config.php and regenerate
// the website: in TEST mode only, the website then talks to this emulator instead of the gateways' sandboxes.
// Credentials the emulator accepts: any, except a secret equal to "wrong" (→ 401). PayU's salt must be "salt-<merchant key>".
//
// Test helpers: GET /_emu/state (all data), POST /_emu/set {gateway, id, field, value}, POST /_emu/reset.

declare(strict_types=1);

const STATE_FILE = '/tmp/wb-payment-emulator.json';

$method = $_SERVER['REQUEST_METHOD'];
$uri = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$self = 'http://' . $_SERVER['HTTP_HOST'];
$raw = (string) file_get_contents('php://input');
$hdr = array_change_key_case(function_exists('getallheaders') ? getallheaders() : [], CASE_LOWER);

function state(?callable $fn = null): array
{
    $fp = fopen(STATE_FILE, 'c+');
    flock($fp, LOCK_EX);
    $data = json_decode((string) stream_get_contents($fp), true) ?: [];
    if ($fn) {
        $data = $fn($data);
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    flock($fp, LOCK_UN);
    fclose($fp);
    return $data;
}
function json(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}
function html(string $title, string $body, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . htmlspecialchars($title) . '</title>'
        . '<style>body{font-family:system-ui,sans-serif;background:#f1f5f9;display:grid;place-items:center;min-height:95vh;margin:0}.box{background:#fff;padding:2rem;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.08);max-width:420px;width:90%}'
        . 'button{display:block;width:100%;padding:.8rem;margin:.5rem 0;border:0;border-radius:8px;font-size:1rem;cursor:pointer}.ok{background:#16a34a;color:#fff}.fail{background:#dc2626;color:#fff}.other{background:#e2e8f0}small{color:#64748b}</style></head>'
        . '<body><div class="box"><small>PAYMENT GATEWAY EMULATOR (local tests)</small><h2>' . htmlspecialchars($title) . '</h2>' . $body . '</div></body></html>';
    exit;
}
/** Auto-posting form (how Razorpay and PayU send the customer back). */
function autopost(string $url, array $fields): never
{
    $in = '';
    foreach ($fields as $k => $v) {
        $in .= '<input type="hidden" name="' . htmlspecialchars((string) $k) . '" value="' . htmlspecialchars((string) $v) . '">';
    }
    html('Returning to the website…', '<form id="back" method="post" action="' . htmlspecialchars($url) . '">' . $in . '<button class="other">Continue</button></form><script>document.getElementById("back").submit()</script>');
}
function outcomes(string $action, array $hidden, array $extra = []): string
{
    $h = '';
    foreach ($hidden as $k => $v) {
        $h .= '<input type="hidden" name="' . htmlspecialchars($k) . '" value="' . htmlspecialchars((string) $v) . '">';
    }
    $b = '<form method="post" action="' . htmlspecialchars($action) . '">' . $h . '<button class="ok" name="outcome" value="success" id="emuPay">Pay (success)</button>'
        . '<button class="fail" name="outcome" value="failure" id="emuFail">Fail the payment</button>';
    foreach ($extra as $v => $label) {
        $b .= '<button class="other" name="outcome" value="' . $v . '">' . htmlspecialchars($label) . '</button>';
    }
    return $b . '</form>';
}
function rid(string $prefix, int $n = 14): string
{
    return $prefix . substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(16))), 0, $n);
}

// ------------------------------------------------------------------ test helpers
if ($uri === '/_emu/state') {
    json(state());
}
if ($uri === '/_emu/reset' && $method === 'POST') {
    state(fn () => []);
    json(['ok' => true]);
}
if ($uri === '/_emu/set' && $method === 'POST') {
    state(function ($s) {
        $s[$_POST['gateway']][$_POST['id']][$_POST['field']] = is_numeric($_POST['value']) ? $_POST['value'] + 0 : $_POST['value'];
        return $s;
    });
    json(['ok' => true]);
}

// ================================================================== Razorpay
if (str_starts_with($uri, '/razorpay/')) {
    $p = substr($uri, 9);
    $auth = base64_decode(substr($hdr['authorization'] ?? '', 6)) ?: '';
    [$keyId, $secret] = array_pad(explode(':', $auth, 2), 2, '');
    $api = str_starts_with($p, '/v1/');
    if ($api && (!str_starts_with($keyId, 'rzp_test_') || $secret === '' || $secret === 'wrong')) {
        json(['error' => ['code' => 'BAD_REQUEST_ERROR', 'description' => 'Authentication failed']], 401);
    }
    if ($p === '/v1/orders' && $method === 'POST') {
        $in = json_decode($raw, true) ?: [];
        if (($in['amount'] ?? 0) < 100) {
            json(['error' => ['code' => 'BAD_REQUEST_ERROR', 'description' => 'Order amount less than minimum amount allowed']], 400);
        }
        $o = ['id' => rid('order_'), 'entity' => 'order', 'amount' => (int) $in['amount'], 'currency' => $in['currency'] ?? 'INR', 'receipt' => $in['receipt'] ?? null,
            'status' => 'created', 'notes' => $in['notes'] ?? [], '_secret' => $secret, 'created_at' => time()];
        state(function ($s) use ($o) { $s['razorpay'][$o['id']] = $o; return $s; });
        unset($o['_secret']);
        json($o);
    }
    if ($p === '/v1/orders' && $method === 'GET') {
        json(['entity' => 'collection', 'count' => 0, 'items' => []]);
    }
    if (preg_match('#^/v1/orders/([^/]+)/payments$#', $p, $m)) {
        $o = state()['razorpay'][$m[1]] ?? json(['error' => ['description' => 'The id provided does not exist']], 400);
        json(['entity' => 'collection', 'count' => count($o['payments'] ?? []), 'items' => array_values($o['payments'] ?? [])]);
    }
    if (preg_match('#^/v1/payments/([^/]+)(/capture)?$#', $p, $m)) {
        $found = null;
        state(function ($s) use ($m, &$found) {
            foreach ($s['razorpay'] ?? [] as $oid => $o) {
                if (isset($o['payments'][$m[1]])) {
                    if (!empty($m[2]) && $s['razorpay'][$oid]['payments'][$m[1]]['status'] === 'authorized') {
                        $s['razorpay'][$oid]['payments'][$m[1]]['status'] = 'captured';
                        $s['razorpay'][$oid]['status'] = 'paid';
                    }
                    $found = $s['razorpay'][$oid]['payments'][$m[1]];
                }
            }
            return $s;
        });
        $found ? json($found) : json(['error' => ['description' => 'The id provided does not exist']], 400);
    }
    if ($p === '/checkout.js') {
        header('Content-Type: application/javascript');
        echo 'window.Razorpay=function(o){this.o=o};window.Razorpay.prototype.open=function(){var o=this.o;window.location.href=' . json_encode($self) . '+"/razorpay/pay?order_id="+encodeURIComponent(o.order_id)+"&key="+encodeURIComponent(o.key)+"&callback_url="+encodeURIComponent(o.callback_url)};';
        exit;
    }
    if ($p === '/pay' && $method === 'GET') {
        $o = state()['razorpay'][$_GET['order_id'] ?? ''] ?? html('Unknown order', '', 404);
        html('Razorpay · ₹' . number_format($o['amount'] / 100, 2), outcomes('/razorpay/pay', ['order_id' => $o['id'], 'callback_url' => $_GET['callback_url'] ?? ''],
            ['authorized' => 'Pay (manual capture account)', 'wrong_amount' => 'Pay a different amount (tamper test)', 'no_callback' => 'Pay, but never return to the website']));
    }
    if ($p === '/pay' && $method === 'POST') {
        $oid = (string) $_POST['order_id'];
        $outcome = (string) $_POST['outcome'];
        $pay = null;
        $s = state(function ($s) use ($oid, $outcome, &$pay) {
            $o = $s['razorpay'][$oid];
            $pay = ['id' => rid('pay_'), 'entity' => 'payment', 'amount' => $outcome === 'wrong_amount' ? 100 : $o['amount'], 'currency' => 'INR', 'order_id' => $oid, 'method' => 'upi',
                'status' => match ($outcome) { 'failure' => 'failed', 'authorized' => 'authorized', default => 'captured' }, 'captured' => $outcome !== 'failure' && $outcome !== 'authorized',
                'error_description' => $outcome === 'failure' ? 'Payment failed because the bank declined it' : null, 'created_at' => time()];
            $s['razorpay'][$oid]['payments'][$pay['id']] = $pay;
            if ($pay['status'] === 'captured') {
                $s['razorpay'][$oid]['status'] = 'paid';
            }
            return $s;
        });
        $secret = $s['razorpay'][$oid]['_secret'];
        if ($outcome === 'no_callback') {
            html('Paid', '<p>Payment ' . $pay['id'] . ' captured. (Browser closed before returning.)</p>');
        }
        if ($outcome === 'failure') {
            autopost((string) $_POST['callback_url'], ['error[code]' => 'BAD_REQUEST_ERROR', 'error[description]' => 'Payment failed because the bank declined it', 'error[source]' => 'bank',
                'error[reason]' => 'payment_failed', 'error[metadata][order_id]' => $oid, 'error[metadata][payment_id]' => $pay['id']]);
        }
        autopost((string) $_POST['callback_url'], ['razorpay_payment_id' => $pay['id'], 'razorpay_order_id' => $oid, 'razorpay_signature' => hash_hmac('sha256', $oid . '|' . $pay['id'], $secret)]);
    }
}

// ================================================================== Stripe
if (str_starts_with($uri, '/stripe/')) {
    $p = substr($uri, 7);
    $key = substr($hdr['authorization'] ?? '', 7);
    if (str_starts_with($p, '/v1/') && (!preg_match('/^(sk|rk)_test_/', $key) || str_ends_with($key, 'wrong'))) {
        json(['error' => ['type' => 'invalid_request_error', 'message' => 'Invalid API Key provided']], 401);
    }
    if ($p === '/v1/balance') {
        json(['object' => 'balance', 'available' => [['amount' => 0, 'currency' => 'inr']]]);
    }
    if ($p === '/v1/checkout/sessions' && $method === 'POST') {
        parse_str($raw, $in);
        $li = $in['line_items'][0] ?? [];
        $sess = ['id' => rid('cs_test_', 24), 'object' => 'checkout.session', 'mode' => $in['mode'] ?? '', 'status' => 'open', 'payment_status' => 'unpaid',
            'amount_total' => (int) ($li['price_data']['unit_amount'] ?? 0) * (int) ($li['quantity'] ?? 1), 'currency' => $li['price_data']['currency'] ?? '',
            'client_reference_id' => $in['client_reference_id'] ?? null, 'customer_email' => $in['customer_email'] ?? null, 'metadata' => $in['metadata'] ?? [],
            'success_url' => $in['success_url'] ?? '', 'cancel_url' => $in['cancel_url'] ?? '', 'payment_intent' => null];
        if (!str_contains($sess['success_url'], '{CHECKOUT_SESSION_ID}') || $sess['amount_total'] <= 0) {
            json(['error' => ['message' => 'Invalid checkout session parameters']], 400);
        }
        $sess['url'] = $self . '/stripe/pay/' . $sess['id'];
        state(function ($s) use ($sess) { $s['stripe'][$sess['id']] = $sess; return $s; });
        json($sess);
    }
    if (preg_match('#^/v1/checkout/sessions/([^/]+)$#', $p, $m)) {
        $sess = state()['stripe'][$m[1]] ?? json(['error' => ['message' => 'No such checkout.session']], 404);
        json($sess);
    }
    if (preg_match('#^/pay/([^/]+)$#', $p, $m)) {
        $sess = state()['stripe'][$m[1]] ?? html('Unknown session', '', 404);
        if ($method === 'GET') {
            html('Stripe Checkout · ₹' . number_format($sess['amount_total'] / 100, 2), outcomes('/stripe/pay/' . $sess['id'], [], ['cancel' => 'Back (cancel)']));
        }
        $o = (string) $_POST['outcome'];
        if ($o === 'cancel' || $o === 'failure') {
            header('Location: ' . $sess['cancel_url'], true, 303);
            exit;
        }
        state(function ($s) use ($sess) {
            $s['stripe'][$sess['id']]['status'] = 'complete';
            $s['stripe'][$sess['id']]['payment_status'] = 'paid';
            $s['stripe'][$sess['id']]['payment_intent'] = rid('pi_', 24);
            return $s;
        });
        header('Location: ' . str_replace('{CHECKOUT_SESSION_ID}', $sess['id'], $sess['success_url']), true, 303);
        exit;
    }
}

// ================================================================== Cashfree
if (str_starts_with($uri, '/cashfree/')) {
    $p = substr($uri, 9);
    if (str_starts_with($p, '/pg/')) {
        if (($hdr['x-api-version'] ?? '') === '') {
            json(['message' => 'version_missing', 'code' => 'request_failed', 'type' => 'invalid_request_error'], 400);
        }
        if (($hdr['x-client-id'] ?? '') === '' || ($hdr['x-client-secret'] ?? '') === '' || $hdr['x-client-secret'] === 'wrong') {
            json(['message' => 'authentication Failed', 'code' => 'request_failed', 'type' => 'authentication_error'], 401);
        }
    }
    if ($p === '/pg/orders' && $method === 'POST') {
        $in = json_decode($raw, true) ?: [];
        if (empty($in['customer_details']['customer_phone']) || !preg_match('/^\d{10}$/', (string) $in['customer_details']['customer_phone'])) {
            json(['message' => 'customer_details.customer_phone : should be 10 digits', 'code' => 'customer_details.customer_phone_invalid', 'type' => 'invalid_request_error'], 400);
        }
        if (isset(state()['cashfree'][$in['order_id']])) {
            json(['message' => 'order with same id is already present', 'code' => 'order_already_exists', 'type' => 'invalid_request_error'], 409);
        }
        $o = ['cf_order_id' => (string) random_int(1000000, 9999999), 'order_id' => $in['order_id'], 'entity' => 'order', 'order_currency' => $in['order_currency'], 'order_amount' => $in['order_amount'],
            'order_status' => 'ACTIVE', 'payment_session_id' => rid('session_', 40), 'order_meta' => $in['order_meta'] ?? [], 'customer_details' => $in['customer_details'], 'payments' => [],
            '_secret' => $hdr['x-client-secret']];
        state(function ($s) use ($o) { $s['cashfree'][$o['order_id']] = $o; return $s; });
        unset($o['payments'], $o['_secret']);
        json($o);
    }
    if (preg_match('#^/pg/orders/([^/]+)(/payments)?$#', $p, $m)) {
        $o = state()['cashfree'][$m[1]] ?? json(['message' => 'order not found', 'code' => 'order_not_found', 'type' => 'invalid_request_error'], 404);
        if (!empty($m[2])) {
            json(array_reverse($o['payments']));
        }
        unset($o['payments'], $o['_secret']);
        json($o);
    }
    if ($p === '/sdk.js') {
        header('Content-Type: application/javascript');
        echo 'window.Cashfree=function(c){return{checkout:function(o){window.location.href=' . json_encode($self) . '+"/cashfree/pay?session="+encodeURIComponent(o.paymentSessionId)+"&mode="+encodeURIComponent(c.mode)}}};';
        exit;
    }
    if ($p === '/pay') {
        $sid = (string) ($_GET['session'] ?? $_POST['session'] ?? '');
        $o = null;
        foreach (state()['cashfree'] ?? [] as $x) {
            if ($x['payment_session_id'] === $sid) {
                $o = $x;
            }
        }
        $o ?? html('Unknown session', '', 404);
        if ($method === 'GET') {
            html('Cashfree · ₹' . number_format((float) $o['order_amount'], 2), outcomes('/cashfree/pay', ['session' => $sid], ['drop' => 'Close the payment window']));
        }
        $out = (string) $_POST['outcome'];
        state(function ($s) use ($o, $out) {
            $pay = ['cf_payment_id' => (string) random_int(100000000, 999999999), 'order_id' => $o['order_id'], 'payment_amount' => $o['order_amount'], 'payment_group' => 'upi',
                'payment_status' => match ($out) { 'success' => 'SUCCESS', 'drop' => 'USER_DROPPED', default => 'FAILED' }, 'payment_message' => $out === 'success' ? 'Transaction successful' : 'Transaction declined'];
            $s['cashfree'][$o['order_id']]['payments'][] = $pay;
            if ($out === 'success') {
                $s['cashfree'][$o['order_id']]['order_status'] = 'PAID';
            }
            return $s;
        });
        header('Location: ' . str_replace('{order_id}', rawurlencode($o['order_id']), (string) $o['order_meta']['return_url']), true, 303);
        exit;
    }
}

// ================================================================== PhonePe (Standard Checkout v2)
if (str_starts_with($uri, '/phonepe/')) {
    $p = substr($uri, 8);
    $p = str_starts_with($p, '/apis/pg-sandbox/') ? substr($p, 16) : $p; // same paths as the real sandbox host
    if ($p === '/v1/oauth/token' && $method === 'POST') {
        parse_str($raw, $in);
        if (($in['grant_type'] ?? '') !== 'client_credentials' || empty($in['client_id']) || empty($in['client_version']) || empty($in['client_secret']) || $in['client_secret'] === 'wrong') {
            json(['code' => 'INVALID_CLIENT', 'message' => 'Client Not Found'], 401);
        }
        $tok = rid('tok_', 32);
        state(function ($s) use ($tok) { $s['phonepe_tokens'][$tok] = time() + 3600; return $s; });
        json(['access_token' => $tok, 'encrypted_access_token' => $tok, 'expires_in' => null, 'issued_at' => time(), 'expires_at' => time() + 3600, 'session_expires_at' => time() + 3600, 'token_type' => 'O-Bearer']);
    }
    if (str_starts_with($p, '/checkout/v2/')) {
        $tok = (string) preg_replace('/^O-Bearer\s+/', '', $hdr['authorization'] ?? '');
        if (!isset(state()['phonepe_tokens'][$tok])) {
            json(['code' => 'AUTHORIZATION_FAILED', 'message' => 'Unauthorized'], 401);
        }
    }
    if ($p === '/checkout/v2/pay' && $method === 'POST') {
        $in = json_decode($raw, true) ?: [];
        if (($in['paymentFlow']['type'] ?? '') !== 'PG_CHECKOUT' || empty($in['merchantOrderId']) || ($in['amount'] ?? 0) < 100) {
            json(['code' => 'BAD_REQUEST', 'message' => 'Invalid request'], 400);
        }
        $o = ['orderId' => rid('OMO', 20), 'merchantOrderId' => $in['merchantOrderId'], 'amount' => (int) $in['amount'], 'state' => 'PENDING', 'expireAt' => (time() + 1200) * 1000,
            'redirect' => $in['paymentFlow']['merchantUrls']['redirectUrl'] ?? '', 'paymentDetails' => []];
        state(function ($s) use ($o) { $s['phonepe'][$o['merchantOrderId']] = $o; return $s; });
        json(['orderId' => $o['orderId'], 'state' => 'PENDING', 'expireAt' => $o['expireAt'], 'redirectUrl' => $self . '/phonepe/pay/' . rawurlencode($o['merchantOrderId'])]);
    }
    if (preg_match('#^/checkout/v2/order/([^/]+)/status$#', $p, $m)) {
        $o = state()['phonepe'][$m[1]] ?? json(['code' => 'ORDER_NOT_FOUND', 'message' => 'Order not found'], 404);
        unset($o['redirect']);
        json($o);
    }
    if (preg_match('#^/pay/([^/]+)$#', $p, $m)) {
        $o = state()['phonepe'][$m[1]] ?? html('Unknown order', '', 404);
        if ($method === 'GET') {
            html('PhonePe · ₹' . number_format($o['amount'] / 100, 2), outcomes('/phonepe/pay/' . rawurlencode($o['merchantOrderId']), [], ['pending' => 'Approve later in the UPI app (stays pending)']));
        }
        $out = (string) $_POST['outcome'];
        state(function ($s) use ($o, $out) {
            $st = match ($out) { 'success' => 'COMPLETED', 'pending' => 'PENDING', default => 'FAILED' };
            $s['phonepe'][$o['merchantOrderId']]['state'] = $st;
            $s['phonepe'][$o['merchantOrderId']]['paymentDetails'][] = ['paymentMode' => 'UPI_INTENT', 'transactionId' => rid('OM', 18), 'timestamp' => time() * 1000, 'amount' => $o['amount'], 'state' => $st] + ($st === 'FAILED' ? ['errorCode' => 'PAYMENT_DECLINED'] : []);
            if ($st === 'FAILED') {
                $s['phonepe'][$o['merchantOrderId']]['errorCode'] = 'PAYMENT_DECLINED';
            }
            return $s;
        });
        header('Location: ' . $o['redirect'], true, 302);
        exit;
    }
}

// ================================================================== PayU
if (str_starts_with($uri, '/payu/')) {
    $p = substr($uri, 5);
    $saltFor = static fn (string $key): string => 'salt-' . $key;
    if ($p === '/_payment' && $method === 'POST') {
        $f = $_POST;
        foreach (['key', 'txnid', 'amount', 'productinfo', 'firstname', 'email', 'phone', 'surl', 'furl', 'hash'] as $req) {
            if (($f[$req] ?? '') === '') {
                html('Error', '<p>Mandatory parameter missing: ' . $req . '</p>', 400);
            }
        }
        $expected = hash('sha512', implode('|', [$f['key'], $f['txnid'], $f['amount'], $f['productinfo'], $f['firstname'], $f['email'], $f['udf1'] ?? '', $f['udf2'] ?? '', $f['udf3'] ?? '', $f['udf4'] ?? '', $f['udf5'] ?? '', '', '', '', '', '', $saltFor($f['key'])]));
        if (!hash_equals($expected, $f['hash'])) {
            html('Error', '<p>Transaction failed due to incorrectly calculated hash parameter.</p>', 400);
        }
        state(function ($s) use ($f) { $s['payu'][$f['txnid']] = ['request' => $f, 'status' => 'pending']; return $s; });
        html('PayU · ₹' . $f['amount'], outcomes('/payu/pay', ['txnid' => $f['txnid']], ['tamper' => 'Return with a tampered amount']));
    }
    if ($p === '/pay' && $method === 'POST') {
        $t = state()['payu'][$_POST['txnid'] ?? ''] ?? html('Unknown transaction', '', 404);
        $f = $t['request'];
        $out = (string) $_POST['outcome'];
        $status = $out === 'failure' ? 'failure' : 'success';
        $mih = (string) random_int(10000000000, 99999999999);
        state(function ($s) use ($f, $status, $mih) { $s['payu'][$f['txnid']]['status'] = $status; $s['payu'][$f['txnid']]['mihpayid'] = $mih; return $s; });
        $r = ['mihpayid' => $mih, 'mode' => 'UPI', 'status' => $status, 'key' => $f['key'], 'txnid' => $f['txnid'], 'amount' => $f['amount'], 'productinfo' => $f['productinfo'],
            'firstname' => $f['firstname'], 'email' => $f['email'], 'phone' => $f['phone'], 'udf1' => '', 'udf2' => '', 'udf3' => '', 'udf4' => '', 'udf5' => '',
            'bank_ref_num' => (string) random_int(100000, 999999), 'error' => $status === 'success' ? 'E000' : 'E308', 'error_Message' => $status === 'success' ? 'No Error' : 'Transaction declined by bank'];
        $r['hash'] = hash('sha512', implode('|', [$saltFor($f['key']), $status, '', '', '', '', '', '', '', '', '', '', $f['email'], $f['firstname'], $f['productinfo'], $f['amount'], $f['txnid'], $f['key']]));
        if ($out === 'tamper') {
            $r['amount'] = '1.00';
        }
        autopost($status === 'success' ? $f['surl'] : $f['furl'], $r);
    }
    if ($p === '/merchant/postservice.php' && $method === 'POST') {
        parse_str($raw, $in);
        if (!hash_equals(hash('sha512', ($in['key'] ?? '') . '|' . ($in['command'] ?? '') . '|' . ($in['var1'] ?? '') . '|' . $saltFor((string) ($in['key'] ?? ''))), (string) ($in['hash'] ?? ''))) {
            json(['status' => 0, 'msg' => 'Invalid Hash.']);
        }
        $t = state()['payu'][$in['var1']] ?? null;
        if (!$t) {
            json(['status' => 0, 'msg' => '0 out of 1 Transactions Fetched Successfully', 'transaction_details' => [$in['var1'] => ['mihpayid' => 'Not Found', 'status' => 'Not Found']]]);
        }
        json(['status' => 1, 'msg' => '1 out of 1 Transactions Fetched Successfully', 'transaction_details' => [$in['var1'] => [
            'mihpayid' => $t['mihpayid'] ?? 'Not Found', 'txnid' => $in['var1'], 'amt' => $t['request']['amount'], 'transaction_amount' => $t['request']['amount'],
            'status' => $t['status'], 'mode' => 'UPI', 'error_Message' => $t['status'] === 'failure' ? 'Transaction declined by bank' : 'NO ERROR']]]);
    }
}

json(['error' => 'Not found in emulator: ' . $method . ' ' . $uri], 404);
