"""Real-estate scenes. generate(out_dir, niche, primary, secondary, seed, logo_names) writes:
prop-<slug>.svg (one exterior per listing, 1200x900), room-*.svg (interiors for listing galleries, 1200x900),
cat-<slug>.svg (1000x1000), hero.svg (1600x1000), about-1.svg (1000x850), about-2.svg (800x800), banner.svg,
cta.svg (1600x700), agent-1..4, customer-1..3 and logo-1..4 (home-loan partners)."""
import random
from kit import *  # noqa: F401,F403
from business_images import logos, banner

from real_estate_content import NICHES as CONTENT

PW, PH = 1200, 900
GLASS = '#9CC3DA'
GLASS2 = '#6E9DBA'
LIT = '#FFD98A'
WALL = '#F3EEE6'
WALL2 = '#E2D8CA'
CONCRETE = '#C9C3BA'
ROOF = '#8A4B3A'
GRASS = '#8DB572'
GRASS2 = '#6E9A57'
ROAD = '#5B5F66'


def g(x, y, s, body):
    return f'<g transform="translate({x:.1f} {y:.1f}) scale({s:.3f})">{body}</g>'


def backdrop(w, h, pal, seed, dusk=False, horizon=.74, gid='sky'):
    top, bot = (mix(pal.P, '#1B1F3B', .5), mix(pal.S, '#FFC9A0', .4)) if dusk else (mix('#8EC5E8', pal.P, .12), '#E9F4FA')
    b = sky(w, h, top, bot, gid)
    b += circle(w * .82, h * .2, h * .07, '#FFF3D6' if not dusk else '#FFD27F', .9 if not dusk else .85)
    b += clouds(seed, w, h * .1, 3, .55 if dusk else .8)
    base = h * horizon
    b += skyline(w, base, mix(top if dusk else '#B9CFDD', '#FFFFFF', .15 if dusk else .25), seed + 3, .55, int(h * .08), int(h * .26))
    b += rect(0, base, w, h - base, GRASS) + rect(0, base, w, h * .025, GRASS2, op=.5)
    return b, base


def trees(w, base, s, seed, n=4, kind='round', margin=.12):
    random.seed(seed)
    out = ''
    for i in range(n):
        x = (random.uniform(0, margin) if i % 2 == 0 else random.uniform(1 - margin, 1)) * w
        out += tree(x, base + 6, s * random.uniform(.85, 1.15), kind=kind)
    return out


def palm(x, base, s, c='#4E8A4C', trunk='#8B6B4A'):
    t = f'<path d="M{x} {base} C{x+10*s} {base-120*s} {x+18*s} {base-220*s} {x+30*s} {base-300*s}" stroke="{trunk}" stroke-width="{12*s}" fill="none" stroke-linecap="round"/>'
    cx, cy = x + 30 * s, base - 300 * s
    for a in (-160, -120, -60, -20, 20, 200):
        t += f'<path d="M{cx} {cy} q{60*s} {-30*s} {120*s} {10*s}" stroke="{c}" stroke-width="{16*s}" fill="none" stroke-linecap="round" transform="rotate({a} {cx} {cy})"/>'
    return t


# ----------------------------------------------------------------------------- buildings (local coords: x centred on 0, base at y=0)

def b_tower(pal, seed):
    out = ''
    for dx, h, w, c in ((-170, 560, 230, WALL), (110, 460, 210, WALL2)):
        x = dx - w / 2
        out += rect(x, -h, w, h, c) + rect(x, -h, w, 16, pal.P1)
        rows = int((h - 60) // 44)
        random.seed(seed + dx)
        for r in range(rows):
            y = -h + 34 + r * 44
            for k in range(3):
                wx = x + 14 + k * (w - 28) / 3
                out += rect(wx, y, (w - 28) / 3 - 10, 26, LIT if random.random() < .18 else GLASS)
            out += rect(x - 6, y + 28, w + 12, 6, pal.P3)
        out += rect(x + w / 2 - 26, -54, 52, 54, pal.P1)
    out += rect(-320, -10, 640, 10, CONCRETE)
    return out


def b_penthouse(pal, seed):
    h, w = 640, 280
    out = rect(-w / 2, -h, w, h, WALL)
    random.seed(seed)
    for r in range(12):
        y = -h + 90 + r * 44
        out += rect(-w / 2 + 16, y, w - 32, 26, LIT if random.random() < .2 else GLASS) + rect(-w / 2 - 8, y + 28, w + 16, 6, pal.P3)
    out += rect(-w / 2 - 30, -h - 6, w + 60, 14, pal.P1) + rect(-w / 2 + 20, -h - 70, w - 40, 64, GLASS2)
    out += ''.join(rect(-w / 2 + 10 + i * 34, -h - 96, 8, 30, pal.S) for i in range(9)) + rect(-w / 2 + 6, -h - 102, w - 12, 10, pal.S)
    out += tree(-w / 2 + 30, -h - 6, .4) + tree(w / 2 - 30, -h - 6, .35)
    out += rect(-48, -60, 96, 60, pal.P1) + rect(-260, -10, 520, 10, CONCRETE)
    return out


def b_villa(pal, seed):
    out = rect(-300, -200, 600, 200, WALL) + rect(-150, -380, 380, 180, WALL2)
    out += rect(-320, -214, 640, 16, pal.P1) + rect(-170, -394, 420, 16, pal.P1)
    out += rect(-120, -360, 160, 140, GLASS) + rect(70, -360, 130, 140, GLASS2) + rect(-280, -180, 220, 160, GLASS) + rect(40, -180, 120, 180, pal.P1)
    out += rect(190, -160, 90, 120, GLASS)
    out += f'<path d="M-120 -290 h160 M-40 -360 v140 M-280 -100 h220 M-170 -180 v160" stroke="#fff" stroke-width="4" opacity=".7"/>'
    out += rect(-330, 0, 420, 50, '#7EC8E3') + rect(-330, 0, 420, 8, '#FFFFFF', op=.6)
    out += plant(250, 0, .7, pal.S, GRASS, GRASS2)
    return out


def b_bungalow(pal, seed):
    out = rect(-260, -200, 520, 200, WALL)
    out += f'<path d="M-300 -196 L0 -380 L300 -196Z" fill="{ROOF}"/><path d="M-300 -196 L0 -380 L300 -196" stroke="{mix(ROOF, "#000", .25)}" stroke-width="10" fill="none"/>'
    out += rect(-210, -160, 110, 90, GLASS) + rect(100, -160, 110, 90, GLASS) + rect(-40, -150, 80, 150, pal.P1) + circle(26, -76, 5, pal.S)
    out += f'<path d="M-210 -115 h110 M-155 -160 v90 M100 -115 h110 M155 -160 v90" stroke="#fff" stroke-width="4"/>'
    out += rect(-90, -214, 180, 16, pal.S) + rect(-80, -200, 12, 200, '#fff') + rect(68, -200, 12, 200, '#fff')
    out += rect(-30, -280, 60, 60, GLASS) + rect(-340, -40, 30, 40, pal.P3) + rect(310, -40, 30, 40, pal.P3)
    out += ''.join(rect(-340 + i * 22, -50, 8, 50, '#fff', op=.9) for i in range(4)) + ''.join(rect(270 + i * 22, -50, 8, 50, '#fff', op=.9) for i in range(4))
    return out


def b_townhouse(pal, seed):
    out = ''
    cols = [WALL, mix(pal.S, '#fff', .7), WALL2, mix(pal.P, '#fff', .82)]
    for i in range(4):
        x = -340 + i * 170
        out += rect(x, -300, 170, 300, cols[i]) + f'<path d="M{x-6} -296 L{x+85} -380 L{x+176} -296Z" fill="{ROOF if i % 2 == 0 else pal.P1}"/>'
        out += rect(x + 20, -260, 54, 64, GLASS) + rect(x + 96, -260, 54, 64, LIT if i == 2 else GLASS) + rect(x + 20, -160, 54, 64, GLASS) + rect(x + 100, -130, 46, 130, pal.P1)
        out += rect(x + 10, -186, 150, 8, pal.P3)
    out += rect(-360, -8, 720, 8, CONCRETE)
    return out


def b_office(pal, seed):
    out = rect(-280, -560, 560, 560, mix(GLASS2, pal.P, .25)) + rect(-280, -560, 560, 20, pal.P1)
    out += ''.join(f'<path d="M{-280+i*56} -540 V0" stroke="#fff" stroke-opacity=".35" stroke-width="4"/>' for i in range(1, 10))
    out += ''.join(f'<path d="M-280 {-540+j*48} H280" stroke="#fff" stroke-opacity=".3" stroke-width="3"/>' for j in range(1, 11))
    out += f'<path d="M-280 -560 L-80 -560 L-280 -200Z" fill="#fff" opacity=".18"/>'
    out += rect(-340, -120, 120, 120, WALL2) + rect(-90, -90, 180, 90, pal.P1) + rect(-80, -82, 160, 82, GLASS, op=.8)
    out += text(0, -508, 'TOWER A', 26, '#fff', 800, 'middle', .85) + rect(-380, -8, 760, 8, CONCRETE)
    return out


def b_retail(pal, seed):
    out = rect(-360, -320, 720, 320, WALL) + rect(-360, -330, 720, 22, pal.P1)
    aw = [pal.P, pal.S, pal.P2, pal.S2]
    for i in range(4):
        x = -350 + i * 178
        out += rect(x, -170, 160, 160, GLASS) + rect(x + 60, -150, 40, 140, GLASS2)
        out += ''.join(f'<path d="M{x+k*20} -200 h20 l-4 30 h-12Z" fill="{aw[i] if k % 2 == 0 else "#fff"}"/>' for k in range(8))
        out += rect(x + 10, -290, 140, 70, GLASS)
    out += rect(-360, -8, 720, 8, CONCRETE)
    return out


def b_heritage(pal, seed):
    red = mix('#B5563D', pal.P, .2)
    out = rect(-340, -380, 680, 380, red) + rect(-360, -400, 720, 26, '#EFE6D6') + rect(-360, -210, 720, 16, '#EFE6D6')
    for i in range(7):
        x = -300 + i * 100
        out += rect(x - 30, -360, 60, 120, '#EFE6D6') + f'<path d="M{x-22} -250 V-320 a22 22 0 0 1 44 0 V-250Z" fill="{GLASS2}"/>'
        out += rect(x - 30, -190, 60, 190, '#EFE6D6') + f'<path d="M{x-22} -10 V-140 a22 22 0 0 1 44 0 V-10Z" fill="{mix(GLASS2, "#000", .15) if i == 3 else GLASS}"/>'
    out += f'<path d="M-120 -400 L0 -470 L120 -400Z" fill="#EFE6D6"/>' + circle(0, -425, 18, red)
    out += rect(-380, -8, 760, 8, CONCRETE)
    return out


def b_farmhouse(pal, seed):
    out = rect(-300, -170, 600, 170, WALL) + f'<path d="M-340 -166 L-220 -300 L220 -300 L340 -166Z" fill="{ROOF}"/>'
    out += ''.join(f'<path d="M{-330+i*40} -170 L{-220+i*30} -296" stroke="{mix(ROOF, "#000", .2)}" stroke-width="3"/>' for i in range(0, 15))
    out += ''.join(rect(-260 + i * 120, -140, 80, 110, GLASS if i != 2 else pal.P1) for i in range(5))
    out += rect(-320, 0, 300, 36, '#7EC8E3') + rect(-320, 0, 300, 6, '#fff', op=.6)
    out += palm(330, 0, .9) + palm(-380, 0, .8)
    return out


def b_cottage(pal, seed):
    out = rect(-220, -200, 300, 200, WALL) + f'<path d="M-250 -196 L-70 -330 L110 -196Z" fill="{pal.P1}"/>'
    out += rect(80, -150, 180, 150, WALL2) + f'<path d="M60 -146 L170 -230 L280 -146Z" fill="{pal.P1}"/>'
    out += rect(-180, -160, 70, 70, GLASS) + rect(-40, -160, 70, 70, LIT) + rect(130, -110, 70, 110, pal.S1 if hasattr(pal, 'S1') else pal.S) + rect(-120, -60, 60, 60, pal.P)
    out += rect(-150, -380, 30, 80, '#8C8C8C')
    out += tree(-320, 0, .9, kind='tall') + tree(330, 0, .8, kind='tall')
    return out


def b_warehouse(pal, seed):
    out = rect(-380, -260, 760, 260, '#D9DDE2') + f'<path d="M-400 -256 L0 -330 L400 -256Z" fill="{pal.P1}"/>'
    out += ''.join(f'<path d="M{-380+i*38} -260 V0" stroke="#000" stroke-opacity=".06" stroke-width="3"/>' for i in range(21))
    for i in range(3):
        x = -320 + i * 230
        out += rect(x, -170, 180, 170, '#A9B0B8') + ''.join(f'<path d="M{x} {-170+k*14} h180" stroke="#fff" stroke-opacity=".4" stroke-width="2"/>' for k in range(12))
    out += text(0, -210, 'BAY 1 · BAY 2 · BAY 3', 28, pal.P1, 800, 'middle') + truck(260, 0, .7, pal.S, '#fff')
    return out


def b_plot(pal, seed):
    out = f'<path d="M-420 0 L-260 -170 L300 -170 L440 0Z" fill="{mix(GRASS, "#C9A97A", .45)}"/>'
    out += f'<path d="M-260 -170 L-420 0 M300 -170 L440 0 M-340 -85 L370 -85" stroke="#fff" stroke-width="5" stroke-dasharray="14 12" opacity=".8"/>'
    out += f'<path d="M20 -170 L10 0" stroke="#fff" stroke-width="5" stroke-dasharray="14 12" opacity=".8"/>'
    for x, y in ((-260, -170), (300, -170), (-420, 0), (440, 0), (20, -170), (10, 0)):
        out += rect(x - 8, y - 26, 16, 30, '#F4F4F4') + rect(x - 8, y - 26, 16, 8, '#D94F3D')
    out += rect(-470, 0, 940, 46, ROAD) + ''.join(rect(-450 + i * 120, 20, 60, 6, '#fff', op=.8) for i in range(8))
    out += rect(160, -380, 12, 210, '#6B5B4B') + rect(60, -390, 220, 120, pal.P) + text(170, -335, 'PLOTS', 34, '#fff', 800, 'middle') + text(170, -295, 'AVAILABLE', 24, pal.S, 800, 'middle')
    return out


def b_layout(pal, seed):
    out = rect(-400, -420, 800, 420, mix(GRASS, '#fff', .55), 18)
    out += rect(-400, -230, 800, 40, ROAD) + rect(-40, -420, 80, 420, ROAD)
    random.seed(seed)
    for bx, by in ((-380, -400), (60, -400), (-380, -170), (60, -170)):
        for i in range(4):
            for j in range(2):
                sold = random.random() < .3
                out += rect(bx + i * 78, by + j * 76, 70, 68, pal.S2 if sold else '#fff', 6) + rect(bx + i * 78, by + j * 76, 70, 68, 'none', 6, extra=f' stroke="{pal.P1}" stroke-width="3"')
    out += circle(0, -210, 34, GRASS2) + tree(330, -20, .3) + tree(-330, -20, .3)
    return out


BUILDINGS = {'tower': b_tower, 'penthouse': b_penthouse, 'villa': b_villa, 'bungalow': b_bungalow, 'townhouse': b_townhouse, 'office': b_office,
             'retail': b_retail, 'heritage': b_heritage, 'farmhouse': b_farmhouse, 'cottage': b_cottage, 'warehouse': b_warehouse, 'plot': b_plot, 'layout': b_layout}
SIZE = {'tower': 640, 'penthouse': 720, 'villa': 420, 'bungalow': 400, 'townhouse': 400, 'office': 600, 'retail': 340, 'heritage': 480, 'farmhouse': 320,
        'cottage': 380, 'warehouse': 340, 'plot': 400, 'layout': 420}
WIDTH = {'tower': 660, 'penthouse': 560, 'villa': 680, 'bungalow': 700, 'townhouse': 740, 'office': 780, 'retail': 740, 'heritage': 780, 'farmhouse': 760,
         'cottage': 700, 'warehouse': 820, 'plot': 960, 'layout': 820}
LABELS = {'tower': 'Apartment towers', 'penthouse': 'Penthouse tower', 'villa': 'Modern villa with a pool', 'bungalow': 'Independent bungalow', 'townhouse': 'Row houses',
          'office': 'Office tower', 'retail': 'Shops and showrooms', 'heritage': 'Heritage building', 'farmhouse': 'Farmhouse with a pool', 'cottage': 'Hill cottage',
          'warehouse': 'Warehouse', 'plot': 'Open plot', 'layout': 'Plotted layout'}


def exterior(pal, kind, w, h, seed, dusk=False, fill=.78, gid='sky'):
    b, base = backdrop(w, h, pal, seed, dusk, .8 if kind in ('plot', 'layout') else .78, gid)
    bh, bw = SIZE[kind], WIDTH[kind]
    s = min(h * fill * .82 / bh, w * .86 / bw)
    if kind in ('tower', 'penthouse', 'office', 'heritage', 'retail'):
        b += rect(0, base + h * .07, w, h * .05, ROAD) + ''.join(rect(i * 120 + 30, base + h * .093, 60, 4, '#fff', op=.7) for i in range(int(w / 120) + 1))
    if kind == 'layout':
        b = rect(0, 0, w, h, mix(GRASS, '#fff', .3))
        return b + g(w / 2, h * .5 + bh * s / 2, s, BUILDINGS[kind](pal, seed))
    b += trees(w, base, h / 900 * .9, seed, 2 if kind in ('plot',) else 3, 'round')
    b += g(w / 2, base + 6, s, BUILDINGS[kind](pal, seed))
    if kind not in ('plot', 'farmhouse', 'cottage', 'warehouse'):
        b += tree(w * .08, base + 10, h / 900 * 1.1) + tree(w * .93, base + 14, h / 900 * 1.0)
    return b


# ----------------------------------------------------------------------------- interiors (full canvas 1200x900)

def floor_wall(pal, wall, floor, split=.66):
    return rect(0, 0, PW, PH * split, wall) + rect(0, PH * split, PW, PH * (1 - split), floor) + rect(0, PH * split - 8, PW, 12, mix(floor, '#000', .15))


def window_view(x, y, w, h, pal, dusk=False):
    top = mix(pal.P, '#1B1F3B', .5) if dusk else '#A9D3EC'
    gid = f'wv{int(x)}{int(y)}'
    inner = grad(gid, top, '#EAF5FB') + rect(0, 0, w, h, f'url(#{gid})') + skyline(int(w) + 20, h, '#FFFFFF' if dusk else '#B9CFDD', int(x + y) % 11, .55, int(h * .15), int(h * .5))
    return (rect(x - 14, y - 14, w + 28, h + 28, '#FFFFFF') + f'<svg x="{x}" y="{y}" width="{w}" height="{h}" viewBox="0 0 {w} {h}">{inner}</svg>'
            + f'<path d="M{x+w/2} {y} V{y+h} M{x} {y+h/2} H{x+w}" stroke="#fff" stroke-width="10"/>')


def room_living(pal):
    b = floor_wall(pal, mix(pal.WARM, '#fff', .4), '#C9A47E')
    b += window_view(640, 120, 420, 360, pal)
    b += rect(140, 430, 440, 120, pal.P, 26) + rect(120, 380, 480, 80, pal.P1, 26) + rect(110, 420, 50, 140, pal.P1, 20) + rect(560, 420, 50, 140, pal.P1, 20)
    b += rect(170, 400, 110, 70, pal.S2, 18) + rect(440, 400, 110, 70, pal.S3, 18)
    b += f'<ellipse cx="380" cy="700" rx="300" ry="60" fill="{pal.S3}"/>' + rect(250, 610, 260, 26, '#6B4E3A', 8) + rect(270, 636, 12, 50, '#6B4E3A') + rect(478, 636, 12, 50, '#6B4E3A')
    b += rect(200, 140, 260, 170, '#fff') + rect(214, 154, 232, 142, pal.P3) + circle(330, 225, 40, pal.S, .8)
    b += plant(1110, 600, 1.4, pal.S, GRASS, GRASS2) + rect(900, 560, 4, 40, '#333') + f'<path d="M860 330 h80 l-20 60 h-40Z" fill="{pal.S}"/>' + rect(898, 390, 4, 210, '#333')
    return b


def room_kitchen(pal):
    b = floor_wall(pal, '#F6F2EC', '#D9D3CB')
    b += ''.join(rect(120 + i * 6, 0, 2, 0, '#000') for i in range(1))
    b += rect(100, 140, 1000, 160, pal.P2, 10) + ''.join(f'<path d="M{100+i*200} 140 V300" stroke="#fff" stroke-width="5"/>' for i in range(1, 5))
    b += rect(100, 360, 1000, 230, pal.P1, 10) + rect(90, 340, 1020, 26, '#EDE6DA') + ''.join(f'<path d="M{100+i*200} 380 V580" stroke="#fff" stroke-opacity=".4" stroke-width="4"/>' for i in range(1, 5))
    b += ''.join(rect(160 + i * 200, 470, 60, 8, '#E9E9E9', 4) for i in range(5))
    b += rect(300, 640, 600, 120, '#EDE6DA', 10) + rect(310, 760, 580, 30, mix('#EDE6DA', '#000', .2), 6)
    for x in (380, 600, 820):
        b += rect(x - 4, 0, 8, 200, '#444') + f'<path d="M{x-60} 260 Q{x} 180 {x+60} 260Z" fill="{pal.S}"/>' + circle(x, 262, 16, LIT, .8)
    b += rect(500, 300, 200, 40, '#C8CCD2', 6) + plant(1040, 340, .6, '#fff', GRASS, GRASS2)
    return b


def room_bedroom(pal):
    b = floor_wall(pal, mix(pal.P, '#fff', .85), '#B8906A')
    b += rect(300, 220, 600, 200, pal.P1, 20) + rect(260, 420, 680, 200, '#fff', 20) + rect(260, 500, 680, 120, pal.S2, 10) + rect(340, 380, 200, 80, '#F4F1EC', 24) + rect(660, 380, 200, 80, '#F4F1EC', 24)
    b += rect(140, 480, 110, 140, '#6B4E3A', 8) + f'<path d="M160 400 h70 l-14 60 h-42Z" fill="{pal.S}"/>' + rect(194, 460, 4, 20, '#333')
    b += rect(950, 480, 110, 140, '#6B4E3A', 8) + window_view(960, 110, 200, 260, pal, True)
    b += rect(380, 100, 440, 90, '#fff') + rect(392, 112, 416, 66, pal.P3)
    b += f'<ellipse cx="600" cy="760" rx="380" ry="60" fill="{pal.S3}" opacity=".8"/>'
    return b


def room_bath(pal):
    b = floor_wall(pal, '#EEF3F4', '#C9D3D6')
    b += ''.join(f'<path d="M{i*80} 0 V594" stroke="#fff" stroke-width="3"/>' for i in range(16)) + ''.join(f'<path d="M0 {j*80} H1200" stroke="#fff" stroke-width="3"/>' for j in range(8))
    b += rect(140, 430, 520, 170, '#fff', 80) + rect(170, 450, 460, 60, '#BFE3F0', 30) + rect(600, 300, 14, 140, '#B9BEC4')
    b += rect(780, 430, 300, 170, pal.P1, 10) + f'<ellipse cx="930" cy="430" rx="110" ry="22" fill="#fff"/>' + rect(920, 370, 10, 50, '#B9BEC4')
    b += rect(800, 120, 260, 220, '#DCEAF0', 120) + rect(812, 132, 236, 196, '#EAF4F8', 110)
    b += plant(110, 600, .9, pal.S, GRASS, GRASS2) + rect(300, 330, 200, 16, pal.S, 8) + rect(320, 346, 30, 70, '#fff', 6) + rect(380, 346, 30, 70, pal.S3, 6)
    return b


def room_balcony(pal):
    b = sky(PW, PH, '#8EC5E8', '#EAF5FB', 'bk') + skyline(PW, 560, '#B9CFDD', 5, .8, 80, 300) + tree(1050, 600, 1.2) + tree(150, 600, 1)
    b += rect(0, 600, PW, 300, '#C9A47E') + ''.join(rect(i * 60, 470, 10, 140, '#fff') for i in range(21)) + rect(0, 460, PW, 16, '#fff')
    b += rect(340, 640, 200, 30, '#6B4E3A', 6) + rect(360, 670, 10, 60, '#6B4E3A') + rect(510, 670, 10, 60, '#6B4E3A')
    b += rect(600, 560, 160, 140, pal.P, 30) + rect(180, 560, 140, 140, pal.S, 30) + plant(900, 720, 1.1, pal.S2, GRASS, GRASS2)
    return b


def room_office(pal):
    b = floor_wall(pal, '#EEF0F2', '#9AA3AD', .62)
    b += ''.join(window_view(80 + i * 360, 70, 300, 280, pal) for i in range(3))
    for r in range(2):
        for c in range(3):
            x, y = 140 + c * 330, 600 + r * 150
            b += rect(x, y, 260, 16, '#fff', 4) + rect(x + 20, y + 16, 8, 60, '#666') + rect(x + 232, y + 16, 8, 60, '#666') + rect(x + 80, y - 70, 100, 70, '#333', 6) + rect(x + 86, y - 64, 88, 56, pal.P3)
            b += rect(x + 100, y + 40, 60, 70, pal.P if (r + c) % 2 else pal.S, 14)
    b += plant(1120, 720, 1, pal.S, GRASS, GRASS2)
    return b


def room_cabin(pal):
    b = floor_wall(pal, mix(pal.P, '#fff', .9), '#8E959C', .64)
    b += rect(120, 110, 520, 300, '#fff', 10) + rect(140, 130, 480, 260, pal.P1, 6) + bars(200, 360, 5, 50, 30, [80, 130, 110, 180, 210], pal.S, pal.S2)
    b += rect(220, 600, 760, 40, '#6B4E3A', 14) + rect(560, 640, 80, 120, '#6B4E3A')
    for i, x in enumerate((260, 420, 780, 940)):
        b += rect(x - 50, 520, 100, 90, pal.P if i % 2 else pal.S, 20) + rect(x - 50, 660, 100, 70, pal.P if i % 2 else pal.S, 20)
    b += window_view(760, 100, 340, 300, pal)
    return b


def room_pool(pal):
    b = sky(PW, PH, '#8EC5E8', '#EAF5FB', 'pl') + rect(0, 470, PW, 430, '#EDE6DA')
    b += rect(0, 300, PW, 180, WALL2) + rect(0, 290, PW, 16, pal.P1) + ''.join(rect(60 + i * 220, 330, 150, 120, GLASS) for i in range(6))
    b += f'<path d="M80 620 L1120 620 L1180 860 L20 860Z" fill="#6CC4E0"/>' + ''.join(f'<path d="M{120+i*140} {700+(i%2)*60} q40 -14 80 0" stroke="#fff" stroke-width="5" fill="none" opacity=".7"/>' for i in range(8))
    b += rect(140, 520, 160, 30, '#fff', 12) + rect(420, 520, 160, 30, '#fff', 12) + palm(1080, 560, .9) + palm(40, 560, .8)
    b += f'<path d="M820 520 L900 420 L980 520Z" fill="{pal.S}"/>' + rect(896, 420, 8, 100, '#666')
    return b


def room_garden(pal):
    b = sky(PW, PH, '#8EC5E8', '#EAF5FB', 'gd') + rect(0, 520, PW, 380, GRASS) + f'<path d="M520 900 Q600 700 640 520 L680 520 Q680 700 760 900Z" fill="#E6D8C2"/>'
    b += ''.join(tree(x, 560, s) for x, s in ((100, 1.2), (300, .9), (930, 1.1), (1110, 1.3)))
    b += rect(380, 380, 440, 150, WALL) + f'<path d="M360 384 L600 290 L840 384Z" fill="{ROOF}"/>' + rect(560, 430, 80, 100, pal.P1)
    b += ''.join(circle(160 + i * 110, 820 - (i % 2) * 20, 30, pal.S2 if i % 2 else pal.P2, .9) for i in range(10))
    return b


def room_road(pal):
    b = sky(PW, PH, '#8EC5E8', '#EAF5FB', 'rd') + rect(0, 520, PW, 380, mix(GRASS, '#C9A97A', .35))
    b += f'<path d="M520 900 L580 520 L620 520 L680 900Z" fill="{ROAD}"/>' + ''.join(rect(596, 560 + i * 70, 8, 34, '#fff', op=.8) for i in range(5))
    b += ''.join(rect(x - 4, 440, 8, 100, '#666') + circle(x, 440, 12, LIT) for x in (500, 700))
    b += ''.join(tree(x, 540, s) for x, s in ((80, 1), (240, .7), (960, .8), (1120, 1.1)))
    b += rect(150, 700, 300, 10, '#fff', op=.7) + rect(750, 700, 300, 10, '#fff', op=.7)
    return b


def room_clubhouse(pal):
    b = floor_wall(pal, '#F1ECE4', '#C9A47E')
    b += rect(80, 120, 400, 340, '#fff', 10) + rect(96, 136, 368, 308, '#7EC8E3') + ''.join(f'<path d="M{110+i*60} 300 q20 -10 40 0" stroke="#fff" stroke-width="4" fill="none"/>' for i in range(6))
    for i, x in enumerate((620, 780, 940)):
        b += rect(x - 20, 400, 40, 200, '#555', 8) + rect(x - 60, 380, 120, 30, '#333', 8)
    b += rect(560, 640, 520, 40, pal.P1, 10) + circle(700, 610, 30, pal.S) + circle(900, 610, 30, pal.P2)
    b += plant(130, 720, 1, pal.S, GRASS, GRASS2)
    return b


ROOMS = {'living': room_living, 'kitchen': room_kitchen, 'bedroom': room_bedroom, 'bath': room_bath, 'balcony': room_balcony, 'office': room_office,
         'cabin': room_cabin, 'pool': room_pool, 'garden': room_garden, 'road': room_road, 'clubhouse': room_clubhouse}
ROOM_LABELS = {'living': 'Living room', 'kitchen': 'Modular kitchen', 'bedroom': 'Bedroom', 'bath': 'Bathroom', 'balcony': 'Balcony view', 'office': 'Open-plan office',
               'cabin': 'Meeting room', 'pool': 'Swimming pool', 'garden': 'Garden and lawn', 'road': 'Internal roads', 'clubhouse': 'Clubhouse and gym'}


# ----------------------------------------------------------------------------- people and compositions

def agent_portrait(cv, name, pal, i):
    styles = ['short', 'long', 'grey', 'bun', 'short', 'long']
    tops = [pal.P, pal.INK, pal.P1, pal.S, pal.P2, pal.INK]
    portrait(cv, name, pal, SKINS[(i * 2 + 1) % 6], HAIR[i % 5], tops[i % 6], styles[i % 6], i)


def hero(pal, c, seed):
    w, h = 1600, 1000
    kinds = c['hero_kinds']
    b, base = backdrop(w, h, pal, seed, c.get('dusk', False), .8, 'hs')
    b += trees(w, base, 1.1, seed, 4)
    if len(kinds) > 1:
        b += g(w * .2, base + 6, .7 * 1000 / 1000, BUILDINGS[kinds[1]](pal, seed + 1))
    k = kinds[0]
    s = min(h * .62 / SIZE[k], w * .55 / WIDTH[k])
    b += g(w * .56, base + 8, s, BUILDINGS[k](pal, seed))
    if len(kinds) > 2:
        b += g(w * .88, base + 6, min(h * .38 / SIZE[kinds[2]], w * .3 / WIDTH[kinds[2]]), BUILDINGS[kinds[2]](pal, seed + 2))
    b += tree(w * .04, base + 12, 1.3) + tree(w * .97, base + 16, 1.2)
    return b


def about_office(pal, seed):
    w, h = 1000, 850
    b = rect(0, 0, w, h, mix(pal.P, '#fff', .9)) + rect(0, h * .7, w, h * .3, '#C9A47E')
    b += window_view(560, 90, 360, 300, pal)
    b += rect(80, 90, 400, 280, '#fff', 10) + g(280, 340, .32, b_villa(pal, seed)) + rect(80, 90, 400, 280, 'none', 10, extra=f' stroke="{pal.P1}" stroke-width="10"')
    b += figure(260, 760, .95, SKINS[1], HAIR[0], pal.P, 'short') + figure(740, 760, .95, SKINS[4], HAIR[2], pal.S, 'long')
    b += rect(120, 600, 760, 30, '#6B4E3A', 10) + rect(140, 630, 20, 220, '#6B4E3A') + rect(840, 630, 20, 220, '#6B4E3A')
    b += rect(430, 540, 140, 60, '#fff', 6) + rect(445, 552, 110, 8, pal.P3, 4) + rect(445, 568, 80, 8, pal.P3, 4) + circle(600, 580, 14, pal.S)
    return b


def keys_scene(pal):
    w = 800
    b = rect(0, 0, w, w, pal.S3) + circle(400, 400, 300, pal.P4)
    b += g(400, 520, .55, b_bungalow(pal, 1))
    b += f'<g transform="translate(560 600) rotate(-20)"><circle cx="0" cy="0" r="46" fill="none" stroke="{pal.P1}" stroke-width="18"/><rect x="40" y="-9" width="150" height="18" rx="6" fill="{pal.P1}"/><rect x="150" y="9" width="16" height="30" fill="{pal.P1}"/><rect x="176" y="9" width="14" height="22" fill="{pal.P1}"/></g>'
    return b


def generate(out_dir, niche, primary, secondary, seed=1, logo_names=None):
    cv = Canvas(out_dir)
    pal = Palette(primary, secondary)
    c = CONTENT[niche]
    for i, it in enumerate(c['listings']):
        cv.save(f'prop-{it["slug"]}.svg', PW, PH, exterior(pal, it['kind'], PW, PH, seed + i * 3, dusk=(i % 4 == 3)), LABELS[it['kind']])
    for r in c['rooms']:
        cv.save(f'room-{r}.svg', PW, PH, ROOMS[r](pal), ROOM_LABELS[r])
    for i, k in enumerate(c['categories']):
        cv.save(f'cat-{k["slug"]}.svg', 1000, 1000, exterior(pal, k['kind'], 1000, 1000, seed + 40 + i, dusk=(i == 3), fill=.72), LABELS[k['kind']])
    cv.save('hero.svg', 1600, 1000, hero(pal, c, seed), LABELS[c['hero_kinds'][0]])
    cv.save('about-1.svg', 1000, 850, about_office(pal, seed), 'Advisors meeting clients in the office')
    cv.save('about-2.svg', 800, 800, keys_scene(pal), 'House keys handed over')
    cv.save('cta.svg', 1600, 700, exterior(pal, c['hero_kinds'][0], 1600, 700, seed + 9, dusk=True, fill=.7, gid='cs'), 'City skyline at dusk')
    banner(cv, pal, 'city')
    for i in range(4):
        agent_portrait(cv, f'agent-{i+1}.svg', pal, i + seed)
    for i in range(3):
        portrait(cv, f'customer-{i+1}.svg', pal, SKINS[(i * 3 + seed) % 6], HAIR[(i + 1) % 5], [pal.S, pal.P2, pal.P][i], ['long', 'short', 'grey'][i], i + 1, 400, 400)
    logos(cv, pal, logo_names or c['logos'])
