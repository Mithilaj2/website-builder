<?php
declare(strict_types=1);

namespace SiteRuntime;

/**
 * Public contact/enquiry forms. Stateless protection (no session cookie on public pages):
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
        if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || ($phone !== '' && !preg_match('/^\+?[0-9 ()\-]{6,20}$/', $phone))) {
            $this->redirect($back, $form, 'error', 'invalid');
        }

        $row = [
            'form_key' => $form, 'name' => $name, 'email' => $email, 'phone' => $phone ?: null,
            'subject' => $subject ?: null, 'message' => $message, 'page_slug' => $back ?: null,
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
