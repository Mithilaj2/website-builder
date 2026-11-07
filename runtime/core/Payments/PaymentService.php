<?php
declare(strict_types=1);

namespace SiteRuntime\Payments;

use SiteRuntime\Crypto;
use SiteRuntime\Db;
use SiteRuntime\ValidationError;

/**
 * Online payments for one website: settings (keys encrypted with the website key), transactions,
 * and the rules every gateway result goes through (amount check, idempotent status changes).
 */
final class PaymentService
{
    public const STATUSES = ['created', 'pending', 'paid', 'failed', 'cancelled'];
    private const START_LIMIT = 20;      // payment attempts per IP …
    private const START_WINDOW = 600;    // … per 10 minutes

    private ?array $settings = null;

    /**
     * @param array $seed       config/payment.php written by the builder (enabled, gateway, mode) — used until the owner saves settings
     * @param ?string $emulator local gateway imitation for automated tests (test mode only; never used in live mode)
     */
    public function __construct(private Db $db, private Crypto $crypto, private array $seed = [], private ?string $emulator = null, private ?Http $http = null)
    {
    }

    // ------------------------------------------------------------------ settings

    public static function defaults(): array
    {
        return [
            'enabled' => false, 'gateway' => '', 'mode' => 'test', 'keys' => ['test' => [], 'live' => []],
            'page' => [
                'title' => 'Pay online',
                'intro' => 'Pay securely online. You will receive a receipt by email.',
                'purposes' => [],
                'custom_purpose' => true,
                'min_amount' => 1,
                'max_amount' => 500000,
                'success_message' => 'Thank you! Your payment was successful.',
            ],
        ];
    }

    public function settings(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }
        $raw = $this->db->value('SELECT setting_value FROM ' . $this->db->t('settings') . ' WHERE setting_key = ?', ['payments']);
        $saved = is_string($raw) ? (json_decode($raw, true) ?: []) : [];
        $d = self::defaults();
        if (!$saved) {
            $d['enabled'] = (bool) ($this->seed['enabled'] ?? false);
            $d['gateway'] = (string) ($this->seed['gateway'] ?? '');
            $d['mode'] = ($this->seed['mode'] ?? 'test') === 'live' ? 'live' : 'test';
        }
        $s = array_replace($d, $saved);
        $s['page'] = array_replace($d['page'], (array) ($saved['page'] ?? []));
        $s['keys'] = array_replace($d['keys'], (array) ($saved['keys'] ?? []));
        if (!isset(Gateways::ALL[$s['gateway']])) {
            $s['gateway'] = '';
        }
        return $this->settings = $s;
    }

    /** Settings for forms: secrets replaced by a "saved" marker; never send secrets to the browser. */
    public function publicSettings(): array
    {
        $s = $this->settings();
        foreach (['test', 'live'] as $mode) {
            foreach (Gateways::definitions() as $gid => $def) {
                foreach ($def['fields'] as $f) {
                    $v = $s['keys'][$mode][$gid][$f['key']] ?? '';
                    $s['keys'][$mode][$gid][$f['key']] = $f['secret'] ? ($v !== '' ? '__saved__' : '') : (string) $v;
                }
            }
        }
        return $s;
    }

    /** Save from the admin form. Blank secret fields keep the saved value. */
    public function saveSettings(array $in): void
    {
        $cur = $this->settings();
        $errors = [];
        $gateway = (string) ($in['gateway'] ?? '');
        $enabled = ($in['enabled'] ?? '') === '1';
        $mode = ($in['mode'] ?? 'test') === 'live' ? 'live' : 'test';
        if ($enabled && !isset(Gateways::ALL[$gateway])) {
            $errors['gateway'] = 'Choose a payment gateway.';
        }
        $keys = $cur['keys'];
        $postedKeys = is_array($in['keys'] ?? null) ? $in['keys'] : [];
        foreach (['test', 'live'] as $m) {
            foreach (Gateways::definitions() as $gid => $def) {
                foreach ($def['fields'] as $f) {
                    $posted = $postedKeys[$m][$gid][$f['key']] ?? null;
                    if (!is_string($posted)) {
                        continue;
                    }
                    $posted = trim($posted);
                    if ($f['secret']) {
                        if ($posted === '__clear__') {
                            $keys[$m][$gid][$f['key']] = '';
                        } elseif ($posted !== '' && $posted !== '__saved__') {
                            $keys[$m][$gid][$f['key']] = $this->crypto->encrypt(mb_substr($posted, 0, 500));
                        }
                    } else {
                        $keys[$m][$gid][$f['key']] = mb_substr($posted, 0, 200);
                    }
                }
            }
        }
        if ($enabled && !$errors) {
            foreach (Gateways::ALL[$gateway]::fields() as $f) {
                if ($f['required'] && trim((string) ($keys[$mode][$gateway][$f['key']] ?? '')) === '') {
                    $errors["keys[$mode][$gateway][{$f['key']}]"] = $f['label'] . ' is required to accept payments in ' . $mode . ' mode.';
                }
            }
            if ($gateway === 'razorpay' && ($k = (string) ($keys[$mode]['razorpay']['key_id'] ?? '')) !== '' && !str_starts_with($k, 'rzp_' . $mode . '_')) {
                $errors["keys[$mode][razorpay][key_id]"] = 'A ' . $mode . ' Key ID starts with rzp_' . $mode . '_.';
            }
            if ($gateway === 'stripe' && ($k = $this->crypto->decrypt((string) ($keys[$mode]['stripe']['secret_key'] ?? '')) ?? '') !== '' && !preg_match('/^(sk|rk)_' . $mode . '_/', $k)) {
                $errors["keys[$mode][stripe][secret_key]"] = 'A ' . $mode . ' secret key starts with sk_' . $mode . '_ (or rk_' . $mode . '_).';
            }
        }

        $p = (array) ($in['page'] ?? []);
        $min = (int) ($p['min_amount'] ?? 1);
        $max = (int) ($p['max_amount'] ?? 500000);
        if ($min < 1) {
            $errors['page[min_amount]'] = 'The minimum is at least ₹1.';
        }
        if ($max < $min || $max > 10000000) {
            $errors['page[max_amount]'] = 'The maximum must be between the minimum and ₹1,00,00,000.';
        }
        $purposes = [];
        foreach (preg_split('/\R/', (string) ($p['purposes'] ?? '')) ?: [] as $line) {
            $line = trim(strip_tags($line));
            if ($line === '') {
                continue;
            }
            if (!preg_match('/^(.{1,120}?)(?:\s*[|=]\s*₹?\s*([\d,]+(?:\.\d{1,2})?))?$/u', $line, $m)) {
                $errors['page[purposes]'] = 'Could not read “' . mb_substr($line, 0, 40) . '”.';
                continue;
            }
            $amount = isset($m[2]) && $m[2] !== '' ? (int) round((float) str_replace(',', '', $m[2]) * 100) : 0;
            $purposes[] = ['label' => trim($m[1]), 'amount' => $amount];
        }
        if (count($purposes) > 30) {
            $errors['page[purposes]'] = 'Up to 30 options.';
        }
        $custom = ($p['custom_purpose'] ?? '') === '1';
        if (!$purposes && !$custom) {
            $errors['page[purposes]'] = 'Add at least one option, or allow visitors to type what the payment is for.';
        }
        $title = trim(strip_tags((string) ($p['title'] ?? '')));
        if ($title === '' || mb_strlen($title) > 80) {
            $errors['page[title]'] = 'Enter a page title (up to 80 characters).';
        }
        if ($errors) {
            throw new ValidationError('Please correct the highlighted fields.', $errors);
        }
        $value = [
            'enabled' => $enabled, 'gateway' => $gateway, 'mode' => $mode, 'keys' => $keys,
            'page' => [
                'title' => $title,
                'intro' => mb_substr(trim(strip_tags((string) ($p['intro'] ?? ''))), 0, 600),
                'purposes' => $purposes,
                'custom_purpose' => $custom,
                'min_amount' => $min,
                'max_amount' => $max,
                'success_message' => mb_substr(trim(strip_tags((string) ($p['success_message'] ?? ''))), 0, 300) ?: self::defaults()['page']['success_message'],
            ],
        ];
        $this->db->query('REPLACE INTO ' . $this->db->t('settings') . ' (setting_key, setting_value, updated_at) VALUES (?, ?, ?)',
            ['payments', json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), date('Y-m-d H:i:s')]);
        $this->settings = null;
    }

    /** Whether the public "Pay online" page accepts payments right now. */
    public function ready(): bool
    {
        $s = $this->settings();
        if (!$s['enabled'] || $s['gateway'] === '') {
            return false;
        }
        foreach (Gateways::ALL[$s['gateway']]::fields() as $f) {
            if ($f['required'] && trim((string) ($s['keys'][$s['mode']][$s['gateway']][$f['key']] ?? '')) === '') {
                return false;
            }
        }
        return true;
    }

    /** Gateway instance with decrypted credentials (current settings, or the gateway/mode a transaction used). */
    public function gateway(?string $id = null, ?string $mode = null): Gateway
    {
        $s = $this->settings();
        $id ??= $s['gateway'];
        $mode ??= $s['mode'];
        $creds = [];
        $defs = Gateways::definitions()[$id]['fields'] ?? [];
        foreach ($defs as $f) {
            $v = (string) ($s['keys'][$mode][$id][$f['key']] ?? '');
            $creds[$f['key']] = $f['secret'] ? (string) ($this->crypto->decrypt($v) ?? '') : $v;
        }
        return Gateways::make($id, $creds, $mode, $this->http, $this->emulator);
    }

    // ------------------------------------------------------------------ transactions

    /** Validate the public form and create a transaction (status "created"). */
    public function create(array $in, string $ip): array
    {
        $s = $this->settings();
        $page = $s['page'];
        $errors = [];
        $name = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($in['name'] ?? ''))) ?? '');
        $email = strtolower(trim((string) ($in['email'] ?? '')));
        $phone = trim((string) ($in['phone'] ?? ''));
        $note = mb_substr(trim(strip_tags((string) ($in['note'] ?? ''))), 0, 500);
        if ($name === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'Enter your name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors['email'] = 'Enter a valid email address for your receipt.';
        }
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if (!preg_match('/^\+?[0-9 ()\-]{10,20}$/', $phone) || strlen($digits) < 10 || strlen($digits) > 13) {
            $errors['phone'] = 'Enter a valid mobile number.';
        }
        // Purpose: one of the configured options (with its fixed amount, if any) or free text when allowed.
        $purpose = '';
        $fixed = 0;
        $choice = (string) ($in['purpose_option'] ?? '');
        if ($choice !== '' && $choice !== 'other' && isset($page['purposes'][(int) $choice]) && ctype_digit($choice)) {
            $opt = $page['purposes'][(int) $choice];
            $purpose = (string) $opt['label'];
            $fixed = (int) $opt['amount'];
        } elseif ($page['custom_purpose']) {
            $purpose = mb_substr(trim(strip_tags((string) ($in['purpose'] ?? ''))), 0, 190);
        }
        if ($purpose === '') {
            $errors[$page['purposes'] ? 'purpose_option' : 'purpose'] = 'Tell us what the payment is for.';
        }
        if ($fixed > 0) {
            $amount = $fixed;
        } else {
            $raw = str_replace([',', '₹', ' '], '', (string) ($in['amount'] ?? ''));
            $amount = preg_match('/^\d{1,8}(\.\d{1,2})?$/', $raw) ? (int) round(((float) $raw) * 100) : 0;
            if ($amount < $page['min_amount'] * 100 || $amount > $page['max_amount'] * 100) {
                $errors['amount'] = 'Enter an amount between ₹' . self::inr($page['min_amount'] * 100, false) . ' and ₹' . self::inr($page['max_amount'] * 100, false) . '.';
            }
        }
        if ($errors) {
            throw new ValidationError('Please correct the highlighted fields.', $errors);
        }
        $recent = (int) $this->db->value('SELECT COUNT(*) FROM ' . $this->db->t('payment_transactions') . ' WHERE ip = ? AND created_at >= ?', [$ip, date('Y-m-d H:i:s', time() - self::START_WINDOW)]);
        if ($recent >= self::START_LIMIT) {
            throw new ValidationError('Too many payment attempts from your connection. Please try again in a few minutes.');
        }
        $now = date('Y-m-d H:i:s');
        $ref = 'P' . date('ymd') . strtoupper(bin2hex(random_bytes(5)));
        $id = $this->db->insert('payment_transactions', [
            'reference' => $ref, 'gateway' => $s['gateway'], 'mode' => $s['mode'], 'purpose' => $purpose, 'amount' => $amount, 'currency' => 'INR',
            'customer_name' => $name, 'customer_email' => $email, 'customer_phone' => $phone, 'note' => $note ?: null,
            'status' => 'created', 'ip' => $ip, 'created_at' => $now, 'updated_at' => $now,
        ]);
        return $this->find($id);
    }

    /** A payment for a shop order: amount and customer come from the order (already validated). */
    public function createForOrder(array $order, string $ip): array
    {
        $s = $this->settings();
        $recent = (int) $this->db->value('SELECT COUNT(*) FROM ' . $this->db->t('payment_transactions') . ' WHERE ip = ? AND created_at >= ?', [$ip, date('Y-m-d H:i:s', time() - self::START_WINDOW)]);
        if ($recent >= self::START_LIMIT) {
            throw new ValidationError('Too many payment attempts from your connection. Please try again in a few minutes.');
        }
        $now = date('Y-m-d H:i:s');
        $id = $this->db->insert('payment_transactions', [
            'reference' => 'P' . date('ymd') . strtoupper(bin2hex(random_bytes(5))), 'gateway' => $s['gateway'], 'mode' => $s['mode'],
            'purpose' => 'Order ' . $order['order_no'], 'amount' => (int) $order['total'], 'currency' => 'INR',
            'customer_name' => $order['customer_name'], 'customer_email' => $order['customer_email'], 'customer_phone' => $order['customer_phone'],
            'note' => null, 'order_id' => (int) $order['id'], 'status' => 'created', 'ip' => $ip, 'created_at' => $now, 'updated_at' => $now,
        ]);
        return $this->find($id);
    }

    /** The most recent payment attempt for a shop order. */
    public function latestForOrder(int $orderId): ?array
    {
        return $this->db->fetch('SELECT * FROM ' . $this->db->t('payment_transactions') . ' WHERE order_id = ? ORDER BY id DESC LIMIT 1', [$orderId]);
    }

    /** Create the order at the gateway; returns the browser action. Marks the transaction failed if the gateway refuses. */
    public function start(array $tx, array $ctx): array
    {
        try {
            $r = $this->gateway($tx['gateway'], $tx['mode'])->start($tx, $ctx);
        } catch (GatewayError $e) {
            $this->db->update('payment_transactions', ['status' => 'failed', 'message' => mb_substr($e->getMessage(), 0, 250), 'updated_at' => date('Y-m-d H:i:s')], (int) $tx['id']);
            $this->event((int) $tx['id'], $tx['gateway'], 'start', false, 'error', $e->getMessage());
            throw $e;
        }
        $this->db->update('payment_transactions', ['status' => 'pending', 'gateway_order_id' => $r['gateway_order_id'], 'updated_at' => date('Y-m-d H:i:s')], (int) $tx['id']);
        $this->event((int) $tx['id'], $tx['gateway'], 'start', true, 'pending', 'Order ' . $r['gateway_order_id']);
        return $r['action'];
    }

    /**
     * Apply a gateway outcome. Rules: a paid transaction never changes again; the amount must match;
     * "pending" never overwrites a final state. Returns [transaction, becamePaid].
     */
    public function apply(array $tx, array $o, string $source, ?string $payload = null): array
    {
        $status = $o['status'];
        $msg = $o['message'];
        if ($status === 'invalid') {
            $this->event((int) $tx['id'], $tx['gateway'], $source, false, 'invalid', $msg, $payload);
            return [$this->find((int) $tx['id']), false];
        }
        if ($status === 'paid' && $o['amount'] !== null && (int) $o['amount'] !== (int) $tx['amount']) {
            $status = 'failed';
            $msg = 'Amount mismatch: the gateway reported ₹' . self::inr((int) $o['amount']) . ' for ₹' . self::inr((int) $tx['amount']) . '. Check this payment in the gateway dashboard.';
        }
        $this->event((int) $tx['id'], $tx['gateway'], $source, true, $status, $msg ?? $o['payment_id'], $payload);
        $now = date('Y-m-d H:i:s');
        $became = false;
        if ($status === 'paid') {
            $became = $this->db->query('UPDATE ' . $this->db->t('payment_transactions') . " SET status = 'paid', paid_at = ?, updated_at = ?, gateway_payment_id = ?, method = ?, message = NULL WHERE id = ? AND status <> 'paid'",
                [$now, $now, $o['payment_id'], $o['method'] ? mb_substr((string) $o['method'], 0, 40) : null, (int) $tx['id']])->rowCount() === 1;
        } elseif ($status === 'failed' || $status === 'cancelled') {
            $this->db->query('UPDATE ' . $this->db->t('payment_transactions') . ' SET status = ?, updated_at = ?, gateway_payment_id = COALESCE(?, gateway_payment_id), method = COALESCE(?, method), message = ? WHERE id = ? AND status IN (\'created\', \'pending\', \'failed\', \'cancelled\')',
                [$status, $now, $o['payment_id'], $o['method'] ? mb_substr((string) $o['method'], 0, 40) : null, $msg ? mb_substr($msg, 0, 250) : null, (int) $tx['id']]);
        }
        return [$this->find((int) $tx['id']), $became];
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM ' . $this->db->t('payment_transactions') . ' WHERE id = ?', [$id]);
    }

    public function byReference(string $ref): ?array
    {
        return preg_match('/^[A-Z0-9]{6,30}$/', $ref) ? $this->db->fetch('SELECT * FROM ' . $this->db->t('payment_transactions') . ' WHERE reference = ?', [$ref]) : null;
    }

    public function byGatewayOrder(string $gateway, string $orderId): ?array
    {
        return $this->db->fetch('SELECT * FROM ' . $this->db->t('payment_transactions') . ' WHERE gateway = ? AND gateway_order_id = ?', [$gateway, $orderId]);
    }

    public function markEmailed(int $id): void
    {
        $this->db->update('payment_transactions', ['emails_sent' => 1], $id);
    }

    public function event(?int $txId, string $gateway, string $source, bool $verified, ?string $result, ?string $detail, ?string $payload = null): void
    {
        $this->db->insert('payment_events', [
            'transaction_id' => $txId, 'gateway' => mb_substr($gateway, 0, 20), 'source' => $source, 'verified' => $verified ? 1 : 0,
            'result' => $result, 'detail' => $detail !== null ? mb_substr($detail, 0, 500) : null,
            'payload' => $payload !== null ? mb_substr($payload, 0, 20000) : null, 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Unguessable token for the receipt page link (the reference alone is not enough to view a receipt). */
    public function receiptToken(string $reference, string $appKey): string
    {
        return substr(hash_hmac('sha256', 'receipt|' . $reference, $appKey), 0, 32);
    }

    // ------------------------------------------------------------------ admin lists

    public function list(array $f, int $page = 1, int $perPage = 20): array
    {
        [$where, $params] = $this->filters($f);
        $t = $this->db->t('payment_transactions');
        $perPage = max(1, min(100, $perPage));
        $total = (int) $this->db->value("SELECT COUNT(*) FROM $t WHERE $where", $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $rows = $this->db->fetchAll("SELECT id, reference, gateway, mode, purpose, amount, customer_name, customer_email, customer_phone, status, gateway_payment_id, method, message, created_at, paid_at FROM $t WHERE $where ORDER BY id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage), $params);
        $sum = (int) $this->db->value("SELECT COALESCE(SUM(amount), 0) FROM $t WHERE $where AND status = 'paid'", $params);
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage, 'paid_total' => $sum];
    }

    public function export(array $f): array
    {
        [$where, $params] = $this->filters($f);
        return $this->db->fetchAll('SELECT reference, created_at, paid_at, status, mode, gateway, purpose, amount, customer_name, customer_email, customer_phone, note, gateway_order_id, gateway_payment_id, method, message FROM '
            . $this->db->t('payment_transactions') . " WHERE $where ORDER BY id DESC", $params);
    }

    public function events(int $txId): array
    {
        return $this->db->fetchAll('SELECT source, verified, result, detail, created_at FROM ' . $this->db->t('payment_events') . ' WHERE transaction_id = ? ORDER BY id', [$txId]);
    }

    /** Totals for the dashboard: paid this month and today (live mode only). */
    public function summary(): array
    {
        $t = $this->db->t('payment_transactions');
        $month = $this->db->fetch("SELECT COUNT(*) AS n, COALESCE(SUM(amount), 0) AS total FROM $t WHERE status = 'paid' AND mode = 'live' AND paid_at >= ?", [date('Y-m-01 00:00:00')]) ?? ['n' => 0, 'total' => 0];
        return ['month_count' => (int) $month['n'], 'month_total' => (int) $month['total']];
    }

    private function filters(array $f): array
    {
        $where = ['1=1'];
        $params = [];
        if (in_array($f['status'] ?? '', self::STATUSES, true)) {
            $where[] = 'status = :status';
            $params['status'] = $f['status'];
        } elseif (($f['status'] ?? '') === '') {
            $where[] = "status <> 'created'";
        }
        if (in_array($f['mode'] ?? '', ['test', 'live'], true)) {
            $where[] = 'mode = :mode';
            $params['mode'] = $f['mode'];
        }
        $q = trim((string) ($f['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(reference LIKE :q1 OR customer_name LIKE :q2 OR customer_email LIKE :q3 OR customer_phone LIKE :q4 OR purpose LIKE :q5 OR gateway_payment_id LIKE :q6)';
            foreach (['q1', 'q2', 'q3', 'q4', 'q5', 'q6'] as $k) {
                $params[$k] = '%' . addcslashes($q, '%_\\') . '%';
            }
        }
        foreach (['from' => '>=', 'to' => '<='] as $k => $op) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($f[$k] ?? ''))) {
                $where[] = "created_at $op :$k";
                $params[$k] = $f[$k] . ($k === 'from' ? ' 00:00:00' : ' 23:59:59');
            }
        }
        return [implode(' AND ', $where), $params];
    }

    /** ₹ amount in Indian digit grouping (1,23,456.00). */
    public static function inr(int $paise, bool $decimals = true): string
    {
        $neg = $paise < 0;
        $paise = abs($paise);
        $r = intdiv($paise, 100);
        $p = $paise % 100;
        $s = (string) $r;
        if (strlen($s) > 3) {
            $last3 = substr($s, -3);
            $rest = substr($s, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $s = $rest . ',' . $last3;
        }
        return ($neg ? '-' : '') . $s . ($decimals || $p ? '.' . str_pad((string) $p, 2, '0', STR_PAD_LEFT) : '');
    }
}
