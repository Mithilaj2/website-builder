<?php
declare(strict_types=1);

namespace App\Modules\Templates;

/**
 * Validates a template folder: manifest structure, required files, and a security scan.
 *
 * Template contract (see docs/TEMPLATE_GUIDE.md):
 *  manifest.json, layouts/default.php, partials/header.php, partials/footer.php,
 *  sections/<type>.php for every declared section type, assets/.
 *  PHP is allowed only in layouts/, partials/ and sections/, and may not call
 *  dangerous functions, include files, or read request superglobals.
 */
final class ManifestValidator
{
    public const ID_PATTERN = '/^[a-z0-9]+(?:_[a-z0-9]+)*$/';

    private const PHP_DIRS = ['layouts', 'partials', 'sections'];

    private const FORBIDDEN_EXT = ['phar', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'pht', 'sh', 'bash', 'exe', 'bat', 'cmd', 'com', 'pl', 'py', 'cgi', 'htaccess', 'htpasswd', 'ini', 'user.ini', 'so', 'dll'];

    private const FORBIDDEN_FUNCS = [
        'eval', 'assert', 'exec', 'shell_exec', 'system', 'passthru', 'proc_open', 'popen', 'pcntl_exec',
        'create_function', 'call_user_func', 'call_user_func_array', 'preg_replace_callback',
        'file_put_contents', 'file_get_contents', 'fopen', 'fwrite', 'fputs', 'file', 'readfile', 'unlink', 'rename',
        'copy', 'mkdir', 'rmdir', 'chmod', 'chown', 'symlink', 'link', 'tempnam', 'move_uploaded_file',
        'curl_init', 'curl_exec', 'fsockopen', 'pfsockopen', 'stream_socket_client', 'mail', 'header', 'setcookie',
        'base64_decode', 'gzinflate', 'gzuncompress', 'str_rot13', 'ini_set', 'putenv', 'dl', 'extract', 'parse_str',
        'mysqli_connect', 'mysqli_query', 'pg_connect', 'phpinfo', 'getenv', 'posix_kill', 'session_start',
    ];

    private const FORBIDDEN_TOKENS = [T_EVAL, T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE, T_GLOBAL, T_HALT_COMPILER];

    private const SUPERGLOBALS = ['$_GET', '$_POST', '$_REQUEST', '$_FILES', '$_COOKIE', '$_SERVER', '$_SESSION', '$_ENV', '$GLOBALS'];

    private array $errors = [];
    private array $warnings = [];
    private ?array $manifest = null;

    /**
     * @param array<string> $categorySlugs known category slugs
     */
    public function validate(string $dir, array $categorySlugs, ?string $expectedId = null, ?string $expectedCategory = null): self
    {
        $this->errors = $this->warnings = [];
        $this->manifest = null;
        $dir = rtrim($dir, '/');

        if (!is_file("$dir/manifest.json")) {
            $this->errors[] = 'manifest.json is missing from the template root.';
            return $this;
        }
        $m = json_decode((string) file_get_contents("$dir/manifest.json"), true);
        if (!is_array($m)) {
            $this->errors[] = 'manifest.json is not valid JSON: ' . json_last_error_msg();
            return $this;
        }
        $this->manifest = $m;
        $this->checkManifest($m, $dir, $categorySlugs, $expectedId, $expectedCategory);
        $this->checkFiles($dir);
        return $this;
    }

    public function ok(): bool
    {
        return $this->errors === [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function warnings(): array
    {
        return $this->warnings;
    }

    public function manifest(): ?array
    {
        return $this->manifest;
    }

    public function report(): array
    {
        return ['ok' => $this->ok(), 'errors' => $this->errors, 'warnings' => $this->warnings];
    }

    private function checkManifest(array $m, string $dir, array $categorySlugs, ?string $expectedId, ?string $expectedCategory): void
    {
        foreach (['id', 'name', 'category', 'version', 'section_types', 'page_types', 'default_pages', 'theme'] as $key) {
            if (!array_key_exists($key, $m)) {
                $this->errors[] = "manifest.json: \"$key\" is required.";
            }
        }
        if ($this->errors) {
            return;
        }

        if (!is_string($m['id']) || !preg_match(self::ID_PATTERN, $m['id']) || strlen($m['id']) > 80) {
            $this->errors[] = 'manifest.json: "id" must use lowercase letters, numbers and underscores (e.g. business_template_01).';
        } elseif ($expectedId !== null && $m['id'] !== $expectedId) {
            $this->errors[] = "manifest.json: \"id\" is \"{$m['id']}\" but must be \"$expectedId\".";
        }
        if (!is_string($m['name']) || trim($m['name']) === '' || mb_strlen($m['name']) > 150) {
            $this->errors[] = 'manifest.json: "name" must be 1–150 characters.';
        }
        if (!is_string($m['category']) || !in_array($m['category'], $categorySlugs, true)) {
            $this->errors[] = 'manifest.json: "category" must be an existing category slug (' . implode(', ', $categorySlugs) . ').';
        } elseif ($expectedCategory !== null && $m['category'] !== $expectedCategory) {
            $this->errors[] = "manifest.json: \"category\" is \"{$m['category']}\" but this template belongs to \"$expectedCategory\".";
        }
        if (!is_string($m['version']) || !preg_match('/^\d+\.\d+\.\d+$/', $m['version'])) {
            $this->errors[] = 'manifest.json: "version" must look like 1.0.0.';
        }

        // Section types
        $types = $m['section_types'];
        if (!is_array($types) || $types === [] || array_values($types) !== $types) {
            $this->errors[] = 'manifest.json: "section_types" must be a non-empty list.';
            $types = [];
        }
        $custom = is_array($m['custom_section_types'] ?? null) ? $m['custom_section_types'] : [];
        foreach ($types as $t) {
            if (!is_string($t) || !preg_match(self::ID_PATTERN, $t)) {
                $this->errors[] = 'manifest.json: invalid section type name "' . (is_string($t) ? $t : json_encode($t)) . '".';
                continue;
            }
            if (!is_file("$dir/sections/$t.php")) {
                $this->errors[] = "Section type \"$t\" has no view file sections/$t.php.";
            }
        }
        foreach ($custom as $type => $schemaFile) {
            if (!in_array($type, $types, true)) {
                $this->errors[] = "Custom section type \"$type\" must also be listed in section_types.";
            }
            $schemaPath = is_string($schemaFile) ? $this->safeRelative($dir, $schemaFile) : null;
            if ($schemaPath === null || !is_file($schemaPath)) {
                $this->errors[] = "Custom section type \"$type\": schema file not found.";
            } elseif (!is_array(json_decode((string) file_get_contents($schemaPath), true))) {
                $this->errors[] = "Custom section type \"$type\": schema file is not valid JSON.";
            }
        }

        // Page types
        if (!is_array($m['page_types']) || $m['page_types'] === [] || array_values($m['page_types']) === $m['page_types']) {
            $this->errors[] = 'manifest.json: "page_types" must be an object of page type definitions.';
        } else {
            foreach ($m['page_types'] as $pt => $def) {
                if (!preg_match(self::ID_PATTERN, (string) $pt)) {
                    $this->errors[] = "manifest.json: invalid page type name \"$pt\".";
                }
                foreach ((array) ($def['default_sections'] ?? []) as $s) {
                    if (!in_array($s, $types, true)) {
                        $this->errors[] = "Page type \"$pt\" uses section \"$s\" which is not in section_types.";
                    }
                }
                if (isset($def['layout']) && !is_file("$dir/layouts/" . basename((string) $def['layout']) . '.php')) {
                    $this->errors[] = "Page type \"$pt\" uses layout \"{$def['layout']}\" which does not exist in layouts/.";
                }
            }
        }

        // Default pages
        if (!is_array($m['default_pages']) || $m['default_pages'] === []) {
            $this->errors[] = 'manifest.json: "default_pages" must be a non-empty list.';
        } else {
            $homes = 0;
            $slugs = [];
            foreach ($m['default_pages'] as $i => $p) {
                $label = 'default_pages[' . $i . ']';
                if (!is_array($p) || empty($p['title']) || !isset($p['slug']) || empty($p['type'])) {
                    $this->errors[] = "manifest.json: $label needs title, slug and type.";
                    continue;
                }
                if ($p['slug'] !== '' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $p['slug'])) {
                    $this->errors[] = "manifest.json: $label slug \"{$p['slug']}\" is invalid.";
                }
                if (in_array($p['slug'], $slugs, true)) {
                    $this->errors[] = "manifest.json: duplicate page slug \"{$p['slug']}\".";
                }
                $slugs[] = $p['slug'];
                if (is_array($m['page_types']) && !isset($m['page_types'][$p['type']])) {
                    $this->errors[] = "manifest.json: $label type \"{$p['type']}\" is not defined in page_types.";
                }
                $homes += !empty($p['is_home']) ? 1 : 0;
            }
            if ($homes !== 1) {
                $this->errors[] = 'manifest.json: exactly one default page must have "is_home": true.';
            }
        }

        if (!is_array($m['theme']) || !is_array($m['theme']['defaults'] ?? null)) {
            $this->errors[] = 'manifest.json: "theme.defaults" is required.';
        } else {
            foreach (['primary', 'secondary', 'bg', 'text'] as $c) {
                $v = $m['theme']['defaults'][$c] ?? null;
                if (!is_string($v) || !preg_match('/^#[0-9a-fA-F]{6}$/', $v)) {
                    $this->errors[] = "manifest.json: theme.defaults.$c must be a colour like #1A2B3C.";
                }
            }
        }
        foreach (['features', 'optional_features'] as $f) {
            if (isset($m[$f]) && (!is_array($m[$f]) || array_diff($m[$f], ['contact', 'blog', 'listings', 'payments', 'ecommerce']))) {
                $this->errors[] = "manifest.json: \"$f\" may only contain contact, blog, listings, payments, ecommerce.";
            }
        }
    }

    private function checkFiles(string $dir): void
    {
        foreach (['layouts/default.php', 'partials/header.php', 'partials/footer.php'] as $req) {
            if (!is_file("$dir/$req")) {
                $this->errors[] = "Required file $req is missing.";
            }
        }
        if (!is_dir("$dir/assets")) {
            $this->errors[] = 'Required folder assets/ is missing.';
        }

        $total = 0;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            /** @var \SplFileInfo $file */
            $rel = substr(str_replace('\\', '/', $file->getPathname()), strlen($dir) + 1);
            if ($file->isLink()) {
                $this->errors[] = "Symbolic links are not allowed ($rel).";
                continue;
            }
            $total += $file->getSize();
            $name = strtolower($file->getFilename());
            $ext = strtolower($file->getExtension());
            if (in_array($ext, self::FORBIDDEN_EXT, true) || in_array($name, ['.htaccess', '.user.ini', 'php.ini'], true)) {
                $this->errors[] = "File type not allowed in templates: $rel";
                continue;
            }
            if ($ext === 'php') {
                $top = explode('/', $rel)[0];
                if (!in_array($top, self::PHP_DIRS, true)) {
                    $this->errors[] = "PHP files are only allowed in layouts/, partials/ and sections/ ($rel).";
                    continue;
                }
                $this->scanPhp($file->getPathname(), $rel);
            } elseif (in_array($ext, ['css', 'html', 'js'], true)) {
                $this->scanExternal((string) file_get_contents($file->getPathname()), $rel);
            }
        }
        if ($total > 150 * 1024 * 1024) {
            $this->errors[] = 'Template is larger than 150 MB.';
        }
    }

    private function scanPhp(string $path, string $rel): void
    {
        $code = (string) file_get_contents($path);
        try {
            $tokens = token_get_all($code, TOKEN_PARSE);
        } catch (\ParseError $e) {
            $this->errors[] = "$rel: PHP syntax error on line {$e->getLine()}: {$e->getMessage()}";
            return;
        }
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $t = $tokens[$i];
            if (is_string($t)) {
                if ($t === '`') {
                    $this->errors[] = "$rel: backtick shell execution is not allowed.";
                }
                continue;
            }
            [$id, $text, $line] = $t;
            if (in_array($id, self::FORBIDDEN_TOKENS, true)) {
                $this->errors[] = "$rel line $line: \"" . strtolower(trim($text)) . '" is not allowed in templates (use $view->partial() for partials).';
                continue;
            }
            if ($id === T_VARIABLE && in_array($text, self::SUPERGLOBALS, true)) {
                $this->errors[] = "$rel line $line: $text may not be used in templates; use the data passed to the view.";
                continue;
            }
            if ($id === T_STRING || $id === T_NAME_FULLY_QUALIFIED) {
                $fn = strtolower(ltrim($text, '\\'));
                if (in_array($fn, self::FORBIDDEN_FUNCS, true)) {
                    // Function call if the next non-whitespace token is "(" and the previous is not -> or ::
                    $next = $this->nextSignificant($tokens, $i);
                    $prev = $this->prevSignificant($tokens, $i);
                    $isMethod = is_array($prev) && in_array($prev[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true);
                    if ($next === '(' && !$isMethod) {
                        $this->errors[] = "$rel line $line: function $fn() is not allowed in templates.";
                    }
                }
            }
            if ($id === T_VARIABLE && $this->nextSignificant($tokens, $i) === '(') {
                $this->errors[] = "$rel line $line: variable function calls are not allowed in templates.";
            }
        }
    }

    private function scanExternal(string $content, string $rel): void
    {
        if (preg_match_all('#(?:src|href)\s*=\s*["\'](https?:)?//([^"\'/]+)|url\(\s*["\']?(https?:)?//([^)"\']+)|@import\s+["\'](https?:)?//([^"\']+)#i', $content, $m)) {
            $hosts = array_unique(array_filter(array_merge($m[2], $m[4], $m[6])));
            // Links to the client's own social/profile pages are content, not assets; we only flag asset loads.
            $this->warnings[] = "$rel references external resources (" . implode(', ', array_slice($hosts, 0, 3)) . '). Generated sites must bundle all assets locally.';
        }
        if (preg_match('#(?:src|href)\s*=\s*["\'](?:\.\./){3,}|url\(\s*["\']?(?:\.\./){3,}#i', $content)) {
            $this->warnings[] = "$rel references files far outside its folder (../../../). Templates must only use their own files.";
        }
    }

    private function nextSignificant(array $tokens, int $i): mixed
    {
        for ($j = $i + 1, $n = count($tokens); $j < $n; $j++) {
            if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            return $tokens[$j];
        }
        return null;
    }

    private function prevSignificant(array $tokens, int $i): mixed
    {
        for ($j = $i - 1; $j >= 0; $j--) {
            if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            return $tokens[$j];
        }
        return null;
    }

    private function safeRelative(string $dir, string $rel): ?string
    {
        if (str_contains($rel, '..') || str_starts_with($rel, '/') || str_contains($rel, '\\')) {
            return null;
        }
        return $dir . '/' . $rel;
    }
}
