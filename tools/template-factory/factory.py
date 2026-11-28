#!/usr/bin/env python3
"""Template factory — generates standalone templates from design specs.

Usage:
  python3 tools/template-factory/factory.py specs/business/business_template_02.json [...]
  python3 tools/template-factory/factory.py --category business      # every spec in specs/business/

Each generated template is fully independent: its own prefix, markup, stylesheet, script,
images and demo content. Nothing generated here depends on the factory at runtime.
"""
import glob
import importlib
import json
import os
import shutil
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(os.path.dirname(HERE))
LIB = os.path.join(HERE, 'lib')
sys.path[:0] = [os.path.join(HERE, 'images'), os.path.join(HERE, 'demos')]

CATEGORY_DIRS = {'business': 'business', 'real-estate': 'real-estate', 'travel': 'travel', 'restaurant': 'restaurant', 'hotel': 'hotel',
                 'portfolio': 'portfolio', 'services': 'services', 'education': 'education', 'hospital': 'hospital', 'ecommerce': 'ecommerce'}

# Component -> CSS module; sections listed here pick "<type>.<variant>.php".
VARIANT_SECTIONS = {'hero': 'hero', 'page_banner': 'banner', 'about': 'about', 'services': 'services', 'stats': 'stats',
                    'process': 'process', 'testimonials': 'testimonials', 'cta': 'cta', 'team': 'team', 'gallery': 'gallery', 'faq': 'faq',
                    'courses': 'courses', 'events': 'events', 'notices': 'notices', 'doctors': 'doctors', 'opening_hours': 'opening_hours', 'product_categories': 'product_categories', 'listing_categories': 'listing_categories'}
CSS_ORDER = ['header', 'hero', 'banner', 'head', 'card', 'about', 'services', 'stats', 'process', 'testimonials', 'cta', 'team', 'gallery', 'faq', 'logos', 'courses', 'events', 'notices', 'doctors', 'opening_hours', 'product-card', 'product_categories', 'listing-card', 'listing_categories', 'footer']
SURFACE_CLASS = {'services': 'services', 'testimonials': 'testimonials', 'features': 'features', 'faq': 'faq', 'team': 'team', 'pricing': 'pricing', 'process': 'process', 'about': 'about', 'gallery': 'gallery', 'contact': 'contact',
                 'courses': 'courses', 'events': 'events', 'booking_form': 'booking', 'stats': 'stats', 'doctors': 'doctors', 'opening_hours': 'hours', 'listings': 'listings', 'listing_categories': 'ltypes', 'products': 'products'}

HEADER_STYLES = {
    'bar': {'sticky': 'Sticky, with info bar', 'minimal': 'Sticky, no info bar', 'static': 'Scrolls with the page'},
    'default': {'sticky': 'Sticky', 'static': 'Scrolls with the page'},
    'shop': {'announce': 'With announcement bar', 'minimal': 'No announcement bar'},
}
FOOTER_STYLES = {'dark': 'Dark', 'light': 'Light'}


def read(path):
    with open(path, encoding='utf-8') as fh:
        return fh.read()


def fill(text, spec):
    return text.replace('{{p}}', spec['prefix']).replace('{{mood}}', spec.get('mood', 'light')).replace('{{name}}', spec['name'])


def tokens(spec):
    p, t = spec['prefix'], spec.get('tokens', {})
    dark = spec.get('mood') == 'dark'
    accent = t.get('accent', 'secondary')
    other = 'primary' if accent == 'secondary' else 'secondary'
    v = {
        'ink': 'var(--wb-text)',
        'muted': 'rgba(var(--wb-text-rgb), .68)' if dark else 'rgba(var(--wb-text-rgb), .7)',
        'line': 'rgba(var(--wb-text-rgb), .12)' if dark else 'rgba(var(--wb-text-rgb), .1)',
        'line-strong': 'rgba(var(--wb-text-rgb), .24)' if dark else 'rgba(var(--wb-text-rgb), .2)',
        'surface': t.get('surface', 'color-mix(in srgb, #fff 4%, var(--wb-bg))' if dark else 'color-mix(in srgb, var(--wb-primary) 5%, var(--wb-bg))'),
        'card': t.get('card', 'color-mix(in srgb, #fff 6%, var(--wb-bg))' if dark else 'color-mix(in srgb, #fff 75%, var(--wb-bg))'),
        'field': 'color-mix(in srgb, #fff 7%, var(--wb-bg))' if dark else 'color-mix(in srgb, var(--wb-text) 3%, var(--wb-bg))',
        'deep': t.get('deep', 'color-mix(in srgb, #000 40%, var(--wb-bg))' if dark else 'color-mix(in srgb, var(--wb-primary) 82%, #000)'),
        'header-bg': t.get('header_bg', 'color-mix(in srgb, var(--wb-bg) 88%, transparent)' if dark else 'var(--wb-bg)'),
        'accent': f'var(--wb-{accent})', 'on-accent': f'var(--wb-on-{accent})',
        'accent-hover': t.get('accent_hover', f'var(--wb-{other})'), 'on-accent-hover': t.get('on_accent_hover', f'var(--wb-on-{other})'),
        'hl': t.get('hl', 'var(--wb-secondary)'),
        'link': t.get('link', 'var(--wb-secondary)' if dark else 'var(--wb-primary)'),
        'hero-ink': t.get('hero_ink', 'var(--wb-text)' if dark else 'var(--wb-primary)'),
        'brand-color': t.get('brand_color', 'var(--wb-text)' if dark else 'var(--wb-primary)'),
        'nav-color': 'var(--{p}-ink)'.format(p=p), 'nav-active': t.get('nav_active', 'var(--{p}-link)'.format(p=p)),
        'nav-font': t.get('nav_font', 'var(--wb-heading-font)'),
        'btn-font': t.get('btn_font', 'var(--wb-heading-font)'), 'btn-track': t.get('btn_track', '0'), 'btn-case': t.get('btn_case', 'none'),
        'icon-bg': {'solid': 'var(--wb-primary)', 'soft': 'rgba(var(--wb-secondary-rgb), .16)', 'accent': 'var(--wb-secondary)', 'tint': 'rgba(var(--wb-primary-rgb), .1)'}[t.get('icon', 'solid')],
        'icon-color': {'solid': 'var(--wb-on-primary)', 'soft': t.get('hl', 'var(--wb-secondary)'), 'accent': 'var(--wb-on-secondary)', 'tint': 'var(--wb-primary)'}[t.get('icon', 'solid')],
        'icon-radius': t.get('icon_radius', 'var(--wb-radius)'), 'mark-radius': t.get('mark_radius', 'var(--wb-radius)'),
        'radius': 'var(--wb-radius)', 'radius-lg': t.get('radius_lg', 'calc(var(--wb-radius) * 1.6)'),
        'shadow': t.get('shadow', '0 22px 48px -24px rgba(0, 0, 0, .55)' if dark else '0 18px 45px -22px rgba(15, 23, 42, .3)'),
        'shadow-sm': t.get('shadow_sm', '0 8px 22px -12px rgba(0, 0, 0, .5)' if dark else '0 6px 18px -10px rgba(15, 23, 42, .22)'),
        'hw': str(t.get('heading_weight', 700)), 'track': t.get('track', '-.01em'), 'case': t.get('case', 'none'),
        'hero-weight': str(t.get('hero_weight', 800)), 'hero-scale': str(t.get('hero_scale', 1)), 'pad': t.get('pad', '6rem'),
        'band': t.get('band', 'var(--wb-secondary)'), 'on-band': t.get('on_band', 'var(--wb-on-secondary)'),
    }
    body = ' '.join(f'--{p}-{k}: {val};' for k, val in v.items())
    return f'body.{p} {{ {body} }}\n'


def stylesheet(spec, sections):
    v = spec['variants']
    out = [f'/* ==========================================================================\n   {spec["name"]} — {spec["id"]}\n   {spec["direction"]}\n'
           '   Built on --wb-* theme variables so colours, fonts and radius follow the\n   site\'s theme settings. Generated by tools/template-factory; edit freely.\n   ========================================================================== */\n\n']
    out.append(tokens(spec))
    out.append(read(os.path.join(LIB, 'css/base.css')))
    for comp in CSS_ORDER:
        name = v.get(comp)
        if not name:
            continue
        css = read(os.path.join(LIB, f'css/{comp}.{name}.css')).strip()
        if css:
            out.append(f'\n/* ---------- {comp}: {name} ---------- */\n' + css + '\n')
    for t in sections:
        extra = os.path.join(LIB, f'css/sec.{t}.css')
        if os.path.exists(extra):
            out.append(f'\n/* ---------- {t} ---------- */\n' + read(extra).strip() + '\n')
    surface = spec.get('surface', [])
    if surface:
        p = spec['prefix']
        sel = ', '.join(f'.{p} .{p}-{SURFACE_CLASS[s]}' for s in surface)
        out.append(f'\n/* ---------- section tones ---------- */\n{sel} {{ background: var(--{p}-surface); }}\n')
        if v.get('card') == 'tint':
            out.append(', '.join(f'.{p}-{SURFACE_CLASS[s]} .{p}-card' for s in surface) + f' {{ background: var(--{p}-card); }}\n')
    if spec.get('extra_css'):
        out.append('\n/* ---------- template specifics ---------- */\n' + spec['extra_css'].strip() + '\n')
    return fill(''.join(out), spec)


def build(spec_path):
    spec = json.load(open(spec_path))
    cat = spec['category']
    dest = os.path.join(ROOT, 'templates', CATEGORY_DIRS[cat], spec['id'])
    preview = os.path.join(dest, 'preview.jpg')
    keep = read_bytes(preview) if os.path.exists(preview) else None
    if os.path.isdir(dest):
        shutil.rmtree(dest)
    for d in ('layouts', 'partials', 'sections', 'assets/css', 'assets/js', 'assets/images'):
        os.makedirs(os.path.join(dest, d), exist_ok=True)

    demo_mod = importlib.import_module(cat.replace('-', '_'))
    demo = demo_mod.build(spec)
    section_types = demo_mod.SECTION_TYPES
    v = spec['variants']

    write(dest, 'layouts/default.php', fill(read(os.path.join(LIB, 'layouts/default.php')), spec))
    for f in sorted(glob.glob(os.path.join(LIB, 'partials/*.php'))):
        base = os.path.basename(f)
        parts = base.split('.')
        if len(parts) == 3:  # header.bar.php / footer.columns.php
            if v.get(parts[0]) != parts[1]:
                continue
            base = parts[0] + '.php'
        write(dest, 'partials/' + base, fill(read(f), spec))
    for t in section_types:
        comp = VARIANT_SECTIONS.get(t)
        src = os.path.join(LIB, f'sections/{t}.{v[comp]}.php') if comp else os.path.join(LIB, f'sections/{t}.php')
        write(dest, f'sections/{t}.php', fill(read(src), spec))

    write(dest, 'assets/css/style.css', stylesheet(spec, section_types))
    write(dest, 'assets/js/main.js', fill(read(os.path.join(LIB, 'js/main.js')), spec))

    img_mod = importlib.import_module(cat.replace('-', '_') + '_images')
    th = spec['theme']
    img_mod.generate(os.path.join(dest, 'assets/images'), spec['niche'], spec.get('image_primary', th['primary']), spec.get('image_secondary', th['secondary']),
                     spec.get('seed', 1), demo_mod_logos(demo))

    shop = 'ecommerce' in getattr(demo_mod, 'FEATURES', [])
    header_styles = HEADER_STYLES['shop'] if shop else HEADER_STYLES['bar'] if v['header'] == 'bar' else HEADER_STYLES['default']
    manifest = {
        'id': spec['id'], 'name': spec['name'], 'category': cat, 'version': spec.get('version', '1.0.0'),
        'description': spec['description'], 'libraries': ['bootstrap', 'bootstrap-icons'],
        'features': getattr(demo_mod, 'FEATURES', ['contact']), 'optional_features': getattr(demo_mod, 'OPTIONAL_FEATURES', ['blog']),
        'theme': {'defaults': dict(th, header_style='announce' if shop else 'sticky', footer_style=spec.get('footer_style', 'dark')),
                  'header_styles': header_styles, 'footer_styles': FOOTER_STYLES},
        'section_types': section_types,
        'page_types': {k: {'default_sections': s} for k, s in demo_mod.page_types(demo).items()},
        'default_pages': demo_mod.default_pages(spec) if hasattr(demo_mod, 'default_pages') else demo_mod.DEFAULT_PAGES,
    }
    if hasattr(demo_mod, 'listings_config'):
        manifest['listings'] = demo_mod.listings_config(spec)
    write(dest, 'manifest.json', json.dumps(manifest, indent=2, ensure_ascii=False) + '\n')
    write(dest, 'demo.json', json.dumps(demo, indent=2, ensure_ascii=False) + '\n')
    if keep:
        with open(preview, 'wb') as fh:
            fh.write(keep)
    print(f'built {spec["id"]:<24} {spec["name"]:<28} prefix={spec["prefix"]}  {os.path.relpath(dest, ROOT)}')


def demo_mod_logos(demo):
    for s in demo['pages']['']['sections']:
        if s['type'] == 'logos':
            return [i['name'] for i in s['data']['items']]
    return None


def read_bytes(p):
    with open(p, 'rb') as fh:
        return fh.read()


def write(dest, rel, content):
    with open(os.path.join(dest, rel), 'w', encoding='utf-8') as fh:
        fh.write(content)


def main(argv):
    if not argv:
        print(__doc__)
        return 1
    paths = []
    if argv[0] == '--category':
        paths = sorted(glob.glob(os.path.join(HERE, 'specs', argv[1], '*.json')))
    else:
        paths = [a if os.path.isabs(a) else os.path.join(HERE, a) if not os.path.exists(a) else a for a in argv]
    prefixes = {}
    for p in paths:
        s = json.load(open(p))
        if s['prefix'] in prefixes:
            raise SystemExit(f'prefix {s["prefix"]} used by both {prefixes[s["prefix"]]} and {s["id"]}')
        prefixes[s['prefix']] = s['id']
    for p in paths:
        build(p)
    return 0


if __name__ == '__main__':
    sys.exit(main(sys.argv[1:]))
