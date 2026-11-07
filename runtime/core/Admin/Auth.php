<?php
declare(strict_types=1);

namespace SiteRuntime\Admin;

use SiteRuntime\Db;

/**
 * Website admin authentication: bcrypt passwords, session hardening, idle timeout,
 * login rate limiting (per email+IP and per IP), password reset tokens (hashed, 1 hour).
 */
final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW = 900;
    private const IDLE = 1800;

    private ?array $user = null;
    private bool $loaded = false;

    public function __construct(private Db $db)
    {
    }

    public static function startSession(string $siteCode, string $cookiePath): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('wbadm_' . preg_replace('/[^0-9a-z]/i', '', $siteCode));
        session_set_cookie_params(['lifetime' => 0, 'path' => $cookiePath ?: '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);
        session_start();
    }

    /** @return array{ok:bool, message:string} */
    public function attempt(string $email, string $password, string $ip): array
    {
        $email = strtolower(trim($email));
        if ($this->lockedOut($email, $ip)) {
            return ['ok' => false, 'message' => 'Too many failed attempts. Please wait 15 minutes and try again.', 'locked' => true];
        }
        $u = $this->db->fetch('SELECT * FROM ' . $this->db->t('admin_users') . ' WHERE email = ?', [$email]);
        $hash = $u['password_hash'] ?? '$2y$10$JTCMbZCDCxNjuud9jA0vZeTBjaAEIpsNKXI/pfxqeKoIGltqJ4FC2';
        $valid = password_verify($password, $hash) && $u !== null;
        $this->db->insert('login_attempts', ['email' => substr($email, 0, 190), 'ip' => $ip, 'success' => $valid ? 1 : 0, 'attempted_at' => date('Y-m-d H:i:s')]);
        if (!$valid) {
            return ['ok' => false, 'message' => 'Invalid email or password.'];
        }
        if ($u['status'] !== 'active') {
            return ['ok' => false, 'message' => 'This account is inactive.'];
        }
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            $this->db->update('admin_users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], (int) $u['id']);
        }
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $u['id'];
        $_SESSION['last'] = time();
        $_SESSION['fp'] = hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $this->db->update('admin_users', ['last_login_at' => date('Y-m-d H:i:s'), 'last_login_ip' => $ip], (int) $u['id']);
        $this->db->query('DELETE FROM ' . $this->db->t('login_attempts') . ' WHERE email = ? AND ip = ? AND success = 0', [$email, $ip]);
        $this->loaded = false;
        return ['ok' => true, 'message' => 'Welcome back.'];
    }

    public function lockedOut(string $email, string $ip): bool
    {
        $since = date('Y-m-d H:i:s', time() - self::WINDOW);
        $t = $this->db->t('login_attempts');
        $pair = (int) $this->db->value("SELECT COUNT(*) FROM $t WHERE email = ? AND ip = ? AND success = 0 AND attempted_at >= ?", [$email, $ip, $since]);
        $byIp = (int) $this->db->value("SELECT COUNT(*) FROM $t WHERE ip = ? AND success = 0 AND attempted_at >= ?", [$ip, $since]);
        return $pair >= self::MAX_ATTEMPTS || $byIp >= self::MAX_ATTEMPTS * 4;
    }

    public function user(): ?array
    {
        if ($this->loaded) {
            return $this->user;
        }
        $this->loaded = true;
        $uid = $_SESSION['uid'] ?? null;
        if (!is_int($uid)) {
            return null;
        }
        if (time() - (int) ($_SESSION['last'] ?? 0) > self::IDLE || ($_SESSION['fp'] ?? '') !== hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''))) {
            $this->logout();
            return null;
        }
        $u = $this->db->fetch('SELECT id, name, email, role, status, last_login_at, last_login_ip FROM ' . $this->db->t('admin_users') . ' WHERE id = ?', [$uid]);
        if (!$u || $u['status'] !== 'active') {
            $this->logout();
            return null;
        }
        $_SESSION['last'] = time();
        $u['id'] = (int) $u['id'];
        return $this->user = $u;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            session_destroy();
        }
        $this->user = null;
    }

    public function csrf(): string
    {
        if (empty($_SESSION['csrf']) || strlen((string) $_SESSION['csrf']) !== 64) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public function verifyCsrf(): bool
    {
        $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_token'] ?? '');
        return is_string($sent) && !empty($_SESSION['csrf']) && hash_equals((string) $_SESSION['csrf'], $sent);
    }

    // ------------------------------------------------------------------ password reset

    /** Create a reset token for an active user. Returns [user, token] or null (callers must not reveal which). */
    public function createResetToken(string $email): ?array
    {
        $u = $this->db->fetch('SELECT * FROM ' . $this->db->t('admin_users') . " WHERE email = ? AND status = 'active'", [strtolower(trim($email))]);
        if (!$u) {
            return null;
        }
        $token = bin2hex(random_bytes(32));
        $this->db->update('admin_users', ['reset_token_hash' => hash('sha256', $token), 'reset_expires_at' => date('Y-m-d H:i:s', time() + 3600)], (int) $u['id']);
        return [$u, $token];
    }

    public function userForReset(string $email, string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $u = $this->db->fetch('SELECT * FROM ' . $this->db->t('admin_users') . " WHERE email = ? AND status = 'active' AND reset_token_hash IS NOT NULL AND reset_expires_at >= ?", [strtolower(trim($email)), date('Y-m-d H:i:s')]);
        return $u && hash_equals((string) $u['reset_token_hash'], hash('sha256', $token)) ? $u : null;
    }

    public static function strongPassword(string $p): bool
    {
        return strlen($p) >= 10 && preg_match('/[A-Za-z]/', $p) && preg_match('/\d/', $p);
    }
}
