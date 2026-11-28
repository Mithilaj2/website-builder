"""Palette-driven SVG illustration primitives shared by the category scene generators."""
import html
import math
import os
import random

F = 'font-family="Arial, Helvetica, sans-serif"'
SKINS = ['#C68B59', '#8D5A33', '#E0A97A', '#A86B3C', '#F1C49B', '#6B4026']
HAIR = ['#1E120B', '#3B2416', '#2A1B14', '#5A3A22', '#9A9A9A', '#111111']


def hx(c):
    c = c.lstrip('#')
    if len(c) == 3:
        c = ''.join(ch * 2 for ch in c)
    return tuple(int(c[i:i + 2], 16) for i in (0, 2, 4))


def to_hex(rgb):
    return '#%02X%02X%02X' % tuple(max(0, min(255, round(v))) for v in rgb)


def mix(a, b, t):
    """Blend colour a towards b by t (0..1)."""
    ra, rb = hx(a), hx(b)
    return to_hex(tuple(ra[i] + (rb[i] - ra[i]) * t for i in range(3)))


def lum(c):
    r, g, b = (v / 255 for v in hx(c))
    return 0.2126 * r + 0.7152 * g + 0.0722 * b


class Palette:
    def __init__(self, primary, secondary, accent=None):
        self.P = primary
        self.S = secondary
        self.A = accent or mix(secondary, '#FFFFFF', .35)
        self.P1 = mix(primary, '#000000', .25)
        self.P2 = mix(primary, '#FFFFFF', .35)
        self.P3 = mix(primary, '#FFFFFF', .7)
        self.P4 = mix(primary, '#FFFFFF', .88)
        self.BG = mix(primary, '#FFFFFF', .93)
        self.S2 = mix(secondary, '#FFFFFF', .45)
        self.S3 = mix(secondary, '#FFFFFF', .78)
        self.INK = mix(primary, '#000000', .55) if lum(primary) > .25 else mix(primary, '#000000', .3)
        self.WARM = mix(secondary, '#FFF6E8', .7)


class Canvas:
    def __init__(self, out_dir):
        self.out = out_dir
        os.makedirs(out_dir, exist_ok=True)

    def save(self, name, w, h, body, label=''):
        aria = f' role="img" aria-label="{html.escape(label, quote=True)}"' if label else ''
        with open(os.path.join(self.out, name), 'w') as fh:
            fh.write(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} {h}" width="{w}" height="{h}"{aria}>{body}</svg>')


def rect(x, y, w, h, fill, rx=0, op=None, extra=''):
    o = f' opacity="{op}"' if op is not None else ''
    r = f' rx="{rx}"' if rx else ''
    return f'<rect x="{x:.1f}" y="{y:.1f}" width="{w:.1f}" height="{h:.1f}"{r} fill="{fill}"{o}{extra}/>'


def circle(cx, cy, r, fill, op=None):
    o = f' opacity="{op}"' if op is not None else ''
    return f'<circle cx="{cx:.1f}" cy="{cy:.1f}" r="{r:.1f}" fill="{fill}"{o}/>'


def grad(gid, top, bottom, horizontal=False):
    x2, y2 = ('1', '0') if horizontal else ('0', '1')
    return f'<defs><linearGradient id="{gid}" x1="0" y1="0" x2="{x2}" y2="{y2}"><stop offset="0" stop-color="{top}"/><stop offset="1" stop-color="{bottom}"/></linearGradient></defs>'


def sky(w, h, top, bottom, gid='sk'):
    return grad(gid, top, bottom) + f'<rect width="{w}" height="{h}" fill="url(#{gid})"/>'


def clouds(seed, w, y0, n=3, op=.7, fill='#fff'):
    random.seed(seed)
    out = ''
    for _ in range(n):
        x, y, s = random.uniform(0, w), y0 + random.uniform(0, 80), random.uniform(.6, 1.2)
        out += (f'<g opacity="{op}" fill="{fill}"><ellipse cx="{x:.0f}" cy="{y:.0f}" rx="{70*s:.0f}" ry="{18*s:.0f}"/>'
                f'<ellipse cx="{x-30*s:.0f}" cy="{y-10*s:.0f}" rx="{36*s:.0f}" ry="{20*s:.0f}"/><ellipse cx="{x+20*s:.0f}" cy="{y-16*s:.0f}" rx="{40*s:.0f}" ry="{24*s:.0f}"/></g>')
    return out


def blob(cx, cy, r, c, op=None):
    o = f' opacity="{op}"' if op is not None else ''
    return (f'<path d="M{cx-r:.0f} {cy:.0f} C{cx-r:.0f} {cy-r*1.1:.0f} {cx+r*0.9:.0f} {cy-r*1.2:.0f} {cx+r:.0f} {cy-r*0.1:.0f} '
            f'C{cx+r*1.1:.0f} {cy+r*0.9:.0f} {cx-r*0.2:.0f} {cy+r*1.2:.0f} {cx-r:.0f} {cy:.0f}Z" fill="{c}"{o}/>')


def dots(x, y, w, h, c, gap=22, r=2.2, op=.35):
    return ''.join(circle(x + i * gap, y + j * gap, r, c, op) for i in range(int(w // gap) + 1) for j in range(int(h // gap) + 1))


def card(x, y, w, h, fill='#fff', r=16, shadow='#000', sh=True):
    s = rect(x + 10, y + 14, w, h, shadow, r, .08) if sh else ''
    return s + rect(x, y, w, h, fill, r)


def lines(x, y, n, w, gap=22, c='#D5DEE8', h=10):
    return ''.join(rect(x, y + i * gap, w * (1 if i % 3 else .7), h, c, h / 2) for i in range(n))


def badge(cx, cy, r, fill, text, tc='#fff', fs=None):
    fs = fs or r * .7
    return circle(cx, cy, r, fill) + f'<text x="{cx}" y="{cy+fs*0.35:.0f}" text-anchor="middle" {F} font-size="{fs:.0f}" font-weight="700" fill="{tc}">{text}</text>'


def text(x, y, s, size, fill, weight=700, anchor='start', op=None):
    o = f' opacity="{op}"' if op is not None else ''
    return f'<text x="{x}" y="{y}" text-anchor="{anchor}" {F} font-size="{size}" font-weight="{weight}" fill="{fill}"{o}>{html.escape(str(s), quote=False)}</text>'


def tree(x, base, s=1, c1='#6F8F6A', c2='#4E6E4C', trunk='#9A6B4A', kind='round'):
    t = rect(x - 5 * s, base - 70 * s, 10 * s, 70 * s, trunk)
    if kind == 'round':
        t += circle(x, base - 100 * s, 48 * s, c2) + circle(x - 24 * s, base - 82 * s, 34 * s, c1) + circle(x + 22 * s, base - 118 * s, 30 * s, c1)
    else:
        t += f'<ellipse cx="{x}" cy="{base-130*s:.0f}" rx="{30*s:.0f}" ry="{90*s:.0f}" fill="{c2}"/><ellipse cx="{x-8*s:.0f}" cy="{base-120*s:.0f}" rx="{18*s:.0f}" ry="{70*s:.0f}" fill="{c1}"/>'
    return t


def plant(x, base, s, pot, leaf, leaf2):
    return (f'<path d="M{x-28*s} {base-60*s} h{56*s} l-8 {60*s} h{-40*s}Z" fill="{pot}"/>'
            + ''.join(f'<ellipse cx="{x+dx*s}" cy="{base-60*s-dy*s}" rx="{14*s}" ry="{36*s}" fill="{leaf if i % 2 else leaf2}" transform="rotate({rot} {x+dx*s} {base-60*s-dy*s})"/>'
                      for i, (dx, dy, rot) in enumerate(((-18, 30, -30), (0, 44, 0), (18, 30, 30), (-6, 26, -12), (10, 24, 14)))))


def windows(x, y, cols, rows, w, h, gx, gy, glass, lit=None, lit_ratio=0.0, seed=1):
    random.seed(seed)
    out = ''
    for r in range(rows):
        for c in range(cols):
            fill = lit if lit and random.random() < lit_ratio else glass
            out += rect(x + c * (w + gx), y + r * (h + gy), w, h, fill)
    return out


def tower(x, base, w, h, color, glass, lit=None, lit_ratio=0.0, seed=1, cols=3):
    gx = 10
    ww = (w - 20 - (cols - 1) * gx) / cols
    rows = int((h - 30) // 34)
    return rect(x, base - h, w, h, color) + windows(x + 10, base - h + 16, cols, rows, ww, 22, gx, 12, glass, lit, lit_ratio, seed)


def skyline(w, base, color, seed, op=1, hmin=120, hmax=360):
    random.seed(seed)
    out, x = '', -10
    while x < w:
        bw, bh = random.randint(60, 130), random.randint(hmin, hmax)
        out += rect(x, base - bh, bw, bh, color, op=op)
        x += bw + random.randint(2, 10)
    return out


def figure(cx, base, s, skin, hair, top, hairstyle='short', collar='#fff', ink='#1E1B1C', helmet=None):
    g = [f'<g transform="translate({cx:.1f} {base:.1f}) scale({s})">']
    g.append(f'<path d="M-170 0 C-165 -95 -110 -135 -60 -145 L60 -145 C110 -135 165 -95 170 0Z" fill="{top}"/>')
    g.append(f'<path d="M-50 -146 L0 -70 L50 -146" fill="{collar}"/>')
    g.append(f'<rect x="-24" y="-185" width="48" height="50" rx="18" fill="{skin}"/>')
    if hairstyle == 'long':
        g.append(f'<path d="M-72 -250 C-80 -330 80 -330 72 -250 L78 -150 C60 -140 40 -150 30 -170 L-30 -170 C-40 -150 -60 -140 -78 -150Z" fill="{hair}"/>')
    g.append(f'<ellipse cx="0" cy="-250" rx="62" ry="72" fill="{skin}"/><ellipse cx="-62" cy="-245" rx="10" ry="16" fill="{skin}"/><ellipse cx="62" cy="-245" rx="10" ry="16" fill="{skin}"/>')
    if helmet:
        g.append(f'<path d="M-74 -262 C-74 -350 74 -350 74 -262Z" fill="{helmet}"/><rect x="-86" y="-270" width="172" height="16" rx="8" fill="{helmet}"/><rect x="-8" y="-338" width="16" height="70" rx="6" fill="#000" opacity=".12"/>')
    elif hairstyle == 'short':
        g.append(f'<path d="M-64 -262 C-70 -330 70 -335 64 -262 C55 -290 30 -300 0 -298 C-30 -300 -55 -290 -64 -262Z" fill="{hair}"/>')
    elif hairstyle == 'bun':
        g.append(f'<circle cx="0" cy="-330" r="26" fill="{hair}"/><path d="M-64 -258 C-72 -330 72 -330 64 -258 C50 -300 -50 -300 -64 -258Z" fill="{hair}"/>')
    elif hairstyle == 'long':
        g.append(f'<path d="M-64 -255 C-70 -328 70 -328 64 -255 C40 -300 -10 -300 -64 -255Z" fill="{hair}"/>')
    elif hairstyle == 'grey':
        g.append(f'<path d="M-64 -258 C-70 -325 70 -330 64 -258 C40 -285 -40 -285 -64 -258Z" fill="{hair}"/><path d="M-40 -205 Q0 -170 40 -205 Q0 -185 -40 -205Z" fill="{hair}"/>')
    elif hairstyle == 'bald':
        g.append(f'<path d="M-62 -250 C-64 -275 -58 -290 -50 -296 M62 -250 C64 -275 58 -290 50 -296" stroke="{hair}" stroke-width="12" fill="none"/>')
    g.append(f'<circle cx="-22" cy="-250" r="6" fill="{ink}"/><circle cx="22" cy="-250" r="6" fill="{ink}"/>'
             f'<path d="M-34 -268 q12 -8 24 0 M10 -268 q12 -8 24 0" stroke="{ink}" stroke-width="4" fill="none" stroke-linecap="round" opacity=".7"/>'
             f'<path d="M-18 -218 Q0 -204 18 -218" stroke="{ink}" stroke-width="5" fill="none" stroke-linecap="round"/>'
             f'<circle cx="-36" cy="-228" r="9" fill="#C0392B" opacity=".12"/><circle cx="36" cy="-228" r="9" fill="#C0392B" opacity=".12"/>')
    g.append('</g>')
    return ''.join(g)


def portrait(cv, name, pal, skin, hair, top, style, variant=0, size=600, h=660):
    bgs = [pal.P4, pal.S3, pal.BG, mix(pal.P, '#FFFFFF', .8)]
    bg = bgs[variant % len(bgs)]
    b = [rect(0, 0, size, h, bg)]
    if variant % 3 == 0:
        b.append(f'<path d="M{size*0.6} 0 L{size} 0 L{size} {h*0.55}Z" fill="{pal.S}" opacity=".35"/>')
    elif variant % 3 == 1:
        b.append(circle(size * .78, h * .22, size * .22, pal.P3, .6))
    else:
        b.append(dots(size * .06, h * .08, size * .3, h * .25, pal.P2, 24, 3, .5))
    b.append(figure(size / 2, h + 10, 1.32 * size / 600 * 1.08, skin, hair, top, style))
    cv.save(name, size, h, ''.join(b), 'Portrait illustration')


def wordmark(cv, name, label, mark, color, w=240, h=64):
    shapes = {
        'circle': circle(28, 32, 18, color),
        'square': rect(10, 14, 36, 36, color, 6),
        'diamond': f'<path d="M28 10 L46 32 L28 54 L10 32Z" fill="{color}"/>',
        'ring': f'<circle cx="28" cy="32" r="15" fill="none" stroke="{color}" stroke-width="7"/>',
        'bars': ''.join(rect(10 + i * 12, 46 - (i + 1) * 10, 8, (i + 1) * 10, color, 2) for i in range(3)),
        'tri': f'<path d="M10 50 L28 14 L46 50Z" fill="{color}"/>',
        'leaf': f'<path d="M12 50 C12 24 30 12 48 12 C48 36 34 50 12 50Z" fill="{color}"/>',
    }
    cv.save(name, w, h, shapes.get(mark, shapes['circle']) + text(58, 41, label, 24, color, 700), label)


def truck(x, base, s, cab, box, wheel='#2B2B2B', label='', lc='#fff'):
    g = f'<g transform="translate({x} {base}) scale({s})">'
    g += rect(0, -150, 260, 120, box, 6)
    if label:
        g += text(130, -80, label, 30, lc, 800, 'middle')
    g += f'<path d="M262 -110 h60 l38 40 v40 h-98Z" fill="{cab}"/>' + rect(278, -100, 38, 30, '#CFE3F0', 4)
    g += rect(0, -34, 360, 10, '#3A3A3A')
    for wx in (50, 110, 300):
        g += circle(wx, -18, 24, wheel) + circle(wx, -18, 10, '#BBBBBB')
    return g + '</g>'


def container(x, y, w, h, color, ribs=10):
    out = rect(x, y, w, h, color)
    out += ''.join(rect(x + 8 + i * (w - 16) / ribs, y + 8, 4, h - 16, '#000', op=.12) for i in range(ribs + 1))
    return out


def crate(x, y, s, c, c2):
    return rect(x, y, 80 * s, 64 * s, c) + rect(x, y, 80 * s, 10 * s, c2) + f'<path d="M{x} {y} l{80*s} {64*s} M{x+80*s} {y} l{-80*s} {64*s}" stroke="{c2}" stroke-width="{4*s}"/>'


def monitor(x, y, w, h, frame, screen, body=''):
    return (rect(x, y, w, h, frame, 14) + rect(x + 12, y + 12, w - 24, h - 34, screen, 6) + body
            + rect(x + w / 2 - 24, y + h, 48, 40, frame) + rect(x + w / 2 - 70, y + h + 36, 140, 12, frame, 6))


def bars(x, base, n, w, gap, heights, c1, c2):
    return ''.join(rect(x + i * (w + gap), base - hgt, w, hgt, c2 if i >= n - 2 else c1, 4) for i, hgt in enumerate(heights[:n]))


def gear(cx, cy, r, c, teeth=10, hole='#fff'):
    pts = []
    for i in range(teeth * 2):
        a = math.pi * i / teeth
        rr = r if i % 2 == 0 else r * .82
        a2 = a + math.pi / teeth
        pts.append(f'{cx+math.cos(a)*rr:.1f},{cy+math.sin(a)*rr:.1f} {cx+math.cos(a2)*rr:.1f},{cy+math.sin(a2)*rr:.1f}')
    return f'<polygon points="{" ".join(pts)}" fill="{c}"/>' + circle(cx, cy, r * .35, hole)


def solar_panel(x, y, w, h, c, line='#fff', skew=30):
    g = f'<path d="M{x+skew} {y} h{w} l{-skew} {h} h{-w}Z" fill="{c}"/>'
    for i in range(1, 4):
        g += f'<path d="M{x+skew-skew*i/4} {y+h*i/4} h{w}" stroke="{line}" stroke-width="2" opacity=".5"/>'
    for i in range(1, 6):
        g += f'<path d="M{x+skew+w*i/6} {y} l{-skew} {h}" stroke="{line}" stroke-width="2" opacity=".5"/>'
    return g


def turbine(x, base, h, c, blade, rot=0):
    hub = (x, base - h)
    g = f'<path d="M{x-6} {base} L{x-3} {base-h} h6 L{x+6} {base}Z" fill="{c}"/>'
    for k in range(3):
        a = rot + k * 120
        g += f'<path d="M{hub[0]} {hub[1]} q8 -10 0 -{h*0.5:.0f} q-10 10 0 {h*0.5:.0f}Z" fill="{blade}" transform="rotate({a} {hub[0]} {hub[1]})"/>'
    return g + circle(hub[0], hub[1], 7, c)


def crane(x, base, h, arm, c):
    g = ''.join(f'<path d="M{x} {base-i*30} l20 -30 M{x+20} {base-i*30} l-20 -30" stroke="{c}" stroke-width="3"/>' for i in range(int(h // 30)))
    g += f'<path d="M{x} {base} V{base-h} M{x+20} {base} V{base-h}" stroke="{c}" stroke-width="5"/>'
    g += f'<path d="M{x-arm*0.3} {base-h} H{x+arm}" stroke="{c}" stroke-width="10"/><path d="M{x+10} {base-h-40} L{x-arm*0.3} {base-h} M{x+10} {base-h-40} L{x+arm} {base-h}" stroke="{c}" stroke-width="3"/>'
    g += rect(x - arm * 0.3, base - h + 2, 50, 30, c) + f'<path d="M{x+arm*0.75} {base-h} V{base-h+160}" stroke="{c}" stroke-width="2"/>' + rect(x + arm * 0.75 - 24, base - h + 160, 48, 26, c, 3)
    return g


def column(x, base, w, h, c, shade):
    g = rect(x - 10, base - 18, w + 20, 18, c) + rect(x - 10, base - h, w + 20, 18, c)
    g += rect(x, base - h + 18, w, h - 36, c)
    g += ''.join(rect(x + 6 + i * (w - 12) / 4, base - h + 24, 4, h - 48, shade) for i in range(5))
    return g


def book_row(x, base, n, seed, colors):
    random.seed(seed)
    out, cx = '', x
    for _ in range(n):
        w, h = random.randint(18, 30), random.randint(90, 130)
        out += rect(cx, base - h, w, h, random.choice(colors), 2) + rect(cx + 4, base - h + 14, w - 8, 4, '#fff', op=.5)
        cx += w + 2
    return out
