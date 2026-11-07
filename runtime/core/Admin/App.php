<?php
declare(strict_types=1);

namespace SiteRuntime\Admin;

use SiteRuntime\ContentService;
use SiteRuntime\Crypto;
use SiteRuntime\Db;
use SiteRuntime\DbSource;
use SiteRuntime\Mailer;
use SiteRuntime\Payments\GatewayError;
use SiteRuntime\Payments\Gateways;
use SiteRuntime\Payments\PaymentService;
use SiteRuntime\MediaService;
use SiteRuntime\NotFound;
use SiteRuntime\SectionTypes;
use SiteRuntime\SettingsService;
use SiteRuntime\Site;
use SiteRuntime\Theme;
use SiteRuntime\ValidationError;

/**
 * The website's own admin panel (/admin). Runs entirely from this website's folder and database.
 * Roles: owner (everything) and editor (content, media, navigation, appearance, enquiries).
 */
final class App
{
    private Auth $auth;
    private array $cache = [];

    public function __construct(private string $root, private Db $db, private array $app, private string $base)
    {
        $this->auth = new Auth($db);
    }

    public function handle(string $path, Site $site): void
    {
        $path = trim($path, '/');
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self' data:; frame-src 'self'; frame-ancestors 'self'; form-action 'self'; base-uri 'self'");
        header('X-Frame-Options: SAMEORIGIN');
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');

        require_once __DIR__ . '/helpers.php';
        Auth::startSession((string) ($this->app['site_code'] ?? 'site'), $this->base . '/admin');
        $GLOBALS['__wb_admin_csrf'] = $this->auth->csrf();

        // Older websites: bring the schema up to date before anything else (idempotent).
        $mig = new \SiteRuntime\Migrator($this->db, $this->root . '/core/schema');
        if ($mig->pending()) {
            $mig->migrate();
        }

        foreach ($this->routes() as [$m, $pattern, $handler, $access]) {
            $regex = '#^' . preg_replace('#\{([a-z]+)\}#', '(?P<$1>\d+)', $pattern) . '$#';
            if ($m !== $method || !preg_match($regex, $path, $params)) {
                continue;
            }
            $params = array_map('intval', array_filter($params, 'is_string', ARRAY_FILTER_USE_KEY));
            if ($method === 'POST' && !$this->auth->verifyCsrf()) {
                $this->fail('Your session has expired. Please reload the page and try again.', [], 403);
            }
            if ($access !== 'guest') {
                $user = $this->auth->user();
                if (!$user) {
                    if ($this->isAjax()) {
                        $this->json(['ok' => false, 'message' => 'Please log in again.', 'redirect' => $this->url('login')], 401);
                    }
                    $this->redirect('login');
                }
                if ($access === 'owner' && $user['role'] !== 'owner') {
                    $this->fail('Only the website owner can do this.', [], 403);
                }
            }
            try {
                $this->$handler($params);
            } catch (ValidationError $e) {
                if ($e->extra) {
                    $this->json(['ok' => false, 'message' => $e->getMessage(), 'errors' => (object) $e->errors] + $e->extra, 409);
                }
                $this->fail($e->getMessage(), $e->errors);
            } catch (NotFound $e) {
                $this->fail($e->getMessage(), [], 404);
            }
            return;
        }
        if (!$this->auth->user() && $method === 'GET' && !str_starts_with($path, 'api/')) {
            $this->redirect('login');
        }
        $this->isAjax() ? $this->json(['ok' => false, 'message' => 'Not found.'], 404) : $site->status(404, 'Page not found');
    }

    private function routes(): array
    {
        return [
            ['GET', 'login', 'loginPage', 'guest'], ['POST', 'login', 'login', 'guest'],
            ['GET', 'forgot', 'forgotPage', 'guest'], ['POST', 'forgot', 'forgot', 'guest'],
            ['GET', 'reset', 'resetPage', 'guest'], ['POST', 'reset', 'reset', 'guest'],
            ['POST', 'logout', 'logout', 'auth'],
            ['GET', '', 'dashboard', 'auth'],
            ['GET', 'content', 'contentPage', 'auth'],
            ['GET', 'media', 'mediaPage', 'auth'],
            ['GET', 'appearance', 'appearancePage', 'auth'],
            ['GET', 'navigation', 'navigationPage', 'auth'],
            ['GET', 'enquiries', 'enquiriesPage', 'auth'],
            ['GET', 'profile', 'profilePage', 'auth'], ['POST', 'profile', 'saveProfile', 'auth'], ['POST', 'profile/password', 'changePassword', 'auth'],
            ['GET', 'settings', 'settingsPage', 'owner'], ['POST', 'settings/mail', 'saveMail', 'owner'], ['POST', 'settings/mail-test', 'testMail', 'owner'],
            // JSON API used by the shared editor (same shapes as the builder's endpoints)
            ['GET', 'api/pages', 'apiPages', 'auth'], ['POST', 'api/pages', 'apiPageCreate', 'auth'], ['POST', 'api/pages/reorder', 'apiPagesReorder', 'auth'],
            ['POST', 'api/pages/{pid}', 'apiPageUpdate', 'auth'], ['POST', 'api/pages/{pid}/home', 'apiPageHome', 'auth'],
            ['POST', 'api/pages/{pid}/toggle', 'apiPageToggle', 'auth'], ['POST', 'api/pages/{pid}/delete', 'apiPageDelete', 'auth'],
            ['GET', 'api/pages/{pid}/sections', 'apiSections', 'auth'], ['POST', 'api/pages/{pid}/sections', 'apiSectionAdd', 'auth'],
            ['POST', 'api/pages/{pid}/sections/reorder', 'apiSectionsReorder', 'auth'],
            ['GET', 'api/sections/{sid}', 'apiSection', 'auth'], ['POST', 'api/sections/{sid}', 'apiSectionUpdate', 'auth'],
            ['POST', 'api/sections/{sid}/toggle', 'apiSectionToggle', 'auth'], ['POST', 'api/sections/{sid}/duplicate', 'apiSectionDuplicate', 'auth'],
            ['POST', 'api/sections/{sid}/delete', 'apiSectionDelete', 'auth'],
            ['GET', 'api/media', 'apiMedia', 'auth'], ['POST', 'api/media', 'apiMediaUpload', 'auth'], ['POST', 'api/media/{mid}', 'apiMediaUpdate', 'auth'],
            ['POST', 'api/media/{mid}/replace', 'apiMediaReplace', 'auth'], ['POST', 'api/media/{mid}/delete', 'apiMediaDelete', 'auth'],
            ['GET', 'api/settings', 'apiSettings', 'auth'], ['POST', 'api/settings/theme', 'apiTheme', 'auth'], ['POST', 'api/settings/details', 'apiDetails', 'auth'],
            ['GET', 'api/navigation', 'apiNavigation', 'auth'], ['POST', 'api/navigation', 'apiNavigationSave', 'auth'],
            ['GET', 'api/enquiries', 'apiEnquiries', 'auth'], ['GET', 'api/enquiries/export', 'apiEnquiriesExport', 'auth'],
            ['GET', 'api/enquiries/{eid}', 'apiEnquiry', 'auth'], ['POST', 'api/enquiries/{eid}/status', 'apiEnquiryStatus', 'auth'],
            ['POST', 'api/enquiries/{eid}/delete', 'apiEnquiryDelete', 'auth'],
            ['GET', 'payments', 'paymentsPage', 'auth'], ['GET', 'payments/settings', 'paymentSettingsPage', 'owner'],
            ['POST', 'payments/settings', 'savePaymentSettings', 'owner'], ['POST', 'payments/test', 'testPaymentConnection', 'owner'],
            ['GET', 'api/payments', 'apiPayments', 'auth'], ['GET', 'api/payments/export', 'apiPaymentsExport', 'auth'],
            ['GET', 'api/payments/{tid}', 'apiPayment', 'auth'], ['POST', 'api/payments/{tid}/recheck', 'apiPaymentRecheck', 'auth'],
            ['GET', 'api/users', 'apiUsers', 'owner'], ['POST', 'api/users', 'apiUserSave', 'owner'], ['POST', 'api/users/{uid}', 'apiUserSave', 'owner'],
            ['POST', 'api/users/{uid}/delete', 'apiUserDelete', 'owner'],
        ];
    }

    // ================================================================== auth pages

    private function loginPage(): void
    {
        if ($this->auth->user()) {
            $this->redirect('');
        }
        $this->render('login', ['title' => 'Sign in'], 'auth');
    }

    private function login(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || ($_POST['password'] ?? '') === '') {
            $this->fail('Please enter your email and password.');
        }
        $r = $this->auth->attempt($email, (string) $_POST['password'], $this->ip());
        if (!$r['ok']) {
            $this->fail($r['message'], [], !empty($r['locked']) ? 429 : 422);
        }
        $this->ok($r['message'], ['redirect' => $this->url('')]);
    }

    private function logout(): void
    {
        $this->auth->logout();
        $this->ok('You have been logged out.', ['redirect' => $this->url('login')]);
    }

    private function forgotPage(): void
    {
        $this->render('forgot', ['title' => 'Reset password'], 'auth');
    }

    /** Always answers the same way, so the form cannot be used to discover admin emails. */
    private function forgot(): void
    {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->fail('Enter a valid email address.', ['email' => 'Enter a valid email address.']);
        }
        $msg = 'If that email belongs to an administrator, a reset link has been sent. It is valid for one hour.';
        // Rate limit reset requests per IP using the login attempts table.
        $recent = (int) $this->db->value('SELECT COUNT(*) FROM ' . $this->db->t('login_attempts') . " WHERE ip = ? AND email LIKE 'reset:%' AND attempted_at >= ?", [$this->ip(), date('Y-m-d H:i:s', time() - 900)]);
        $this->db->insert('login_attempts', ['email' => 'reset:' . substr($email, 0, 180), 'ip' => $this->ip(), 'success' => 0, 'attempted_at' => date('Y-m-d H:i:s')]);
        if ($recent >= 5) {
            $this->ok($msg);
        }
        $r = $this->auth->createResetToken($email);
        if ($r) {
            [$u, $token] = $r;
            $link = $this->absolute($this->url('reset') . '?email=' . rawurlencode($u['email']) . '&token=' . $token);
            try {
                $mailer = $this->mailer();
                $mailer->send([$u['email']], 'Reset your password', $mailer->wrap('Reset your password',
                    '<p>Hello ' . htmlspecialchars((string) $u['name'], ENT_QUOTES, 'UTF-8') . ',</p><p>Someone (hopefully you) asked to reset the password for the website admin.</p>'
                    . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" style="background:#1f2937;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;display:inline-block">Choose a new password</a></p>'
                    . '<p style="color:#6b7280;font-size:13px">The link is valid for one hour. If you did not ask for this, ignore this email.</p>'), "Reset your password: $link (valid for one hour)");
            } catch (\Throwable $e) {
                $this->log('Password reset email failed: ' . $e->getMessage());
            }
        }
        $this->ok($msg);
    }

    private function resetPage(): void
    {
        $email = (string) ($_GET['email'] ?? '');
        $token = (string) ($_GET['token'] ?? '');
        $this->render('reset', ['title' => 'Choose a new password', 'email' => $email, 'token' => $token, 'valid' => $this->auth->userForReset($email, $token) !== null], 'auth');
    }

    private function reset(): void
    {
        $u = $this->auth->userForReset((string) ($_POST['email'] ?? ''), (string) ($_POST['token'] ?? ''));
        if (!$u) {
            $this->fail('This reset link is invalid or has expired. Request a new one.');
        }
        $p = (string) ($_POST['password'] ?? '');
        if (!Auth::strongPassword($p)) {
            $this->fail('Please correct the highlighted fields.', ['password' => 'At least 10 characters with letters and numbers.']);
        }
        if ($p !== (string) ($_POST['password_confirmation'] ?? '')) {
            $this->fail('Please correct the highlighted fields.', ['password_confirmation' => 'Passwords do not match.']);
        }
        $this->db->update('admin_users', ['password_hash' => password_hash($p, PASSWORD_DEFAULT), 'reset_token_hash' => null, 'reset_expires_at' => null, 'updated_at' => date('Y-m-d H:i:s')], (int) $u['id']);
        $this->ok('Password changed. You can now sign in.', ['redirect' => $this->url('login')]);
    }

    // ================================================================== pages

    private function dashboard(): void
    {
        $t = fn ($n) => $this->db->t($n);
        $stats = [
            'pages' => (int) $this->db->value('SELECT COUNT(*) FROM ' . $t('pages') . " WHERE status = 'active'"),
            'sections' => (int) $this->db->value('SELECT COUNT(*) FROM ' . $t('sections') . ' WHERE is_enabled = 1'),
            'media' => (int) $this->db->value('SELECT COUNT(*) FROM ' . $t('media')),
            'new' => (int) $this->db->value('SELECT COUNT(*) FROM ' . $t('contact_submissions') . " WHERE status = 'new'"),
            'enquiries' => (int) $this->db->value('SELECT COUNT(*) FROM ' . $t('contact_submissions') . " WHERE status <> 'spam'"),
        ];
        $recent = $this->db->fetchAll('SELECT id, name, email, subject, status, created_at FROM ' . $t('contact_submissions') . " WHERE status <> 'spam' ORDER BY id DESC LIMIT 6");
        $pay = $this->payments();
        $this->render('dashboard', ['title' => 'Dashboard', 'active' => 'dashboard', 'stats' => $stats, 'recent' => $recent, 'mailReady' => $this->mailer()->configured(),
            'payments' => $pay->settings()['enabled'] ? $pay->summary() + ['mode' => $pay->settings()['mode'], 'ready' => $pay->ready()] : null]);
    }

    private function contentPage(): void
    {
        $this->render('content', ['title' => 'Pages & sections', 'active' => 'content', 'scripts' => ['vendor/sortablejs/Sortable.min.js', 'vendor/tinymce/tinymce.min.js', 'js/website-workspace.js']] + $this->editorVars());
    }

    private function mediaPage(): void
    {
        $this->render('media', ['title' => 'Media library', 'active' => 'media', 'scripts' => ['vendor/sortablejs/Sortable.min.js', 'vendor/tinymce/tinymce.min.js', 'js/website-workspace.js']] + $this->editorVars());
    }

    private function appearancePage(): void
    {
        $fonts = Theme::fonts($this->root . '/assets/fonts');
        $fontOptions = '';
        foreach ($fonts as $k => $f) {
            $fontOptions .= '<option value="' . e($k) . '">' . e($f['name']) . '</option>';
        }
        $m = $this->manifest();
        $this->render('appearance', [
            'title' => 'Appearance', 'active' => 'appearance', 'fontOptions' => $fontOptions,
            'headerStyles' => (array) ($m['theme']['header_styles'] ?? []), 'footerStyles' => (array) ($m['theme']['footer_styles'] ?? []),
            'themeAction' => $this->url('api/settings/theme'), 'detailsAction' => $this->url('api/settings/details'),
            'scripts' => ['vendor/sortablejs/Sortable.min.js', 'vendor/tinymce/tinymce.min.js', 'js/website-workspace.js'],
        ] + $this->editorVars());
    }

    private function navigationPage(): void
    {
        $this->render('navigation', ['title' => 'Navigation', 'active' => 'navigation', 'scripts' => ['vendor/sortablejs/Sortable.min.js', 'js/admin-navigation.js']]);
    }

    private function enquiriesPage(): void
    {
        $this->render('enquiries', ['title' => 'Enquiries', 'active' => 'enquiries', 'scripts' => ['js/admin-enquiries.js']]);
    }

    private function profilePage(): void
    {
        $this->render('profile', ['title' => 'My profile', 'active' => 'profile', 'user' => $this->auth->user()]);
    }

    private function settingsPage(): void
    {
        $mail = (array) ($this->settings()->all()['mail'] ?? []);
        $this->render('settings', ['title' => 'Settings', 'active' => 'settings', 'mail' => $mail, 'siteEmail' => (string) ($this->settings()->all()['site']['email'] ?? ''), 'scripts' => ['js/admin-settings.js']]);
    }

    // ================================================================== profile & users

    private function saveProfile(): void
    {
        $u = $this->auth->user();
        $name = trim(strip_tags((string) ($_POST['name'] ?? '')));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $errors = [];
        if ($name === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'Name is required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif ((int) $this->db->value('SELECT COUNT(*) FROM ' . $this->db->t('admin_users') . ' WHERE email = ? AND id <> ?', [$email, $u['id']]) > 0) {
            $errors['email'] = 'Another administrator uses this email.';
        }
        if ($errors) {
            $this->fail('Please correct the highlighted fields.', $errors);
        }
        $this->db->update('admin_users', ['name' => $name, 'email' => $email, 'updated_at' => date('Y-m-d H:i:s')], $u['id']);
        $this->ok('Profile saved.');
    }

    private function changePassword(): void
    {
        $u = $this->auth->user();
        $hash = (string) $this->db->value('SELECT password_hash FROM ' . $this->db->t('admin_users') . ' WHERE id = ?', [$u['id']]);
        if (!password_verify((string) ($_POST['current_password'] ?? ''), $hash)) {
            $this->fail('Your current password is incorrect.', ['current_password' => 'Current password is incorrect.']);
        }
        $p = (string) ($_POST['password'] ?? '');
        if (!Auth::strongPassword($p)) {
            $this->fail('Please correct the highlighted fields.', ['password' => 'At least 10 characters with letters and numbers.']);
        }
        if ($p !== (string) ($_POST['password_confirmation'] ?? '')) {
            $this->fail('Please correct the highlighted fields.', ['password_confirmation' => 'Passwords do not match.']);
        }
        $this->db->update('admin_users', ['password_hash' => password_hash($p, PASSWORD_DEFAULT), 'updated_at' => date('Y-m-d H:i:s')], $u['id']);
        session_regenerate_id(true);
        $this->ok('Password changed.');
    }

    private function apiUsers(): void
    {
        $me = $this->auth->user()['id'];
        $rows = $this->db->fetchAll('SELECT id, name, email, role, status, last_login_at FROM ' . $this->db->t('admin_users') . ' ORDER BY role DESC, name');
        foreach ($rows as &$r) {
            $r['is_me'] = (int) $r['id'] === $me;
        }
        $this->json(['rows' => $rows, 'total' => count($rows), 'page' => 1, 'pages' => 1, 'per_page' => count($rows) ?: 1]);
    }

    private function apiUserSave(array $p): void
    {
        $id = (int) ($p['uid'] ?? 0);
        $me = $this->auth->user();
        $existing = $id ? $this->db->fetch('SELECT * FROM ' . $this->db->t('admin_users') . ' WHERE id = ?', [$id]) : null;
        if ($id && !$existing) {
            throw new NotFound('User not found.');
        }
        $name = trim(strip_tags((string) ($_POST['name'] ?? '')));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $role = in_array($_POST['role'] ?? '', ['owner', 'editor'], true) ? $_POST['role'] : 'editor';
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $password = (string) ($_POST['password'] ?? '');
        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif ((int) $this->db->value('SELECT COUNT(*) FROM ' . $this->db->t('admin_users') . ' WHERE email = ? AND id <> ?', [$email, $id]) > 0) {
            $errors['email'] = 'Another administrator uses this email.';
        }
        if ((!$id || $password !== '') && !Auth::strongPassword($password)) {
            $errors['password'] = 'At least 10 characters with letters and numbers.';
        }
        if ($errors) {
            $this->fail('Please correct the highlighted fields.', $errors);
        }
        if ($existing && (int) $existing['id'] === $me['id'] && ($role !== 'owner' || $status !== 'active')) {
            $this->fail('You cannot remove your own owner role or deactivate yourself.');
        }
        $data = ['name' => $name, 'email' => $email, 'role' => $role, 'status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        if ($existing) {
            $this->db->update('admin_users', $data, $id);
        } else {
            $this->db->insert('admin_users', $data + ['created_at' => date('Y-m-d H:i:s')]);
        }
        $this->ok($existing ? 'User saved.' : 'User added.');
    }

    private function apiUserDelete(array $p): void
    {
        if ((int) $p['uid'] === $this->auth->user()['id']) {
            $this->fail('You cannot delete your own account.');
        }
        $this->db->delete('admin_users', (int) $p['uid']);
        $this->ok('User deleted.');
    }

    // ================================================================== email settings

    private function saveMail(): void
    {
        $in = $_POST;
        $current = (array) ($this->settings()->all()['mail'] ?? []);
        $errors = [];
        $notify = array_values(array_filter(array_map('trim', explode(',', (string) ($in['notify_to'] ?? '')))));
        foreach ($notify as $e) {
            if (!filter_var($e, FILTER_VALIDATE_EMAIL)) {
                $errors['notify_to'] = "“{$e}” is not a valid email address.";
            }
        }
        if (count($notify) > 5) {
            $errors['notify_to'] = 'Up to 5 addresses.';
        }
        $from = trim((string) ($in['from_email'] ?? ''));
        if ($from !== '' && !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $errors['from_email'] = 'Enter a valid email address.';
        }
        $smtpOn = ($in['smtp_enabled'] ?? '') === '1';
        $host = trim((string) ($in['smtp_host'] ?? ''));
        $port = (int) ($in['smtp_port'] ?? 0);
        $enc = in_array($in['smtp_encryption'] ?? '', ['tls', 'ssl', 'none'], true) ? $in['smtp_encryption'] : 'tls';
        if ($smtpOn) {
            if (!preg_match('/^[a-z0-9.\-]{3,190}$/i', $host)) {
                $errors['smtp_host'] = 'Enter the SMTP server name, e.g. mail.yourdomain.com.';
            }
            if ($port < 1 || $port > 65535) {
                $errors['smtp_port'] = 'Enter a port such as 587 or 465.';
            }
        }
        if ($errors) {
            $this->fail('Please correct the highlighted fields.', $errors);
        }
        $password = (string) ($in['smtp_password'] ?? '');
        $mail = [
            'notify_to' => implode(', ', array_map('strtolower', $notify)),
            'from_email' => strtolower($from),
            'from_name' => mb_substr(trim(strip_tags((string) ($in['from_name'] ?? ''))), 0, 100),
            'smtp' => [
                'enabled' => $smtpOn, 'host' => $host, 'port' => $port ?: 587, 'encryption' => $enc,
                'username' => mb_substr(trim((string) ($in['smtp_username'] ?? '')), 0, 190),
                // Blank keeps the saved password. Stored encrypted with this website's key.
                'password' => $password !== '' ? $this->crypto()->encrypt($password) : (string) ($current['smtp']['password'] ?? ''),
            ],
        ];
        $this->settings()->save('mail', $mail);
        $this->ok('Email settings saved.');
    }

    private function testMail(): void
    {
        $to = $this->auth->user()['email'];
        try {
            $m = $this->mailer();
            $m->send([$to], 'Test email from your website', $m->wrap('It works!', '<p>This test message confirms that your website can send email. Enquiry notifications will be delivered to: <strong>' . htmlspecialchars(implode(', ', $m->notifyTo()) ?: '(no address set)', ENT_QUOTES, 'UTF-8') . '</strong>.</p>'), 'This test message confirms that your website can send email.');
        } catch (\Throwable $e) {
            $this->fail($e->getMessage());
        }
        $this->ok("Test email sent to $to.");
    }

    // ================================================================== content API (shared editor)

    private function apiPages(): void
    {
        $this->json(['ok' => true, 'pages' => $this->content()->pages()]);
    }

    private function apiPageCreate(): void
    {
        $this->ok('Page added with the default sections for its layout.', ['id' => $this->content()->savePage(null, $_POST)]);
    }

    private function apiPageUpdate(array $p): void
    {
        $c = $this->content();
        $c->savePage($c->page($p['pid']), $_POST);
        $this->ok('Page saved.');
    }

    private function apiPageHome(array $p): void
    {
        $page = $this->content()->setHome($p['pid']);
        $this->ok('“' . $page['title'] . '” is now the homepage.');
    }

    private function apiPageToggle(array $p): void
    {
        $this->ok('Page ' . ($this->content()->togglePage($p['pid']) === 'active' ? 'activated.' : 'deactivated.'));
    }

    private function apiPagesReorder(): void
    {
        $this->content()->reorderPages((array) ($_POST['ids'] ?? []));
        $this->ok('Page order saved.');
    }

    private function apiPageDelete(array $p): void
    {
        $this->content()->deletePage($p['pid']);
        $this->ok('Page deleted.');
    }

    private function apiSections(array $p): void
    {
        $c = $this->content();
        $this->json(['ok' => true, 'sections' => $c->sections($p['pid']), 'page' => $c->page($p['pid'])]);
    }

    private function apiSectionAdd(array $p): void
    {
        $this->ok('Section added. Edit it to add your content.', ['id' => $this->content()->addSection($p['pid'], (string) ($_POST['section_type'] ?? ''), (int) ($_POST['after'] ?? 0))]);
    }

    private function apiSectionsReorder(array $p): void
    {
        $this->content()->reorderSections($p['pid'], (array) ($_POST['ids'] ?? []));
        $this->ok('Section order saved.');
    }

    private function apiSection(array $p): void
    {
        $this->json(['ok' => true] + $this->content()->sectionForEditor($p['sid']));
    }

    private function apiSectionUpdate(array $p): void
    {
        $data = $this->content()->updateSection($p['sid'], $this->jsonField('data'), $this->jsonField('style'), (string) ($_POST['label'] ?? ''));
        $this->ok('Section saved.', ['data' => $data]);
    }

    private function apiSectionToggle(array $p): void
    {
        $this->ok($this->content()->toggleSection($p['sid']) ? 'Section enabled.' : 'Section hidden. It stays here and can be enabled again.');
    }

    private function apiSectionDuplicate(array $p): void
    {
        $this->ok('Section duplicated.', ['id' => $this->content()->duplicateSection($p['sid'])]);
    }

    private function apiSectionDelete(array $p): void
    {
        $this->content()->deleteSection($p['sid']);
        $this->ok('Section deleted.');
    }

    private function apiMedia(): void
    {
        $this->json($this->media()->list((string) ($_GET['folder'] ?? ''), trim((string) ($_GET['q'] ?? '')), (int) ($_GET['page'] ?? 1), (int) ($_GET['per_page'] ?? 24)));
    }

    private function apiMediaUpload(): void
    {
        [$saved, $skipped] = $this->media()->upload($this->files('files'), (string) ($_POST['folder'] ?? 'general'));
        $this->ok(count($saved) . ' image(s) uploaded.' . ($skipped ? ' Skipped: ' . implode(' ', $skipped) : ''), ['items' => $saved, 'skipped' => $skipped]);
    }

    private function apiMediaUpdate(array $p): void
    {
        $this->media()->update($p['mid'], (string) ($_POST['alt_text'] ?? ''), (string) ($_POST['folder'] ?? ''));
        $this->ok('Image details saved.');
    }

    private function apiMediaReplace(array $p): void
    {
        $f = $_FILES['file'] ?? null;
        $this->ok('Image replaced everywhere it is used.', ['url' => $this->media()->replace($p['mid'], is_array($f) && ($f['error'] ?? 4) !== UPLOAD_ERR_NO_FILE ? $f : null)]);
    }

    private function apiMediaDelete(array $p): void
    {
        $this->media()->delete($p['mid'], ($_POST['force'] ?? '') === '1');
        $this->ok('Image deleted.');
    }

    private function apiSettings(): void
    {
        $s = $this->settings();
        $all = $s->all();
        $theme = $s->theme();
        $u = fn ($p) => is_string($p) && str_starts_with($p, 'uploads/') ? $this->base . '/' . $p : null;
        $this->json(['ok' => true, 'theme' => $theme + ['logo_url' => $u($theme['logo'] ?? ''), 'favicon_url' => $u($theme['favicon'] ?? '')], 'site' => (array) ($all['site'] ?? []), 'seo' => (array) ($all['seo'] ?? [])]);
    }

    private function apiTheme(): void
    {
        $this->settings()->saveTheme($_POST);
        $this->ok('Theme saved. The website shows the change immediately.');
    }

    private function apiDetails(): void
    {
        $this->settings()->saveDetails($_POST);
        $this->ok('Site details saved.');
    }

    private function apiNavigation(): void
    {
        $c = $this->content();
        $pages = array_map(static fn ($p) => ['id' => (int) $p['id'], 'title' => $p['title'], 'slug' => $p['slug'], 'status' => $p['status'], 'show_in_menu' => (int) $p['show_in_menu'], 'show_in_footer' => (int) $p['show_in_footer']], $c->pages());
        $this->json(['ok' => true, 'header' => $c->menu('header'), 'footer' => $c->menu('footer'), 'pages' => $pages, 'payments' => $this->payments()->settings()['enabled']]);
    }

    private function apiNavigationSave(): void
    {
        $location = (string) ($_POST['location'] ?? '');
        $items = json_decode((string) ($_POST['items'] ?? '[]'), true);
        $this->content()->saveMenu($location, is_array($items) ? $items : []);
        $this->ok($items ? 'Menu saved.' : 'Menu reset to automatic (built from your pages).');
    }

    // ================================================================== enquiries

    private function apiEnquiries(): void
    {
        $t = $this->db->t('contact_submissions');
        $where = ['1=1'];
        $params = [];
        $status = (string) ($_GET['status'] ?? '');
        if (in_array($status, ['new', 'read', 'replied', 'spam'], true)) {
            $where[] = 'status = :status';
            $params['status'] = $status;
        } else {
            $where[] = "status <> 'spam'";
        }
        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(name LIKE :q1 OR email LIKE :q2 OR phone LIKE :q3 OR subject LIKE :q4 OR message LIKE :q5)';
            foreach (['q1', 'q2', 'q3', 'q4', 'q5'] as $k) {
                $params[$k] = '%' . addcslashes($q, '%_\\') . '%';
            }
        }
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 20)));
        $total = (int) $this->db->value("SELECT COUNT(*) FROM $t WHERE " . implode(' AND ', $where), $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $pages);
        $rows = $this->db->fetchAll("SELECT id, name, email, phone, subject, LEFT(message, 140) AS excerpt, status, email_sent, page_slug, created_at FROM $t WHERE " . implode(' AND ', $where) . " ORDER BY id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage), $params);
        $this->json(['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage]);
    }

    private function apiEnquiry(array $p): void
    {
        $e = $this->db->fetch('SELECT * FROM ' . $this->db->t('contact_submissions') . ' WHERE id = ?', [$p['eid']]);
        if (!$e) {
            throw new NotFound('Enquiry not found.');
        }
        if ($e['status'] === 'new') {
            $this->db->update('contact_submissions', ['status' => 'read'], (int) $e['id']);
            $e['status'] = 'read';
        }
        $this->json(['ok' => true, 'enquiry' => $e]);
    }

    private function apiEnquiryStatus(array $p): void
    {
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, ['new', 'read', 'replied', 'spam'], true)) {
            $this->fail('Unknown status.');
        }
        if (!$this->db->update('contact_submissions', ['status' => $status], $p['eid']) && !$this->db->value('SELECT id FROM ' . $this->db->t('contact_submissions') . ' WHERE id = ?', [$p['eid']])) {
            throw new NotFound('Enquiry not found.');
        }
        $this->ok('Marked as ' . $status . '.');
    }

    private function apiEnquiryDelete(array $p): void
    {
        $this->db->delete('contact_submissions', $p['eid']);
        $this->ok('Enquiry deleted.');
    }

    /** CSV download of enquiries (spreadsheet-safe: formula characters are escaped). */
    private function apiEnquiriesExport(): void
    {
        $rows = $this->db->fetchAll('SELECT id, created_at, name, email, phone, subject, message, status, page_slug FROM ' . $this->db->t('contact_submissions') . " WHERE status <> 'spam' ORDER BY id DESC");
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="enquiries-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID', 'Date', 'Name', 'Email', 'Phone', 'Subject', 'Message', 'Status', 'Page']);
        foreach ($rows as $r) {
            fputcsv($out, array_map(static fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : (string) $v, array_values($r)));
        }
        fclose($out);
        exit;
    }

    // ================================================================== payments

    private function paymentsPage(): void
    {
        $svc = $this->payments();
        $s = $svc->settings();
        $this->render('payments', ['title' => 'Payments', 'active' => 'payments', 'settings' => $s, 'ready' => $svc->ready(),
            'gatewayName' => $s['gateway'] !== '' ? Gateways::ALL[$s['gateway']]::name() : '', 'payUrl' => $this->payUrl(), 'scripts' => ['js/admin-payments.js']]);
    }

    private function paymentSettingsPage(): void
    {
        $svc = $this->payments();
        $webhooks = [];
        foreach (array_keys(Gateways::ALL) as $g) {
            $webhooks[$g] = $this->origin() . $this->payUrl('webhook/' . $g);
        }
        $this->render('payment-settings', ['title' => 'Payment settings', 'active' => 'payments', 'settings' => $svc->publicSettings(), 'ready' => $svc->ready(),
            'gateways' => Gateways::definitions(), 'webhooks' => $webhooks, 'payUrl' => $this->payUrl(), 'https' => str_starts_with($this->origin(), 'https://'),
            'scripts' => ['js/admin-payments.js']]);
    }

    private function savePaymentSettings(): void
    {
        $svc = $this->payments();
        $svc->saveSettings($_POST);
        $s = $svc->settings();
        $msg = !$s['enabled'] ? 'Payment settings saved. Online payments are switched off.'
            : 'Payment settings saved. ' . Gateways::ALL[$s['gateway']]::name() . ' is accepting ' . ($s['mode'] === 'live' ? 'LIVE payments.' : 'test payments (no real money).');
        $this->ok($msg);
    }

    /** Check the saved credentials of the chosen gateway and mode against the gateway. */
    private function testPaymentConnection(): void
    {
        $gateway = (string) ($_POST['gateway'] ?? '');
        $mode = ($_POST['mode'] ?? 'test') === 'live' ? 'live' : 'test';
        if (!isset(Gateways::ALL[$gateway])) {
            $this->fail('Choose a payment gateway.');
        }
        try {
            $msg = $this->payments()->gateway($gateway, $mode)->testConnection();
        } catch (GatewayError $e) {
            $this->fail($e->getMessage());
        }
        $this->ok($msg);
    }

    private function apiPayments(): void
    {
        $r = $this->payments()->list($_GET, (int) ($_GET['page'] ?? 1), (int) ($_GET['per_page'] ?? 20));
        foreach ($r['rows'] as &$row) {
            $row['amount_text'] = '₹' . PaymentService::inr((int) $row['amount']);
        }
        $r['paid_total_text'] = '₹' . PaymentService::inr((int) $r['paid_total']);
        $this->json($r);
    }

    private function apiPayment(array $p): void
    {
        $svc = $this->payments();
        $tx = $svc->find($p['tid']) ?? throw new NotFound('Payment not found.');
        unset($tx['ip']);
        $tx['amount_text'] = '₹' . PaymentService::inr((int) $tx['amount']);
        $tx['gateway_name'] = isset(Gateways::ALL[$tx['gateway']]) ? Gateways::ALL[$tx['gateway']]::name() : $tx['gateway'];
        $tx['receipt_url'] = $this->payUrl('receipt/' . $tx['reference']) . '?t=' . $svc->receiptToken($tx['reference'], (string) $this->app['key']);
        $this->json(['ok' => true, 'payment' => $tx, 'events' => $svc->events((int) $tx['id'])]);
    }

    /** Ask the gateway for the current state of a payment (e.g. when a customer says money was deducted). */
    private function apiPaymentRecheck(array $p): void
    {
        $svc = $this->payments();
        $tx = $svc->find($p['tid']) ?? throw new NotFound('Payment not found.');
        if ($tx['status'] === 'paid') {
            $this->ok('This payment is already confirmed as paid.');
        }
        try {
            [$tx] = $svc->apply($tx, $svc->gateway($tx['gateway'], $tx['mode'])->status($tx), 'recheck');
        } catch (GatewayError $e) {
            $this->fail($e->getMessage());
        }
        $labels = ['paid' => 'Paid ✓ — the gateway confirms the payment.', 'pending' => 'Still pending at the gateway.', 'failed' => 'Not paid: the gateway reports the payment failed.', 'cancelled' => 'Not paid (cancelled).', 'created' => 'Not paid: the customer did not reach the gateway.'];
        $this->ok($labels[$tx['status']] ?? 'Status updated.', ['status' => $tx['status']]);
    }

    private function apiPaymentsExport(): void
    {
        $rows = $this->payments()->export($_GET);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="payments-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Reference', 'Created', 'Paid at', 'Status', 'Mode', 'Gateway', 'For', 'Amount (INR)', 'Name', 'Email', 'Phone', 'Note', 'Gateway order', 'Gateway payment ID', 'Method', 'Message']);
        foreach ($rows as $r) {
            $r['amount'] = PaymentService::inr((int) $r['amount']);
            $r['amount'] = str_replace(',', '', $r['amount']);
            fputcsv($out, array_map(static fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : (string) $v, array_values($r)));
        }
        fclose($out);
        exit;
    }

    private function payments(): PaymentService
    {
        $cfgFile = $this->root . '/config/payment.php';
        $seed = is_file($cfgFile) ? (array) (require $cfgFile) : [];
        $emu = is_string($this->app['payment_emulator'] ?? null) && $this->app['payment_emulator'] !== '' ? $this->app['payment_emulator'] : null;
        return $this->cache['payments'] ??= new PaymentService($this->db, $this->crypto(), $seed, $emu);
    }

    public function payUrl(string $sub = ''): string
    {
        return $this->base . (!empty($this->app['clean_urls']) ? '/pay' : '/index.php/pay') . ($sub !== '' ? '/' . $sub : '');
    }

    private function origin(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443' || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        return ($https ? 'https' : 'http') . '://' . (preg_match('/^[a-z0-9.\-]+(:\d{1,5})?$/i', $host) ? $host : 'localhost');
    }

    // ================================================================== services

    private function manifest(): array
    {
        return $this->cache['manifest'] ??= (json_decode((string) file_get_contents($this->root . '/theme/manifest.json'), true) ?: []);
    }

    private function types(): SectionTypes
    {
        return $this->cache['types'] ??= new SectionTypes($this->root . '/core/section-types', $this->root . '/theme', $this->manifest());
    }

    private function imageOk(): callable
    {
        return $this->cache['imageOk'] ??= MediaService::imageValidator($this->db, $this->root . '/theme');
    }

    private function content(): ContentService
    {
        return $this->cache['content'] ??= new ContentService($this->db, $this->types(), $this->manifest(), $this->richtext(), $this->imageOk());
    }

    private function media(): MediaService
    {
        $schemas = fn () => $this->content()->schemas();
        return $this->cache['media'] ??= new MediaService($this->db, $this->root, fn (string $p): string => $this->base . '/' . $p, static fn (string $t): string => $schemas()[$t]['label'] ?? $t);
    }

    private function settings(): SettingsService
    {
        return $this->cache['settings'] ??= new SettingsService($this->db, $this->manifest(), $this->root . '/assets/fonts', $this->imageOk());
    }

    private function crypto(): Crypto
    {
        return new Crypto((string) $this->app['key']);
    }

    private function mailer(): Mailer
    {
        return Mailer::fromSettings($this->settings()->all(), $this->crypto(), (string) ($_SERVER['HTTP_HOST'] ?? ''));
    }

    private function richtext(): callable
    {
        $cacheDir = $this->root . '/storage/cache/htmlpurifier';
        return static function (string $html) use ($cacheDir): string {
            static $p = null;
            if (trim($html) === '') {
                return '';
            }
            if ($p === null) {
                if (!class_exists(\HTMLPurifier::class)) {
                    return htmlspecialchars(strip_tags($html), ENT_QUOTES, 'UTF-8'); // safe fallback without the library
                }
                $c = \HTMLPurifier_Config::createDefault();
                if (!is_dir($cacheDir)) {
                    @mkdir($cacheDir, 0755, true);
                }
                $c->set('Cache.SerializerPath', is_writable($cacheDir) ? $cacheDir : null);
                $c->set('HTML.Allowed', 'p,br,strong,b,em,i,u,s,a[href|title|target|rel],ul,ol,li,h2,h3,h4,blockquote,hr,table,thead,tbody,tr,th,td,span');
                $c->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
                $c->set('Attr.AllowedFrameTargets', ['_blank']);
                $c->set('HTML.TargetNoopener', true);
                $c->set('AutoFormat.RemoveEmpty', true);
                $p = new \HTMLPurifier($c);
            }
            return $p->purify($html);
        };
    }

    /** Variables for the shared editor views and the workspace JS config. */
    private function editorVars(): array
    {
        $m = $this->manifest();
        return [
            'editable' => true,
            'pageTypes' => $this->content()->pageTypes(),
            'schemas' => $this->content()->schemas(),
            'config' => [
                'id' => 0, 'editable' => true, 'schemas' => $this->content()->schemas(), 'mediaFolders' => MediaService::FOLDERS,
                'themeDefaults' => (array) ($m['theme']['defaults'] ?? []), 'pageTypes' => $this->content()->pageTypes(),
                'urls' => [
                    'api' => $this->url('api'), 'media' => $this->base . '/uploads/', 'theme' => $this->base . '/theme/assets/',
                    'preview_home' => $this->base . '/', 'preview_page' => !empty($this->app['clean_urls']) ? $this->base . '/{slug}' : $this->base . '/index.php?page={slug}',
                    'tinymce' => $this->asset('vendor/tinymce'),
                ],
            ],
        ];
    }

    // ================================================================== plumbing

    public function url(string $path = ''): string
    {
        return $this->base . '/admin' . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }

    public function asset(string $path): string
    {
        $file = $this->root . '/assets/admin/' . $path;
        return $this->base . '/assets/admin/' . $path . (is_file($file) ? '?v=' . substr(md5((string) filemtime($file)), 0, 8) : '');
    }

    private function render(string $view, array $data, string $layout = 'main'): void
    {
        $user = $this->auth->user();
        $data += ['admin' => $this, 'user' => $user, 'siteName' => (string) ($this->settings()->all()['site']['name'] ?? $this->app['name'] ?? 'Website'),
            'base' => $this->base, 'csrf' => $this->auth->csrf(), 'scripts' => [], 'active' => '',
            'paymentsOn' => $user ? $this->payments()->settings()['enabled'] : false,
            'newEnquiries' => $user ? (int) $this->db->value('SELECT COUNT(*) FROM ' . $this->db->t('contact_submissions') . " WHERE status = 'new'") : 0];
        $content = $this->capture(__DIR__ . '/views/' . $view . '.php', $data);
        header('Content-Type: text/html; charset=utf-8');
        echo $this->capture(__DIR__ . '/views/layout-' . $layout . '.php', $data + ['content' => $content]);
    }

    private function capture(string $file, array $vars): string
    {
        ob_start();
        (static function (string $__f, array $__v): void {
            extract($__v, EXTR_SKIP);
            include $__f;
        })($file, $vars);
        return (string) ob_get_clean();
    }

    private function jsonField(string $key): array
    {
        $v = json_decode((string) ($_POST[$key] ?? ''), true);
        if (!is_array($v)) {
            $this->fail('Invalid data.');
        }
        return $v;
    }

    private function files(string $key): array
    {
        $f = $_FILES[$key] ?? null;
        if (!is_array($f) || !is_array($f['name'] ?? null)) {
            return [];
        }
        $out = [];
        foreach ($f['name'] as $i => $name) {
            if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $out[] = ['name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
            }
        }
        return $out;
    }

    private function absolute(string $path): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $host = preg_replace('/[^a-z0-9.\-:\[\]]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        return ($https ? 'https' : 'http') . '://' . $host . $path;
    }

    private function ip(): string
    {
        return filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: '0.0.0.0';
    }

    private function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    private function log(string $msg): void
    {
        $dir = $this->root . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($dir . '/site-' . date('Y-m-d') . '.log', '[' . date('Y-m-d H:i:s') . "] WARNING: $msg\n", FILE_APPEND | LOCK_EX);
    }

    private function redirect(string $path): never
    {
        header('Location: ' . $this->url($path));
        exit;
    }

    private function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function ok(string $message, array $extra = []): never
    {
        $this->json(['ok' => true, 'message' => $message] + $extra);
    }

    private function fail(string $message, array $errors = [], int $status = 422): never
    {
        if (!$this->isAjax() && $status === 403 && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }
        $this->json(['ok' => false, 'message' => $message, 'errors' => (object) $errors], $status);
    }
}
