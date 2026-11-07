<?php
declare(strict_types=1);

/**
 * Phase 4 end-to-end test: a generated website's own admin panel (/admin).
 *
 *   php tests/phase4_http_test.php <builder-url> <email> <password> <website-id> <mail-dir> [smtp-host:port] [site-db-dsn]
 *
 * website-id: an already generated website (status generated/testing/ready).
 * mail-dir:   folder where a local SMTP sink writes received messages (one file per email), e.g.
 *             python3 tools/mailsink.py /tmp/mailsink   (listens on 127.0.0.1:2525)
 * site-db-dsn: optional "mysql:host=…;dbname=…|user|pass" — clears this IP's old login attempts
 *             first, so the test can be re-run within the 15-minute lockout window.
 */

require __DIR__ . '/support.php';

[$_, $base, $email, $password, $wid, $mailDir, $smtp, $dsn] = $argv + [null, 'http://127.0.0.1:8080', 'info@channelsoftech.com', '', '2', '/tmp/mailsink', '127.0.0.1:2525', ''];
$base = rtrim((string) $base, '/');
$wid = (int) $wid;
[$smtpHost, $smtpPort] = explode(':', (string) $smtp) + ['127.0.0.1', '2525'];
$sfx = substr(bin2hex(random_bytes(3)), 0, 6);
$pass = $fail = 0;
$tmp = sys_get_temp_dir();
$started = microtime(true);

/** Decoded text of every email received for $rcpt since the test started. */
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

$b = new Client($base);
check('Builder login', ($b->login($email, $password)['json']['ok'] ?? false) === true);
$set = $b->req('GET', 'settings', [], false)['body'];
preg_match('/font-monospace" value="([A-Za-z0-9]+)"/', $set, $m);
$qa = 'qa:' . ($m[1] ?? '');

echo "\n== Builder: website admin login ==\n";
$r = $b->req('GET', "websites/$wid/admin-account");
check('Admin account card data (URL, generated)', ($r['json']['ok'] ?? false) && ($r['json']['generated'] ?? false) === true && str_ends_with((string) ($r['json']['url'] ?? ''), '/admin/'), $r['body']);
$adminUrl = rtrim((string) ($r['json']['url'] ?? ''), '/');
$siteUrl = substr($adminUrl, 0, -strlen('admin'));
check('Invalid owner email rejected', isset($b->req('POST', "websites/$wid/admin-account", ['email' => 'not-an-email'])['json']['errors']['email']));
$owner = "owner$sfx@example.com";
$r = $b->req('POST', "websites/$wid/admin-account", ['email' => $owner, 'name' => 'Asha Owner']);
$ownerPw = (string) ($r['json']['password'] ?? '');
check('Owner login created; password shown once (16 chars + dashes, letters and digits)', ($r['json']['ok'] ?? false) && preg_match('/^[A-Za-z2-9]{4}(-[A-Za-z2-9]{4}){3}$/', $ownerPw) === 1 && preg_match('/\d/', $ownerPw) === 1, $r['body']);
$users = $b->req('GET', "websites/$wid/admin-account")['json']['users'] ?? [];
check('Owner listed (role owner, never signed in) and no password exposed', (bool) array_filter($users, fn ($u) => $u['email'] === $owner && $u['role'] === 'owner' && $u['last_login_at'] === null) && !str_contains(json_encode($users), 'password'));

if ($dsn !== '') {
    [$d, $du, $dp] = explode('|', $dsn) + ['', '', ''];
    $pdo = new PDO($d, $du, $dp);
    $prefix = (string) $pdo->query('SELECT table_prefix FROM wb_websites WHERE id = ' . $wid)->fetchColumn();
    $pdo->exec("DELETE FROM {$prefix}login_attempts WHERE ip IN ('127.0.0.1', '::1')");
    // The contact form allows 5 posts per IP per 10 minutes; age earlier local test posts out of that window.
    $pdo->exec("UPDATE {$prefix}contact_submissions SET created_at = created_at - INTERVAL 15 MINUTE WHERE ip IN ('127.0.0.1', '::1') AND created_at > NOW() - INTERVAL 15 MINUTE");
}

echo "\n== Access control ==\n";
$guest = new Client($adminUrl);
$guest->basic = $qa;
$r = $guest->req('GET', '', [], false);
$cookie = implode(' ', $r['headers']['set-cookie'] ?? []);
check('Admin home redirects guests to the login page', $r['code'] === 302 && str_ends_with($r['location'], '/admin/login'), $r['code'] . ' ' . $r['location']);
check('Admin API returns 401 JSON for guests', $guest->req('GET', 'api/pages')['code'] === 401);
$r = $guest->req('GET', 'login', [], false);
check('Login page loads', $r['code'] === 200 && str_contains($r['body'], 'name="password"'));
$csp = implode(' ', $r['headers']['content-security-policy'] ?? []);
check('Security headers: CSP without unsafe scripts, no-store, frame protection, noindex',
    str_contains($csp, "script-src 'self'") && !str_contains($csp, "script-src 'self' 'unsafe") && str_contains(implode(',', $r['headers']['cache-control'] ?? []), 'no-store')
    && ($r['headers']['x-frame-options'][0] ?? '') === 'SAMEORIGIN' && str_contains($r['headers']['x-robots-tag'][0] ?? '', 'noindex'), $csp);
check('Session cookie: own name per website, HttpOnly, SameSite=Strict, scoped to /admin', preg_match('/wbadm_\w+=/', $cookie) === 1 && stripos($cookie, 'HttpOnly') !== false && stripos($cookie, 'SameSite=Strict') !== false && str_contains($cookie, '/admin'), $cookie);
$csrf = $guest->csrf;
$guest->csrf = '';
check('Login without CSRF token refused (403)', $guest->req('POST', 'login', ['email' => $owner, 'password' => $ownerPw])['code'] === 403);
$guest->csrf = $csrf;
$r = $guest->req('POST', 'login', ['email' => $owner, 'password' => 'wrong-password-1']);
check('Wrong password refused with a generic message', $r['code'] === 422 && ($r['json']['message'] ?? '') === 'Invalid email or password.');
check('Unknown email gets the same message', ($guest->req('POST', 'login', ['email' => "nobody$sfx@example.com", 'password' => 'whatever123'])['json']['message'] ?? '') === 'Invalid email or password.');
preg_match('/wbadm_\w+=([^;]+)/', $cookie, $m1);
$sidBefore = $m1[1] ?? '';
$r = $guest->req('POST', 'login', ['email' => strtoupper($owner), 'password' => $ownerPw]);
check('Owner signs in (email case-insensitive)', ($r['json']['ok'] ?? false) === true && str_ends_with((string) ($r['json']['redirect'] ?? ''), '/admin'), $r['body']);
preg_match('/wbadm_\w+=([^;]+)/', implode(' ', $r['headers']['set-cookie'] ?? []), $m2);
check('Session ID regenerated on login', ($m2[1] ?? '') !== '' && ($m2[1] ?? '') !== $sidBefore);
$o = $guest;
$dash = $o->req('GET', '', [], false);
check('Dashboard loads', $dash['code'] === 200 && str_contains($dash['body'], 'Asha Owner') && str_contains($dash['body'], 'Dashboard'));
foreach (['content' => 'id="pageList"', 'media' => 'id="mediaManager"', 'appearance' => 'id="themeForm"', 'navigation' => 'nav-editor', 'enquiries' => 'id="enqTable"', 'profile' => 'current_password', 'settings' => 'id="mailForm"'] as $page => $marker) {
    $r = $o->req('GET', $page, [], false);
    check("Admin page /admin/$page", $r['code'] === 200 && str_contains($r['body'], $marker) && !preg_match('/(Fatal error|Warning:|Notice:|Deprecated:)/', $r['body']), (string) $r['code']);
}
preg_match_all('#(?:src|href)="([^"]+/assets/admin/[^"]+)"#', $o->req('GET', 'content', [], false)['body'], $am);
$assetsOk = count($am[1]) >= 6;
$host = (string) preg_replace('#^(https?://[^/]+).*$#', '$1', $siteUrl);
foreach ($am[1] as $u) {
    $ar = $o->req('GET', $host . html_entity_decode($u), [], false);
    $assetsOk = $assetsOk && $ar['code'] === 200;
}
check('Admin scripts and styles load from the website itself (' . count($am[1]) . ' files, no CDN)', $assetsOk);
$noCsrf = clone $o;
$noCsrf->csrf = '';
check('API write without CSRF token refused (403)', $noCsrf->req('POST', 'api/pages', ['title' => 'x'])['code'] === 403);
foreach (['vendor/autoload.php', 'vendor/phpmailer/phpmailer/src/PHPMailer.php', 'core/Admin/App.php', 'core/Admin/views/login.php', 'core/editor/theme-form.php', 'config/app.php'] as $priv) {
    $r = $o->req('GET', $siteUrl . $priv, [], false);
    check("Private path blocked: /$priv", in_array($r['code'], [403, 404], true), (string) $r['code']);
}

echo "\n== Pages & sections from the website admin ==\n";
$r = $o->req('POST', 'api/pages', ['title' => "Offers $sfx", 'slug' => "offers-$sfx", 'page_type' => 'generic', 'show_in_menu' => '1']);
$pid = (int) ($r['json']['id'] ?? 0);
check('Add page', ($r['json']['ok'] ?? false) && $pid > 0, $r['body']);
check('Reserved URL "admin" rejected', isset($o->req('POST', 'api/pages', ['title' => 'A', 'slug' => 'admin', 'page_type' => 'generic'])['json']['errors']['slug']));
$secs = $o->req('GET', "api/pages/$pid/sections")['json']['sections'] ?? [];
check('New page has its default sections', count($secs) >= 2, json_encode(array_column($secs, 'section_type')));
$banner = $secs[0] ?? ['id' => 0];
$ed = $o->req('GET', "api/sections/{$banner['id']}")['json'];
$data = $ed['section']['data'] ?? [];
$data['heading'] = "Festival offers $sfx <script>x()</script>";
$r = $o->req('POST', "api/sections/{$banner['id']}", ['data' => json_encode($data), 'style' => '{}']);
check('Edit section content', ($r['json']['ok'] ?? false) === true, $r['body']);
$front = $o->req('GET', $siteUrl . "offers-$sfx", [], false);
check('Change is live on the website immediately (tags stripped)', $front['code'] === 200 && str_contains($front['body'], "Festival offers $sfx x()") && !str_contains($front['body'], '<script>x()'));
$r = $o->req('POST', "api/pages/$pid/sections", ['section_type' => 'faq']);
$faq = (int) ($r['json']['id'] ?? 0);
check('Add a section', ($r['json']['ok'] ?? false) && $faq > 0, $r['body']);
check('Duplicate a section', ($o->req('POST', "api/sections/$faq/duplicate")['json']['ok'] ?? false) === true);
$ids = array_column($o->req('GET', "api/pages/$pid/sections")['json']['sections'], 'id');
check('Reorder sections', ($o->req('POST', "api/pages/$pid/sections/reorder", ['ids' => array_reverse($ids)])['json']['ok'] ?? false) === true
    && (int) $o->req('GET', "api/pages/$pid/sections")['json']['sections'][0]['id'] === (int) end($ids));
check('Hide a section', ($o->req('POST', "api/sections/$faq/toggle")['json']['ok'] ?? false) === true);
check('Delete a section', ($o->req('POST', "api/sections/$faq/delete")['json']['ok'] ?? false) === true);

echo "\n== Media ==\n";
$r = $o->req('POST', 'api/media', ['folder' => 'gallery', 'files[0]' => new CURLFile(makeImageFile("$tmp/p4$sfx.jpg", 900, 600), 'image/jpeg', "shop-$sfx.jpg")]);
$item = $r['json']['items'][0] ?? null;
check('Upload image', ($r['json']['ok'] ?? false) && $item !== null, $r['body']);
check('Uploaded image served from the website', $item && $o->req('GET', $siteUrl . $item['path'], [], false)['code'] === 200);
file_put_contents("$tmp/p4evil$sfx.jpg", "\xFF\xD8\xFF<?php echo 'pwned'; ?>");
$r = $o->req('POST', 'api/media', ['files[0]' => new CURLFile("$tmp/p4evil$sfx.jpg", 'image/jpeg', 'photo.jpg')]);
check('Fake image (PHP inside) rejected', empty($r['json']['items']) && str_contains($r['body'], 'photo.jpg'), $r['body']);
check('Edit alt text', $item && ($o->req('POST', "api/media/{$item['id']}", ['alt_text' => "Shop front $sfx", 'folder' => 'gallery'])['json']['ok'] ?? false) === true);
check('Media list searchable', $item && in_array($item['id'], array_column($o->req('GET', 'api/media', ['q' => "shop-$sfx"])['json']['rows'] ?? [], 'id'), true));
check('Delete image', $item && ($o->req('POST', "api/media/{$item['id']}/delete")['json']['ok'] ?? false) === true);

echo "\n== Appearance ==\n";
$st = $o->req('GET', 'api/settings')['json'];
$theme = $st['theme'] ?? [];
$r = $o->req('POST', 'api/settings/theme', ['primary' => '#7A1F3D'] + array_map(fn ($v) => is_scalar($v) ? (string) $v : '', array_intersect_key($theme, array_flip(['secondary', 'bg', 'text', 'heading_font', 'body_font', 'button_style', 'radius', 'header_style', 'footer_style', 'logo', 'favicon']))));
check('Save theme colours', ($r['json']['ok'] ?? false) === true, $r['body']);
check('Theme change visible on the website', str_contains(strtolower($o->req('GET', $siteUrl, [], false)['body']), '--wb-primary:#7a1f3d'));
$o->req('POST', 'api/settings/theme', ['primary' => (string) ($theme['primary'] ?? '#2E3192')] + array_map(fn ($v) => is_scalar($v) ? (string) $v : '', array_intersect_key($theme, array_flip(['secondary', 'bg', 'text', 'heading_font', 'body_font', 'button_style', 'radius', 'header_style', 'footer_style', 'logo', 'favicon']))));
$site = $st['site'] ?? [];
$r = $o->req('POST', 'api/settings/details', ['name' => (string) ($site['name'] ?? 'Site'), 'tagline' => "Since 1998 $sfx", 'email' => 'bad'] + array_map('strval', array_filter($site, 'is_scalar')));
check('Invalid site email rejected', isset($r['json']['errors']['email']) || ($r['json']['ok'] ?? true) === false);

echo "\n== Navigation ==\n";
$nav = $o->req('GET', 'api/navigation')['json'];
check('Navigation data (automatic menus, page list)', ($nav['ok'] ?? false) && ($nav['header'] ?? null) === [] && count($nav['pages'] ?? []) >= 7, json_encode($nav['header'] ?? null));
$contactPage = array_values(array_filter($nav['pages'], fn ($p) => $p['slug'] === 'contact'))[0] ?? ['id' => 0];
$items = [['page_id' => $pid, 'label' => "Deals $sfx"], ['page_id' => $contactPage['id'], 'label' => 'Reach us'], ['url' => 'https://example.org/catalogue', 'label' => "Catalogue $sfx", 'new_tab' => 1]];
check('javascript: link refused in menus', ($o->req('POST', 'api/navigation', ['location' => 'header', 'items' => json_encode([['url' => 'javascript:alert(1)', 'label' => 'x']])])['json']['ok'] ?? true) === false);
check('Save custom header menu', ($o->req('POST', 'api/navigation', ['location' => 'header', 'items' => json_encode($items)])['json']['ok'] ?? false) === true);
$home = $o->req('GET', $siteUrl, [], false)['body'];
preg_match('#<header.*?</header>#s', $home, $hm);
$hdr = $hm[0] ?? $home;
check('Custom menu rendered in order with custom labels', ($p1 = strpos($hdr, "Deals $sfx")) !== false && ($p2 = strpos($hdr, 'Reach us')) !== false && $p1 < $p2);
check('External link opens in a new tab safely', preg_match('#href="https://example.org/catalogue"[^>]*target="_blank"[^>]*rel="noopener#', $hdr) === 1 || preg_match('#target="_blank"[^>]*href="https://example.org/catalogue"#', $hdr) === 1, substr($hdr, 0, 600));
check('Pages not in the custom menu are left out', !str_contains($hdr, '>Gallery<'));
check('Save footer menu', ($o->req('POST', 'api/navigation', ['location' => 'footer', 'items' => json_encode([['page_id' => $contactPage['id'], 'label' => "Footer contact $sfx"]])])['json']['ok'] ?? false) === true
    && str_contains($o->req('GET', $siteUrl, [], false)['body'], "Footer contact $sfx"));
check('Deactivated page drops out of the custom menu', ($o->req('POST', "api/pages/$pid/toggle")['json']['ok'] ?? false) && !str_contains($o->req('GET', $siteUrl, [], false)['body'], "Deals $sfx"));
$o->req('POST', "api/pages/$pid/toggle");
check('Reset menus to automatic', ($o->req('POST', 'api/navigation', ['location' => 'header', 'items' => '[]'])['json']['ok'] ?? false) && ($o->req('POST', 'api/navigation', ['location' => 'footer', 'items' => '[]'])['json']['ok'] ?? false)
    && str_contains($o->req('GET', $siteUrl, [], false)['body'], '>Gallery<'));
check('Deleting a page removes its menu links', ($o->req('POST', 'api/navigation', ['location' => 'header', 'items' => json_encode($items)])['json']['ok'] ?? false)
    && ($o->req('POST', "api/pages/$pid/delete")['json']['ok'] ?? false) && count($o->req('GET', 'api/navigation')['json']['header'] ?? []) === 2);
$o->req('POST', 'api/navigation', ['location' => 'header', 'items' => '[]']);

echo "\n== Email settings ==\n";
$notify = "notify$sfx@example.com";
$r = $o->req('POST', 'settings/mail', ['notify_to' => 'bad-address', 'smtp_enabled' => '1', 'smtp_host' => $smtpHost, 'smtp_port' => $smtpPort]);
check('Invalid notification address rejected', isset($r['json']['errors']['notify_to']));
$r = $o->req('POST', 'settings/mail', ['notify_to' => $notify, 'from_email' => "no-reply$sfx@example.com", 'from_name' => 'Website', 'smtp_enabled' => '1', 'smtp_host' => $smtpHost, 'smtp_port' => $smtpPort, 'smtp_encryption' => 'none', 'smtp_username' => '', 'smtp_password' => "S3cret-$sfx"]);
check('Save SMTP settings', ($r['json']['ok'] ?? false) === true, $r['body']);
$sp = $o->req('GET', 'settings', [], false)['body'];
check('SMTP password not shown back (stored encrypted)', !str_contains($sp, "S3cret-$sfx") && str_contains($sp, 'Saved — leave empty to keep'));
$r = $o->req('POST', 'settings/mail-test');
check('Send test email', ($r['json']['ok'] ?? false) === true, $r['body']);
check('Test email delivered to the signed-in owner', (bool) array_filter(mails($mailDir, $owner, $started), fn ($t) => str_contains($t, 'It works!')));

echo "\n== Enquiries ==\n";
$fc = new Client($siteUrl);
$fc->basic = $qa;
$contact = $fc->req('GET', 'contact', [], false)['body'];
preg_match('/name="_t" value="([^"]+)"/', $contact, $t);
preg_match('/<form class="mrd-form" method="post" action="([^"]+)"/', $contact, $act);
$action = $host . html_entity_decode($act[1] ?? '');
sleep(3);
$r = $fc->req('POST', $action, ['_t' => $t[1] ?? '', '_back' => 'contact', 'name' => "Ravi $sfx", 'email' => "ravi$sfx@example.com", 'phone' => '+91 98470 11111', 'subject' => "Bulk order $sfx", 'message' => "Need 200 units.\nPlease call."], false);
check('Visitor submits the contact form', $r['code'] === 303 && str_contains($r['location'], 'status=sent'), $r['code'] . ' ' . $r['location']);
$r = $fc->req('POST', $action, ['_t' => $t[1] ?? '', '_back' => 'contact', 'name' => '=HYPERLINK("http://evil")', 'email' => "csv$sfx@example.com", 'message' => '@SUM(1+1)'], false);
check('Second enquiry (formula-like text) accepted', str_contains($r['location'], 'status=sent'), $r['location']);
$n = array_values(array_filter(mails($mailDir, $notify, $started), fn ($x) => str_contains($x, "Bulk order $sfx")));
check('Notification email sent to the configured address', count($n) === 1);
check('Notification contains the enquiry and Reply-To the visitor', $n && str_contains($n[0], "Ravi $sfx") && str_contains($n[0], 'Need 200 units.') && preg_match("/Reply-To: .*ravi$sfx@example\\.com/i", $n[0]) === 1);
$list = $o->req('GET', 'api/enquiries', ['q' => $sfx])['json'];
$enq = array_values(array_filter($list['rows'] ?? [], fn ($x) => $x['name'] === "Ravi $sfx"))[0] ?? null;
check('Enquiry listed as new, email_sent=1', $enq && $enq['status'] === 'new' && (int) $enq['email_sent'] === 1, json_encode($list['rows'] ?? []));
check('New-enquiry badge in the sidebar', preg_match('#Enquiries</span>\s*<span class="badge[^"]*">\d+</span>#', $o->req('GET', '', [], false)['body']) === 1);
$v = $enq ? $o->req('GET', "api/enquiries/{$enq['id']}")['json'] : [];
check('Opening an enquiry shows the full message and marks it read', ($v['enquiry']['message'] ?? '') === "Need 200 units.\nPlease call." && ($v['enquiry']['status'] ?? '') === 'read');
check('Mark as replied', $enq && ($o->req('POST', "api/enquiries/{$enq['id']}/status", ['status' => 'replied'])['json']['ok'] ?? false) === true);
check('Unknown status refused', $enq && ($o->req('POST', "api/enquiries/{$enq['id']}/status", ['status' => 'deleted'])['json']['ok'] ?? true) === false);
check('Filter by status', $enq && in_array($enq['id'], array_column($o->req('GET', 'api/enquiries', ['status' => 'replied', 'q' => $sfx])['json']['rows'] ?? [], 'id'), true));
$csv = $o->req('GET', 'api/enquiries/export', [], false);
check('CSV export (UTF-8 BOM, attachment)', $csv['code'] === 200 && str_starts_with($csv['body'], "\xEF\xBB\xBF") && str_contains($csv['headers']['content-disposition'][0] ?? '', 'attachment') && str_contains($csv['body'], "Bulk order $sfx"));
check('CSV export neutralises spreadsheet formulas', str_contains($csv['body'], "\"'=HYPERLINK(") && str_contains($csv['body'], "'@SUM(1+1)") && !preg_match('/(^|,)"?=HYPERLINK/m', $csv['body']));
$spamId = (int) (array_values(array_filter($o->req('GET', 'api/enquiries', ['q' => "csv$sfx"])['json']['rows'] ?? [], fn ($x) => true))[0]['id'] ?? 0);
check('Mark as spam hides it from the default list', $spamId && ($o->req('POST', "api/enquiries/$spamId/status", ['status' => 'spam'])['json']['ok'] ?? false)
    && !in_array($spamId, array_column($o->req('GET', 'api/enquiries', ['q' => "csv$sfx"])['json']['rows'] ?? [], 'id'), true));
check('Delete enquiry', $enq && ($o->req('POST', "api/enquiries/{$enq['id']}/delete")['json']['ok'] ?? false) && $o->req('GET', "api/enquiries/{$enq['id']}")['code'] === 404);

echo "\n== Administrators & roles ==\n";
$editorEmail = "editor$sfx@example.com";
check('Weak password refused for a new administrator', isset($o->req('POST', 'api/users', ['name' => 'Ed', 'email' => $editorEmail, 'role' => 'editor', 'status' => 'active', 'password' => 'short'])['json']['errors']['password']));
check('Add an editor', ($o->req('POST', 'api/users', ['name' => 'Ed Itor', 'email' => $editorEmail, 'role' => 'editor', 'status' => 'active', 'password' => "Editor-pass-$sfx-1"])['json']['ok'] ?? false) === true);
$ul = $o->req('GET', 'api/users')['json']['rows'] ?? [];
$me = array_values(array_filter($ul, fn ($u) => $u['email'] === $owner))[0] ?? ['id' => 0];
$edRow = array_values(array_filter($ul, fn ($u) => $u['email'] === $editorEmail))[0] ?? ['id' => 0];
check('Owner cannot demote or delete themselves', ($o->req('POST', "api/users/{$me['id']}", ['name' => 'Asha Owner', 'email' => $owner, 'role' => 'editor', 'status' => 'active'])['json']['ok'] ?? true) === false
    && ($o->req('POST', "api/users/{$me['id']}/delete")['json']['ok'] ?? true) === false);
$e = new Client($adminUrl);
$e->basic = $qa;
check('Editor signs in', ($e->login($editorEmail, "Editor-pass-$sfx-1")['json']['ok'] ?? false) === true);
check('Editor can manage content', $e->req('GET', 'api/pages')['code'] === 200 && $e->req('GET', 'enquiries', [], false)['code'] === 200);
check('Editor has no Settings link', !str_contains($e->req('GET', '', [], false)['body'], '/admin/settings"'));
check('Editor cannot open settings or manage administrators (403)', $e->req('GET', 'settings')['code'] === 403 && $e->req('GET', 'api/users')['code'] === 403
    && $e->req('POST', 'api/users', ['name' => 'X', 'email' => "x$sfx@example.com", 'role' => 'owner', 'password' => 'Abcdefgh1234'])['code'] === 403 && $e->req('POST', 'settings/mail', ['notify_to' => 'a@b.co'])['code'] === 403);
check('Deactivating a user ends their session', ($o->req('POST', "api/users/{$edRow['id']}", ['name' => 'Ed Itor', 'email' => $editorEmail, 'role' => 'editor', 'status' => 'inactive'])['json']['ok'] ?? false)
    && $e->req('GET', 'api/pages')['code'] === 401);
$e2 = new Client($adminUrl);
$e2->basic = $qa;
check('Inactive user cannot sign in', ($e2->login($editorEmail, "Editor-pass-$sfx-1")['json']['ok'] ?? true) === false);
check('Delete administrator', ($o->req('POST', "api/users/{$edRow['id']}/delete")['json']['ok'] ?? false) === true);

echo "\n== Profile & password ==\n";
check('Change password needs the current password', isset($o->req('POST', 'profile/password', ['current_password' => 'nope', 'password' => 'NewPass12345', 'password_confirmation' => 'NewPass12345'])['json']['errors']['current_password']));
check('Mismatched confirmation refused', isset($o->req('POST', 'profile/password', ['current_password' => $ownerPw, 'password' => "Second-$sfx-pass1", 'password_confirmation' => 'other'])['json']['errors']['password_confirmation']));
check('Change password', ($o->req('POST', 'profile/password', ['current_password' => $ownerPw, 'password' => "Second-$sfx-pass1", 'password_confirmation' => "Second-$sfx-pass1"])['json']['ok'] ?? false) === true);
$ownerPw = "Second-$sfx-pass1";
check('Update profile name', ($o->req('POST', 'profile', ['name' => "Asha $sfx", 'email' => $owner])['json']['ok'] ?? false) === true && str_contains($o->req('GET', '', [], false)['body'], "Asha $sfx"));

echo "\n== Forgotten password ==\n";
$g = new Client($adminUrl);
$g->basic = $qa;
$g->req('GET', 'forgot', [], false);
$same1 = $g->req('POST', 'forgot', ['email' => "nobody$sfx@example.com"])['json']['message'] ?? 'a';
$same2 = $g->req('POST', 'forgot', ['email' => $owner])['json']['message'] ?? 'b';
check('Same answer for known and unknown emails (no account discovery)', $same1 === $same2);
$rm = array_values(array_filter(mails($mailDir, $owner, $started), fn ($x) => str_contains($x, 'Reset your password')));
preg_match('#https?://[^\s"<>]+/admin/reset\?email=[^\s"<>&]+&(?:amp;)?token=([a-f0-9]{64})#', $rm[0] ?? '', $lm);
check('Reset email delivered with a one-hour link', isset($lm[1]), substr($rm[0] ?? 'no mail', 0, 300));
$token = $lm[1] ?? '';
check('Reset page accepts the link', str_contains($g->req('GET', 'reset', ['email' => $owner, 'token' => $token], false)['body'], 'name="password"'));
check('Tampered token refused', ($g->req('POST', 'reset', ['email' => $owner, 'token' => str_repeat('a', 64), 'password' => 'Third-pass-123', 'password_confirmation' => 'Third-pass-123'])['json']['ok'] ?? true) === false);
check('Set a new password from the link', ($g->req('POST', 'reset', ['email' => $owner, 'token' => $token, 'password' => "Third-$sfx-pass1", 'password_confirmation' => "Third-$sfx-pass1"])['json']['ok'] ?? false) === true);
check('Reset link works only once', ($g->req('POST', 'reset', ['email' => $owner, 'token' => $token, 'password' => "Fourth-$sfx-pass1", 'password_confirmation' => "Fourth-$sfx-pass1"])['json']['ok'] ?? true) === false);
check('Old password no longer works; new one does', ($g->req('POST', 'login', ['email' => $owner, 'password' => $ownerPw])['json']['ok'] ?? true) === false
    && ($g->req('POST', 'login', ['email' => $owner, 'password' => "Third-$sfx-pass1"])['json']['ok'] ?? false) === true);

echo "\n== Login lockout ==\n";
$l = new Client($adminUrl);
$l->basic = $qa;
$l->req('GET', 'login', [], false);
$codes = [];
for ($i = 0; $i < 6; $i++) {
    $codes[] = $l->req('POST', 'login', ['email' => $owner, 'password' => "wrong-$i-password"])['code'];
}
check('Locked after 5 failed attempts (429)', $codes === [422, 422, 422, 422, 422, 429], implode(',', $codes));
check('Even the right password is refused while locked', $l->req('POST', 'login', ['email' => $owner, 'password' => "Third-$sfx-pass1"])['code'] === 429);
check('Builder "set new password" unlocks the account', ($b->req('POST', "websites/$wid/admin-account", ['email' => $owner])['json']['ok'] ?? false) === true
    && ($l->req('POST', 'login', ['email' => $owner, 'password' => (string) $b->req('POST', "websites/$wid/admin-account", ['email' => $owner])['json']['password']])['json']['ok'] ?? false) === true);

echo "\n== Logout ==\n";
check('Logout', ($o->req('POST', 'logout')['json']['ok'] ?? false) === true);
check('Session is gone after logout', $o->req('GET', 'api/pages')['code'] === 401);

// Clean up: email back to PHP mail(); remove the test owner (needs the DSN; otherwise it keeps a random password nobody knows).
$l->req('POST', 'settings/mail', ['notify_to' => '', 'smtp_enabled' => '0']);
if (isset($pdo, $prefix)) {
    $pdo->prepare("DELETE FROM {$prefix}admin_users WHERE email = ?")->execute([$owner]);
    $pdo->exec("DELETE FROM {$prefix}login_attempts WHERE ip IN ('127.0.0.1', '::1')");
}

echo "\nPassed: $pass   Failed: $fail\n";
exit($fail ? 1 : 0);
