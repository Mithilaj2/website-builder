<?php
declare(strict_types=1);

namespace SiteRuntime;

/**
 * Strict allow-list SVG sanitiser (written in-house so it can ship inside client websites).
 * Rejects DOCTYPE/ENTITY declarations, removes scripts, event handlers, foreignObject,
 * external references and CSS that could load or execute anything.
 * Returns the cleaned SVG markup, or null if the file is not a usable SVG.
 */
final class SvgSanitizer
{
    private const ELEMENTS = [
        'svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon', 'text', 'tspan', 'textpath',
        'defs', 'lineargradient', 'radialgradient', 'stop', 'clippath', 'mask', 'use', 'symbol', 'title', 'desc',
        'pattern', 'marker', 'filter', 'fegaussianblur', 'feoffset', 'feblend', 'fecolormatrix', 'femerge', 'femergenode',
        'feflood', 'fecomposite', 'style',
    ];

    private const ATTRIBUTES = [
        'id', 'class', 'style', 'viewbox', 'width', 'height', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'cx', 'cy', 'r', 'rx', 'ry',
        'd', 'points', 'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin',
        'stroke-dasharray', 'stroke-dashoffset', 'stroke-opacity', 'stroke-miterlimit', 'opacity', 'transform', 'offset', 'stop-color',
        'stop-opacity', 'gradientunits', 'gradienttransform', 'fx', 'fy', 'clip-path', 'clip-rule', 'mask', 'font-family',
        'font-size', 'font-weight', 'font-style', 'text-anchor', 'dominant-baseline', 'letter-spacing', 'preserveaspectratio',
        'xmlns', 'version', 'href', 'xlink:href', 'patternunits', 'patterntransform', 'markerwidth', 'markerheight', 'refx',
        'refy', 'orient', 'stddeviation', 'dx', 'dy', 'in', 'in2', 'mode', 'result', 'values', 'type', 'operator',
        'flood-color', 'flood-opacity', 'visibility', 'display', 'vector-effect', 'shape-rendering', 'xml:space',
        'maskunits', 'clippathunits', 'filterunits', 'primitiveunits', 'spreadmethod', 'aria-hidden', 'role', 'focusable',
    ];

    public static function sanitize(string $svg): ?string
    {
        if ($svg === '' || strlen($svg) > 2_000_000) {
            return null;
        }
        if (preg_match('/<!DOCTYPE|<!ENTITY|<\?php|<%/i', $svg)) {
            return null;
        }
        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($svg, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$ok || !$dom->documentElement || strtolower($dom->documentElement->localName) !== 'svg') {
            return null;
        }
        self::clean($dom->documentElement);
        $out = $dom->saveXML($dom->documentElement);
        return $out === false ? null : $out;
    }

    private static function clean(\DOMElement $el): void
    {
        foreach (iterator_to_array($el->childNodes) as $child) {
            if ($child instanceof \DOMElement) {
                if (!in_array(strtolower($child->localName), self::ELEMENTS, true)) {
                    $el->removeChild($child);
                    continue;
                }
                self::clean($child);
            } elseif ($child instanceof \DOMProcessingInstruction || $child instanceof \DOMComment) {
                $el->removeChild($child);
            } elseif ($child instanceof \DOMCdataSection || $child instanceof \DOMText) {
                if (strtolower($el->localName) === 'style' && !self::safeCss($child->nodeValue ?? '')) {
                    $el->removeChild($child);
                }
            }
        }
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            $value = trim((string) $attr->nodeValue);
            $remove = !in_array($name, self::ATTRIBUTES, true) || str_starts_with($name, 'on');
            if (!$remove && in_array($name, ['href', 'xlink:href'], true) && !preg_match('/^#[A-Za-z][\w.\-:]*$/', $value)) {
                $remove = true; // only internal references (#id)
            }
            if (!$remove && $name === 'style' && !self::safeCss($value)) {
                $remove = true;
            }
            if (!$remove && preg_match('/url\s*\(/i', $value) && !preg_match('/^url\(\s*[\'"]?#[\w\-]+[\'"]?\s*\)$/i', $value)) {
                $remove = true; // fill="url(#grad)" is fine; external url() is not
            }
            if ($remove) {
                $el->removeAttributeNode($attr);
            }
        }
    }

    private static function safeCss(string $css): bool
    {
        $c = strtolower(preg_replace('/\s+|\\\\/', '', $css) ?? '');
        if (preg_match('/expression\(|javascript:|@import|behavior:|-moz-binding|<\/?/', $c)) {
            return false;
        }
        if (preg_match_all('/url\(([^)]*)\)/', $c, $m)) {
            foreach ($m[1] as $u) {
                if (!preg_match('/^[\'"]?#[\w\-]+[\'"]?$/', $u)) {
                    return false;
                }
            }
        }
        return true;
    }
}
