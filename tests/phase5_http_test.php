<?php
declare(strict_types=1);

/**
 * Phase 5 end-to-end test: online payments on a generated website, against the local gateway emulator.
 *
 *   php -S 127.0.0.1:8090 tools/payment-emulator.php      (and 'payment_emulator' => 'http://127.0.0.1:8090' in config/config.php, then regenerate)
 *   python3 tools/mailsink.py /tmp/mailsink                (SMTP on 127.0.0.1:2525)
 *   php tests/phase5_http_test.php <builder-url> <email> <password> <website-id> <emulator-url> <mail-dir> [site-db-dsn]
 */

require __DIR__ . '/support.php';

[$_, $base, $email, $password, $wid, $emu, $mailDir, $dsn] = $argv + [null, 'http://127.0.0.1:8080', 'info@channelsoftech.com', '', '2', 'http://127.0.0.1:8090', '/tmp/mailsink', ''];
$base = rtrim((string) $base, '/');
$emu = rtrim((string) $emu, '/');
$wid = (int) $wid;
$sfx = substr(bin2hex(random_bytes(3)), 0, 6);
$pass = $fail = 0;
$started = microtime(true);

function mails(string $dir, string $rcpt, float $since, string $contains, int $wait = 8): array
{
    for ($i = 0; $i < $wait * 4; $i++) {
        $out = [];
        foreach (glob(rtrim($dir, '/') . '/*.eml') ?: [] as $f) {
            $raw = (string) file_get_contents($f);
            if (filemtime($f) + 1 < (int) $since || stripos(strtok($raw, "\r\n"), $rcpt) === false) {
                continue;
            }
            $t = quoted_printable_decode($raw);
            if (str_contains($t, $contains)) {
                $out[] = $t;
            }
        }
        if ($out) {
            return $out;
        }
        usleep(250000);
    }
    return [];
}
/** First form on a page: [action, fields]. */
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

$b = new Client($base);
check('Builder login', ($b->login($email, $password)['json']['ok'] ?? false) === true);
preg_match('/font-monospace" value="([A-Za-z0-9]+)"/', $b->req('GET', 'settings', [], false)['body'], $m);
$qa = 'qa:' . ($m[1] ?? '');
$owner = "payowner$sfx@example.com";
$r = $b->req('POST', "websites/$wid/admin-account", ['email' => $owner, 'name' => 'Pay Owner']);
$ownerPw = (string) ($r['json']['password'] ?? '');
$adminUrl = rtrim((string) ($r['json']['url'] ?? ''), '/');
$siteUrl = substr($adminUrl, 0, -strlen('admin'));
$host = (string) preg_replace('#^(https?://[^/]+).*$#', '$1', $siteUrl);
if ($dsn !== '') {
    [$d, $du, $dp] = explode('|', $dsn) + ['', '', ''];
    $pdo = new PDO($d, $du, $dp);
    $prefix = (string) $pdo->query('SELECT table_prefix FROM wb_websites WHERE id = ' . $wid)->fetchColumn();
    $pdo->exec("DELETE FROM {$prefix}login_attempts WHERE ip IN ('127.0.0.1', '::1')");
    $pdo->exec("UPDATE {$prefix}payment_transactions SET created_at = created_at - INTERVAL 15 MINUTE WHERE ip IN ('127.0.0.1', '::1') AND created_at > NOW() - INTERVAL 15 MINUTE");
}
$o = new Client($adminUrl);
$o->basic = $qa;
check('Website owner signs in', ($o->login($owner, $ownerPw)['json']['ok'] ?? false) === true);
$o->req('POST', 'settings/mail', ['notify_to' => "shop$sfx@example.com", 'smtp_enabled' => '1', 'smtp_host' => '127.0.0.1', 'smtp_port' => '2525', 'smtp_encryption' => 'none']);

$pub = new Client($siteUrl);
$pub->basic = $qa;
$em = new Client($emu);

echo "\n== Payments switched off ==\n";
$o->req('POST', 'payments/settings', ['enabled' => '0', 'gateway' => '', 'mode' => 'test', 'page' => ['title' => 'Pay online', 'custom_purpose' => '1', 'min_amount' => '1', 'max_amount' => '500000']]);
check('/pay is 404 while payments are off', $pub->req('GET', 'pay', [], false)['code'] === 404);
check('"pay" is a reserved page address', isset($o->req('POST', 'api/pages', ['title' => 'Pay', 'slug' => 'pay', 'page_type' => 'generic'])['json']['errors']['slug']));

$keys = [
    'razorpay' => ['key_id' => "rzp_test_$sfx", 'key_secret' => "rzpsecret$sfx", 'webhook_secret' => "rzpwh$sfx"],
    'stripe' => ['secret_key' => "sk_test_$sfx", 'webhook_secret' => "whsec_$sfx"],
    'cashfree' => ['app_id' => "TEST$sfx", 'secret_key' => "cfsecret$sfx"],
    'phonepe' => ['client_id' => "PPCLIENT$sfx", 'client_version' => '1', 'client_secret' => "ppsecret$sfx", 'webhook_username' => "ppuser$sfx", 'webhook_password' => "pppass$sfx"],
    'payu' => ['merchant_key' => "PK$sfx", 'merchant_salt' => "salt-PK$sfx"],
];
$page = ['title' => 'Pay online', 'intro' => "Pay your invoice $sfx", 'purposes' => "Consultation fee | 500\nAdvance for booking | 2,000\nInvoice payment", 'custom_purpose' => '1',
    'min_amount' => '10', 'max_amount' => '100000', 'success_message' => "Thanks $sfx, payment received."];
function useGateway(Client $o, string $gw, array $keys, array $page, string $mode = 'test'): array
{
    return $o->req('POST', 'payments/settings', ['enabled' => '1', 'gateway' => $gw, 'mode' => $mode, 'keys' => [$mode => [$gw => $keys[$gw]]], 'page' => $page]);
}

echo "\n== Settings ==\n";
$r = $o->req('POST', 'payments/settings', ['enabled' => '1', 'gateway' => 'razorpay', 'mode' => 'test', 'keys' => ['test' => ['razorpay' => ['key_id' => '', 'key_secret' => '']]], 'page' => $page]);
check('Enabling without keys is refused (Key ID required)', isset($r['json']['errors']['keys[test][razorpay][key_id]']), $r['body']);
$r = $o->req('POST', 'payments/settings', ['enabled' => '1', 'gateway' => 'razorpay', 'mode' => 'test', 'keys' => ['test' => ['razorpay' => ['key_id' => "rzp_live_$sfx", 'key_secret' => 'x']]], 'page' => $page]);
check('A live Key ID in test mode is refused', isset($r['json']['errors']['keys[test][razorpay][key_id]']));
$r = useGateway($o, 'razorpay', $keys, $page);
check('Save Razorpay test keys and page options', ($r['json']['ok'] ?? false) === true, $r['body']);
$sp = $o->req('GET', 'payments/settings', [], false)['body'];
check('Secrets never shown back (key secret, webhook secret)', !str_contains($sp, $keys['razorpay']['key_secret']) && !str_contains($sp, $keys['razorpay']['webhook_secret']) && str_contains($sp, "rzp_test_$sfx") && str_contains($sp, 'Saved — leave empty to keep'));
check('Webhook addresses shown for every gateway', substr_count($sp, '/pay/webhook/') >= 5);
$r = $o->req('POST', 'payments/settings', ['enabled' => '1', 'gateway' => 'razorpay', 'mode' => 'test', 'keys' => ['test' => ['razorpay' => ['key_id' => "rzp_test_$sfx", 'key_secret' => '', 'webhook_secret' => '']]], 'page' => $page]);
check('Saving with empty secret fields keeps the saved secrets', ($r['json']['ok'] ?? false) && ($o->req('POST', 'payments/test', ['gateway' => 'razorpay', 'mode' => 'test'])['json']['ok'] ?? false) === true);
foreach ($keys as $gw => $k) {
    useGateway($o, $gw, $keys, $page);
    $t = $o->req('POST', 'payments/test', ['gateway' => $gw, 'mode' => 'test']);
    check("Test connection: $gw", ($t['json']['ok'] ?? false) === true, $t['body']);
}
$bad = $keys;
$bad['razorpay']['key_secret'] = 'wrong';
useGateway($o, 'razorpay', $bad, $page);
$t = $o->req('POST', 'payments/test', ['gateway' => 'razorpay', 'mode' => 'test']);
check('Test connection reports wrong credentials', ($t['json']['ok'] ?? true) === false && str_contains((string) ($t['json']['message'] ?? ''), 'refused the credentials'), $t['body']);
$r = $o->req('POST', 'payments/settings', ['enabled' => '1', 'gateway' => 'razorpay', 'mode' => 'live', 'keys' => ['live' => ['razorpay' => ['key_id' => "rzp_live_$sfx", 'key_secret' => "live$sfx"]]], 'page' => $page]);
$t = $o->req('POST', 'payments/test', ['gateway' => 'razorpay', 'mode' => 'live']);
check('Live mode never uses the emulator (goes to the real Razorpay host)', ($r['json']['ok'] ?? false) && !str_contains($t['body'], 'Authentication failed') && ($t['json']['ok'] ?? true) === false, $t['body']);
useGateway($o, 'razorpay', $keys, $page);

echo "\n== Pay online page ==\n";
$r = $pub->req('GET', 'pay', [], false);
check('Pay page renders inside the website template', $r['code'] === 200 && str_contains($r['body'], "Pay your invoice $sfx") && str_contains($r['body'], '<header') && str_contains($r['body'], 'Test mode'));
check('Options with fixed prices listed', str_contains($r['body'], 'Consultation fee — ₹500.00') && str_contains($r['body'], 'Advance for booking — ₹2,000.00'));
check('Pay page sends no-store and uses only local assets', str_contains(implode(',', $r['headers']['cache-control'] ?? []), 'no-store') && str_contains($r['body'], '/assets/pay/pay.css'));
preg_match('/name="_t" value="([^"]+)"/', $r['body'], $tm);
$token = $tm[1] ?? '';
sleep(2);
$r = $pub->req('POST', 'pay', ['_t' => $token, 'purpose_option' => 'other', 'purpose' => '', 'amount' => '5', 'name' => '', 'email' => 'bad', 'phone' => '12'], false);
check('Validation: purpose, amount range, name, email, mobile', $r['code'] === 422 && substr_count($r['body'], 'wbpay-error') === 5, (string) substr_count($r['body'], 'wbpay-error'));
check('Forged form token refused', str_contains($pub->req('POST', 'pay', ['_t' => '1700000000.' . str_repeat('a', 64), 'purpose' => 'x', 'amount' => '100', 'name' => 'A', 'email' => 'a@b.co', 'phone' => '9847012345'], false)['body'], 'form expired'));

/** Fill the form, pay at the (emulated) gateway with $outcome, follow the customer back. Returns [reference, receipt html, last response]. */
function pay(Client $pub, Client $em, string $gw, string $outcome, array $fields, string $host): array
{
    $r = $pub->req('GET', 'pay', [], false);
    preg_match('/name="_t" value="([^"]+)"/', $r['body'], $tm);
    sleep(2);
    $r = $pub->req('POST', 'pay', ['_t' => $tm[1] ?? ''] + $fields, false);
    $back = null;
    if ($gw === 'razorpay') {
        $opt = optionsOf($r['body']);
        $g = $em->req('POST', 'razorpay/pay', ['order_id' => $opt['order_id'] ?? '', 'callback_url' => $opt['callback_url'] ?? '', 'outcome' => $outcome], false);
        if ($outcome === 'no_callback') {
            return [$opt, '', $g];
        }
        [$action, $f] = formOf($g['body']);
        $back = $pub->req('POST', $action, $f, false);
    } elseif ($gw === 'cashfree') {
        $opt = optionsOf($r['body']);
        $g = $em->req('POST', 'cashfree/pay', ['session' => $opt['paymentSessionId'] ?? '', 'outcome' => $outcome], false);
        $back = $pub->req('GET', $g['location'], [], false);
    } elseif ($gw === 'stripe' || $gw === 'phonepe') {
        $g = $em->req('POST', $r['location'], ['outcome' => $outcome], false);
        $back = $pub->req('GET', $g['location'], [], false);
    } elseif ($gw === 'payu') {
        [$action, $f] = formOf($r['body']);
        $em->req('POST', $action, $f, false);
        $g = $em->req('POST', 'payu/pay', ['txnid' => $f['txnid'] ?? '', 'outcome' => $outcome], false);
        [$action2, $f2] = formOf($g['body']);
        $back = $pub->req('POST', $action2, $f2, false);
    }
    $loc = (string) ($back['location'] ?? '');
    preg_match('#/pay/receipt/([A-Z0-9]+)#', $loc, $rm);
    $receipt = $loc !== '' ? $pub->req('GET', $loc, [], false) : ['body' => '', 'code' => 0];
    return [$rm[1] ?? '', $receipt['body'], $back, $r, $loc];
}
function txByRef(Client $o, string $ref): ?array
{
    foreach ($o->req('GET', 'api/payments', ['q' => $ref, 'status' => ''])['json']['rows'] ?? [] as $row) {
        if ($row['reference'] === $ref) {
            return $row;
        }
    }
    return null;
}

/** The pay form allows 20 attempts per IP per 10 minutes: age this test's earlier attempts out between sections. */
function ageOut(): void
{
    global $pdo, $prefix;
    if (isset($pdo, $prefix)) {
        $pdo->exec("UPDATE {$prefix}payment_transactions SET created_at = created_at - INTERVAL 15 MINUTE WHERE ip IN ('127.0.0.1', '::1') AND created_at > NOW() - INTERVAL 15 MINUTE");
    }
}
$customer = fn (string $n) => ['name' => "Anil $n", 'email' => "anil.$n.$GLOBALS[sfx]@example.com", 'phone' => '+91 98470 12345', 'note' => 'Invoice INV-' . $n];

echo "\n== Razorpay ==\n";
useGateway($o, 'razorpay', $keys, $page);
[$ref, $receipt, $back, $start] = pay($pub, $em, 'razorpay', 'success', ['purpose_option' => '0', 'amount' => '1'] + $customer('rzp'), $host);
check('Checkout page opens Razorpay with the order (key, amount from the option, callback)', str_contains($start['body'], '/razorpay/checkout.js') && (optionsOf($start['body'])['amount'] ?? 0) === 50000 && str_contains(optionsOf($start['body'])['callback_url'] ?? '', '/pay/return/'));
check('Customer returns → 303 to the receipt', ($back['code'] ?? 0) === 303 && $ref !== '');
check('Receipt: payment successful with the custom message', str_contains($receipt, 'Payment successful') && str_contains($receipt, "Thanks $sfx, payment received.") && str_contains($receipt, '₹500.00'));
$tx = txByRef($o, $ref);
check('Transaction paid, ₹500 (fixed option price, not the posted ₹1), payment ID and method stored', $tx && $tx['status'] === 'paid' && (int) $tx['amount'] === 50000 && str_starts_with((string) $tx['gateway_payment_id'], 'pay_') && $tx['method'] === 'upi', json_encode($tx));
check('Receipt emailed to the customer', count(mails($mailDir, "anil.rzp.$sfx@example.com", $started, $ref)) === 1);
check('Owner notified of the payment', count(mails($mailDir, "shop$sfx@example.com", $started, $ref)) === 1);
$det = $o->req('GET', 'api/payments/' . ($tx['id'] ?? 0))['json'];
check('Payment details with history (start → return)', ($det['payment']['reference'] ?? '') === $ref && count($det['events'] ?? []) >= 2 && !isset($det['payment']['ip']));
$replay = $pub->req('POST', $host . str_replace('/receipt/', '/return/', (string) parse_url((string) $back['location'], PHP_URL_PATH)), [], false);
check('Replaying the return does not change or re-email a paid payment', txByRef($o, $ref)['status'] === 'paid' && count(mails($mailDir, "anil.rzp.$sfx@example.com", $started, $ref, 2)) === 1);
check('Receipt needs its token (no guessing other receipts)', $pub->req('GET', 'pay/receipt/' . $ref, ['t' => str_repeat('0', 32)], false)['code'] === 404);

[$ref2, $receipt2] = pay($pub, $em, 'razorpay', 'failure', ['purpose_option' => 'other', 'purpose' => "Custom $sfx", 'amount' => '1,250.50'] + $customer('rzpf'), $host);
check('Failed payment: receipt says not completed, with Try again', str_contains($receipt2, 'Payment not completed') && str_contains($receipt2, 'Try again') && txByRef($o, $ref2)['status'] === 'failed');
check('Free amount stored exactly (₹1,250.50)', (int) (txByRef($o, $ref2)['amount'] ?? 0) === 125050);

[$ref3] = pay($pub, $em, 'razorpay', 'authorized', ['purpose_option' => '2', 'purpose' => '', 'amount' => '300'] + $customer('rzpa'), $host);
check('Manual-capture account: authorized payment is captured automatically → paid', (txByRef($o, $ref3)['status'] ?? '') === 'paid');

[$ref4] = pay($pub, $em, 'razorpay', 'wrong_amount', ['purpose_option' => '2', 'amount' => '700'] + $customer('rzpw'), $host);
$t4 = txByRef($o, $ref4);
check('Amount tampering detected (gateway paid ₹1 for ₹700) → not marked paid', $t4 && $t4['status'] === 'failed' && str_contains((string) $t4['message'], 'Amount mismatch'), json_encode($t4));

// Forged return: wrong signature for a pending order
$r = $pub->req('GET', 'pay', [], false);
preg_match('/name="_t" value="([^"]+)"/', $r['body'], $tm);
sleep(2);
$r = $pub->req('POST', 'pay', ['_t' => $tm[1], 'purpose_option' => '2', 'amount' => '999'] + $customer('rzpx'), false);
$opt = optionsOf($r['body']);
preg_match('#/pay/return/([A-Z0-9]+)#', $opt['callback_url'] ?? '', $cm);
$forged = $pub->req('POST', $opt['callback_url'] ?? '', ['razorpay_payment_id' => 'pay_FAKE', 'razorpay_order_id' => $opt['order_id'] ?? '', 'razorpay_signature' => str_repeat('a', 64)], false);
check('Forged signature is ignored (payment stays pending, logged as unverified)', (txByRef($o, $cm[1] ?? '')['status'] ?? '') === 'pending');
$c = $pub->req('GET', str_replace('/return/', '/cancel/', $opt['callback_url'] ?? ''), [], false);
check('Closing the gateway window → cancelled', (txByRef($o, $cm[1] ?? '')['status'] ?? '') === 'cancelled' && str_contains($pub->req('GET', $c['location'], [], false)['body'], 'Payment cancelled'));

// Webhook: paid at the gateway but the customer never came back
[$optNc] = pay($pub, $em, 'razorpay', 'no_callback', ['purpose_option' => '1', 'amount' => '1'] + $customer('rzpw2'), $host);
preg_match('#/pay/return/([A-Z0-9]+)#', $optNc['callback_url'] ?? '', $nm);
$refNc = $nm[1] ?? '';
check('Customer closed the browser: still pending', (txByRef($o, $refNc)['status'] ?? '') === 'pending');
$body = json_encode(['entity' => 'event', 'event' => 'payment.captured', 'payload' => ['payment' => ['entity' => ['id' => 'pay_x', 'order_id' => $optNc['order_id'] ?? '', 'amount' => 200000, 'status' => 'captured']]]]);
$wh = function (string $gw, string $body, array $headers) use ($siteUrl): int {
    $ch = curl_init($siteUrl . 'pay/webhook/' . $gw);
    $h = [];
    foreach ($headers as $k => $v) {
        $h[] = "$k: $v";
    }
    // No QA password: gateways cannot send one, so webhook addresses must be reachable without it.
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $h, CURLOPT_RETURNTRANSFER => true]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    return $code;
};
check('Webhook with a wrong signature rejected (401)', $wh('razorpay', $body, ['Content-Type' => 'application/json', 'X-Razorpay-Signature' => hash_hmac('sha256', $body, 'nope')]) === 401);
check('Webhook with a valid signature → confirmed with the API → paid', $wh('razorpay', $body, ['Content-Type' => 'application/json', 'X-Razorpay-Signature' => hash_hmac('sha256', $body, $keys['razorpay']['webhook_secret'])]) === 200
    && (txByRef($o, $refNc)['status'] ?? '') === 'paid');
check('Webhook-confirmed payment also emails the receipt', count(mails($mailDir, "anil.rzpw2.$sfx@example.com", $started, $refNc)) === 1);
check('Repeated webhook is harmless (one receipt only)', $wh('razorpay', $body, ['Content-Type' => 'application/json', 'X-Razorpay-Signature' => hash_hmac('sha256', $body, $keys['razorpay']['webhook_secret'])]) === 200
    && count(mails($mailDir, "anil.rzpw2.$sfx@example.com", $started, $refNc, 2)) === 1);

echo "\n== Stripe ==\n";
ageOut();
useGateway($o, 'stripe', $keys, $page);
[$ref, $receipt, $back, $start] = pay($pub, $em, 'stripe', 'success', ['purpose_option' => '1', 'amount' => ''] + $customer('str'), $host);
check('Redirected to Stripe Checkout', ($start['code'] ?? 0) === 303 && str_contains((string) $start['location'], '/stripe/pay/cs_test_'));
check('Paid via Stripe (₹2,000 option), payment intent stored', str_contains($receipt, 'Payment successful') && ($t = txByRef($o, $ref)) && $t['status'] === 'paid' && (int) $t['amount'] === 200000 && str_starts_with((string) $t['gateway_payment_id'], 'pi_'), json_encode(txByRef($o, $ref)));
[$ref, $receipt] = pay($pub, $em, 'stripe', 'cancel', ['purpose_option' => '0'] + $customer('strc'), $host);
check('Stripe cancel → cancelled', str_contains($receipt, 'Payment cancelled') && txByRef($o, $ref)['status'] === 'cancelled');
$state = json_decode($em->req('GET', '_emu/state')['body'], true);
$sess = null;
foreach ($state['stripe'] ?? [] as $s) {
    if (($s['payment_status'] ?? '') === 'paid') {
        $sess = $s;
    }
}
$evt = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => ['object' => 'checkout.session', 'id' => $sess['id'] ?? '']]]);
$ts = time();
check('Stripe webhook: valid signature accepted', $wh('stripe', $evt, ['Stripe-Signature' => "t=$ts,v1=" . hash_hmac('sha256', "$ts.$evt", $keys['stripe']['webhook_secret'])]) === 200);
check('Stripe webhook: old timestamp rejected (replay protection)', $wh('stripe', $evt, ['Stripe-Signature' => 't=' . ($ts - 3600) . ',v1=' . hash_hmac('sha256', ($ts - 3600) . ".$evt", $keys['stripe']['webhook_secret'])]) === 401);

echo "\n== Cashfree ==\n";
ageOut();
useGateway($o, 'cashfree', $keys, $page);
[$ref, $receipt, $back, $start] = pay($pub, $em, 'cashfree', 'success', ['purpose_option' => 'other', 'purpose' => "Fees $sfx", 'amount' => '1500'] + $customer('cf'), $host);
check('Cashfree checkout script opened with a payment session (sandbox)', str_contains($start['body'], '/cashfree/sdk.js') && (optionsOf($start['body'])['mode'] ?? '') === 'sandbox' && str_starts_with((string) (optionsOf($start['body'])['paymentSessionId'] ?? ''), 'session_'));
check('Mobile sent to Cashfree as 10 digits (+91 removed); paid', str_contains($receipt, 'Payment successful') && (txByRef($o, $ref)['status'] ?? '') === 'paid' && (txByRef($o, $ref)['method'] ?? '') === 'upi');
[$ref, $receipt] = pay($pub, $em, 'cashfree', 'drop', ['purpose_option' => '0'] + $customer('cfd'), $host);
check('Cashfree: customer dropped → not completed', str_contains($receipt, 'Payment not completed') && txByRef($o, $ref)['status'] === 'failed');
$cfBody = json_encode(['data' => ['order' => ['order_id' => $ref], 'payment' => ['payment_status' => 'FAILED']], 'type' => 'PAYMENT_FAILED_WEBHOOK']);
$ts = (string) (time() * 1000);
check('Cashfree webhook: signature verified', $wh('cashfree', $cfBody, ['x-webhook-timestamp' => $ts, 'x-webhook-signature' => base64_encode(hash_hmac('sha256', $ts . $cfBody, $keys['cashfree']['secret_key'], true))]) === 200
    && $wh('cashfree', $cfBody, ['x-webhook-timestamp' => $ts, 'x-webhook-signature' => 'forged']) === 401);

echo "\n== PhonePe ==\n";
ageOut();
useGateway($o, 'phonepe', $keys, $page);
[$ref, $receipt, $back, $start] = pay($pub, $em, 'phonepe', 'success', ['purpose_option' => '0'] + $customer('pp'), $host);
check('Redirected to PhonePe (OAuth token + v2 pay)', ($start['code'] ?? 0) === 303 && str_contains((string) $start['location'], '/phonepe/pay/'));
check('Paid via PhonePe', str_contains($receipt, 'Payment successful') && (txByRef($o, $ref)['status'] ?? '') === 'paid' && (txByRef($o, $ref)['method'] ?? '') === 'UPI_INTENT');
[$ref, $receipt] = pay($pub, $em, 'phonepe', 'pending', ['purpose_option' => '0'] + $customer('ppp'), $host);
check('UPI approval pending: receipt says “Confirming”, refreshes itself', str_contains($receipt, 'Confirming your payment') && str_contains($receipt, 'http-equiv="refresh"') && txByRef($o, $ref)['status'] === 'pending');
$em->req('POST', '_emu/set', ['gateway' => 'phonepe', 'id' => $ref, 'field' => 'state', 'value' => 'COMPLETED']);
$ppBody = json_encode(['event' => 'checkout.order.completed', 'payload' => ['merchantOrderId' => $ref, 'state' => 'COMPLETED', 'amount' => 50000]]);
check('PhonePe webhook with wrong authorization rejected', $wh('phonepe', $ppBody, ['Authorization' => hash('sha256', 'x:y')]) === 401);
check('PhonePe webhook (SHA256 of username:password) → paid', $wh('phonepe', $ppBody, ['Authorization' => hash('sha256', $keys['phonepe']['webhook_username'] . ':' . $keys['phonepe']['webhook_password'])]) === 200
    && txByRef($o, $ref)['status'] === 'paid');
check('Admin “Re-check with gateway” on a paid payment', str_contains((string) ($o->req('POST', 'api/payments/' . txByRef($o, $ref)['id'] . '/recheck')['json']['message'] ?? ''), 'already confirmed'));

echo "\n== PayU ==\n";
ageOut();
useGateway($o, 'payu', $keys, $page);
[$ref, $receipt, $back, $start] = pay($pub, $em, 'payu', 'success', ['purpose_option' => '0'] + $customer('pu'), $host);
[$act, $fields] = formOf($start['body']);
check('PayU form posts to PayU with a valid hash (emulator accepted it)', str_ends_with($act, '/payu/_payment') && strlen($fields['hash'] ?? '') === 128 && ($fields['amount'] ?? '') === '500.00' && ($fields['phone'] ?? '') === '9847012345');
check('Paid via PayU (reverse hash + verify_payment)', str_contains($receipt, 'Payment successful') && (txByRef($o, $ref)['status'] ?? '') === 'paid');
[$ref, $receipt] = pay($pub, $em, 'payu', 'tamper', ['purpose_option' => '0'] + $customer('put'), $host);
check('PayU response with a tampered amount fails the hash check (ignored); verify API still decides', in_array(txByRef($o, $ref)['status'] ?? '', ['paid', 'pending'], true));
[$ref, $receipt] = pay($pub, $em, 'payu', 'failure', ['purpose_option' => '0'] + $customer('puf'), $host);
check('PayU failure', str_contains($receipt, 'Payment not completed') && txByRef($o, $ref)['status'] === 'failed');

echo "\n== Admin ==\n";
$list = $o->req('GET', 'api/payments', ['status' => 'paid', 'q' => $sfx])['json'];
check('Transactions list: filter paid, total shown', ($list['total'] ?? 0) >= 7 && str_starts_with((string) ($list['paid_total_text'] ?? ''), '₹'), json_encode(['t' => $list['total'] ?? null]));
check('Search by customer email', count($o->req('GET', 'api/payments', ['q' => "anil.cf.$sfx"])['json']['rows'] ?? []) === 1);
$csv = $o->req('GET', 'api/payments/export', ['q' => $sfx], false);
check('CSV export', $csv['code'] === 200 && str_starts_with($csv['body'], "\xEF\xBB\xBF") && str_contains($csv['body'], 'Reference,Created') && substr_count($csv['body'], "anil.") >= 10);
check('Payments page and dashboard tile', str_contains($o->req('GET', 'payments', [], false)['body'], 'TEST MODE') && str_contains($o->req('GET', '', [], false)['body'], 'test mode'));
$o->req('POST', 'api/users', ['name' => 'Ed', 'email' => "ped$sfx@example.com", 'role' => 'editor', 'status' => 'active', 'password' => "Editor-$sfx-pass1"]);
$e = new Client($adminUrl);
$e->basic = $qa;
$e->login("ped$sfx@example.com", "Editor-$sfx-pass1");
check('Editor can see payments but not payment settings', $e->req('GET', 'api/payments')['code'] === 200 && $e->req('GET', 'payments/settings')['code'] === 403
    && $e->req('POST', 'payments/settings', ['enabled' => '0'])['code'] === 403 && $e->req('POST', 'payments/test', ['gateway' => 'payu'])['code'] === 403);
check('“Pay online” can be added to menus (page:pay)', ($o->req('POST', 'api/navigation', ['location' => 'footer', 'items' => json_encode([['url' => 'page:pay', 'label' => "Pay now $sfx"]])])['json']['ok'] ?? false)
    && preg_match('#href="[^"]*/pay"[^>]*>Pay now ' . $sfx . '#', $pub->req('GET', '', [], false)['body']) === 1);
$o->req('POST', 'api/navigation', ['location' => 'footer', 'items' => '[]']);
check('Builder wizard reads the same gateway list', str_contains($b->req('GET', "websites/$wid", [], false)['body'], 'Client ID, Client version, Client secret'));

// Clean up: payments off, mail back to default, remove test accounts.
$o->req('POST', 'payments/settings', ['enabled' => '0', 'gateway' => 'payu', 'mode' => 'test', 'page' => $page]);
$o->req('POST', 'settings/mail', ['notify_to' => '', 'smtp_enabled' => '0']);
if (isset($pdo, $prefix)) {
    $pdo->prepare("DELETE FROM {$prefix}admin_users WHERE email IN (?, ?)")->execute([$owner, "ped$sfx@example.com"]);
}

echo "\nPassed: $pass   Failed: $fail\n";
exit($fail ? 1 : 0);
