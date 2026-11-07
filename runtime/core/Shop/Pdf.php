<?php
declare(strict_types=1);

namespace SiteRuntime\Shop;

/**
 * A very small PDF writer (A4, Helvetica / Helvetica-Bold, lines, filled boxes) for invoices.
 * No dependencies, so generated websites stay self-contained. Coordinates are in points from the top-left.
 * Text is converted to Windows-1252; characters outside it (e.g. ₹) are replaced.
 */
final class Pdf
{
    public const W = 595.28;
    public const H = 841.89;

    private const WIDTHS = [
        '' => [278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278, 556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556, 1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778, 667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556, 333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556, 556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584],
        'B' => [278, 333, 474, 556, 556, 889, 722, 238, 333, 333, 389, 584, 278, 333, 278, 278, 556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 333, 333, 584, 584, 584, 611, 975, 722, 722, 722, 722, 667, 611, 778, 722, 278, 556, 722, 611, 833, 722, 778, 667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 333, 278, 333, 584, 556, 333, 556, 611, 556, 611, 556, 333, 611, 611, 278, 278, 556, 278, 889, 611, 611, 611, 611, 389, 556, 333, 611, 556, 778, 556, 556, 500, 389, 280, 389, 584],
    ];

    /** @var list<string> */
    private array $pages = [];
    private string $style = '';
    private float $size = 10;
    private string $color = '0 0 0';

    public function addPage(): void
    {
        $this->pages[] = '';
    }

    public function font(string $style, float $size): void
    {
        $this->style = $style === 'B' ? 'B' : '';
        $this->size = $size;
    }

    /** Text colour, 0–255 per channel. */
    public function color(int $r, int $g, int $b): void
    {
        $this->color = sprintf('%.3F %.3F %.3F', $r / 255, $g / 255, $b / 255);
    }

    public function text(float $x, float $y, string $s): void
    {
        $s = self::encode($s);
        if ($s === '') {
            return;
        }
        $this->out(sprintf('BT %s rg /F%s %.2F Tf %.2F %.2F Td (%s) Tj ET', $this->color, $this->style === 'B' ? '2' : '1', $this->size, $x, self::H - $y, self::escape($s)));
    }

    public function textRight(float $right, float $y, string $s): void
    {
        $this->text($right - $this->width($s), $y, $s);
    }

    public function width(string $s): float
    {
        $w = 0;
        $table = self::WIDTHS[$this->style];
        foreach (str_split(self::encode($s)) as $ch) {
            $o = ord($ch);
            $w += ($o >= 32 && $o <= 126) ? $table[$o - 32] : 556;
        }
        return $w * $this->size / 1000;
    }

    /** Split text into lines that fit $width at the current font. */
    public function wrap(string $s, float $width): array
    {
        $lines = [];
        foreach (preg_split('/\R/', $s) ?: [] as $para) {
            $line = '';
            foreach (preg_split('/\s+/', trim($para)) ?: [] as $word) {
                $try = $line === '' ? $word : $line . ' ' . $word;
                if ($line !== '' && $this->width($try) > $width) {
                    $lines[] = $line;
                    $line = $word;
                } else {
                    $line = $try;
                }
            }
            $lines[] = $line;
        }
        return $lines;
    }

    public function line(float $x1, float $y1, float $x2, float $y2, float $w = 0.5, int $gray = 200): void
    {
        $g = $gray / 255;
        $this->out(sprintf('%.3F G %.2F w %.2F %.2F m %.2F %.2F l S', $g, $w, $x1, self::H - $y1, $x2, self::H - $y2));
    }

    /** Filled rectangle, colour 0–255. */
    public function box(float $x, float $y, float $w, float $h, int $r, int $g, int $b): void
    {
        $this->out(sprintf('%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f', $r / 255, $g / 255, $b / 255, $x, self::H - $y - $h, $w, $h));
    }

    public function output(string $title = ''): string
    {
        $objs = [];
        $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        $n = 5;
        $pageObjs = [];
        foreach ($this->pages as $content) {
            $pageObjs[] = [$n, $n + 1, $content];
            $kids[] = "$n 0 R";
            $n += 2;
        }
        $objs[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        $objs[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objs[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        foreach ($pageObjs as [$pid, $cid, $content]) {
            $objs[$pid] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>', self::W, self::H, $cid);
            $objs[$cid] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        }
        $info = $n;
        $objs[$info] = '<< /Producer (Website runtime) /Title (' . self::escape(self::encode($title)) . ') /CreationDate (D:' . date('YmdHis') . ') >>';
        ksort($objs);
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objs as $i => $body) {
            $offsets[$i] = strlen($pdf);
            $pdf .= "$i 0 obj\n$body\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref' . "\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objs); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        return $pdf . 'trailer' . "\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R /Info $info 0 R >>\nstartxref\n$xref\n%%EOF\n";
    }

    private function out(string $s): void
    {
        if (!$this->pages) {
            $this->addPage();
        }
        $this->pages[array_key_last($this->pages)] .= $s . "\n";
    }

    private static function encode(string $s): string
    {
        $s = str_replace(['₹', "\u{2009}", "\u{00A0}", '×', '→'], ['Rs.', ' ', ' ', 'x', '->'], $s);
        $out = function_exists('iconv') ? @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s) : false;
        return $out === false ? (string) preg_replace('/[^\x20-\x7E]/', '?', $s) : $out;
    }

    private static function escape(string $s): string
    {
        return strtr($s, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)', "\r" => '', "\n" => ' ']);
    }
}
