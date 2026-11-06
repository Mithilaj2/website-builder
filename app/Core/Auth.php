<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_MINUTES = 15;

    private static ?array $user = null;
    private static bool $loaded = false;

    /**
     * @return array{ok:bool, message:string}
     */
    public static function attempt(string $email, string $password, string $ip): array
    {
        $email = strtolower(trim($email));
        if (self::isLockedOut($email, $ip)) {
            return ['ok' => false, 'message' => 'Too many failed attempts. Please wait ' . self::WINDOW_MINUTES . ' minutes and try again.'];
        }

        $user = Database::fetch('SELECT * FROM wb_users WHERE email = ?', [$email]);
        // Always run password_verify to keep timing similar for unknown emails.
        $hash = $user['password_hash'] ?? '$2y$10$3UFUgVtjAIjEN76icFijIOXD0NLCZM0HBixkKeHbQaIu95D5LL6kG';
        $valid = password_verify($password, $hash) && $user !== null;

        Database::insert('wb_login_attempts', [
            'email' => substr($email, 0, 190), 'ip' => $ip, 'success' => $valid ? 1 : 0, 'attempted_at' => now(),
        ]);

        if (!$valid) {
            return ['ok' => false, 'message' => 'Invalid email or password.'];
        }
        if ($user['status'] !== 'active') {
            return ['ok' => false, 'message' => 'This account is inactive. Contact a Super Admin.'];
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            Database::update('wb_users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], (int) $user['id']);
        }

        Session::regenerate();
        Session::set('uid', (int) $user['id']);
        Session::set('last_activity', time());
        Session::set('fingerprint', self::fingerprint());
        Database::update('wb_users', ['last_login_at' => now(), 'last_login_ip' => $ip], (int) $user['id']);
        // Successful login clears this IP+email's failure streak.
        Database::query('DELETE FROM wb_login_attempts WHERE email = ? AND ip = ? AND success = 0', [$email, $ip]);
        Activity::log('login', 'user', (int) $user['id'], null, (int) $user['id']);
        self::$loaded = false;
        return ['ok' => true, 'message' => 'Welcome back.'];
    }

    public static function isLockedOut(string $email, string $ip): bool
    {
        $since = date('Y-m-d H:i:s', time() - self::WINDOW_MINUTES * 60);
        $byPair = (int) Database::value(
            'SELECT COUNT(*) FROM wb_login_attempts WHERE email = ? AND ip = ? AND success = 0 AND attempted_at >= ?',
            [$email, $ip, $since]
        );
        $byIp = (int) Database::value(
            'SELECT COUNT(*) FROM wb_login_attempts WHERE ip = ? AND success = 0 AND attempted_at >= ?',
            [$ip, $since]
        );
        return $byPair >= self::MAX_ATTEMPTS || $byIp >= self::MAX_ATTEMPTS * 4;
    }

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;
        self::$user = null;

        $uid = Session::get('uid');
        if (!is_int($uid)) {
            return null;
        }
        $idle = (int) Config::get('session.idle_timeout', 1800);
        $last = (int) Session::get('last_activity', 0);
        if ($last && time() - $last > $idle) {
            Session::destroy();
            return null;
        }
        if (Session::get('fingerprint') !== self::fingerprint()) {
            Session::destroy();
            return null;
        }
        $user = Database::fetch('SELECT id, name, email, role, status FROM wb_users WHERE id = ?', [$uid]);
        if (!$user || $user['status'] !== 'active') {
            Session::destroy();
            return null;
        }
        Session::set('last_activity', time());
        $user['id'] = (int) $user['id'];
        self::$user = $user;
        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function logout(): void
    {
        if ($id = self::id()) {
            Activity::log('logout', 'user', $id);
        }
        Session::destroy();
        self::$user = null;
        self::$loaded = true;
    }

    private static function fingerprint(): string
    {
        return hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    }
}
