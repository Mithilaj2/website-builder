<?php
declare(strict_types=1);

/**
 * One-time builder installer.
 * Checks requirements, creates the builder tables, seeds categories, creates the first
 * Super Admin and writes config/config.php. Locks itself (storage/setup.lock) afterwards.
 */

$base = dirname(__DIR__);
$configFile = $base . '/config/config.php';
$lockFile = $base . '/storage/setup.lock';

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

if (is_file($lockFile) || is_file($configFile)) {
    http_response_code(403);
    echo '<!doctype html><meta charset="utf-8"><title>Setup locked</title><div style="font-family:system-ui;max-width:600px;margin:15vh auto">'
        . '<h1>Setup is locked</h1><p>The builder is already installed. For security, delete <code>public/setup.php</code> from the server.</p>'
        . '<p>To reinstall, remove <code>config/config.php</code> and <code>storage/setup.lock</code> first.</p></div>';
    exit;
}

session_name('CSWBSETUP');
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
if (empty($_SESSION['setup_csrf'])) {
    $_SESSION['setup_csrf'] = bin2hex(random_bytes(32));
}

// ---- Requirements ---------------------------------------------------------
$checks = [
    'PHP 8.1 or newer' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'PDO MySQL extension' => extension_loaded('pdo_mysql'),
    'mbstring extension' => extension_loaded('mbstring'),
    'fileinfo extension' => extension_loaded('fileinfo'),
    'GD extension (image processing)' => extension_loaded('gd'),
    'Zip extension' => class_exists('ZipArchive'),
    'OpenSSL extension' => extension_loaded('openssl'),
    'Sodium extension' => extension_loaded('sodium'),
    'config/ is writable' => is_writable($base . '/config'),
    'storage/ is writable' => is_writable($base . '/storage'),
    'public/uploads/ is writable' => is_writable($base . '/public/uploads'),
    'templates/ is writable' => is_writable($base . '/templates'),
    'generated_websites/ is writable' => is_writable($base . '/generated_websites'),
];
$allOk = !in_array(false, $checks, true);

// ---- Defaults --------------------------------------------------------------
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/setup.php')), '/');
// If the site is reached through the root .htaccess rewrite, /public is not part of public URLs.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
if (str_ends_with($dir, '/public') && !str_contains($requestPath, '/public/')) {
    $dir = substr($dir, 0, -7);
}
$defaults = [
    'base_url' => ($https ? 'https' : 'http') . '://' . $host . $dir,
    'db_host' => 'localhost', 'db_port' => '3306', 'db_name' => '', 'db_user' => '', 'db_pass' => '',
    'admin_name' => 'Super Admin', 'admin_email' => 'info@channelsoftech.com',
];

$errors = [];
$input = $defaults;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['setup_csrf'], (string) ($_POST['_token'] ?? ''))) {
        $errors[] = 'Your session expired. Please submit the form again.';
    }
    foreach ($defaults as $k => $v) {
        $input[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $input['db_pass'] = (string) ($_POST['db_pass'] ?? '');
    $password = (string) ($_POST['admin_password'] ?? '');
    $password2 = (string) ($_POST['admin_password_confirmation'] ?? '');

    if (!$allOk) {
        $errors[] = 'Fix the failed requirements first.';
    }
    if (!filter_var($input['base_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Base URL must be a full URL, e.g. https://builder.example.com';
    }
    if ($input['db_name'] === '' || $input['db_user'] === '') {
        $errors[] = 'Database name and user are required.';
    }
    if (!filter_var($input['admin_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Super Admin email is not valid.';
    }
    if ($input['admin_name'] === '') {
        $errors[] = 'Super Admin name is required.';
    }
    if (strlen($password) < 10 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = 'Password must be at least 10 characters and include letters and numbers.';
    }
    if ($password !== $password2) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $input['db_host'], (int) $input['db_port'], $input['db_name']),
                $input['db_user'],
                $input['db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            // Run schema + seeds statement by statement.
            foreach (['/database/schema.sql', '/database/seeds/categories.sql'] as $sqlFile) {
                $sql = (string) file_get_contents($base . $sqlFile);
                $sql = preg_replace('/^\s*--.*$/m', '', $sql);
                foreach (array_filter(array_map('trim', explode(';', (string) $sql))) as $stmt) {
                    $pdo->exec($stmt);
                }
            }
            $exists = $pdo->prepare('SELECT COUNT(*) FROM wb_users WHERE email = ?');
            $exists->execute([strtolower($input['admin_email'])]);
            if ((int) $exists->fetchColumn() === 0) {
                $now = date('Y-m-d H:i:s');
                $pdo->prepare('INSERT INTO wb_users (name, email, password_hash, role, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$input['admin_name'], strtolower($input['admin_email']), password_hash($password, PASSWORD_DEFAULT), 'super_admin', 'active', $now, $now]);
            }

            $config = [
                'app' => [
                    'name' => 'Channel Softech Website Builder',
                    'base_url' => rtrim($input['base_url'], '/'),
                    'debug' => false,
                    'timezone' => 'Asia/Kolkata',
                    'key' => base64_encode(random_bytes(32)),
                ],
                'db' => [
                    'host' => $input['db_host'], 'port' => (int) $input['db_port'], 'name' => $input['db_name'],
                    'user' => $input['db_user'], 'pass' => $input['db_pass'], 'charset' => 'utf8mb4',
                ],
                'session' => ['name' => 'CSWBSESSID', 'idle_timeout' => 1800],
            ];
            $php = "<?php\n// Generated by setup.php on " . date('Y-m-d H:i:s') . ". Keep this file private.\nreturn " . var_export($config, true) . ";\n";
            if (file_put_contents($configFile, $php, LOCK_EX) === false) {
                throw new RuntimeException('Could not write config/config.php.');
            }
            @chmod($configFile, 0600);
            file_put_contents($lockFile, date('c'));
            $_SESSION = [];
            session_destroy();
            $done = rtrim($input['base_url'], '/') . '/login';
        } catch (Throwable $e) {
            $errors[] = 'Installation failed: ' . $e->getMessage();
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Setup · Channel Softech Website Builder</title>
<link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-body">
<div class="container py-5" style="max-width:760px">
  <div class="text-center mb-4"><img src="assets/images/logo.png" alt="Channel Softech" style="max-width:260px"></div>
  <div class="card shadow-sm border-0"><div class="card-body p-4">
    <h1 class="h4 mb-1">Builder setup</h1>
    <p class="text-muted">Run once to install the Channel Softech Website Builder.</p>

    <?php if (!empty($done)): ?>
      <div class="alert alert-success"><strong>Installation complete.</strong> Delete <code>public/setup.php</code> from the server, then log in.</div>
      <a class="btn btn-primary" href="<?= h($done) ?>">Go to login</a>
    <?php else: ?>
      <h2 class="h6 mt-3">Requirements</h2>
      <ul class="list-group mb-4">
        <?php foreach ($checks as $label => $ok): ?>
          <li class="list-group-item d-flex justify-content-between"><span><?= h($label) ?></span>
            <span class="badge text-bg-<?= $ok ? 'success' : 'danger' ?>"><?= $ok ? 'OK' : 'Missing' ?></span></li>
        <?php endforeach; ?>
      </ul>

      <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= h($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

      <form method="post" autocomplete="off">
        <input type="hidden" name="_token" value="<?= h($_SESSION['setup_csrf']) ?>">
        <h2 class="h6">Builder URL</h2>
        <div class="mb-3"><label class="form-label">Base URL</label><input class="form-control" name="base_url" value="<?= h($input['base_url']) ?>" required>
          <div class="form-text">The address you will open the builder at, without a trailing slash.</div></div>

        <h2 class="h6 mt-4">Database (create it first in cPanel → MySQL Databases)</h2>
        <div class="row g-3">
          <div class="col-md-8"><label class="form-label">Host</label><input class="form-control" name="db_host" value="<?= h($input['db_host']) ?>" required></div>
          <div class="col-md-4"><label class="form-label">Port</label><input class="form-control" name="db_port" value="<?= h($input['db_port']) ?>" required></div>
          <div class="col-md-12"><label class="form-label">Database name</label><input class="form-control" name="db_name" value="<?= h($input['db_name']) ?>" placeholder="cpaneluser_wbmaster" required></div>
          <div class="col-md-6"><label class="form-label">Database user</label><input class="form-control" name="db_user" value="<?= h($input['db_user']) ?>" required></div>
          <div class="col-md-6"><label class="form-label">Database password</label><input type="password" class="form-control" name="db_pass"></div>
        </div>

        <h2 class="h6 mt-4">First Super Admin</h2>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="admin_name" value="<?= h($input['admin_name']) ?>" required></div>
          <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="admin_email" value="<?= h($input['admin_email']) ?>" required></div>
          <div class="col-md-6"><label class="form-label">Password</label><input type="password" class="form-control" name="admin_password" required minlength="10"></div>
          <div class="col-md-6"><label class="form-label">Confirm password</label><input type="password" class="form-control" name="admin_password_confirmation" required minlength="10"></div>
        </div>
        <button class="btn btn-primary mt-4" <?= $allOk ? '' : 'disabled' ?>>Install</button>
      </form>
    <?php endif; ?>
  </div></div>
</div>
</body>
</html>
