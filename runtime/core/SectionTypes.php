<?php
declare(strict_types=1);

namespace SiteRuntime;

/**
 * Section type schemas: the configurable fields of each section type.
 * Core types live in runtime/section-types/*.json; templates may add their own.
 *
 * Field types: text, textarea, richtext, image, color, link, number, select, toggle, icon, repeater.
 */
final class SectionTypes
{
    public const FIELD_TYPES = ['text', 'textarea', 'richtext', 'image', 'color', 'link', 'number', 'select', 'toggle', 'icon', 'repeater'];

    /** @var array<string, array> */
    private array $schemas = [];

    public function __construct(string $coreDir, ?string $themeDir = null, ?array $manifest = null)
    {
        foreach (glob(rtrim($coreDir, '/') . '/*.json') ?: [] as $file) {
            $s = json_decode((string) file_get_contents($file), true);
            if (is_array($s) && isset($s['type'])) {
                $this->schemas[$s['type']] = $s;
            }
        }
        foreach ((array) ($manifest['custom_section_types'] ?? []) as $type => $rel) {
            $path = $themeDir . '/' . $rel;
            if (is_string($rel) && !str_contains($rel, '..') && is_file($path)) {
                $s = json_decode((string) file_get_contents($path), true);
                if (is_array($s)) {
                    $this->schemas[$type] = ['type' => $type] + $s;
                }
            }
        }
    }

    public function get(string $type): ?array
    {
        return $this->schemas[$type] ?? null;
    }

    /** Schemas restricted to the given (template-supported) types, in that order. */
    public function only(array $types): array
    {
        $out = [];
        foreach ($types as $t) {
            if (isset($this->schemas[$t])) {
                $out[$t] = $this->schemas[$t];
            }
        }
        return $out;
    }

    public function all(): array
    {
        return $this->schemas;
    }

    /** Default data for a type, built from field defaults. */
    public function defaults(string $type): array
    {
        $schema = $this->get($type);
        return $schema ? self::fieldDefaults($schema['fields'] ?? []) : [];
    }

    private static function fieldDefaults(array $fields): array
    {
        $out = [];
        foreach ($fields as $f) {
            $key = $f['key'];
            if ($f['type'] === 'repeater') {
                $out[$key] = array_map(static fn ($item) => is_array($item) ? $item : [], (array) ($f['default'] ?? []));
            } elseif ($f['type'] === 'link') {
                $out[$key] = (array) ($f['default'] ?? ['text' => '', 'url' => '']);
            } elseif ($f['type'] === 'toggle') {
                $out[$key] = (bool) ($f['default'] ?? false);
            } else {
                $out[$key] = $f['default'] ?? '';
            }
        }
        return $out;
    }

    /**
     * Validate and clean submitted section data against the schema.
     *
     * @param callable(string):?string $richtext  sanitises HTML (e.g. HTML Purifier)
     * @param callable(string):bool    $imageOk   true if an image reference is allowed for this site
     * @return array{0: array, 1: array<string,string>} [clean data, errors keyed by field path]
     */
    public function sanitize(string $type, array $input, callable $richtext, callable $imageOk): array
    {
        $schema = $this->get($type);
        if (!$schema) {
            return [[], ['_' => 'Unknown section type.']];
        }
        $errors = [];
        $data = self::clean($schema['fields'] ?? [], $input, $richtext, $imageOk, $errors, '');
        return [$data, $errors];
    }

    private static function clean(array $fields, array $input, callable $richtext, callable $imageOk, array &$errors, string $path): array
    {
        $out = [];
        foreach ($fields as $f) {
            $key = $f['key'];
            $label = $f['label'] ?? $key;
            $p = $path . $key;
            $v = $input[$key] ?? null;
            $max = (int) ($f['max'] ?? 0);
            switch ($f['type']) {
                case 'text':
                case 'icon':
                    $v = is_scalar($v) ? trim(strip_tags((string) $v)) : '';
                    $limit = $max ?: ($f['type'] === 'icon' ? 60 : 300);
                    if (mb_strlen($v) > $limit) {
                        $errors[$p] = "$label may not exceed $limit characters.";
                    }
                    if ($f['type'] === 'icon' && $v !== '' && !preg_match('/^bi-[a-z0-9-]+$/', $v)) {
                        $errors[$p] = "$label must be a Bootstrap icon name such as bi-briefcase.";
                    }
                    $out[$key] = $v;
                    break;
                case 'textarea':
                    $v = is_scalar($v) ? trim(strip_tags((string) $v)) : '';
                    $limit = $max ?: 2000;
                    if (mb_strlen($v) > $limit) {
                        $errors[$p] = "$label may not exceed $limit characters.";
                    }
                    $out[$key] = $v;
                    break;
                case 'richtext':
                    $v = is_scalar($v) ? (string) $v : '';
                    if (mb_strlen($v) > 100000) {
                        $errors[$p] = "$label is too long.";
                        $v = '';
                    }
                    $out[$key] = trim((string) $richtext($v));
                    break;
                case 'image':
                    $v = is_scalar($v) ? trim((string) $v) : '';
                    if ($v !== '' && !$imageOk($v)) {
                        $errors[$p] = "$label must be chosen from this website's media library.";
                        $v = '';
                    }
                    $out[$key] = $v;
                    break;
                case 'color':
                    $v = is_scalar($v) ? trim((string) $v) : '';
                    if ($v !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $v)) {
                        $errors[$p] = "$label must be a colour like #1A2B3C.";
                        $v = '';
                    }
                    $out[$key] = $v;
                    break;
                case 'link':
                    $text = is_array($v) && is_scalar($v['text'] ?? null) ? trim(strip_tags((string) $v['text'])) : '';
                    $url = is_array($v) && is_scalar($v['url'] ?? null) ? trim((string) $v['url']) : '';
                    if ($url !== '' && !self::validUrl($url)) {
                        $errors[$p] = "$label link must be a page (page:slug), a full http(s) URL, #anchor, mailto: or tel:.";
                        $url = '';
                    }
                    $out[$key] = ['text' => mb_substr($text, 0, 80), 'url' => mb_substr($url, 0, 500)];
                    break;
                case 'number':
                    if ($v === null || $v === '') {
                        $out[$key] = '';
                        break;
                    }
                    if (!is_numeric($v)) {
                        $errors[$p] = "$label must be a number.";
                        $out[$key] = '';
                        break;
                    }
                    $n = $v + 0;
                    if (isset($f['min']) && $n < $f['min']) {
                        $errors[$p] = "$label must be at least {$f['min']}.";
                    }
                    if (isset($f['max']) && $n > $f['max']) {
                        $errors[$p] = "$label may not exceed {$f['max']}.";
                    }
                    $out[$key] = $n;
                    break;
                case 'select':
                    $opts = array_map('strval', array_keys((array) ($f['options'] ?? [])));
                    $v = is_scalar($v) ? (string) $v : '';
                    $out[$key] = in_array($v, $opts, true) ? $v : (string) ($f['default'] ?? ($opts[0] ?? ''));
                    break;
                case 'toggle':
                    $out[$key] = in_array($v, [true, 1, '1', 'true', 'on'], true);
                    break;
                case 'repeater':
                    $items = is_array($v) ? array_values($v) : [];
                    $maxItems = (int) ($f['max_items'] ?? 24);
                    if (count($items) > $maxItems) {
                        $errors[$p] = "$label may have at most $maxItems items.";
                        $items = array_slice($items, 0, $maxItems);
                    }
                    $minItems = (int) ($f['min_items'] ?? 0);
                    if (count($items) < $minItems) {
                        $errors[$p] = "$label needs at least $minItems item(s).";
                    }
                    $out[$key] = [];
                    foreach ($items as $i => $item) {
                        $out[$key][] = self::clean($f['fields'] ?? [], is_array($item) ? $item : [], $richtext, $imageOk, $errors, "$p.$i.");
                    }
                    break;
            }
            if (!empty($f['required']) && ($out[$key] === '' || $out[$key] === [] || (is_array($out[$key]) && ($out[$key]['url'] ?? null) === ''))) {
                $errors[$p] ??= "$label is required.";
            }
        }
        return $out;
    }

    public static function validUrl(string $url): bool
    {
        if (preg_match('/^page:[a-z0-9-]*$/', $url) || preg_match('/^#[A-Za-z][\w-]*$/', $url)) {
            return true;
        }
        if (preg_match('/^(mailto:[^\s<>"]+|tel:\+?[0-9 ()\-]{3,20})$/i', $url)) {
            return true;
        }
        return (bool) filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url);
    }
}
