<?php
// Website installer — Channel Softech Website Builder.
// Upload the package, create a MySQL database in cPanel, then open this file in the browser.
// It imports the database, creates the owner login, writes config/ and then disables itself.
// Written in conservative PHP so that older PHP versions show a clear message instead of a blank page.

error_reporting(E_ALL);
ini_set('display_errors', '0');
define('WB_ROOT', __DIR__);
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

function h($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

function page($title, $body)
{
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">'
        . '<title>' . h($title) . '</title><style>'
        . 'body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f1f5f9;color:#0f172a;margin:0;padding:2rem 1rem}'
        . '.box{max-width:720px;margin:0 auto;background:#fff;border-radius:14px;box-shadow:0 10px 30px rgba(15,23,42,.08);padding:2rem}'
        . 'h1{font-size:1.5rem;margin:.2rem 0 1rem}h2{font-size:1.05rem;margin:1.6rem 0 .6rem;border-top:1px solid #e2e8f0;padding-top:1.2rem}'
        . 'label{display:block;font-weight:600;font-size:.9rem;margin:.8rem 0 .3rem}input[type=text],input[type=password],input[type=email]{width:100%;box-sizing:border-box;padding:.65rem .75rem;border:1px solid #cbd5e1;border-radius:8px;font:inherit}'
        . '.row{display:grid;grid-template-columns:1fr 1fr;gap:0 1rem}@media(max-width:560px){.row{grid-template-columns:1fr}}'
        . '.hint{font-size:.82rem;color:#64748b;margin-top:.25rem}.btn{display:inline-block;background:#2e3192;color:#fff;border:0;border-radius:8px;padding:.8rem 1.4rem;font:inherit;font-weight:600;cursor:pointer;text-decoration:none;margin-top:1.4rem}'
        . '.ok{color:#15803d}.bad{color:#b91c1c}.warn{color:#b45309}.alert{padding:.8rem 1rem;border-radius:8px;margin:1rem 0}.alert-bad{background:#fee2e2;color:#7f1d1d}.alert-ok{background:#dcfce7;color:#14532d}.alert-warn{background:#fef3c7;color:#78350f}'
        . 'table{width:100%;border-collapse:collapse;font-size:.92rem}td{padding:.35rem 0;border-bottom:1px solid #f1f5f9}td:last-child{text-align:right}code{background:#f1f5f9;padding:.1rem .35rem;border-radius:4px}'
        . '.brand{font-size:.8rem;color:#64748b;letter-spacing:.04em;text-transform:uppercase}'
        . '</style></head><body><div class="box"><div class="brand">Website installer</div>' . $body . '</div></body></html>';
}

if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    page('PHP 8.1 required', '<h1>PHP 8.1 or newer is required</h1><p>This server runs PHP ' . h(PHP_VERSION) . '. In cPanel, open <strong>MultiPHP Manager</strong> (or <strong>Select PHP Version</strong>) and choose PHP 8.1, 8.2 or 8.3 for this domain, then reload this page.</p>');
    exit;
}

if (is_file(WB_ROOT . '/storage/install.lock') || (is_file(WB_ROOT . '/config/app.php') && is_file(WB_ROOT . '/config/database.php'))) {
    page('Already installed', '<h1>This website is already installed</h1><p>For security, <strong>delete <code>install.php</code></strong> from the website folder (cPanel → File Manager). To reinstall, delete <code>config/app.php</code>, <code>config/database.php</code> and <code>storage/install.lock</code> first.</p>');
    exit;
}

$meta = is_file(WB_ROOT . '/config/install.php') ? include WB_ROOT . '/config/install.php' : null;
if (!is_array($meta) || empty($meta['key_hash'])) {
    page('Package incomplete', '<h1>The installation files are incomplete</h1><p><code>config/install.php</code> is missing. Upload the complete package again (make sure hidden files such as <code>.htaccess</code> are included).</p>');
    exit;
}
require WB_ROOT . '/core/SqlSplitter.php';

// ------------------------------------------------------------------ requirements
$req = array();
$req[] = array('PHP ' . PHP_VERSION . ' (8.1 or newer)', true, '');
foreach (array('pdo_mysql' => 'database', 'mbstring' => 'text', 'json' => 'data', 'openssl' => 'security', 'sodium' => 'encryption of keys', 'gd' => 'image processing', 'fileinfo' => 'upload checks', 'curl' => 'payments and email') as $ext => $why) {
    $req[] = array('PHP extension ' . $ext . ' (' . $why . ')', extension_loaded($ext), 'Enable it in cPanel → Select PHP Version → Extensions.');
}
foreach (array('config', 'storage', 'uploads') as $dir) {
    $p = WB_ROOT . '/' . $dir;
    if (!is_dir($p)) {
        @mkdir($p, 0755, true);
    }
    $req[] = array('Folder ' . $dir . '/ is writable', is_dir($p) && is_writable($p), 'Set the folder permission to 755 in File Manager.');
}
$reqOk = true;
foreach ($req as $r) {
    $reqOk = $reqOk && $r[1];
}

// ------------------------------------------------------------------ form handling
$errors = array();
$alert = '';
$in = array('db_host' => 'localhost', 'db_name' => '', 'db_user' => '', 'prefix' => (string) ($meta['prefix'] ?? 'site_'), 'owner_name' => '', 'owner_email' => '', 'replace' => '');
if (empty($_COOKIE['wb_install'])) {
    $csrf = bin2hex(random_bytes(16));
    setcookie('wb_install', $csrf, array('path' => '/', 'httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'));
} else {
    $csrf = (string) $_COOKIE['wb_install'];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $reqOk) {
    foreach ($in as $k => $v) {
        $in[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $dbPass = (string) ($_POST['db_pass'] ?? '');
    $ownerPass = (string) ($_POST['owner_pass'] ?? '');
    // Slow down guessing of the installation key (10 tries per hour).
    $attemptsFile = WB_ROOT . '/storage/install.attempts';
    $attempts = array_filter(array_map('intval', is_file($attemptsFile) ? explode("\n", (string) file_get_contents($attemptsFile)) : array()), function ($t) { return $t > time() - 3600; });
    if (!hash_equals($csrf, (string) ($_POST['_csrf'] ?? ''))) {
        $alert = 'Your session expired. Please submit the form again.';
    } elseif (count($attempts) >= 10) {
        $alert = 'Too many attempts with a wrong installation key. Try again in an hour.';
    } elseif (!password_verify(strtoupper(trim((string) ($_POST['install_key'] ?? ''))), (string) $meta['key_hash'])) {
        $attempts[] = time();
        @file_put_contents($attemptsFile, implode("\n", $attempts));
        $errors['install_key'] = 'This is not the installation key of this website. It is shown on the builder’s export page and in DEPLOYMENT_CHECKLIST.md.';
    }
    if (!$alert && !$errors) {
        if ($in['db_name'] === '' || $in['db_user'] === '') {
            $errors['db_name'] = 'Enter the database name and user created in cPanel → MySQL Databases.';
        }
        if (!preg_match('/^[a-z][a-z0-9]{0,14}_$/', $in['prefix'])) {
            $errors['prefix'] = 'Letters and digits ending with “_”, e.g. site_.';
        }
        if ($in['owner_name'] === '' || mb_strlen($in['owner_name']) > 120) {
            $errors['owner_name'] = 'Enter the owner’s name.';
        }
        if (!filter_var($in['owner_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['owner_email'] = 'Enter a valid email address.';
        }
        if (strlen($ownerPass) < 10 || !preg_match('/[A-Za-z]/', $ownerPass) || !preg_match('/\d/', $ownerPass)) {
            $errors['owner_pass'] = 'At least 10 characters with letters and numbers.';
        } elseif ($ownerPass !== (string) ($_POST['owner_pass2'] ?? '')) {
            $errors['owner_pass2'] = 'The passwords do not match.';
        }
    }
    if (!$alert && !$errors) {
        try {
            $pdo = new PDO('mysql:host=' . $in['db_host'] . ';dbname=' . $in['db_name'] . ';charset=utf8mb4', $in['db_user'], $dbPass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_TIMEOUT => 10));
        } catch (Throwable $e) {
            $pdo = null;
            $errors['db_name'] = 'Could not connect to the database. Check the name, user and password, and that the user is added to the database with ALL PRIVILEGES. (' . preg_replace('/\s+/', ' ', $e->getMessage()) . ')';
        }
    }
    if (!$alert && !$errors && $pdo) {
        $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        $isMaria = stripos($version, 'mariadb') !== false;
        $num = preg_replace('/[^0-9.].*$/', '', $version);
        if (($isMaria && version_compare($num, '10.3', '<')) || (!$isMaria && version_compare($num, '5.7', '<'))) {
            $alert = 'The database server is too old (' . $version . '). MySQL 5.7+ or MariaDB 10.3+ is required.';
        }
        $existing = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE ?');
        $existing->execute(array(str_replace('_', '\\_', $in['prefix']) . '%'));
        if (!$alert && (int) $existing->fetchColumn() > 0 && $in['replace'] !== '1') {
            $errors['prefix'] = 'This database already has tables starting with “' . $in['prefix'] . '”. Use another prefix, or tick “Replace existing tables” (their data is deleted).';
        }
    }
    if (!$alert && !$errors && $pdo) {
        $old = (string) ($meta['prefix'] ?? 'site_');
        $new = $in['prefix'];
        $swap = function ($sql) use ($old, $new) {
            if ($old === $new) {
                return $sql;
            }
            if (preg_match('/^(CREATE|DROP|ALTER)\s+TABLE/i', $sql)) {
                return preg_replace('/`(fk_)?' . preg_quote($old, '/') . '/', '`$1' . $new, $sql);
            }
            return preg_replace('/^(INSERT INTO\s+)`' . preg_quote($old, '/') . '/i', '$1`' . $new, $sql, 1);
        };
        $n = 0;
        try {
            foreach (array('schema.sql', 'data.sql') as $file) {
                $path = WB_ROOT . '/database/' . $file;
                if (!is_file($path)) {
                    throw new RuntimeException('database/' . $file . ' is missing from the upload.');
                }
                foreach (SiteRuntime\SqlSplitter::split((string) file_get_contents($path)) as $stmt) {
                    $pdo->exec($swap($stmt));
                    $n++;
                }
            }
            $now = date('Y-m-d H:i:s');
            $pdo->prepare('DELETE FROM `' . $new . 'admin_users` WHERE email = ?')->execute(array(strtolower($in['owner_email'])));
            $pdo->prepare('INSERT INTO `' . $new . 'admin_users` (name, email, password_hash, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute(array($in['owner_name'], strtolower($in['owner_email']), password_hash($ownerPass, PASSWORD_DEFAULT), 'owner', 'active', $now, $now));
        } catch (Throwable $e) {
            $alert = 'The database import stopped at statement ' . ($n + 1) . ': ' . $e->getMessage() . ' Nothing was written to config/. Fix the problem and submit again (tick “Replace existing tables”).';
        }
    }
    if (!$alert && !$errors && $pdo) {
        // Clean URLs: a request to a made-up address must reach index.php (which redirects here while not configured).
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $base = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/install.php'))), '/');
        $clean = true;
        $rewriteNote = '';
        if (function_exists('curl_init')) {
            $ch = curl_init(($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $base . '/wb-rewrite-check-' . bin2hex(random_bytes(3)));
            curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 8, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0));
            curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $loc = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
            curl_close($ch);
            if ($code === 404) {
                $clean = false;
                $rewriteNote = 'Clean addresses are not available on this server (mod_rewrite or .htaccess is off), so pages use addresses like index.php?page=about. Ask your host to enable mod_rewrite, then set clean_urls to true in config/app.php.';
            } elseif ($code === 0 || ($code >= 300 && $code < 400 && stripos($loc, 'install.php') === false)) {
                $rewriteNote = 'Could not check clean addresses automatically; they are switched on. If pages other than the homepage show “Not Found”, set clean_urls to false in config/app.php.';
            }
        }
        $app = include WB_ROOT . '/config/app.example.php';
        $app = is_array($app) ? $app : array();
        $app['key'] = base64_encode(random_bytes(32));
        $app['clean_urls'] = $clean;
        $app['installed_at'] = date('c');
        unset($app['payment_emulator']);
        $pay = is_file(WB_ROOT . '/config/payment.example.php') ? include WB_ROOT . '/config/payment.example.php' : array();
        $files = array(
            'app.php' => array($app, 'Website settings (written by install.php). Keep this file private: the key encrypts saved passwords and payment keys.'),
            'database.php' => array(array('host' => $in['db_host'], 'port' => 3306, 'name' => $in['db_name'], 'user' => $in['db_user'], 'pass' => $dbPass, 'prefix' => $new), 'Database connection (written by install.php).'),
            'payment.php' => array(is_array($pay) ? $pay : array(), 'Payment gateway selection. Keys are entered in the website admin, never in files.'),
        );
        foreach ($files as $name => $f) {
            $path = WB_ROOT . '/config/' . $name;
            if (@file_put_contents($path, "<?php\n// " . $f[1] . "\nreturn " . var_export($f[0], true) . ";\n", LOCK_EX) === false) {
                $alert = 'Could not write config/' . $name . '. Make the config folder writable (755) and submit again.';
                break;
            }
            if (function_exists('posix_geteuid') && @fileowner($path) === posix_geteuid()) {
                @chmod($path, 0600);
            }
        }
        if (!$alert) {
            @file_put_contents(WB_ROOT . '/storage/install.lock', date('c') . "\n");
            @unlink(WB_ROOT . '/config/install.php');
            @unlink(WB_ROOT . '/storage/install.attempts');
            foreach (array('schema.sql', 'data.sql') as $file) {
                @unlink(WB_ROOT . '/database/' . $file);
            }
            $deleted = @unlink(__FILE__);
            $site = $base . '/';
            page('Installed', '<h1 class="ok">✓ ' . h($meta['site_name'] ?? 'Website') . ' is installed</h1>'
                . '<div class="alert alert-ok">The database was imported (' . $n . ' statements), the owner login was created and the configuration was written.</div>'
                . ($rewriteNote ? '<div class="alert alert-warn">' . h($rewriteNote) . '</div>' : '')
                . ($deleted ? '<p>For security, <code>install.php</code> has deleted itself.</p>' : '<div class="alert alert-bad"><strong>Delete <code>install.php</code> now</strong> (cPanel → File Manager). It is locked, but should not stay on the server.</div>')
                . '<p><a class="btn" href="' . h($site) . '">Open the website</a> &nbsp; <a class="btn" href="' . h($base . ($clean ? '/admin' : '/index.php/admin')) . '">Open the admin</a></p>'
                . '<h2>Next steps</h2><ol><li>Sign in to the admin with <strong>' . h(strtolower($in['owner_email'])) . '</strong>.</li><li>Settings → Email: set up SMTP so enquiries and receipts are delivered reliably.</li>'
                . '<li>If online payments are used: Payments → Settings → enter the gateway keys (test first).</li><li>Turn on SSL in cPanel (SSL/TLS Status → Run AutoSSL) if it is not on yet.</li></ol>');
            exit;
        }
    }
}

// ------------------------------------------------------------------ page
$err = function ($k) use ($errors) { return isset($errors[$k]) ? '<div class="hint bad">' . h($errors[$k]) . '</div>' : ''; };
$rows = '';
foreach ($req as $r) {
    $rows .= '<tr><td>' . h($r[0]) . ($r[1] ? '' : '<div class="hint">' . h($r[2]) . '</div>') . '</td><td class="' . ($r[1] ? 'ok' : 'bad') . '">' . ($r[1] ? '✓ OK' : '✗ Missing') . '</td></tr>';
}
$body = '<h1>Install ' . h($meta['site_name'] ?? 'your website') . '</h1>'
    . ($alert ? '<div class="alert alert-bad">' . h($alert) . '</div>' : '')
    . ($errors ? '<div class="alert alert-bad">Please correct the fields marked below.</div>' : '')
    . '<h2>1. Server check</h2><table>' . $rows . '</table>';
if (!$reqOk) {
    $body .= '<div class="alert alert-bad">Fix the items marked “Missing”, then reload this page.</div>';
    page('Install', $body);
    exit;
}
$body .= '<form method="post" autocomplete="off"><input type="hidden" name="_csrf" value="' . h($csrf) . '">'
    . '<h2>2. Installation key</h2><label for="k">Installation key</label><input type="text" id="k" name="install_key" placeholder="XXXX-XXXX-XXXX" required>'
    . '<div class="hint">Shown on the builder’s export page and in DEPLOYMENT_CHECKLIST.md. It stops anyone else from installing over your website.</div>' . $err('install_key')
    . '<h2>3. Database</h2><div class="hint">Create it in cPanel → <strong>MySQL Databases</strong>: a database, a user, then add the user to the database with ALL PRIVILEGES. cPanel adds your account name in front of both (e.g. <code>myacct_site</code>).</div>'
    . '<div class="row"><div><label for="dbn">Database name</label><input type="text" id="dbn" name="db_name" value="' . h($in['db_name']) . '" required></div>'
    . '<div><label for="dbu">Database user</label><input type="text" id="dbu" name="db_user" value="' . h($in['db_user']) . '" required></div></div>'
    . $err('db_name')
    . '<div class="row"><div><label for="dbp">Database password</label><input type="password" id="dbp" name="db_pass"></div>'
    . '<div><label for="dbh">Database host</label><input type="text" id="dbh" name="db_host" value="' . h($in['db_host']) . '"><div class="hint">Almost always “localhost”.</div></div></div>'
    . '<div class="row"><div><label for="pre">Table prefix</label><input type="text" id="pre" name="prefix" value="' . h($in['prefix']) . '">' . $err('prefix') . '</div>'
    . '<div><label>&nbsp;</label><label style="font-weight:400"><input type="checkbox" name="replace" value="1"' . ($in['replace'] === '1' ? ' checked' : '') . '> Replace existing tables with this prefix</label></div></div>'
    . '<h2>4. Owner login for the website admin</h2>'
    . '<div class="row"><div><label for="on">Name</label><input type="text" id="on" name="owner_name" value="' . h($in['owner_name']) . '" required>' . $err('owner_name') . '</div>'
    . '<div><label for="oe">Email</label><input type="email" id="oe" name="owner_email" value="' . h($in['owner_email']) . '" required>' . $err('owner_email') . '</div></div>'
    . '<div class="row"><div><label for="op">Password</label><input type="password" id="op" name="owner_pass" autocomplete="new-password" required><div class="hint">At least 10 characters with letters and numbers.</div>' . $err('owner_pass') . '</div>'
    . '<div><label for="op2">Confirm password</label><input type="password" id="op2" name="owner_pass2" autocomplete="new-password" required>' . $err('owner_pass2') . '</div></div>'
    . '<button class="btn" type="submit">Install the website</button></form>';
page('Install', $body);
