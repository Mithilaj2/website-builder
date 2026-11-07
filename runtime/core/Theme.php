<?php
declare(strict_types=1);

namespace SiteRuntime;

/**
 * Turns the site's theme settings into CSS: @font-face rules for the selected bundled fonts
 * and the --wb-* custom properties every template is built on.
 */
final class Theme
{
    public const BUTTON_STYLES = ['solid' => 'Solid', 'outline' => 'Outline', 'pill' => 'Pill (rounded)', 'sharp' => 'Sharp (square)'];
    public const RADII = ['0px' => 'None', '4px' => 'Small', '8px' => 'Medium', '14px' => 'Large', '24px' => 'Extra large'];

    public static function fonts(string $fontsDir): array
    {
        static $cache = [];
        return $cache[$fontsDir] ??= (json_decode((string) @file_get_contents($fontsDir . '/fonts.json'), true) ?: []);
    }

    /** Merge saved theme settings over the template defaults, dropping invalid values. */
    public static function resolve(array $defaults, array $saved, array $fonts): array
    {
        $t = array_merge($defaults, array_filter($saved, static fn ($v) => $v !== null && $v !== ''));
        foreach (['primary', 'secondary', 'bg', 'text'] as $c) {
            if (!is_string($t[$c] ?? null) || !preg_match('/^#[0-9a-fA-F]{6}$/', $t[$c])) {
                $t[$c] = $defaults[$c] ?? '#000000';
            }
        }
        foreach (['heading_font' => 'inter', 'body_font' => 'inter'] as $k => $fallback) {
            $key = self::fontKey((string) ($t[$k] ?? ''), $fonts);
            $t[$k] = $key ?? (self::fontKey((string) ($defaults[$k] ?? ''), $fonts) ?? $fallback);
        }
        if (!isset(self::RADII[$t['radius'] ?? ''])) {
            $t['radius'] = isset(self::RADII[$defaults['radius'] ?? '']) ? $defaults['radius'] : '8px';
        }
        if (!isset(self::BUTTON_STYLES[$t['button_style'] ?? ''])) {
            $t['button_style'] = 'solid';
        }
        return $t;
    }

    /** Accepts a font key ("dm-sans") or display name ("DM Sans"). */
    public static function fontKey(string $value, array $fonts): ?string
    {
        if (isset($fonts[$value])) {
            return $value;
        }
        foreach ($fonts as $k => $f) {
            if (strcasecmp($f['name'], $value) === 0) {
                return $k;
            }
        }
        return null;
    }

    /**
     * @param string $fontUrlPattern e.g. "/fonts/{path}" — {path} becomes "inter/400.woff2"
     */
    public static function css(array $theme, array $fonts, string $fontUrlPattern): string
    {
        $css = '';
        foreach (array_unique([$theme['heading_font'], $theme['body_font']]) as $key) {
            $f = $fonts[$key] ?? null;
            if (!$f) {
                continue;
            }
            foreach ($f['weights'] as $w) {
                $url = str_replace('{path}', rawurlencode($key) . '/' . (int) $w . '.woff2', $fontUrlPattern);
                $css .= sprintf("@font-face{font-family:'%s';font-style:normal;font-weight:%d;font-display:swap;src:url('%s') format('woff2');}\n", $f['name'], $w, $url);
            }
        }
        $heading = ($fonts[$theme['heading_font']]['name'] ?? 'Inter');
        $body = ($fonts[$theme['body_font']]['name'] ?? 'Inter');
        $vars = [
            '--wb-primary' => $theme['primary'],
            '--wb-primary-rgb' => self::rgb($theme['primary']),
            '--wb-primary-dark' => self::shade($theme['primary'], -0.18),
            '--wb-primary-light' => self::shade($theme['primary'], 0.88),
            '--wb-secondary' => $theme['secondary'],
            '--wb-secondary-rgb' => self::rgb($theme['secondary']),
            '--wb-bg' => $theme['bg'],
            '--wb-text' => $theme['text'],
            '--wb-text-rgb' => self::rgb($theme['text']),
            '--wb-on-primary' => self::contrast($theme['primary']),
            '--wb-on-secondary' => self::contrast($theme['secondary']),
            '--wb-heading-font' => "'$heading', " . ($fonts[$theme['heading_font']]['fallback'] ?? 'sans-serif'),
            '--wb-body-font' => "'$body', " . ($fonts[$theme['body_font']]['fallback'] ?? 'sans-serif'),
            '--wb-radius' => $theme['radius'],
            '--wb-btn-radius' => match ($theme['button_style']) {
                'pill' => '999px',
                'sharp' => '0px',
                default => $theme['radius'],
            },
        ];
        $css .= ':root{';
        foreach ($vars as $k => $v) {
            $css .= $k . ':' . $v . ';';
        }
        return $css . "}\n";
    }

    public static function rgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        return hexdec(substr($hex, 0, 2)) . ',' . hexdec(substr($hex, 2, 2)) . ',' . hexdec(substr($hex, 4, 2));
    }

    /** Lighten (amount > 0) towards white or darken (amount < 0) towards black. */
    public static function shade(string $hex, float $amount): string
    {
        $hex = ltrim($hex, '#');
        $out = '#';
        for ($i = 0; $i < 3; $i++) {
            $c = hexdec(substr($hex, $i * 2, 2));
            $c = $amount >= 0 ? $c + (255 - $c) * $amount : $c * (1 + $amount);
            $out .= str_pad(dechex((int) round(max(0, min(255, $c)))), 2, '0', STR_PAD_LEFT);
        }
        return $out;
    }

    /** Black or white, whichever reads better on the given colour (WCAG relative luminance). */
    public static function contrast(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $lum = 0;
        foreach ([0.2126, 0.7152, 0.0722] as $i => $w) {
            $c = hexdec(substr($hex, $i * 2, 2)) / 255;
            $lum += $w * ($c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4);
        }
        return $lum > 0.45 ? '#111111' : '#FFFFFF';
    }
}
