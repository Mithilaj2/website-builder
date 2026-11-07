<?php
declare(strict_types=1);

namespace App\Modules\Websites;

use App\Core\Activity;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Generator\QaAccess;

/**
 * Review step: the website's own admin login (/admin).
 * The builder never stores the password: it is generated, shown once and only its hash is kept.
 */
final class AdminAccountController extends SiteController
{
    public function show(Request $request): void
    {
        $svc = $this->site($request->params['id']);
        $db = $svc->db();
        $users = $db->fetchAll('SELECT id, name, email, role, status, last_login_at FROM ' . $db->t('admin_users') . ' ORDER BY role DESC, name');
        Response::json([
            'ok' => true,
            'generated' => is_file(BASE_PATH . '/' . $svc->website()['folder_path'] . '/core/Admin/App.php'),
            'url' => QaAccess::siteUrl($svc->website()) . 'admin/',
            'default_email' => $this->clientEmail($svc),
            'users' => $users,
        ]);
    }

    /** Create the owner login, or set a new password for an existing administrator with that email. */
    public function store(Request $request): void
    {
        $svc = $this->site($request->params['id']);
        $email = strtolower($request->str('email'));
        $name = mb_substr(trim(strip_tags($request->str('name'))), 0, 120);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            Response::fail('Enter a valid email address.', ['email' => 'Enter a valid email address.']);
        }
        $db = $svc->db();
        $t = $db->t('admin_users');
        $existing = $db->fetch("SELECT id, name, role FROM $t WHERE email = ?", [$email]);
        $password = self::password();
        $now = date('Y-m-d H:i:s');
        if ($existing) {
            $db->update('admin_users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'status' => 'active', 'reset_token_hash' => null, 'reset_expires_at' => null, 'updated_at' => $now] + ($name !== '' ? ['name' => $name] : []), (int) $existing['id']);
            $db->query('DELETE FROM ' . $db->t('login_attempts') . ' WHERE email = ?', [$email]);
            $message = 'New password set for ' . $email . '.';
            Activity::log('website.admin_password_reset', 'website', $svc->id(), $email);
        } else {
            $db->insert('admin_users', ['name' => $name !== '' ? $name : 'Website owner', 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'owner', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
            $message = 'Owner login created for ' . $email . '.';
            Activity::log('website.admin_created', 'website', $svc->id(), $email);
        }
        Response::ok($message, ['email' => $email, 'password' => $password, 'url' => QaAccess::siteUrl($svc->website()) . 'admin/']);
    }

    private function clientEmail(SiteService $svc): string
    {
        return (string) Database::value('SELECT email FROM wb_clients WHERE id = ?', [(int) $svc->website()['client_id']]);
    }

    /** 16 characters, easy to read aloud (no 0/O, 1/l/I), always letters and digits. */
    public static function password(): string
    {
        $letters = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
        $digits = '23456789';
        $all = $letters . $digits;
        do {
            $p = '';
            for ($i = 0; $i < 16; $i++) {
                $p .= $all[random_int(0, strlen($all) - 1)];
            }
        } while (!preg_match('/\d/', $p) || !preg_match('/[A-Za-z]/', $p));
        return implode('-', str_split($p, 4));
    }
}
