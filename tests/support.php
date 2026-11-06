<?php
declare(strict_types=1);

/** Shared helpers for the HTTP test scripts. */

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
            $hasFile = (bool) array_filter($data, static fn ($v) => $v instanceof CURLFile);
            // Arrays (ids[]) must be url-encoded; multipart is only needed for file uploads.
            curl_setopt($ch, CURLOPT_POSTFIELDS, $hasFile ? $data : http_build_query($data));
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

