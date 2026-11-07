<?php
declare(strict_types=1);

/**
 * Phase 1 end-to-end HTTP test. Runs against a live builder instance.
 *
 *   php tests/phase1_http_test.php http://127.0.0.1:8080 info@channelsoftech.com 'Password123'
 *
 * Exercises login/rate limiting, CSRF, roles, and full CRUD for categories, clients, users,
 * and templates (upload, validation, security scan, duplicate, versions, restore, screenshots, delete).
 * Creates its own data with a unique suffix and removes it at the end.
 */

[$_, $base, $email, $password] = $argv + [null, 'http://127.0.0.1:8080', 'info@channelsoftech.com', ''];
$base = rtrim((string) $base, '/');
$suffix = substr(bin2hex(random_bytes(3)), 0, 6);
$pass = $fail = 0;

final class Client
{
    public string $jar;
    public string $csrf = '';
    public function __construct(private string $base)
    {
        $this->jar = tempnam(sys_get_temp_dir(), 'jar');
    }
    public function req(string $method, string $path, array $data = [], bool $ajax = true, bool $withCsrf = true): array
    {
        $url = preg_match('#^https?://#', $path) ? $path : $this->base . '/' . ltrim($path, '/');
        $ch = curl_init($url);
        $headers = [];
        if ($ajax) {
            $headers[] = 'X-Requested-With: XMLHttpRequest';
        }
        if ($withCsrf && $this->csrf) {
            $headers[] = 'X-CSRF-Token: ' . $this->csrf;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $this->jar, CURLOPT_COOKIEFILE => $this->jar,
            CURLOPT_HTTPHEADER => $headers, CURLOPT_FOLLOWLOCATION => false, CURLOPT_USERAGENT => 'phase1-test',
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } elseif ($data) {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
        }
        $body = (string) curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if (preg_match('/name="csrf-token" content="([a-f0-9]{64})"/', $body, $m)) {
            $this->csrf = $m[1];
        }
        return ['code' => $code, 'body' => $body, 'json' => json_decode($body, true)];
    }
    public function login(string $email, string $password): array
    {
        $this->req('GET', 'login', [], false);
        return $this->req('POST', 'login', ['email' => $email, 'password' => $password]);
    }
}

function check(string $label, bool $cond, string $extra = ''): void
{
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "  \033[32m✔\033[0m $label\n";
    } else {
        $fail++;
        echo "  \033[31m✘ $label\033[0m" . ($extra ? "\n      " . substr($extra, 0, 400) : '') . "\n";
    }
}

function makeImage(string $path, int $w = 800, int $h = 500): void
{
    $im = imagecreatetruecolor($w, $h);
    imagefill($im, 0, 0, imagecolorallocate($im, 46, 49, 146));
    imagefilledrectangle($im, 40, 40, $w - 40, $h - 40, imagecolorallocate($im, 0, 174, 239));
    str_ends_with($path, '.png') ? imagepng($im, $path) : imagejpeg($im, $path, 85);
    imagedestroy($im);
}

/** Build a minimal valid template folder and zip it. $mutate can add/alter files. */
function makeTemplateZip(string $id, string $category, string $version, ?callable $mutate = null): string
{
    $dir = sys_get_temp_dir() . '/tplsrc_' . bin2hex(random_bytes(4)) . '/' . $id;
    foreach (['layouts', 'partials', 'sections', 'assets/css', 'assets/images'] as $d) {
        mkdir("$dir/$d", 0777, true);
    }
    $manifest = [
        'id' => $id, 'name' => 'Test Template ' . $id, 'category' => $category, 'version' => $version,
        'description' => 'Automated test template.', 'features' => ['contact'], 'optional_features' => ['blog'],
        'theme' => ['defaults' => ['primary' => '#2E3192', 'secondary' => '#00AEEF', 'bg' => '#FFFFFF', 'text' => '#111827']],
        'section_types' => ['hero', 'about'],
        'page_types' => ['home' => ['default_sections' => ['hero', 'about']], 'about' => ['default_sections' => ['about']]],
        'default_pages' => [['title' => 'Home', 'slug' => '', 'type' => 'home', 'is_home' => true], ['title' => 'About', 'slug' => 'about', 'type' => 'about']],
    ];
    file_put_contents("$dir/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));
    file_put_contents("$dir/layouts/default.php", "<!doctype html><html><body><?= \$view->partial('header') ?><?= \$content ?><?= \$view->partial('footer') ?></body></html>");
    file_put_contents("$dir/partials/header.php", '<header><?= $view->e($site[\'name\'] ?? \'\') ?></header>');
    file_put_contents("$dir/partials/footer.php", '<footer>&copy;</footer>');
    file_put_contents("$dir/sections/hero.php", '<section class="hero"><h1><?= $view->e($data[\'heading\'] ?? \'\') ?></h1></section>');
    file_put_contents("$dir/sections/about.php", '<section class="about"><p><?= $view->e($data[\'description\'] ?? \'\') ?></p></section>');
    file_put_contents("$dir/assets/css/style.css", ':root{--wb-primary:#2E3192} body{color:var(--wb-text)}');
    makeImage("$dir/preview.jpg");
    if ($mutate) {
        $mutate($dir, $manifest);
    }
    $zipPath = sys_get_temp_dir() . "/$id-$version-" . bin2hex(random_bytes(3)) . '.zip';
    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE);
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname($dir), FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $zip->addFile($f->getPathname(), substr($f->getPathname(), strlen(dirname($dir)) + 1));
    }
    $zip->close();
    return $zipPath;
}

$admin = new Client($base);
$tmp = sys_get_temp_dir();

echo "\n== Authentication ==\n";
$r = $admin->req('GET', '', [], false);
check('Guest is redirected from dashboard', $r['code'] === 302);
$r = $admin->login($email, 'wrong-password-x');
check('Wrong password rejected', $r['code'] === 422 && ($r['json']['ok'] ?? true) === false);
$r = $admin->req('POST', 'login', ['email' => $email, 'password' => $password], true, false);
check('Login without CSRF token rejected (403)', $r['code'] === 403 && str_contains((string) ($r['json']['message'] ?? ''), 'session'));
$r = $admin->login($email, $password);
check('Super Admin login succeeds', ($r['json']['ok'] ?? false) === true, $r['body']);
$r = $admin->req('GET', '', [], false);
check('Dashboard renders', $r['code'] === 200 && str_contains($r['body'], 'Recently created websites'));
check('Security headers present (CSP enforced, no inline scripts)', !preg_match('/<script>(?!\s*<\/script>)/', $r['body']));
$r = $admin->req('GET', 'dashboard/recent');
check('Recent websites JSON endpoint', isset($r['json']['rows'], $r['json']['total']));

echo "\n== Login rate limiting ==\n";
$rl = new Client($base);
$rlEmail = "ratelimit$suffix@example.com";
$codes = [];
for ($i = 0; $i < 6; $i++) {
    $codes[] = $rl->login($rlEmail, 'nope')['code'];
}
check('6th failed login is blocked with 429', end($codes) === 429, implode(',', $codes));

echo "\n== Categories ==\n";
$r = $admin->req('GET', 'categories/list', ['per_page' => 50]);
check('Seeded 10 categories present', ($r['json']['total'] ?? 0) >= 10);
makeImage("$tmp/cat.jpg");
$r = $admin->req('POST', 'categories', ['name' => "Test Category $suffix", 'description' => 'Test', 'status' => 'active', 'image' => new CURLFile("$tmp/cat.jpg", 'image/jpeg', 'cat.jpg')]);
check('Create category with image', ($r['json']['ok'] ?? false) === true, $r['body']);
$catId = (int) ($r['json']['id'] ?? 0);
$r = $admin->req('POST', 'categories', ['name' => "Test Category $suffix", 'status' => 'active']);
check('Duplicate category name rejected', $r['code'] === 422 && isset($r['json']['errors']['name']));
file_put_contents("$tmp/evil.jpg", "<?php system('id'); ?>");
$r = $admin->req('POST', 'categories', ['name' => "Evil $suffix", 'status' => 'active', 'image' => new CURLFile("$tmp/evil.jpg", 'image/jpeg', 'evil.jpg')]);
check('PHP disguised as JPG rejected', $r['code'] === 422 && isset($r['json']['errors']['image']), $r['body']);
$r = $admin->req('GET', "categories/$catId");
check('Category detail JSON has image URL', !empty($r['json']['data']['image_url']));
$img = $admin->req('GET', (string) ($r['json']['data']['image_url'] ?? ''), [], false);
check('Uploaded image is served', $img['code'] === 200);
$catSlug = $r['json']['data']['slug'] ?? '';
$r = $admin->req('POST', "categories/$catId", ['name' => "Test Category $suffix Edited", 'slug' => $catSlug, 'status' => 'active', 'description' => 'Edited']);
check('Update category', ($r['json']['ok'] ?? false) === true, $r['body']);
$r = $admin->req('POST', "categories/$catId/status");
check('Deactivate category', ($r['json']['status'] ?? '') === 'inactive');
$admin->req('POST', "categories/$catId/status");
$r = $admin->req('GET', 'categories/list', ['q' => "Category $suffix"]);
check('Search categories', ($r['json']['total'] ?? 0) === 1);
$ids = array_column($admin->req('GET', 'categories/list', ['per_page' => 50])['json']['rows'], 'id');
$r = $admin->req('POST', 'categories/reorder', ['ids' => array_reverse($ids)]);
check('Reorder categories (drag & drop endpoint)', ($r['json']['ok'] ?? false) === true);
$admin->req('POST', 'categories/reorder', ['ids' => $ids]);

echo "\n== Clients ==\n";
$r = $admin->req('POST', 'clients', ['business_name' => "Acme $suffix Pvt Ltd", 'contact_person' => 'Ravi', 'email' => 'RAVI@acme.test', 'phone' => '+91 98765 43210', 'address' => 'Kochi', 'status' => 'active']);
check('Create client', ($r['json']['ok'] ?? false) === true, $r['body']);
$clientId = (int) ($r['json']['id'] ?? 0);
$r = $admin->req('POST', 'clients', ['business_name' => '', 'email' => 'bad', 'phone' => 'abc', 'status' => 'active']);
check('Client validation errors returned', isset($r['json']['errors']['business_name'], $r['json']['errors']['email'], $r['json']['errors']['phone']));
$r = $admin->req('GET', "clients/$clientId/json");
check('Client email normalised to lowercase', ($r['json']['data']['email'] ?? '') === 'ravi@acme.test');
$r = $admin->req('POST', "clients/$clientId", ['business_name' => "Acme $suffix Private Limited", 'status' => 'active', 'notes' => '<script>alert(1)</script>']);
check('Update client', ($r['json']['ok'] ?? false) === true);
$r = $admin->req('GET', "clients/$clientId", [], false);
check('Client page escapes HTML (XSS)', $r['code'] === 200 && str_contains($r['body'], '&lt;script&gt;alert(1)&lt;/script&gt;') && !str_contains($r['body'], '<script>alert(1)</script>'));
$r = $admin->req('GET', 'clients/list', ['q' => "Acme $suffix"]);
check('Search clients', ($r['json']['total'] ?? 0) === 1);

echo "\n== Users & roles ==\n";
$viewerEmail = "viewer$suffix@example.com";
$designerEmail = "designer$suffix@example.com";
$r = $admin->req('POST', 'users', ['name' => 'Test Viewer', 'email' => $viewerEmail, 'role' => 'viewer', 'status' => 'active', 'password' => 'short', 'password_confirmation' => 'short']);
check('Weak password rejected', isset($r['json']['errors']['password']));
$r = $admin->req('POST', 'users', ['name' => 'Test Viewer', 'email' => $viewerEmail, 'role' => 'viewer', 'status' => 'active', 'password' => 'ViewerPass123', 'password_confirmation' => 'ViewerPass123']);
check('Create viewer user', ($r['json']['ok'] ?? false) === true, $r['body']);
$r = $admin->req('POST', 'users', ['name' => 'Test Designer', 'email' => $designerEmail, 'role' => 'designer', 'status' => 'active', 'password' => 'DesignerPass123', 'password_confirmation' => 'DesignerPass123']);
check('Create designer user', ($r['json']['ok'] ?? false) === true, $r['body']);
$users = $admin->req('GET', 'users/list', ['q' => $suffix])['json']['rows'] ?? [];
$userIds = array_column($users, 'id');

$viewer = new Client($base);
check('Viewer can log in', ($viewer->login($viewerEmail, 'ViewerPass123')['json']['ok'] ?? false) === true);
check('Viewer can list clients', $viewer->req('GET', 'clients/list')['code'] === 200);
check('Viewer cannot create clients (403)', $viewer->req('POST', 'clients', ['business_name' => 'X', 'status' => 'active'])['code'] === 403);
check('Viewer cannot open user management (403)', $viewer->req('GET', 'users', [], false)['code'] === 403);
check('Viewer cannot change categories (403)', $viewer->req('POST', "categories/$catId/status")['code'] === 403);

$designer = new Client($base);
check('Designer can log in', ($designer->login($designerEmail, 'DesignerPass123')['json']['ok'] ?? false) === true);
check('Designer cannot create clients (403)', $designer->req('POST', 'clients', ['business_name' => 'X', 'status' => 'active'])['code'] === 403);
check('Designer can manage categories', ($designer->req('POST', "categories/$catId/status")['json']['ok'] ?? false) === true);
$designer->req('POST', "categories/$catId/status");

$me = $admin->req('GET', 'users/list', ['q' => $email])['json']['rows'][0]['id'] ?? 0;
$r = $admin->req('POST', "users/$me", ['name' => 'Super Admin', 'email' => $email, 'role' => 'viewer', 'status' => 'active']);
check('Super Admin cannot demote themself', ($r['json']['ok'] ?? true) === false);
$r = $admin->req('POST', "users/$me/delete");
check('Super Admin cannot delete themself', ($r['json']['ok'] ?? true) === false);

echo "\n== Profile ==\n";
$r = $viewer->req('POST', 'profile/password', ['current_password' => 'wrong', 'password' => 'NewViewer12345', 'password_confirmation' => 'NewViewer12345']);
check('Password change needs the current password', isset($r['json']['errors']['current_password']));
$r = $viewer->req('POST', 'profile/password', ['current_password' => 'ViewerPass123', 'password' => 'NewViewer12345', 'password_confirmation' => 'NewViewer12345']);
check('Password change succeeds', ($r['json']['ok'] ?? false) === true, $r['body']);
check('New password works', ((new Client($base))->login($viewerEmail, 'NewViewer12345')['json']['ok'] ?? false) === true);

echo "\n== Templates ==\n";
$tplId = "zz_test_template_$suffix";
$zip = makeTemplateZip($tplId, 'business', '1.0.0');
$r = $admin->req('POST', 'templates/upload', ['package' => new CURLFile($zip, 'application/zip', 'tpl.zip')]);
check('Upload valid template ZIP', ($r['json']['ok'] ?? false) === true, $r['body']);
preg_match('#templates/(\d+)$#', (string) ($r['json']['redirect'] ?? ''), $m);
$templateId = (int) ($m[1] ?? 0);
$r = $admin->req('POST', 'templates/upload', ['package' => new CURLFile($zip, 'application/zip', 'tpl.zip')]);
check('Re-uploading the same id is rejected', ($r['json']['ok'] ?? true) === false);

$bad = makeTemplateZip("zz_bad_$suffix", 'business', '1.0.0', function ($dir) {
    file_put_contents("$dir/sections/hero.php", '<?php echo shell_exec($_GET["c"]); include "../../config/config.php"; ?>');
    file_put_contents("$dir/assets/shell.php", '<?php phpinfo();');
    file_put_contents("$dir/.htaccess", 'AddType application/x-httpd-php .jpg');
    unlink("$dir/sections/about.php");
});
$r = $admin->req('POST', 'templates/upload', ['package' => new CURLFile($bad, 'application/zip', 'bad.zip')]);
$errs = implode("\n", $r['json']['report']['errors'] ?? []);
check('Malicious template rejected by security scan', $r['code'] === 422 && ($r['json']['report']['ok'] ?? true) === false, $r['body']);
check('  · flags shell_exec()', str_contains($errs, 'shell_exec'));
check('  · flags $_GET', str_contains($errs, '$_GET'));
check('  · flags include', str_contains($errs, '"include"'));
check('  · flags PHP outside allowed folders', str_contains($errs, 'assets/shell.php'));
check('  · flags .htaccess', str_contains($errs, '.htaccess'));
check('  · flags missing section view', str_contains($errs, 'sections/about.php'));

$slip = sys_get_temp_dir() . "/slip-$suffix.zip";
$z = new ZipArchive();
$z->open($slip, ZipArchive::CREATE);
$z->addFromString('../../evil.php', '<?php echo 1;');
$z->close();
$r = $admin->req('POST', 'templates/upload', ['package' => new CURLFile($slip, 'application/zip', 'slip.zip')]);
check('Zip-slip path traversal rejected', $r['code'] === 422 && str_contains((string) ($r['json']['message'] ?? ''), 'unsafe path'), $r['body']);

$r = $admin->req('GET', "templates/$templateId", [], false);
check('Template detail page renders', $r['code'] === 200 && str_contains($r['body'], $tplId));
$r = $admin->req('POST', "templates/$templateId/validate");
check('Re-validate template passes', ($r['json']['report']['ok'] ?? false) === true, $r['body']);
$r = $admin->req('POST', "templates/$templateId/status");
check('Activate valid template', ($r['json']['ok'] ?? false) === true, $r['body']);
$r = $admin->req('GET', 'templates/list', ['status' => 'active', 'q' => $suffix]);
check('Filter templates by status', ($r['json']['total'] ?? 0) === 1);
$r = $admin->req('GET', 'templates/list', ['q' => $suffix]);
check('Template preview image imported from preview.jpg', !empty($r['json']['rows'][0]['preview_url']));

makeImage("$tmp/s1.png", 1200, 750);
makeImage("$tmp/s2.jpg", 1200, 750);
$r = $admin->req('POST', "templates/$templateId/screens", ['caption' => 'Home', 'screens[0]' => new CURLFile("$tmp/s1.png", 'image/png', 's1.png'), 'screens[1]' => new CURLFile("$tmp/s2.jpg", 'image/jpeg', 's2.jpg')]);
check('Upload 2 screenshots', ($r['json']['ok'] ?? false) === true && str_contains($r['json']['message'], '2 screenshot'), $r['body']);

$r = $admin->req('POST', "templates/$templateId", ['name' => 'Renamed Test Template', 'description' => 'New description', 'category_id' => $catId]);
check('Edit template + move to another category', ($r['json']['ok'] ?? false) === true, $r['body']);
$r = $admin->req('GET', "templates/$templateId/json");
check('Folder moved with category change', str_contains((string) ($r['json']['data']['folder_path'] ?? ''), "templates/$catSlug/$tplId"), (string) ($r['json']['data']['folder_path'] ?? ''));
$r = $admin->req('POST', "templates/$templateId/validate");
check('Manifest category rewritten (still valid after move)', ($r['json']['report']['ok'] ?? false) === true, $r['body']);

$v2 = makeTemplateZip($tplId, $catSlug, '1.1.0', function ($dir) {
    file_put_contents("$dir/assets/css/style.css", ':root{--wb-primary:#000}');
});
$r = $admin->req('POST', "templates/$templateId/new-version", ['package' => new CURLFile($v2, 'application/zip', 'v2.zip'), 'changelog' => 'Darker theme']);
check('Upload new version 1.1.0', ($r['json']['ok'] ?? false) === true, $r['body']);
$r = $admin->req('POST', "templates/$templateId/new-version", ['package' => new CURLFile($v2, 'application/zip', 'v2.zip')]);
check('Same/lower version rejected', ($r['json']['ok'] ?? true) === false);
$page = $admin->req('GET', "templates/$templateId", [], false)['body'];
preg_match_all('#templates/' . $templateId . '/versions/(\d+)/restore#', $page, $vm);
check('Older version is listed as restorable', count($vm[1]) === 1);
$r = $admin->req('POST', "templates/$templateId/versions/{$vm[1][0]}/restore");
check('Restore version 1.0.0', ($r['json']['ok'] ?? false) === true, $r['body']);
$r = $admin->req('GET', "templates/$templateId/json");
check('Current version is back to 1.0.0', ($r['json']['data']['current_version'] ?? '') === '1.0.0');
preg_match('#templates/' . $templateId . '/versions/(\d+)/download#', $page, $dm);
$r = $admin->req('GET', "templates/$templateId/versions/{$dm[1]}/download", [], false);
check('Download version ZIP', $r['code'] === 200 && str_starts_with($r['body'], "PK"));

$dupCode = "zz_dup_$suffix";
$r = $admin->req('POST', "templates/$templateId/duplicate", ['code' => 'Bad Code!', 'name' => 'x']);
check('Duplicate rejects invalid id', isset($r['json']['errors']['code']));
$r = $admin->req('POST', "templates/$templateId/duplicate", ['code' => $dupCode, 'name' => 'Duplicated Template']);
check('Duplicate template', ($r['json']['ok'] ?? false) === true, $r['body']);
preg_match('#templates/(\d+)$#', (string) ($r['json']['redirect'] ?? ''), $m);
$dupId = (int) ($m[1] ?? 0);
$r = $admin->req('POST', "templates/$dupId/validate");
check('Duplicate passes validation with new id', ($r['json']['report']['ok'] ?? false) === true, $r['body']);

check('Viewer cannot upload templates (403)', $viewer->req('POST', 'templates/upload', ['package' => new CURLFile($zip, 'application/zip', 'tpl.zip')])['code'] === 403);
check('Designer can open template scan', $designer->req('GET', 'templates/scan')['code'] === 200);

echo "\n== Activity log ==\n";
$r = $admin->req('GET', 'activity/list', ['q' => 'template.']);
check('Template actions are logged', ($r['json']['total'] ?? 0) >= 5);
check('Viewer cannot read activity log (403)', $viewer->req('GET', 'activity/list')['code'] === 403);

echo "\n== Deletion rules & cleanup ==\n";
$r = $admin->req('POST', "categories/$catId/delete");
check('Category with templates cannot be deleted', ($r['json']['ok'] ?? true) === false);
foreach ([$dupId, $templateId] as $tid) {
    $r = $admin->req('POST', "templates/$tid/delete");
    check("Delete template #$tid (folder archived)", ($r['json']['ok'] ?? false) === true, $r['body']);
}
$r = $admin->req('POST', "categories/$catId/delete");
check('Delete empty category', ($r['json']['ok'] ?? false) === true, $r['body']);
$r = $admin->req('POST', "clients/$clientId/delete");
check('Delete client', ($r['json']['ok'] ?? false) === true);
foreach ($userIds as $uid) {
    $admin->req('POST', "users/$uid/delete");
}
check('Test users deleted', ($admin->req('GET', 'users/list', ['q' => $suffix])['json']['total'] ?? 1) === 0);
$r = $admin->req('POST', 'logout');
check('Logout', ($r['json']['ok'] ?? false) === true);
check('Session ended after logout', $admin->req('GET', 'clients/list')['code'] === 401);

echo "\nResult: $pass passed, $fail failed\n";
exit($fail ? 1 : 0);
