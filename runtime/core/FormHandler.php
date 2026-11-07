<?php
declare(strict_types=1);

namespace SiteRuntime;

/**
 * Public contact, enquiry, appointment, admission and booking forms. Stateless protection (no session cookie on public pages):
 *  - signed timestamp token (HMAC with the site key): rejects forged/old posts and posts sent < 3 s after page load
 *  - honeypot field
 *  - rate limit per IP (5 per 10 minutes)
 * Submissions are always stored in the site's database first; the email notification is best-effort
 * (a mail failure is logged and never loses the enquiry).
 */
final class FormHandler
{
    private const MIN_SECONDS = 3;
    private const MAX_AGE = 7200;
    private const LIMIT = 5;
    private const WINDOW = 600;

    public function __construct(private Db $db, private string $key, private string $base, private bool $clean, private ?\Closure $onStored = null)
    {
    }

    public function handle(string $form): void
    {
        $back = (string) ($_POST['_back'] ?? '');
        if (!preg_match('/^[a-z0-9-]{0,120}$/', $back)) {
            $back = '';
        }
        $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: '0.0.0.0';

        // Honeypot filled → pretend success, store nothing.
        if (trim((string) ($_POST['website'] ?? '')) !== '') {
            $this->redirect($back, $form, 'sent');
        }
        if (!$this->validToken((string) ($_POST['_t'] ?? ''))) {
            $this->redirect($back, $form, 'error', 'expired');
        }
        $recent = (int) $this->db->value(
            'SELECT COUNT(*) FROM ' . $this->db->t('contact_submissions') . ' WHERE ip = ? AND created_at >= ?',
            [$ip, date('Y-m-d H:i:s', time() - self::WINDOW)]
        );
        if ($recent >= self::LIMIT) {
            $this->redirect($back, $form, 'error', 'limit');
        }

        $name = $this->clean('name', 120);
        $email = strtolower($this->clean('email', 190));
        $phone = $this->clean('phone', 30);
        $subject = $this->clean('subject', 190);
        $message = $this->clean('message', 5000, true);
        // The contact form needs an email and a message. Booking/appointment/admission forms need a name and
        // either an email or a mobile number (email too when the form asks for it); the message is optional.
        $isContact = $form === 'contact';
        $emailOk = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        $phoneOk = $phone !== '' && preg_match('/^\+?[0-9 ()\-]{6,20}$/', $phone) === 1;
        $valid = $name !== '' && ($phone === '' || $phoneOk) && ($email === '' || $emailOk)
            && ($isContact ? ($emailOk && $message !== '') : (($_POST['_email_required'] ?? '') === '1' ? $emailOk : ($emailOk || $phoneOk)));
        $extra = $this->extras();
        if (!$valid || $extra === null) {
            $this->redirect($back, $form, 'error', 'invalid');
        }

        $row = [
            'form_key' => $form, 'name' => $name, 'email' => $email ?: null, 'phone' => $phone ?: null,
            'subject' => $subject ?: null, 'message' => $message ?: null, 'extra_json' => $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null, 'page_slug' => $back ?: null,
            'ip' => $ip, 'status' => 'new', 'email_sent' => 0, 'created_at' => date('Y-m-d H:i:s'),
        ];
        $row['id'] = $this->db->insert('contact_submissions', $row);
        if ($this->onStored) {
            ($this->onStored)($row);
        }
        $this->redirect($back, $form, 'sent');
    }

    private function validToken(string $token): bool
    {
        if (!preg_match('/^(\d{9,11})\.([a-f0-9]{64})$/', $token, $m)) {
            return false;
        }
        $age = time() - (int) $m[1];
        return $age >= self::MIN_SECONDS && $age <= self::MAX_AGE
            && hash_equals(hash_hmac('sha256', 'form|' . $m[1], $this->key), $m[2]);
    }

    /**
     * Extra answers posted as extra[Label] = value (up to 10). Labels and values are plain text;
     * fields whose label mentions "date" must be YYYY-MM-DD. Returns null when something is invalid.
     */
    private function extras(): ?array
    {
        $in = $_POST['extra'] ?? [];
        if (!is_array($in)) {
            return null;
        }
        $out = [];
        foreach (array_slice($in, 0, 10, true) as $label => $value) {
            $label = trim((string) preg_replace('/[^\p{L}\p{N} ().,\/&\-]/u', '', (string) $label));
            if ($label === '' || mb_strlen($label) > 40 || !is_string($value)) {
                continue;
            }
            $value = mb_substr(trim((string) preg_replace('/\s+/', ' ', strip_tags($value))), 0, 300);
            if ($value === '') {
                continue;
            }
            if (stripos($label, 'date') !== false && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) || !checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4)))) {
                return null;
            }
            $out[$label] = $value;
        }
        return $out;
    }

    private function clean(string $field, int $max, bool $multiline = false): string
    {
        $v = $_POST[$field] ?? '';
        if (!is_string($v)) {
            return '';
        }
        $v = strip_tags($v);
        $v = $multiline ? preg_replace("/\r\n?/", "\n", $v) : preg_replace('/\s+/', ' ', $v);
        return mb_substr(trim((string) $v), 0, $max);
    }

    private function redirect(string $back, string $form, string $status, string $reason = ''): never
    {
        $page = $back === '' ? $this->base . '/' : ($this->clean ? $this->base . '/' . $back : $this->base . '/index.php?page=' . rawurlencode($back));
        $q = 'form=' . rawurlencode($form) . '&status=' . $status . ($reason !== '' ? '&reason=' . $reason : '');
        header('Location: ' . $page . (str_contains($page, '?') ? '&' : '?') . $q . '#' . $form . '-form', true, 303);
        exit;
    }
}
