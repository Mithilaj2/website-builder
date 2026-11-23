<?php
declare(strict_types=1);

/**
 * Phase 6 end-to-end test: export a website from the builder, install it on separate hosting, use it.
 *
 *   php tests/phase6_http_test.php <builder-url> <email> <password> <website-id> \
 *       <target-url> <target-dir> <db-name> <db-user> <db-pass> [prefix] [web-user]
 *
 * target-url/target-dir: an empty web folder on hosting that has no access to the builder (separate vhost,
 * separate database user). The folder is wiped and the tables with <prefix> are dropped first.
 * web-user: chown the extracted files to this user (e.g. www-data for Apache).
 */

require __DIR__ . '/support.php';

[$_, $base, $email, $password, $wid, $target, $dir, $dbName, $dbUser, $dbPass, $prefix, $webUser] = $argv + [null, '', '', '', '2', '', '', '', '', '', 'site_', ''];
$base = rtrim((string) $base, '/');
$target = rtrim((string) $target, '/');
$dir = rtrim((string) $dir, '/');
$wid = (int) $wid;
$sfx = substr(bin2hex(random_bytes(3)), 0, 6);
$pass = $fail = 0;
$tmp = sys_get_temp_dir() . "/p6_$sfx";
mkdir($tmp);
register_shutdown_function(static function () use ($tmp): void {
    exec('rm -rf ' . escapeshellarg($tmp));
});

function export(Client $b, int $wid, array $opts): array
{
    $r = $b->req('POST', "websites/$wid/exports", $opts);
    $id = $r['json']['state']['export_id'] ?? 0;
    $s = $r['json']['state'] ?? ['status' => 'not-started', 'error' => $r['body']];
    for ($i = 0; $i < 200 && $id && ($s['status'] ?? '') === 'running'; $i++) {
        $s = $b->req('POST', "websites/$wid/exports/$id/step")['json']['state'] ?? ['status' => 'error'];
    }
    return [$id, $s];
}
function zipNames(string $file): array
{
    $z = new ZipArchive();
    $z->open($file);
    $n = [];
    for ($i = 0; $i < $z->numFiles; $i++) {
        $n[] = (string) $z->getNameIndex($i);
    }
    $z->close();
    return $n;
}

$b = new Client($base);
check('Builder login', ($b->login($email, $password)['json']['ok'] ?? false) === true);
$cfg = require dirname(__DIR__) . '/config/config.php';
$siteRow = null;

echo "\n== Export (builder) ==\n";
$page = $b->req('GET', "websites/$wid/export", [], false);
check('Export & deploy page', $page['code'] === 200 && str_contains($page['body'], 'Full package') && str_contains($page['body'], 'Update package'));
check('Invalid table prefix refused', isset($b->req('POST', "websites/$wid/exports", ['type' => 'full', 'prefix' => 'Bad Prefix'])['json']['errors']['prefix']));
[$fid, $st] = export($b, $wid, ['type' => 'full', 'prefix' => 'site_', 'include_enquiries' => '1']);
check('Full export completes (all steps)', ($st['status'] ?? '') === 'done' && count(array_filter($st['steps'], fn ($s) => $s['status'] === 'success')) === 7, json_encode($st));
$hist = $b->req('GET', "websites/$wid/exports")['json']['exports'] ?? [];
$h = array_values(array_filter($hist, fn ($x) => (int) $x['id'] === $fid))[0] ?? [];
$key = (string) ($h['install_key'] ?? '');
check('Package listed with size, versions and an installation key', ($h['status'] ?? '') === 'done' && preg_match('/^[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}$/', $key) === 1 && (int) $h['file_count'] > 400);
$dl = $b->req('GET', "websites/$wid/exports/$fid/download", [], false);
file_put_contents("$tmp/full.zip", $dl['body']);
check('Download ZIP', $dl['code'] === 200 && str_contains($dl['headers']['content-type'][0] ?? '', 'zip') && filesize("$tmp/full.zip") > 500_000);
$sql = $b->req('GET', "websites/$wid/exports/$fid/download", ['file' => 'sql'], false);
file_put_contents("$tmp/sql.zip", $sql['body']);
check('Download database-only ZIP (schema.sql + data.sql)', $sql['code'] === 200 && zipNames("$tmp/sql.zip") === ['schema.sql', 'data.sql']);
$guest = new Client($base);
check('Packages cannot be downloaded without signing in', !in_array($guest->req('GET', "websites/$wid/exports/$fid/download", [], false)['code'], [200], true));
check('Exports folder is not reachable from the web', $guest->req('GET', '../storage/exports/', [], false)['code'] !== 200);

$names = zipNames("$tmp/full.zip");
foreach (['index.php', '.htaccess', 'install.php', 'core/Site.php', 'core/Admin/App.php', 'core/Payments/PaymentPage.php', 'vendor/autoload.php', 'theme/manifest.json', 'assets/pay/pay.js', 'uploads/.htaccess',
    'database/schema.sql', 'database/data.sql', 'config/.htaccess', 'config/app.example.php', 'config/install.php', 'INSTALLATION.md', 'REQUIREMENTS.md', 'DEPLOYMENT_CHECKLIST.md', 'storage/.htaccess'] as $must) {
    if (!in_array($must, $names, true)) {
        check("Package contains $must", false);
    }
}
check('Package contains the website, installer, database and guides', true);
check('No real configuration files in the package (config/app.php, database.php, payment.php)', !array_intersect(['config/app.php', 'config/database.php', 'config/payment.php'], $names));
check('No logs, cache or QA password files', !preg_grep('#^storage/(logs|cache)/.+|\.htpasswd#', $names));
$z = new ZipArchive();
$z->open("$tmp/full.zip");
$all = '';
foreach ($names as $n) {
    if (preg_match('/\.(php|sql|md|json|htaccess|js|css)$|htaccess$/', $n) && !str_starts_with($n, 'vendor/')) {
        $all .= $z->getFromName($n) . "\n";
    }
}
$data = (string) $z->getFromName('database/data.sql');
$schema = (string) $z->getFromName('database/schema.sql');
$appEx = (string) $z->getFromName('config/app.example.php');
$checklistMd = (string) $z->getFromName('DEPLOYMENT_CHECKLIST.md');
$installMeta = (string) $z->getFromName('config/install.php');
$z->close();
$qaApp = require dirname(__DIR__) . '/generated_websites/' . basename((string) glob(dirname(__DIR__) . "/generated_websites/site_" . sprintf('%06d', $wid) . '_*')[0]) . '/config/app.php';
check('Builder database password not in the package', !str_contains($all, (string) $cfg['db']['pass']));
check('Builder database name and server paths not in the package', !str_contains($all, "'" . $cfg['db']['name'] . "'") && !str_contains($all, dirname(__DIR__)));
check('QA website key not in the package (installer makes a new one)', !str_contains($all, (string) $qaApp['key']) && str_contains($appEx, "'key' => ''"));
check('Developer payment emulator setting removed', !str_contains($appEx, 'payment_emulator') && !str_contains($all, '127.0.0.1:8090'));
check('Environment set to production, debug off', str_contains($appEx, "'environment' => 'production'") && str_contains($appEx, "'debug' => false"));
check('No administrator logins or login history exported', !preg_match('/INSERT INTO `site_(admin_users|login_attempts)`/', $data));
check('Encrypted values blanked (no v1: secrets in the data)', !str_contains($data, 'v1:'));
check('Test payments and payment logs not exported', !preg_match('/INSERT INTO `site_payment_(transactions|events)`/', $data) || !str_contains($data, "'test'"));
check('Tables renamed to the export prefix, including foreign keys', str_contains($schema, 'CREATE TABLE `site_pages`') && str_contains($schema, 'REFERENCES `site_pages`') && !str_contains($schema, '`s0000'));
check('Installation key in the checklist; the installer only holds its hash', str_contains($checklistMd, $key) && !str_contains($installMeta, $key) && str_contains($installMeta, "'key_hash' => '\$2y\$"));

[$uid, $ust] = export($b, $wid, ['type' => 'update']);
$ud = $b->req('GET', "websites/$wid/exports/$uid/download", [], false);
file_put_contents("$tmp/update.zip", $ud['body']);
$un = zipNames("$tmp/update.zip");
check('Update package: code + UPDATE.md only (no images, database, installer or config)', ($ust['status'] ?? '') === 'done' && in_array('UPDATE.md', $un, true) && in_array('core/Site.php', $un, true)
    && !preg_grep('#^(uploads/|database/|install\.php$|config/(app|database|payment)\.php$)#', $un));

echo "\n== Install on separate hosting ==\n";
exec('rm -rf ' . escapeshellarg($dir) . '/* ' . escapeshellarg($dir) . '/.htaccess 2>/dev/null');
@mkdir($dir, 0755, true);
$pdo = new PDO("mysql:host=127.0.0.1;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
foreach ($pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE '" . str_replace('_', '\\_', $prefix) . "%'")->fetchAll(PDO::FETCH_COLUMN) as $t) {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec("DROP TABLE `$t`");
}
check('Client database user cannot see the builder database', !in_array($cfg['db']['name'], $pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN), true));
$z = new ZipArchive();
$z->open("$tmp/full.zip");
$z->extractTo($dir);
$z->close();
if ($webUser !== '') {
    exec('chown -R ' . escapeshellarg($webUser) . ' ' . escapeshellarg($dir));
}
$c = new Client($target);
$r = $c->req('GET', '', [], false);
check('Before installation the website sends visitors to install.php', in_array($r['code'], [302, 303], true) && str_contains($r['location'], 'install.php'), $r['code'] . ' ' . $r['location']);
$r = $c->req('GET', 'install.php', [], false);
check('Installer: server check all OK', $r['code'] === 200 && !str_contains($r['body'], '✗ Missing') && str_contains($r['body'], 'Installation key'), substr(strip_tags($r['body']), 0, 300));
check('Installer pages are not indexed or framed', str_contains($r['body'], 'noindex') && ($r['headers']['x-frame-options'][0] ?? '') === 'DENY');
preg_match('/name="_csrf" value="([^"]+)"/', $r['body'], $cm);
$form = ['_csrf' => $cm[1] ?? '', 'install_key' => $key, 'db_host' => '127.0.0.1', 'db_name' => $dbName, 'db_user' => $dbUser, 'db_pass' => $dbPass, 'prefix' => $prefix,
    'owner_name' => 'Kavya Client', 'owner_email' => "kavya$sfx@client.example", 'owner_pass' => "Client-$sfx-pass1", 'owner_pass2' => "Client-$sfx-pass1"];
check('Wrong installation key refused', str_contains($c->req('POST', 'install.php', ['install_key' => 'AAAA-BBBB-CCCC'] + $form, false)['body'], 'not the installation key'));
check('Wrong database password refused, nothing written', str_contains($c->req('POST', 'install.php', ['db_pass' => 'nope'] + $form, false)['body'], 'Could not connect to the database') && !is_file("$dir/config/app.php"));
check('Weak owner password refused', str_contains($c->req('POST', 'install.php', ['owner_pass' => 'short', 'owner_pass2' => 'short'] + $form, false)['body'], 'At least 10 characters'));
$r = $c->req('POST', 'install.php', $form, false);
check('Installation succeeds', str_contains($r['body'], 'is installed') && str_contains($r['body'], 'statements'), substr(strip_tags($r['body']), 0, 500));
check('install.php deleted itself; database dumps and install key file removed', !is_file("$dir/install.php") && !is_file("$dir/database/data.sql") && !is_file("$dir/config/install.php") && is_file("$dir/storage/install.lock"));
$dbConf = require "$dir/config/database.php";
$appConf = require "$dir/config/app.php";
check('config/ holds only the client database and a new website key', $dbConf['name'] === $dbName && $dbConf['user'] === $dbUser && $dbConf['prefix'] === $prefix && strlen(base64_decode($appConf['key'])) === 32 && $appConf['key'] !== $qaApp['key']);
check('Clean addresses detected', ($appConf['clean_urls'] ?? null) === true, json_encode($appConf['clean_urls'] ?? null));
check('Tables created with the chosen prefix', (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE '" . str_replace('_', '\\_', $prefix) . "%'")->fetchColumn() >= 10);
$grep = shell_exec('grep -rlI --exclude-dir=vendor -e ' . escapeshellarg((string) $cfg['db']['pass']) . ' -e ' . escapeshellarg(dirname(__DIR__)) . ' ' . escapeshellarg($dir) . ' 2>/dev/null');
check('Installed website contains no builder credentials or paths', trim((string) $grep) === '', (string) $grep);

echo "\n== The installed website ==\n";
$home = $c->req('GET', '', [], false);
check('Homepage', $home['code'] === 200 && str_contains($home['body'], '</html>') && !preg_match('/(Fatal error|Warning:|Notice:)/', $home['body']));
preg_match_all('#<a[^>]+href="(' . preg_quote((string) parse_url($target, PHP_URL_PATH), '#') . '/[a-z0-9-]+)"#', $home['body'], $lm);
$hostClient = new Client((string) preg_replace('#^(https?://[^/]+).*$#', '$1', $target));
$pagesOk = true;
foreach (array_unique($lm[1]) as $p) {
    $pagesOk = $pagesOk && $hostClient->req('GET', $p, [], false)['code'] === 200;
}
check('Every menu page opens (' . count(array_unique($lm[1])) . ' pages, clean addresses)', $pagesOk && count(array_unique($lm[1])) >= 4);
preg_match_all('#(?:src|href)="([^"]+/(?:uploads|theme/assets|assets)/[^"?]+)#', $home['body'], $am);
$assetsOk = count($am[1]) >= 3;
foreach (array_slice(array_unique($am[1]), 0, 12) as $u) {
    $assetsOk = $assetsOk && $hostClient->req('GET', $u, [], false)['code'] === 200;
}
check('Theme files, fonts and images load', $assetsOk);
check('Unknown address → 404 page', $c->req('GET', 'no-such-page-' . $sfx, [], false)['code'] === 404);
foreach (['config/app.php', 'config/database.php', 'core/Site.php', 'vendor/autoload.php', 'storage/install.lock', 'database/', 'INSTALLATION.md', 'DEPLOYMENT_CHECKLIST.md', 'theme/manifest.json'] as $priv) {
    $code = $c->req('GET', $priv, [], false)['code'];
    if (!in_array($code, [403, 404], true)) {
        check("Private path blocked: $priv", false, (string) $code);
    }
}
check('Private files blocked (config, core, vendor, storage, database, guides with the install key)', true);
check('install.php cannot be run again', $c->req('GET', 'install.php', [], false)['code'] === 404);

$contact = $c->req('GET', 'contact', [], false)['body'];
preg_match('/name="_t" value="([^"]+)"/', $contact, $t);
preg_match('/<form class="[a-z]+-form" method="post" action="([^"]+)"/', $contact, $act);
sleep(3);
$f = $hostClient->req('POST', html_entity_decode($act[1] ?? ''), ['_t' => $t[1] ?? '', '_back' => 'contact', 'name' => "Visitor $sfx", 'email' => 'v@example.com', 'message' => "Hello from the new host $sfx"], false);
check('Contact form works (signed with the new key)', $f['code'] === 303 && str_contains($f['location'], 'status=sent'), $f['code'] . ' ' . $f['location']);

$a = new Client("$target/admin");
check('Owner signs in to the website admin', ($a->login("kavya$sfx@client.example", "Client-$sfx-pass1")['json']['ok'] ?? false) === true);
check('QA administrator logins were not carried over', ($a->req('GET', 'api/users')['json']['total'] ?? 0) === 1);
check('Enquiry received in the admin', in_array("Visitor $sfx", array_column($a->req('GET', 'api/enquiries', ['q' => $sfx])['json']['rows'] ?? [], 'name'), true));
$pagesApi = $a->req('GET', 'api/pages')['json']['pages'] ?? [];
$home1 = array_values(array_filter($pagesApi, fn ($p) => (int) $p['is_home'] === 1))[0] ?? ['id' => 0];
$hero = $a->req('GET', "api/pages/{$home1['id']}/sections")['json']['sections'][0] ?? ['id' => 0];
$hd = $a->req('GET', "api/sections/{$hero['id']}")['json']['section']['data'] ?? [];
$hd['heading'] = "Live on the client host $sfx";
check('Edit content in the admin → visible on the website', ($a->req('POST', "api/sections/{$hero['id']}", ['data' => json_encode($hd), 'style' => '{}'])['json']['ok'] ?? false) && str_contains($c->req('GET', '', [], false)['body'], "Live on the client host $sfx"));
$up = $a->req('POST', 'api/media', ['folder' => 'gallery', 'files[0]' => new CURLFile(makeImageFile("$tmp/i.jpg"), 'image/jpeg', 'client.jpg')]);
check('Image upload works (uploads/ writable)', ($up['json']['ok'] ?? false) && $c->req('GET', (string) ($up['json']['items'][0]['path'] ?? 'x'), [], false)['code'] === 200);
$ps = $a->req('GET', 'payments/settings', [], false)['body'];
check('Payment keys are empty after installation (client enters them)', !str_contains($ps, 'Saved — leave empty to keep'));
$ms = $a->req('GET', 'settings', [], false)['body'];
check('Saved SMTP password not carried over', !str_contains($ms, 'Saved — leave empty to keep'));

echo "\n== Update package ==\n";
$z = new ZipArchive();
$z->open("$tmp/update.zip");
$z->extractTo($dir);
$z->close();
if ($webUser !== '') {
    exec('chown -R ' . escapeshellarg($webUser) . ' ' . escapeshellarg($dir));
}
check('After the update: content, logins and configuration untouched', str_contains($c->req('GET', '', [], false)['body'], "Live on the client host $sfx")
    && ($a->req('GET', 'api/users')['json']['total'] ?? 0) === 1 && (require "$dir/config/app.php")['key'] === $appConf['key']);
check('After the update: admin and uploaded image still work', $a->req('GET', '', [], false)['code'] === 200 && $c->req('GET', (string) ($up['json']['items'][0]['path'] ?? 'x'), [], false)['code'] === 200);
check('install.php not brought back by the update', !is_file("$dir/install.php"));

echo "\nPassed: $pass   Failed: $fail\n";
exit($fail ? 1 : 0);
