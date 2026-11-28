"""Travel scenes. generate(out_dir, niche, primary, secondary, seed, logo_names) writes:
trip-<slug>.svg (1200x900, one per package), scene-<key>.svg (1200x900, gallery scenes), dest-<slug>.svg (1000x1000),
hero.svg (1600x1000), about-1.svg (1000x850), about-2.svg (800x800), banner.svg, cta.svg (1600x700),
guide-1..4, traveller-1..3 and logo-1..4 (partners)."""
import math
import random
from kit import *  # noqa: F401,F403
from business_images import logos

from travel_content import NICHES as CONTENT

SAND = '#EBD3A4'
SEA = '#3E9DB5'
SEA2 = '#2A7F99'
LEAF = '#4E8A4C'
LEAF2 = '#3B6E3A'
ROCK = '#8C8F99'
SNOW = '#F7FAFC'


def palm(x, base, s, c=LEAF, trunk='#8B6B4A', lean=30):
    t = f'<path d="M{x} {base} C{x+lean*s/3} {base-120*s} {x+lean*s*.6} {base-220*s} {x+lean*s} {base-300*s}" stroke="{trunk}" stroke-width="{12*s}" fill="none" stroke-linecap="round"/>'
    cx, cy = x + lean * s, base - 300 * s
    for a in (-160, -120, -60, -20, 20, 200):
        t += f'<path d="M{cx} {cy} q{60*s} {-30*s} {120*s} {10*s}" stroke="{c}" stroke-width="{16*s}" fill="none" stroke-linecap="round" transform="rotate({a} {cx} {cy})"/>'
    return t


def sky_for(w, h, pal, mood, gid):
    tops = {'day': (mix('#7FC1E6', pal.P, .1), '#EAF6FB'), 'dusk': (mix(pal.S, '#C2507A', .35), mix(pal.S, '#FFE3B8', .65)),
            'night': ('#1B2440', mix(pal.P, '#3A4C7A', .5)), 'dawn': (mix('#F7C59F', pal.S, .2), '#FFF4E4')}
    top, bot = tops[mood]
    b = sky(w, h, top, bot, gid)
    if mood == 'night':
        random.seed(w + h)
        b += ''.join(circle(random.uniform(0, w), random.uniform(0, h * .5), random.uniform(1, 2.4), '#fff', .8) for _ in range(70)) + circle(w * .8, h * .18, h * .05, '#FFF6D8')
    else:
        b += circle(w * .78, h * .22, h * .075, '#FFF4D6' if mood == 'day' else '#FFD68A', .95)
        b += clouds(int(w / 7), w, h * .08, 3, .75 if mood == 'day' else .45)
    return b


def ridge(w, y, amp, c, seed, step=80, op=None):
    random.seed(seed)
    pts = [f'M0 {y}']
    x = 0
    while x <= w + step:
        pts.append(f'L{x} {y - random.uniform(0, amp):.0f}')
        x += step
    o = f' opacity="{op}"' if op else ''
    return f'<path d="{" ".join(pts)} L{w} 9999 L0 9999Z" fill="{c}"{o}/>'


def peaks(w, base, h, c, snow, seed, n=4):
    random.seed(seed)
    out = ''
    for i in range(n):
        cx = w * (i + .5) / n + random.uniform(-60, 60)
        ph = h * random.uniform(.7, 1)
        hw = ph * random.uniform(.8, 1.2)
        out += f'<path d="M{cx-hw} {base} L{cx} {base-ph} L{cx+hw} {base}Z" fill="{c}"/>'
        out += f'<path d="M{cx-ph*.22} {base-ph*.78} L{cx} {base-ph} L{cx+ph*.22} {base-ph*.78} L{cx+ph*.1} {base-ph*.72} L{cx} {base-ph*.8} L{cx-ph*.08} {base-ph*.7}Z" fill="{snow}"/>'
    return out


def water(w, y, h, c, c2):
    b = rect(0, y, w, h, c)
    b += ''.join(f'<path d="M{(i*137)%w} {y+30+(i*53)%int(h-40)} q30 -10 60 0" stroke="{c2}" stroke-width="4" fill="none" opacity=".6"/>' for i in range(24))
    return b


def person(x, base, s, top, skin='#C68B59', hair='#2A1B14', pack=None):
    g = f'<g transform="translate({x} {base}) scale({s})">'
    g += rect(-14, -60, 12, 60, '#3B3F4A', 5) + rect(2, -60, 12, 60, '#3B3F4A', 5) + rect(-20, -128, 40, 72, top, 14)
    if pack:
        g += rect(-34, -124, 18, 50, pack, 6)
    g += circle(0, -146, 18, skin) + f'<path d="M-18 -150 a18 18 0 0 1 36 0Z" fill="{hair}"/>'
    return g + '</g>'


# ----------------------------------------------------------------------------- scenes (w, h, pal, seed)

def s_beach(w, h, pal, seed, mood='day'):
    b = sky_for(w, h, pal, mood, f'sb{seed}') + water(w, h * .5, h * .2, SEA, '#fff')
    b += f'<path d="M0 {h*.68} Q{w*.5} {h*.62} {w} {h*.7} V{h} H0Z" fill="{SAND}"/>' + f'<path d="M0 {h*.68} Q{w*.5} {h*.62} {w} {h*.7}" stroke="#fff" stroke-width="8" fill="none" opacity=".7"/>'
    b += palm(w * .1, h * .9, h / 900 * 1.2, lean=50) + palm(w * .2, h * .95, h / 900 * .9, lean=30)
    b += f'<path d="M{w*.6} {h*.82} l70 -60 l70 60Z" fill="{pal.S}"/>' + rect(w * .6 + 68, h * .76, 4, h * .12, '#666') + rect(w * .66, h * .84, 120, 14, '#fff', 6)
    b += f'<path d="M{w*.3} {h*.56} l40 0 l-10 14 h-24Z" fill="#fff"/><path d="M{w*.32} {h*.56} v-40 l26 34Z" fill="{pal.P}"/>'
    return b


def s_mountains(w, h, pal, seed, mood='day'):
    b = sky_for(w, h, pal, mood, f'sm{seed}') + peaks(w, h * .62, h * .45, mix(pal.P, ROCK, .5), SNOW, seed, 4)
    b += ridge(w, h * .66, h * .1, mix(LEAF2, pal.P, .3), seed + 1) + ridge(w, h * .78, h * .08, LEAF, seed + 2)
    b += ''.join(tree(w * x, h * .86, h / 900 * .7, kind='tall') for x in (.08, .14, .86, .93))
    b += ''.join(f'<path d="M{w*x} {h*.88} l45 -70 l45 70Z" fill="{c}"/>' for x, c in ((.42, pal.S), (.52, pal.P)))
    return b


def s_backwaters(w, h, pal, seed, mood='dusk'):
    b = sky_for(w, h, pal, mood, f'sw{seed}') + water(w, h * .55, h * .45, mix(SEA2, LEAF2, .35), '#fff')
    b += rect(0, h * .52, w, h * .05, LEAF2) + ''.join(palm(w * x, h * .56, h / 900 * s, lean=l) for x, s, l in ((.05, .8, 40), (.14, .65, -30), (.82, .75, 30), (.92, .9, -40), (.66, .5, 20)))
    hx, hy = w * .3, h * .74
    b += f'<path d="M{hx} {hy} h{w*.36} l-30 40 h{-w*.36+60}Z" fill="#6B4A2E"/>'
    b += f'<path d="M{hx+20} {hy} q{w*.16} -120 {w*.32} 0Z" fill="#C9A26A"/>' + ''.join(f'<path d="M{hx+40+i*40} {hy} q{w*.02} -60 0 -80" stroke="#8C6A3E" stroke-width="3" fill="none"/>' for i in range(int(w * .3 / 40)))
    return b


def s_desert(w, h, pal, seed, mood='dusk'):
    b = sky_for(w, h, pal, mood, f'sd{seed}')
    fx, fy = w * .62, h * .5
    b += rect(fx - 200, fy - 120, 400, 140, '#C98B4F') + ''.join(rect(fx - 200 + i * 50, fy - 140, 30, 24, '#C98B4F') for i in range(8))
    b += rect(fx - 260, fy - 200, 90, 220, '#B97B42') + rect(fx + 170, fy - 220, 90, 240, '#B97B42') + f'<path d="M{fx-270} {fy-200} q55 -60 110 0Z M{fx+160} {fy-220} q55 -60 110 0Z" fill="#B97B42"/>'
    b += ''.join(rect(fx - 160 + i * 60, fy - 80, 20, 34, '#6B3E1E') for i in range(6))
    b += f'<path d="M0 {h*.62} Q{w*.3} {h*.5} {w*.6} {h*.62} T{w} {h*.6} V{h} H0Z" fill="{mix(SAND, "#D9A15E", .4)}"/>' + f'<path d="M0 {h*.78} Q{w*.4} {h*.66} {w} {h*.8} V{h} H0Z" fill="{mix(SAND, "#D9A15E", .2)}"/>'
    cx, cy = w * .22, h * .8
    b += f'<path d="M{cx} {cy} c20 -60 60 -60 70 -20 c10 -40 50 -40 60 0 l20 -30 l14 6 l-10 40 l-10 60 h-10 l-6 -50 h-80 l-6 50 h-10 l-6 -60Z" fill="#7A4E2A"/>'
    return b


def s_lake(w, h, pal, seed, mood='dawn'):
    b = sky_for(w, h, pal, mood, f'sl{seed}') + peaks(w, h * .55, h * .38, mix(pal.P, ROCK, .55), SNOW, seed, 5)
    b += ridge(w, h * .58, h * .06, LEAF2, seed + 3) + water(w, h * .6, h * .4, mix(SEA, pal.P, .25), '#fff')
    b += ''.join(tree(w * x, h * .62, h / 900 * .6, kind='tall') for x in (.04, .1, .9, .96))
    sx, sy = w * .45, h * .8
    b += f'<path d="M{sx-160} {sy} q160 40 320 0 l-30 20 h-260Z" fill="#7A4A2A"/>' + rect(sx - 70, sy - 70, 140, 64, pal.S, 6) + f'<path d="M{sx-80} {sy-70} h160 l-20 -20 h-120Z" fill="{pal.P}"/>'
    return b


def s_temple(w, h, pal, seed, mood='dawn'):
    b = sky_for(w, h, pal, mood, f'st{seed}')
    tx, base = w * .5, h * .66
    for i, (tw, th) in enumerate(((360, 70), (300, 70), (240, 70), (180, 70), (120, 70))):
        y = base - 140 - (i + 1) * th
        b += rect(tx - tw / 2, y, tw, th, mix('#D9A066', pal.S, .2) if i % 2 else '#C98B4F') + ''.join(rect(tx - tw / 2 + 12 + k * 30, y + 16, 14, 30, '#8C5A30') for k in range(int((tw - 24) / 30)))
    b += f'<path d="M{tx-50} {base-490} q50 -70 100 0Z" fill="#C98B4F"/>' + circle(tx, base - 545, 12, '#E9B949')
    b += rect(tx - 260, base - 140, 520, 140, '#C98B4F') + f'<path d="M{tx-40} {base} v-90 a40 40 0 0 1 80 0 v90Z" fill="#6B3E1E"/>'
    b += ''.join(f'<path d="M0 {base+i*28} H{w}" stroke="#B88A5A" stroke-width="14"/>' for i in range(1, 4)) + water(w, base + 110, h - base - 110, mix(SEA2, pal.P, .3), '#fff')
    b += ''.join(circle(w * x, base + 160 + (i % 2) * 30, 7, '#FFB347') for i, x in enumerate((.2, .28, .35, .62, .7, .8)))
    return b


def s_city(w, h, pal, seed, mood='dusk'):
    b = sky_for(w, h, pal, mood, f'sc{seed}')
    b += skyline(w, h * .72, mix(pal.P, '#FFFFFF', .45), seed, .7, 120, 360) + skyline(w, h * .76, mix(pal.P, '#000', .2), seed + 4, 1, 80, 300)
    tx = w * .58
    b += f'<path d="M{tx-40} {h*.76} L{tx-12} {h*.08} L{tx+12} {h*.08} L{tx+40} {h*.76}Z" fill="{mix(pal.P, "#000", .35)}"/>' + rect(tx - 3, h * .02, 6, h * .07, '#666')
    random.seed(seed)
    b += ''.join(rect(random.uniform(0, w), random.uniform(h * .45, h * .74), 8, 12, '#FFD98A', .8) for _ in range(60))
    b += water(w, h * .76, h * .24, mix(pal.P, '#0B1430', .5), mix(pal.S, '#fff', .3))
    return b


def s_island(w, h, pal, seed, mood='day'):
    b = sky_for(w, h, pal, mood, f'si{seed}') + water(w, h * .45, h * .55, '#4CC3D9', '#fff') + rect(0, h * .45, w, h * .03, '#7FD6E5')
    for i in range(5):
        x = w * .12 + i * w * .17
        b += rect(x + 30, h * .62, 6, 80, '#7A5A3A') + rect(x + 110, h * .62, 6, 80, '#7A5A3A') + rect(x, h * .55, 150, 70, '#EFE3CC') + f'<path d="M{x-20} {h*.56} L{x+75} {h*.47} L{x+170} {h*.56}Z" fill="#B88A5A"/>'
    b += rect(0, h * .68, w, 10, '#B88A5A') + f'<ellipse cx="{w*.85}" cy="{h*.5}" rx="{w*.14}" ry="{h*.04}" fill="{SAND}"/>' + palm(w * .85, h * .5, h / 900 * .6, lean=20)
    return b


def s_safari(w, h, pal, seed, mood='dusk'):
    b = sky_for(w, h, pal, mood, f'sf{seed}') + ridge(w, h * .6, h * .06, mix(LEAF2, '#C9A26A', .4), seed) + rect(0, h * .66, w, h * .34, '#C9A26A')
    b += f'<path d="M{w*.12} {h*.66} c0 -60 30 -90 0 -160 M{w*.12} {h*.5} c-80 -10 -120 10 -150 0 c60 -40 240 -40 300 0 c-30 10 -70 -10 -150 0Z" stroke="#5A3E26" stroke-width="10" fill="{LEAF2}"/>'
    jx, jy = w * .5, h * .86
    b += rect(jx - 170, jy - 110, 340, 80, '#4F6B3A', 10) + rect(jx - 150, jy - 160, 220, 50, 'none', 0, extra=' stroke="#333" stroke-width="6"') + circle(jx - 110, jy - 20, 34, '#222') + circle(jx + 110, jy - 20, 34, '#222')
    b += person(jx - 70, jy - 110, .6, pal.S) + person(jx + 10, jy - 110, .6, pal.P)
    tx, ty = w * .82, h * .78
    b += f'<path d="M{tx-90} {ty} q0 -60 50 -70 h70 q30 -30 50 0 q10 20 -10 30 v40 h-10 v-30 h-110 v30 h-12Z" fill="#E08A2E"/>' + ''.join(f'<path d="M{tx-70+i*24} {ty-64} l8 30" stroke="#222" stroke-width="6"/>' for i in range(5))
    return b


def s_monastery(w, h, pal, seed, mood='day'):
    b = sky_for(w, h, pal, mood, f'sn{seed}') + peaks(w, h * .6, h * .42, '#B9A58C', SNOW, seed, 4) + ridge(w, h * .68, h * .12, '#A48C6E', seed + 1)
    mx, my = w * .5, h * .62
    for i, (bw, bh) in enumerate(((420, 90), (320, 80), (200, 70))):
        y = my - sum(x[1] for x in ((420, 90), (320, 80), (200, 70))[:i + 1])
        b += rect(mx - bw / 2, y, bw, bh, '#F4EFE6') + rect(mx - bw / 2, y, bw, 14, '#8C2F2F') + ''.join(rect(mx - bw / 2 + 20 + k * 40, y + 30, 18, 30, '#4A2A1A') for k in range(int((bw - 40) / 40)))
    b += rect(0, h * .8, w, h * .2, '#B39A78') + ''.join(f'<path d="M{w*.1+i*60} {h*.75} l20 -10 v40" stroke="{c}" stroke-width="10" fill="none"/>' for i, c in enumerate(['#D7263D', '#F2C14E', '#2E86AB', '#3BB273', '#fff'] * 2))
    return b


def s_road(w, h, pal, seed, mood='day'):
    b = sky_for(w, h, pal, mood, f'sr{seed}') + peaks(w, h * .5, h * .35, '#B39A86', SNOW, seed, 3) + ridge(w, h * .62, h * .12, '#9C8A70', seed + 1)
    b += f'<path d="M{w*.42} {h} C{w*.48} {h*.8} {w*.7} {h*.78} {w*.62} {h*.66} S{w*.5} {h*.6} {w*.56} {h*.56}" stroke="#555" stroke-width="90" fill="none"/>'
    b += f'<path d="M{w*.42} {h} C{w*.48} {h*.8} {w*.7} {h*.78} {w*.62} {h*.66}" stroke="#fff" stroke-width="5" stroke-dasharray="20 18" fill="none"/>'
    bx, by = w * .5, h * .9
    b += circle(bx - 60, by, 34, '#222') + circle(bx + 60, by, 34, '#222') + f'<path d="M{bx-60} {by} L{bx-10} {by-60} L{bx+50} {by-60} L{bx+60} {by}" stroke="{pal.S}" stroke-width="14" fill="none"/>' + person(bx, by - 50, .55, pal.P)
    return b


def s_waterfall(w, h, pal, seed, mood='day'):
    b = sky_for(w, h, pal, mood, f'sw2{seed}') + rect(0, h * .2, w, h * .8, LEAF2)
    b += f'<path d="M{w*.35} {h*.2} L{w*.65} {h*.2} L{w*.62} {h*.75} L{w*.38} {h*.75}Z" fill="#8C8F99"/>' + rect(w * .44, h * .2, w * .12, h * .55, '#E8F6FB')
    b += ''.join(f'<path d="M{w*(.45+i*.025)} {h*.22} V{h*.74}" stroke="#BFE3F0" stroke-width="5"/>' for i in range(4))
    b += water(w, h * .75, h * .25, mix(SEA, LEAF, .3), '#fff') + ''.join(tree(w * x, h * (.3 + (i % 3) * .14), h / 900 * .8) for i, x in enumerate((.05, .15, .25, .75, .85, .95)))
    return b


def s_palace(w, h, pal, seed, mood='dusk'):
    b = sky_for(w, h, pal, mood, f'sp{seed}') + water(w, h * .7, h * .3, mix(SEA, pal.P, .3), '#fff')
    px, py = w * .5, h * .7
    c1 = '#F2E6D4'
    b += rect(px - 380, py - 200, 760, 200, c1) + ''.join(f'<path d="M{px-360+i*80} {py-20} v-110 a26 26 0 0 1 52 0 v110Z" fill="{mix(pal.P, "#000", .1)}"/>' for i in range(10))
    for dx in (-300, 0, 300):
        b += rect(px + dx - 60, py - 290, 120, 90, c1) + f'<path d="M{px+dx-70} {py-290} q70 -110 140 0Z" fill="{c1}"/>' + rect(px + dx - 3, py - 380, 6, 30, '#C9A227')
    b += rect(px - 380, py - 210, 760, 14, pal.S)
    return b


def s_terraces(w, h, pal, seed, mood='day'):
    b = sky_for(w, h, pal, mood, f'stt{seed}')
    for i in range(9):
        y = h * .3 + i * h * .08
        b += f'<path d="M0 {y+20} Q{w*.3} {y-30} {w*.6} {y+10} T{w} {y} V{h} H0Z" fill="{mix(LEAF, "#A6C85A", (i % 3) / 3)}"/>' + f'<path d="M0 {y+20} Q{w*.3} {y-30} {w*.6} {y+10} T{w} {y}" stroke="{LEAF2}" stroke-width="4" fill="none"/>'
    b += palm(w * .85, h * .5, h / 900 * .8) + palm(w * .1, h * .62, h / 900 * .7, lean=-30)
    return b


def s_camp(w, h, pal, seed, mood='night'):
    b = sky_for(w, h, pal, mood, f'scp{seed}') + peaks(w, h * .6, h * .35, '#2E3A55', '#C9D6E8', seed, 4) + ridge(w, h * .7, h * .06, '#1F2A3A', seed) + rect(0, h * .72, w, h * .28, '#24324A')
    b += ''.join(f'<path d="M{w*x} {h*.86} l90 -130 l90 130Z" fill="{c}"/>' for x, c in ((.15, pal.S), (.7, pal.P)))
    fx, fy = w * .48, h * .88
    b += f'<path d="M{fx-30} {fy} q30 -110 60 0Z" fill="#FFB347"/><path d="M{fx-14} {fy} q14 -60 28 0Z" fill="#FFE08A"/>' + circle(fx, fy - 30, 120, '#FFB347', .12)
    return b


def s_tea(w, h, pal, seed, mood='dawn'):
    b = sky_for(w, h, pal, mood, f'stea{seed}') + ridge(w, h * .4, h * .1, mix(LEAF2, pal.P, .3), seed, op=.7)
    for i in range(7):
        y = h * .42 + i * h * .09
        b += f'<path d="M0 {y} Q{w*.25} {y-40} {w*.5} {y} T{w} {y} V{h} H0Z" fill="{mix(LEAF, "#7DBB5A", (i % 2) * .5)}"/>'
        b += ''.join(circle(x, y + 8, 16, LEAF2, .5) for x in range(20, int(w), 46))
    b += person(w * .7, h * .86, .9, pal.S, pack='#C9A26A')
    return b


def s_oldtown(w, h, pal, seed, mood='day'):
    b = sky_for(w, h, pal, mood, f'so{seed}')
    cols = [mix(pal.S, '#fff', .5), '#F2D7B6', mix(pal.P, '#fff', .7), '#E7B8A0', '#F4E9D8', mix(pal.S, '#fff', .3)]
    x = -20
    random.seed(seed)
    i = 0
    while x < w:
        bw, bh = random.randint(120, 180), random.randint(int(h * .3), int(h * .48))
        b += rect(x, h * .78 - bh, bw, bh, cols[i % 6]) + f'<path d="M{x-6} {h*.78-bh} L{x+bw/2} {h*.78-bh-60} L{x+bw+6} {h*.78-bh}Z" fill="#9C4A3A"/>'
        b += ''.join(rect(x + 20 + c * 50, h * .78 - bh + 40 + r * 70, 26, 40, '#5A7A9A') for c in range(int((bw - 20) / 50)) for r in range(int((bh - 60) / 70)))
        x += bw
        i += 1
    tx = w * .5
    b += rect(tx - 50, h * .2, 100, h * .58, '#D9C3A0') + f'<path d="M{tx-60} {h*.2} L{tx} {h*.08} L{tx+60} {h*.2}Z" fill="#4A6A7A"/>' + circle(tx, h * .28, 32, '#fff') + f'<path d="M{tx} {h*.28} v-20 M{tx} {h*.28} h14" stroke="#333" stroke-width="4"/>'
    b += rect(0, h * .78, w, h * .22, '#BFB2A0') + ''.join(rect(k * 60, h * .8 + (k % 2) * 30, 56, 26, '#ADA08C', 4) for k in range(int(w / 60) + 1))
    return b


def s_museum(w, h, pal, seed, mood='day'):
    b = sky_for(w, h, pal, mood, f'smu{seed}') + rect(0, h * .74, w, h * .26, '#CFC7BA')
    mx = w * .5
    b += f'<path d="M{mx-380} {h*.34} L{mx} {h*.16} L{mx+380} {h*.34}Z" fill="#EFE8DC"/>' + rect(mx - 380, h * .34, 760, 20, '#DDD3C2')
    b += ''.join(rect(mx - 350 + i * 100, h * .37, 40, h * .32, '#F4EFE6') for i in range(8)) + rect(mx - 400, h * .69, 800, 30, '#DDD3C2')
    bx, by = w * .22, h * .9
    b += rect(bx - 160, by - 130, 320, 110, '#F2C14E', 16) + ''.join(rect(bx - 140 + i * 56, by - 112, 44, 40, '#9CC3DA', 4) for i in range(5)) + circle(bx - 100, by - 14, 22, '#222') + circle(bx + 100, by - 14, 22, '#222')
    b += text(bx, by - 52, 'SCHOOL TRIP', 22, '#5A3E00', 800, 'middle')
    b += ''.join(person(w * x, h * .92, .55, c, pack=pal.P) for x, c in ((.6, pal.S), (.66, pal.P2), (.72, pal.S2), (.78, pal.P)))
    return b


SCENES = {'beach': s_beach, 'mountains': s_mountains, 'backwaters': s_backwaters, 'desert': s_desert, 'lake': s_lake, 'temple': s_temple, 'city': s_city,
          'island': s_island, 'safari': s_safari, 'monastery': s_monastery, 'road': s_road, 'waterfall': s_waterfall, 'palace': s_palace, 'terraces': s_terraces,
          'camp': s_camp, 'tea': s_tea, 'oldtown': s_oldtown, 'museum': s_museum}
LABELS = {'beach': 'Palm-lined beach', 'mountains': 'Snow peaks and pine valley', 'backwaters': 'Houseboat on the backwaters', 'desert': 'Desert fort and dunes',
          'lake': 'Shikara on a mountain lake', 'temple': 'Temple and river ghats', 'city': 'City skyline at dusk', 'island': 'Overwater villas', 'safari': 'Jungle safari',
          'monastery': 'Mountain monastery', 'road': 'Motorbike on a mountain road', 'waterfall': 'Waterfall in the forest', 'palace': 'Lake palace',
          'terraces': 'Rice terraces', 'camp': 'Camping under the stars', 'tea': 'Tea gardens', 'oldtown': 'Old town square', 'museum': 'Museum visit with a school bus'}
MOODS = ['day', 'dusk', 'dawn', 'day']


def scene(kind, w, h, pal, seed, mood=None):
    fn = SCENES[kind]
    return fn(w, h, pal, seed, mood) if mood else fn(w, h, pal, seed)


def about_scene(pal, seed):
    w, h = 1000, 850
    b = rect(0, 0, w, h, mix(pal.P, '#fff', .9)) + rect(0, h * .72, w, h * .28, '#C9A47E')
    b += rect(80, 80, 500, 340, '#F4EFE6', 10) + rect(80, 80, 500, 340, 'none', 10, extra=f' stroke="{pal.P1}" stroke-width="8"')
    b += ''.join(circle(80 + x, 80 + y, 10, c) for x, y, c in ((120, 120, pal.S), (300, 200, pal.P), (420, 140, pal.S), (220, 280, pal.P)))
    b += f'<path d="M200 200 Q300 120 380 220 T500 220" stroke="{pal.P1}" stroke-width="4" stroke-dasharray="10 8" fill="none"/>'
    b += figure(300, 790, .9, SKINS[2], HAIR[0], pal.P, 'short') + figure(720, 790, .9, SKINS[4], HAIR[1], pal.S, 'long')
    b += rect(120, 620, 760, 30, '#6B4E3A', 10) + rect(420, 570, 180, 50, '#fff', 6) + rect(436, 584, 120, 8, pal.P3, 4)
    b += rect(650, 110, 260, 340, '#fff', 12) + f'<svg x="665" y="125" width="230" height="230" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice">{s_beach(1200, 900, pal, seed)}</svg>'
    return b


def suitcase(pal):
    b = rect(0, 0, 800, 800, pal.S3) + circle(400, 400, 300, pal.P4)
    b += rect(250, 260, 300, 360, pal.P, 30) + rect(330, 200, 140, 70, 'none', 20, extra=f' stroke="{pal.P1}" stroke-width="18"') + rect(250, 380, 300, 20, pal.P1)
    b += circle(300, 640, 20, '#333') + circle(500, 640, 20, '#333') + rect(280, 300, 70, 50, pal.S, 8) + rect(440, 460, 80, 60, '#fff', 8)
    b += f'<g transform="translate(560 220) rotate(20)"><path d="M0 0 h140 l-20 40 h-100Z" fill="#fff"/><path d="M40 0 v-70 l60 70Z" fill="{pal.S}"/></g>'
    return b


def generate(out_dir, niche, primary, secondary, seed=1, logo_names=None):
    cv = Canvas(out_dir)
    pal = Palette(primary, secondary)
    c = CONTENT[niche]
    for i, it in enumerate(c['listings']):
        cv.save(f'trip-{it["slug"]}.svg', 1200, 900, scene(it['scene'], 1200, 900, pal, seed + i), LABELS[it['scene']])
    for k in dict.fromkeys(c['gallery'] + [it['scene'] for it in c['listings']]):
        cv.save(f'scene-{k}.svg', 1200, 900, scene(k, 1200, 900, pal, seed + 30), LABELS[k])
    for i, d in enumerate(c['categories']):
        cv.save(f'dest-{d["slug"]}.svg', 1000, 1000, scene(d['scene'], 1000, 1000, pal, seed + 50 + i), LABELS[d['scene']])
    cv.save('hero.svg', 1600, 1000, scene(c['hero_scene'], 1600, 1000, pal, seed), LABELS[c['hero_scene']])
    cv.save('about-1.svg', 1000, 850, about_scene(pal, seed), 'Trip planners with travellers')
    cv.save('about-2.svg', 800, 800, suitcase(pal), 'Packed suitcase')
    cv.save('cta.svg', 1600, 700, scene(c['cta_scene'], 1600, 700, pal, seed + 9), LABELS[c['cta_scene']])
    cv.save('banner.svg', 1600, 500, scene(c['hero_scene'], 1600, 500, pal, seed + 5), 'Decorative banner')
    for i in range(4):
        portrait(cv, f'guide-{i+1}.svg', pal, SKINS[(i * 2 + seed) % 6], HAIR[i % 5], [pal.P, pal.S, pal.INK, pal.P2][i], ['short', 'long', 'grey', 'bun'][i], i + seed)
    for i in range(3):
        portrait(cv, f'traveller-{i+1}.svg', pal, SKINS[(i * 3 + seed) % 6], HAIR[(i + 1) % 5], [pal.S, pal.P2, pal.P][i], ['long', 'short', 'bun'][i], i + 1, 400, 400)
    logos(cv, pal, logo_names or c['logos'])
