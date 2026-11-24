"""E-commerce scenes. generate(out_dir, niche, primary, secondary, seed, logo_names) writes:
product-<slug>.svg and product-<slug>-2.svg for every demo product, cat-<slug>.svg per category,
hero, about-1, about-2, promo (call-to-action background), customer-1..3, banner and logo-1..5.
Products are drawn as objects on soft studio backgrounds tinted by the template palette."""
import math
import random
from kit import *  # noqa: F401,F403
from business_images import logos, banner
from ecommerce_content import NICHES as CONTENT

PW, PH = 800, 1000


def shade(c, t=.25):
    return mix(c, '#000000', t)


def tint(c, t=.35):
    return mix(c, '#FFFFFF', t)


# --------------------------------------------------------------------------- product objects
# Each draws an object centred on (cx, base) at scale s with main colour a and accent b.

def o_kurta(cx, base, s, a, b):
    w, h = 300 * s, 520 * s
    top = base - h
    g = f'<path d="M{cx-w*0.32} {top} L{cx-w*0.95} {top+120*s} L{cx-w*0.8} {top+190*s} L{cx-w*0.5} {top+120*s} L{cx-w*0.55} {base} L{cx+w*0.55} {base} L{cx+w*0.5} {top+120*s} L{cx+w*0.8} {top+190*s} L{cx+w*0.95} {top+120*s} L{cx+w*0.32} {top}Z" fill="{a}"/>'
    g += f'<path d="M{cx-w*0.18} {top} Q{cx} {top+60*s} {cx+w*0.18} {top}" fill="{shade(a, .3)}"/>' + rect(cx - 4 * s, top + 30 * s, 8 * s, 140 * s, b)
    g += ''.join(circle(cx, top + (60 + i * 30) * s, 5 * s, b) for i in range(4)) + rect(cx - w * 0.55, base - 40 * s, w * 1.1, 14 * s, b, op=.8)
    return g + ''.join(circle(cx - w * 0.4 + i * w * 0.2, top + 300 * s, 9 * s, b, .5) for i in range(5))


def o_saree(cx, base, s, a, b):
    g = ''
    for i, col in enumerate((shade(a, .15), a, tint(a, .15))):
        y = base - (i + 1) * 70 * s
        g += rect(cx - 220 * s, y, 440 * s, 66 * s, col, 10 * s) + rect(cx - 220 * s, y + 46 * s, 440 * s, 14 * s, b) + ''.join(circle(cx - 200 * s + k * 40 * s, y + 53 * s, 4 * s, shade(b, .3)) for k in range(11))
    return g + f'<path d="M{cx+120*s} {base-210*s} q60 -120 140 -60 q-40 40 -40 120Z" fill="{a}"/>'


def o_tote(cx, base, s, a, b):
    return (f'<path d="M{cx-110*s} {base-330*s} Q{cx-110*s} {base-470*s} {cx} {base-470*s} Q{cx+110*s} {base-470*s} {cx+110*s} {base-330*s}" stroke="{shade(a, .3)}" stroke-width="{18*s}" fill="none"/>'
            + f'<path d="M{cx-190*s} {base-340*s} H{cx+190*s} L{cx+160*s} {base} H{cx-160*s}Z" fill="{a}"/>' + rect(cx - 190 * s, base - 340 * s, 380 * s, 30 * s, shade(a, .2)) + rect(cx - 60 * s, base - 220 * s, 120 * s, 80 * s, b, 8 * s))


def o_jutti(cx, base, s, a, b):
    def one(x, flip=1):
        return (f'<path d="M{x-160*s*flip} {base} Q{x-180*s*flip} {base-90*s} {x-60*s*flip} {base-110*s} Q{x+60*s*flip} {base-130*s} {x+170*s*flip} {base-40*s} L{x+170*s*flip} {base}Z" fill="{a}"/>'
                + f'<path d="M{x-40*s*flip} {base-100*s} Q{x+50*s*flip} {base-130*s} {x+130*s*flip} {base-60*s}" stroke="{b}" stroke-width="{12*s}" fill="none"/>' + rect(x - 165 * s if flip > 0 else x - 175 * s, base - 14 * s, 340 * s, 14 * s, shade(a, .4)))
    return one(cx - 20 * s) + f'<g transform="translate(0 {-120*s})">' + one(cx + 30 * s) + '</g>'


def o_headphones(cx, base, s, a, b):
    y = base - 260 * s
    return (f'<path d="M{cx-170*s} {y+40*s} Q{cx-170*s} {y-230*s} {cx} {y-230*s} Q{cx+170*s} {y-230*s} {cx+170*s} {y+40*s}" stroke="{a}" stroke-width="{34*s}" fill="none" stroke-linecap="round"/>'
            + rect(cx - 230 * s, y - 20 * s, 110 * s, 180 * s, a, 50 * s) + rect(cx + 120 * s, y - 20 * s, 110 * s, 180 * s, a, 50 * s)
            + rect(cx - 205 * s, y + 10 * s, 60 * s, 120 * s, b, 26 * s) + rect(cx + 145 * s, y + 10 * s, 60 * s, 120 * s, b, 26 * s))


def o_phone(cx, base, s, a, b):
    return rect(cx - 140 * s, base - 560 * s, 280 * s, 560 * s, a, 40 * s) + rect(cx - 122 * s, base - 540 * s, 244 * s, 520 * s, b, 28 * s) + rect(cx - 40 * s, base - 530 * s, 80 * s, 14 * s, a, 7 * s) + circle(cx, base - 300 * s, 70 * s, tint(b, .25)) + ''.join(rect(cx - 90 * s + (i % 3) * 64 * s, base - 160 * s + (i // 3) * 64 * s, 46 * s, 46 * s, tint(b, .4), 12 * s) for i in range(6))


def o_watch(cx, base, s, a, b):
    y = base - 300 * s
    return (rect(cx - 70 * s, y - 260 * s, 140 * s, 520 * s, shade(a, .2), 40 * s) + rect(cx - 130 * s, y - 130 * s, 260 * s, 260 * s, a, 70 * s) + rect(cx - 110 * s, y - 110 * s, 220 * s, 220 * s, '#111', 56 * s)
            + f'<path d="M{cx-60*s} {y+20*s} L{cx-20*s} {y-30*s} L{cx+20*s} {y+10*s} L{cx+60*s} {y-40*s}" stroke="{b}" stroke-width="{10*s}" fill="none" stroke-linecap="round"/>' + text(cx, y + 70 * s, '10:08', int(42 * s), '#fff', 700, 'middle'))


def o_speaker(cx, base, s, a, b):
    return rect(cx - 150 * s, base - 470 * s, 300 * s, 470 * s, a, 60 * s) + circle(cx, base - 160 * s, 100 * s, shade(a, .35)) + circle(cx, base - 160 * s, 46 * s, b) + circle(cx, base - 360 * s, 50 * s, shade(a, .35)) + circle(cx, base - 360 * s, 20 * s, b) + dots(cx - 110 * s, base - 450 * s, 220 * s, 20 * s, '#fff', 20 * s, 3 * s, .4)


def o_earbuds(cx, base, s, a, b):
    return (rect(cx - 170 * s, base - 220 * s, 340 * s, 220 * s, a, 90 * s) + rect(cx - 170 * s, base - 220 * s, 340 * s, 70 * s, shade(a, .1), 40 * s) + circle(cx, base - 110 * s, 8 * s, b)
            + f'<ellipse cx="{cx-70*s}" cy="{base-330*s}" rx="{46*s}" ry="{54*s}" fill="{a}"/>' + rect(cx - 82 * s, base - 300 * s, 24 * s, 90 * s, a, 12 * s) + f'<ellipse cx="{cx+90*s}" cy="{base-310*s}" rx="{46*s}" ry="{54*s}" fill="{a}"/>' + rect(cx + 78 * s, base - 280 * s, 24 * s, 90 * s, a, 12 * s) + circle(cx - 70 * s, base - 330 * s, 20 * s, b) + circle(cx + 90 * s, base - 310 * s, 20 * s, b))


def o_powerbank(cx, base, s, a, b):
    return rect(cx - 130 * s, base - 480 * s, 260 * s, 480 * s, a, 34 * s) + ''.join(circle(cx - 45 * s + i * 30 * s, base - 400 * s, 9 * s, b if i < 3 else tint(a, .4)) for i in range(4)) + rect(cx - 50 * s, base - 30 * s, 100 * s, 16 * s, shade(a, .4), 8 * s) + text(cx, base - 230 * s, '20K', int(64 * s), tint(a, .5), 800, 'middle')


def o_jar(cx, base, s, a, b, label=''):
    return (rect(cx - 150 * s, base - 380 * s, 300 * s, 380 * s, a, 50 * s) + rect(cx - 130 * s, base - 440 * s, 260 * s, 70 * s, shade(b, .1), 14 * s) + rect(cx - 150 * s, base - 270 * s, 300 * s, 150 * s, '#FFFDF6')
            + (text(cx, base - 180 * s, label, int(46 * s), shade(b, .2), 800, 'middle') if label else '') + rect(cx - 90 * s, base - 150 * s, 180 * s, 10 * s, b, 5 * s))


def o_bottle(cx, base, s, a, b, label=''):
    return (f'<path d="M{cx-40*s} {base-560*s} h{80*s} v{90*s} q{80*s} {40*s} {80*s} {130*s} v{340*s} q0 {30*s} -{30*s} {30*s} h-{180*s} q-{30*s} 0 -{30*s} -{30*s} v-{340*s} q0 -{90*s} {80*s} -{130*s}Z" fill="{a}"/>'
            + rect(cx - 46 * s, base - 600 * s, 92 * s, 60 * s, b, 10 * s) + rect(cx - 120 * s, base - 300 * s, 240 * s, 160 * s, '#FFFDF6', 6 * s) + (text(cx, base - 210 * s, label, int(40 * s), shade(b, .2), 800, 'middle') if label else '') + rect(cx - 70 * s, base - 180 * s, 140 * s, 10 * s, b, 5 * s))


def o_pouch(cx, base, s, a, b, label=''):
    return (f'<path d="M{cx-180*s} {base} L{cx-160*s} {base-520*s} H{cx+160*s} L{cx+180*s} {base}Z" fill="{a}"/>' + rect(cx - 160 * s, base - 520 * s, 320 * s, 40 * s, shade(a, .25)) + rect(cx - 120 * s, base - 380 * s, 240 * s, 200 * s, '#FFFDF6', 14 * s)
            + circle(cx, base - 320 * s, 30 * s, b) + (text(cx, base - 230 * s, label, int(38 * s), shade(b, .25), 800, 'middle') if label else '') + rect(cx - 70 * s, base - 210 * s, 140 * s, 8 * s, b, 4 * s))


def o_tin(cx, base, s, a, b, label=''):
    return f'<ellipse cx="{cx}" cy="{base}" rx="{160*s}" ry="{30*s}" fill="{shade(a, .2)}"/>' + rect(cx - 160 * s, base - 400 * s, 320 * s, 400 * s, a) + f'<ellipse cx="{cx}" cy="{base-400*s}" rx="{160*s}" ry="{30*s}" fill="{tint(a, .2)}"/>' + rect(cx - 160 * s, base - 300 * s, 320 * s, 170 * s, b) + (text(cx, base - 200 * s, label, int(44 * s), '#fff', 800, 'middle') if label else '')


def o_dropper(cx, base, s, a, b):
    return rect(cx - 110 * s, base - 360 * s, 220 * s, 360 * s, a, 30 * s) + rect(cx - 50 * s, base - 440 * s, 100 * s, 90 * s, b, 12 * s) + rect(cx - 30 * s, base - 560 * s, 60 * s, 130 * s, shade(b, .15), 30 * s) + rect(cx - 80 * s, base - 240 * s, 160 * s, 120 * s, '#FFFDF6', 8 * s) + rect(cx - 50 * s, base - 200 * s, 100 * s, 10 * s, b, 5 * s)


def o_cream(cx, base, s, a, b):
    return rect(cx - 190 * s, base - 200 * s, 380 * s, 200 * s, a, 40 * s) + rect(cx - 200 * s, base - 270 * s, 400 * s, 90 * s, b, 26 * s) + rect(cx - 110 * s, base - 140 * s, 220 * s, 60 * s, '#FFFDF6', 8 * s)


def o_pump(cx, base, s, a, b):
    return (rect(cx - 130 * s, base - 450 * s, 260 * s, 450 * s, a, 40 * s) + rect(cx - 40 * s, base - 520 * s, 80 * s, 80 * s, b, 10 * s) + rect(cx - 20 * s, base - 580 * s, 40 * s, 70 * s, b) + rect(cx - 20 * s, base - 590 * s, 120 * s, 30 * s, b, 10 * s)
            + rect(cx - 100 * s, base - 300 * s, 200 * s, 170 * s, '#FFFDF6', 8 * s) + rect(cx - 60 * s, base - 240 * s, 120 * s, 10 * s, b, 5 * s))


def o_lipstick(cx, base, s, a, b):
    g = ''
    for i, dx in enumerate((-110, 110)):
        x = cx + dx * s
        g += rect(x - 60 * s, base - 260 * s, 120 * s, 260 * s, b, 14 * s) + rect(x - 48 * s, base - 340 * s, 96 * s, 90 * s, shade(b, .3), 8 * s) + f'<path d="M{x-40*s} {base-340*s} V{base-450*s} L{x+40*s} {base-500*s} V{base-340*s}Z" fill="{a if i == 0 else tint(a, .25)}"/>'
    return g


def o_soap(cx, base, s, a, b):
    return ''.join(rect(cx - 170 * s, base - (i + 1) * 110 * s, 340 * s, 104 * s, [a, tint(a, .2), shade(a, .1)][i], 34 * s) for i in range(3)) + ''.join(leaf(cx - 60 * s, base - 380 * s, .8 * s, b, -30 + i * 50) for i in range(2))


def leaf(cx, cy, s, c, rot=0):
    return f'<path d="M{cx} {cy} C{cx+40*s} {cy-60*s} {cx+100*s} {cy-50*s} {cx+120*s} {cy-10*s} C{cx+80*s} {cy+30*s} {cx+30*s} {cy+30*s} {cx} {cy}Z" fill="{c}" transform="rotate({rot} {cx} {cy})"/>'


def o_ring(cx, base, s, a, b):
    y = base - 220 * s
    return f'<ellipse cx="{cx}" cy="{y}" rx="{150*s}" ry="{150*s}" fill="none" stroke="{a}" stroke-width="{34*s}"/>' + f'<path d="M{cx-70*s} {y-150*s} L{cx} {y-260*s} L{cx+70*s} {y-150*s} L{cx} {y-110*s}Z" fill="{b}" stroke="{a}" stroke-width="{8*s}"/>' + f'<path d="M{cx-35*s} {y-180*s} L{cx} {y-230*s}" stroke="#fff" stroke-width="{6*s}" opacity=".8"/>'


def o_necklace(cx, base, s, a, b):
    y = base - 520 * s
    return (f'<path d="M{cx-200*s} {y} Q{cx} {y+420*s} {cx+200*s} {y}" stroke="{a}" stroke-width="{12*s}" fill="none"/>' + ''.join(circle(cx - 180 * s + i * 40 * s, y + 160 * s - abs(i - 4.5) * 30 * s, 12 * s, a) for i in range(10))
            + f'<path d="M{cx-50*s} {y+250*s} L{cx} {y+340*s} L{cx+50*s} {y+250*s} Q{cx} {y+210*s} {cx-50*s} {y+250*s}Z" fill="{b}" stroke="{a}" stroke-width="{10*s}"/>')


def o_jhumka(cx, base, s, a, b):
    g = ''
    for dx in (-120, 120):
        x = cx + dx * s
        g += circle(x, base - 470 * s, 30 * s, b) + rect(x - 5 * s, base - 440 * s, 10 * s, 60 * s, a) + f'<path d="M{x-90*s} {base-200*s} Q{x-80*s} {base-390*s} {x} {base-390*s} Q{x+80*s} {base-390*s} {x+90*s} {base-200*s}Z" fill="{a}"/>' + ''.join(circle(x - 80 * s + k * 20 * s, base - 185 * s, 9 * s, b) for k in range(9))
    return g


def o_bangles(cx, base, s, a, b):
    return ''.join(f'<ellipse cx="{cx}" cy="{base-120*s-i*40*s}" rx="{190*s}" ry="{60*s}" fill="none" stroke="{[a, b, a, shade(a, .15), b][i]}" stroke-width="{22*s}"/>' for i in range(5))


def o_teddy(cx, base, s, a, b):
    return (circle(cx, base - 180 * s, 170 * s, a) + circle(cx, base - 420 * s, 130 * s, a) + circle(cx - 110 * s, base - 520 * s, 50 * s, a) + circle(cx + 110 * s, base - 520 * s, 50 * s, a) + circle(cx - 110 * s, base - 520 * s, 26 * s, tint(a, .35))
            + circle(cx + 110 * s, base - 520 * s, 26 * s, tint(a, .35)) + f'<ellipse cx="{cx}" cy="{base-380*s}" rx="{60*s}" ry="{44*s}" fill="{tint(a, .4)}"/>' + circle(cx, base - 395 * s, 14 * s, '#2A1B14') + circle(cx - 45 * s, base - 450 * s, 11 * s, '#2A1B14') + circle(cx + 45 * s, base - 450 * s, 11 * s, '#2A1B14')
            + circle(cx, base - 160 * s, 80 * s, tint(a, .35)) + f'<path d="M{cx-50*s} {base-300*s} L{cx} {base-270*s} L{cx+50*s} {base-300*s} L{cx+50*s} {base-250*s} L{cx} {base-280*s} L{cx-50*s} {base-250*s}Z" fill="{b}"/>')


def o_blocks(cx, base, s, a, b):
    cols = [a, b, tint(a, .2), shade(b, .1)]
    return ''.join(rect(cx - 200 * s + (i % 3) * 140 * s - (0 if i < 3 else -70 * s), base - (130 if i < 3 else 260 if i < 5 else 390) * s, 130 * s, 130 * s, cols[i % 4], 14 * s) + text(cx - 135 * s + (i % 3) * 140 * s + (0 if i < 3 else 70 * s), base - (45 if i < 3 else 175 if i < 5 else 305) * s, 'ABCDEF'[i], int(70 * s), '#fff', 800, 'middle') for i in range(6) if i != 5)


def o_car(cx, base, s, a, b):
    return (f'<path d="M{cx-260*s} {base-90*s} V{base-190*s} Q{cx-250*s} {base-210*s} {cx-200*s} {base-210*s} L{cx-120*s} {base-320*s} H{cx+110*s} L{cx+190*s} {base-210*s} Q{cx+260*s} {base-200*s} {cx+260*s} {base-150*s} V{base-90*s}Z" fill="{a}"/>'
            + f'<path d="M{cx-100*s} {base-300*s} L{cx-160*s} {base-215*s} H{cx-10*s} V{base-300*s}Z M{cx+10*s} {base-300*s} V{base-215*s} H{cx+160*s} L{cx+100*s} {base-300*s}Z" fill="#CFE3F0"/>' + circle(cx - 150 * s, base - 90 * s, 62 * s, '#2B2B2B') + circle(cx + 150 * s, base - 90 * s, 62 * s, '#2B2B2B') + circle(cx - 150 * s, base - 90 * s, 26 * s, b) + circle(cx + 150 * s, base - 90 * s, 26 * s, b))


def o_rocket(cx, base, s, a, b):
    return (f'<path d="M{cx} {base-620*s} Q{cx+120*s} {base-480*s} {cx+90*s} {base-180*s} H{cx-90*s} Q{cx-120*s} {base-480*s} {cx} {base-620*s}Z" fill="{a}"/>' + circle(cx, base - 400 * s, 50 * s, '#CFE3F0') + circle(cx, base - 400 * s, 50 * s, 'none')
            + f'<path d="M{cx-90*s} {base-260*s} L{cx-180*s} {base-120*s} L{cx-80*s} {base-170*s}Z M{cx+90*s} {base-260*s} L{cx+180*s} {base-120*s} L{cx+80*s} {base-170*s}Z" fill="{b}"/>' + f'<path d="M{cx-50*s} {base-180*s} Q{cx} {base-40*s} {cx+50*s} {base-180*s}Z" fill="#FFB703"/>')


def o_xylo(cx, base, s, a, b):
    cols = ['#E4572E', '#F2A541', '#FFC93C', '#6BCB77', '#4D96FF', '#9B5DE5']
    return rect(cx - 260 * s, base - 70 * s, 520 * s, 30 * s, shade(b, .3), 10 * s) + rect(cx - 260 * s, base - 300 * s, 520 * s, 30 * s, shade(b, .3), 10 * s) + ''.join(rect(cx - 240 * s + i * 82 * s, base - 330 * s + i * 14 * s, 64 * s, 300 * s - i * 28 * s, c, 10 * s) for i, c in enumerate(cols)) + f'<path d="M{cx+150*s} {base-420*s} L{cx+260*s} {base-320*s}" stroke="{a}" stroke-width="{12*s}" stroke-linecap="round"/>' + circle(cx + 140 * s, base - 430 * s, 26 * s, a)


def o_coffeebag(cx, base, s, a, b, label=''):
    return (f'<path d="M{cx-170*s} {base} L{cx-150*s} {base-520*s} L{cx-120*s} {base-560*s} H{cx+120*s} L{cx+150*s} {base-520*s} L{cx+170*s} {base}Z" fill="{a}"/>' + rect(cx - 150 * s, base - 560 * s, 300 * s, 30 * s, shade(a, .3))
            + rect(cx - 110 * s, base - 380 * s, 220 * s, 220 * s, '#FFFDF6', 110 * s) + f'<ellipse cx="{cx}" cy="{base-300*s}" rx="{34*s}" ry="{48*s}" fill="{b}"/><path d="M{cx} {base-345*s} q-14 45 0 90" stroke="#FFFDF6" stroke-width="{6*s}" fill="none"/>' + (text(cx, base - 205 * s, label, int(32 * s), shade(b, .2), 800, 'middle') if label else ''))


def o_mug(cx, base, s, a, b):
    return (f'<path d="M{cx+140*s} {base-300*s} q{110*s} 0 {110*s} {90*s} q0 {90*s} -{110*s} {90*s}" stroke="{a}" stroke-width="{34*s}" fill="none"/>' + rect(cx - 160 * s, base - 360 * s, 320 * s, 360 * s, a, 36 * s) + rect(cx - 160 * s, base - 250 * s, 320 * s, 60 * s, b)
            + ''.join(f'<path d="M{cx-60*s+i*60*s} {base-420*s} q-20 -40 0 -80 q20 -40 0 -80" stroke="{tint(b, .2)}" stroke-width="{8*s}" fill="none" opacity=".6"/>' for i in range(3)))


def o_press(cx, base, s, a, b):
    return (rect(cx - 130 * s, base - 470 * s, 260 * s, 470 * s, '#E8F1F4', 20 * s, extra=' opacity=".75"') + rect(cx - 130 * s, base - 280 * s, 260 * s, 280 * s, shade(a, .4), 18 * s) + rect(cx - 150 * s, base - 500 * s, 300 * s, 40 * s, b, 10 * s)
            + rect(cx - 10 * s, base - 600 * s, 20 * s, 110 * s, b) + circle(cx, base - 610 * s, 28 * s, b) + f'<path d="M{cx+130*s} {base-420*s} q{80*s} 0 {80*s} {120*s} q0 {120*s} -{80*s} {120*s}" stroke="{b}" stroke-width="{24*s}" fill="none"/>')


def o_cup(cx, base, s, a, b):
    return f'<ellipse cx="{cx}" cy="{base-40*s}" rx="{250*s}" ry="{50*s}" fill="{tint(a, .3)}"/>' + f'<path d="M{cx-170*s} {base-280*s} H{cx+170*s} Q{cx+160*s} {base-60*s} {cx} {base-60*s} Q{cx-160*s} {base-60*s} {cx-170*s} {base-280*s}Z" fill="{a}"/>' + f'<ellipse cx="{cx}" cy="{base-280*s}" rx="{170*s}" ry="{30*s}" fill="{shade(b, .4)}"/>' + f'<path d="M{cx+165*s} {base-240*s} q{70*s} 0 {60*s} {70*s} q-10 {50*s} -{80*s} {50*s}" stroke="{a}" stroke-width="{20*s}" fill="none"/>'


def o_sneaker(cx, base, s, a, b):
    return (f'<path d="M{cx-280*s} {base-40*s} V{base-200*s} Q{cx-270*s} {base-260*s} {cx-200*s} {base-250*s} Q{cx-120*s} {base-240*s} {cx-60*s} {base-320*s} L{cx+20*s} {base-300*s} Q{cx+90*s} {base-190*s} {cx+230*s} {base-170*s} Q{cx+290*s} {base-150*s} {cx+290*s} {base-80*s} V{base-40*s}Z" fill="{a}"/>'
            + rect(cx - 290 * s, base - 60 * s, 590 * s, 60 * s, '#F4F4F4', 24 * s) + rect(cx - 290 * s, base - 64 * s, 590 * s, 16 * s, b, 8 * s) + f'<path d="M{cx-160*s} {base-180*s} Q{cx} {base-260*s} {cx+140*s} {base-160*s}" stroke="{b}" stroke-width="{20*s}" fill="none" stroke-linecap="round"/>'
            + ''.join(f'<path d="M{cx-60*s+i*30*s} {base-280*s+i*16*s} l{50*s} {-10*s}" stroke="#fff" stroke-width="{7*s}" stroke-linecap="round"/>' for i in range(4)))


def o_dumbbell(cx, base, s, a, b):
    y = base - 130 * s
    return rect(cx - 180 * s, y - 20 * s, 360 * s, 40 * s, '#9AA3AD', 10 * s) + ''.join(rect(cx + dx * s, y - h * s / 2, 60 * s, h * s, a if k % 2 == 0 else shade(a, .2), 14 * s) for k, (dx, h) in enumerate(((-280, 240), (-220, 200), (160, 200), (220, 240)))) + rect(cx - 60 * s, y - 24 * s, 120 * s, 48 * s, b, 12 * s)


def o_mat(cx, base, s, a, b):
    return rect(cx - 260 * s, base - 160 * s, 400 * s, 160 * s, a, 10 * s) + circle(cx + 160 * s, base - 160 * s, 160 * s, a) + circle(cx + 160 * s, base - 160 * s, 110 * s, shade(a, .15)) + circle(cx + 160 * s, base - 160 * s, 60 * s, tint(a, .1)) + rect(cx - 120 * s, base - 170 * s, 30 * s, 180 * s, b, 8 * s) + rect(cx + 20 * s, base - 170 * s, 30 * s, 180 * s, b, 8 * s)


def o_bat(cx, base, s, a, b):
    return (f'<g transform="rotate(-18 {cx} {base-300*s})">' + rect(cx - 70 * s, base - 520 * s, 140 * s, 480 * s, '#E9CFA4', 30 * s) + rect(cx - 18 * s, base - 700 * s, 36 * s, 200 * s, a, 14 * s) + ''.join(rect(cx - 20 * s, base - 690 * s + i * 30 * s, 40 * s, 10 * s, b) for i in range(6)) + '</g>'
            + circle(cx + 200 * s, base - 70 * s, 70 * s, '#C8102E') + f'<path d="M{cx+140*s} {base-90*s} Q{cx+200*s} {base-40*s} {cx+260*s} {base-90*s}" stroke="#fff" stroke-width="{5*s}" fill="none" stroke-dasharray="{8*s} {8*s}"/>')


def o_book(cx, base, s, a, b, label=''):
    return (rect(cx - 170 * s, base - 520 * s, 340 * s, 520 * s, a, 10 * s) + rect(cx - 170 * s, base - 520 * s, 30 * s, 520 * s, shade(a, .25)) + rect(cx + 160 * s, base - 510 * s, 18 * s, 500 * s, '#FFFDF6')
            + rect(cx - 110 * s, base - 440 * s, 240 * s, 6 * s, b) + (text(cx + 15 * s, base - 360 * s, label, int(48 * s), '#fff', 800, 'middle') if label else '') + rect(cx - 110 * s, base - 120 * s, 240 * s, 6 * s, b) + circle(cx + 15 * s, base - 240 * s, 60 * s, b, .8))


def o_notebooks(cx, base, s, a, b):
    return ''.join(f'<g transform="rotate({(-6, 4, -2)[i]} {cx} {base})">' + rect(cx - 200 * s, base - (i + 1) * 70 * s, 400 * s, 64 * s, [a, b, tint(a, .2)][i], 10 * s) + rect(cx - 200 * s, base - (i + 1) * 70 * s, 400 * s, 64 * s, 'none') + rect(cx + 150 * s, base - (i + 1) * 70 * s, 14 * s, 64 * s, shade([a, b, tint(a, .2)][i], .3)) + '</g>' for i in range(3))


def o_pens(cx, base, s, a, b):
    g = rect(cx - 110 * s, base - 300 * s, 220 * s, 300 * s, a, 24 * s)
    for i, (dx, c, rot) in enumerate(((-60, b, -12), (0, '#1E1E1E', 0), (60, tint(b, .3), 10))):
        g += f'<g transform="rotate({rot} {cx+dx*s} {base-300*s})">' + rect(cx + dx * s - 14 * s, base - 560 * s, 28 * s, 280 * s, c, 10 * s) + f'<path d="M{cx+dx*s-14*s} {base-560*s} L{cx+dx*s} {base-610*s} L{cx+dx*s+14*s} {base-560*s}Z" fill="#E9CFA4"/>' + '</g>'
    return g + rect(cx - 110 * s, base - 200 * s, 220 * s, 50 * s, shade(a, .2))


def o_planner(cx, base, s, a, b):
    return rect(cx - 190 * s, base - 500 * s, 380 * s, 500 * s, a, 20 * s) + ''.join(circle(cx - 190 * s, base - 460 * s + i * 50 * s, 12 * s, '#9AA3AD') for i in range(9)) + rect(cx - 120 * s, base - 420 * s, 260 * s, 140 * s, '#FFFDF6', 10 * s) + text(cx + 10 * s, base - 335 * s, '2027', int(64 * s), shade(b, .1), 800, 'middle') + rect(cx + 140 * s, base - 500 * s, 30 * s, 160 * s, b)


OBJECTS = {'kurta': o_kurta, 'saree': o_saree, 'tote': o_tote, 'jutti': o_jutti, 'headphones': o_headphones, 'phone': o_phone, 'watch': o_watch, 'speaker': o_speaker,
           'earbuds': o_earbuds, 'powerbank': o_powerbank, 'jar': o_jar, 'bottle': o_bottle, 'pouch': o_pouch, 'tin': o_tin, 'dropper': o_dropper, 'cream': o_cream,
           'pump': o_pump, 'lipstick': o_lipstick, 'soap': o_soap, 'ring': o_ring, 'necklace': o_necklace, 'jhumka': o_jhumka, 'bangles': o_bangles, 'teddy': o_teddy,
           'blocks': o_blocks, 'car': o_car, 'rocket': o_rocket, 'xylophone': o_xylo, 'coffeebag': o_coffeebag, 'mug': o_mug, 'press': o_press, 'cup': o_cup,
           'sneaker': o_sneaker, 'dumbbell': o_dumbbell, 'mat': o_mat, 'bat': o_bat, 'book': o_book, 'notebooks': o_notebooks, 'pens': o_pens, 'planner': o_planner}
LABELLED = {'jar', 'bottle', 'pouch', 'tin', 'coffeebag', 'book'}
# How large each object is drawn in an 800×1000 product photo (wide objects a little smaller).
SCALE = {'bat': .95, 'sneaker': 1.15, 'car': 1.3, 'xylophone': 1.2, 'saree': 1.4, 'notebooks': 1.4, 'mat': 1.35, 'dumbbell': 1.0, 'jutti': 1.4, 'earbuds': 1.5,
         'cream': 1.45, 'soap': 1.45, 'cup': 1.3, 'ring': 1.6, 'bangles': 1.45, 'blocks': 1.35, 'headphones': 1.35, 'lipstick': 1.45, 'speaker': 1.4}


def draw(obj, cx, base, s, a, b, label=''):
    fn = OBJECTS[obj]
    return fn(cx, base, s, a, b, label) if obj in LABELLED else fn(cx, base, s, a, b)


def studio(pal, variant, w=PW, h=PH, floor=.72):
    bgs = [pal.BG, pal.WARM, pal.P4, pal.S3, mix(pal.P, '#FFFFFF', .86)]
    bg = bgs[variant % len(bgs)]
    fl = mix(bg, '#000000', .06)
    b = rect(0, 0, w, h, bg) + rect(0, h * floor, w, h * (1 - floor), fl)
    if variant % 3 == 1:
        b += circle(w * .78, h * .2, w * .14, pal.S2, .35)
    elif variant % 3 == 2:
        b += f'<path d="M0 {h*0.18} Q{w*0.5} {h*0.05} {w} {h*0.2} V0 H0Z" fill="{pal.P3}" opacity=".35"/>'
    return b


def product_image(pal, prod, idx, alt=False):
    obj, a, b = prod['draw'][:3]
    label = prod['draw'][3] if len(prod['draw']) > 3 else ''
    if not alt:
        k = SCALE.get(obj, 1.3)
        body = studio(pal, idx, floor=.78) + f'<ellipse cx="400" cy="{PH*0.78+8}" rx="{230*k:.0f}" ry="24" fill="#000" opacity=".1"/>' + draw(obj, 400, PH * .78, k, a, b, label)
    else:
        # Detail view: closer crop on a contrasting backdrop.
        k = SCALE.get(obj, 1.3) * 1.35
        body = studio(pal, idx + 2, floor=.86) + f'<g transform="translate(400 {PH*0.86}) scale({k:.2f}) translate(-400 {-PH*0.72})">' + draw(obj, 400, PH * .72, 1.0, a, b, label) + '</g>'
    return body


def group_image(pal, prods, w, h, variant=0, floor=.74):
    b = studio(pal, variant, w, h, floor)
    n = len(prods)
    for i, prod in enumerate(prods):
        obj, a, c = prod['draw'][:3]
        label = prod['draw'][3] if len(prod['draw']) > 3 else ''
        cx = w * (i + 1) / (n + 1)
        s = min(w / (n + .6) / 430, h / 1050) * (1.08 if i == n // 2 else .9)
        b += f'<ellipse cx="{cx}" cy="{h*floor+6}" rx="{200*s}" ry="{18*s}" fill="#000" opacity=".1"/>' + draw(obj, cx, h * floor, s, a, c, label)
    return b


def shelf_scene(pal, prods, w, h, seed):
    b = rect(0, 0, w, h, pal.WARM) + rect(0, h * .8, w, h * .2, mix(pal.WARM, '#000', .08))
    b += rect(w * .06, h * .1, w * .5, h * .5, pal.P4) + rect(w * .06, h * .1, w * .5, h * .5, 'none') + skyline(w * .5, h * .6, pal.P3, seed, 1, 40, int(h * .25)).replace('<rect x="', f'<rect transform="translate({w*0.06} 0)" x="')
    b += rect(w * .06 - 8, h * .1 - 8, w * .5 + 16, 10, pal.INK, op=.3) + rect(w * .62, h * .3, w * .32, 14, pal.INK) + rect(w * .62, h * .55, w * .32, 14, pal.INK)
    for i, prod in enumerate(prods[:4]):
        obj, a, c = prod['draw'][:3]
        label = prod['draw'][3] if len(prod['draw']) > 3 else ''
        x = w * (.7 + .16 * (i % 2)); y = h * (.3 if i < 2 else .55)
        b += draw(obj, x, y, h / 2600, a, c, label)
    b += rect(w * .1, h * .7, w * .5, h * .1, pal.P, 12) + plant(w * .9, h * .8, h / 700, pal.S, pal.P, pal.P2)
    return b


def customer(cv, name, pal, i):
    portrait(cv, name, pal, SKINS[(i * 2 + 1) % 6], HAIR[i % 4], [pal.P, pal.S, pal.P2][i % 3], ['long', 'short', 'bun'][i % 3], i, 400, 400)


def generate(out_dir, niche, primary, secondary, seed=1, logo_names=None):
    cv = Canvas(out_dir)
    pal = Palette(primary, secondary)
    c = CONTENT[niche]
    prods = c['products']
    for i, p in enumerate(prods):
        cv.save(f'product-{p["slug"]}.svg', PW, PH, product_image(pal, p, i), p['name'])
        cv.save(f'product-{p["slug"]}-2.svg', PW, PH, product_image(pal, p, i, alt=True), p['name'] + ' detail')
    for j, cat in enumerate(c['categories']):
        members = [p for p in prods if p['cat'] == cat['slug']][:3]
        cv.save(f'cat-{cat["slug"]}.svg', 800, 1000, group_image(pal, members, 800, 1000, j + 1, floor=.6), cat['name'])
    featured = [p for p in prods if p.get('featured')][:3] or prods[:3]
    cv.save('hero.svg', 1600, 1000, group_image(pal, featured, 1600, 1000, 1, .78) + circle(1380, 160, 90, pal.S, .35), c['name'] + ' collection')
    cv.save('about-1.svg', 1000, 850, shelf_scene(pal, prods, 1000, 850, seed), 'Our studio and store')
    cv.save('about-2.svg', 800, 800, group_image(pal, prods[3:5], 800, 800, 3), 'Packed with care')
    cv.save('promo.svg', 1600, 700, rect(0, 0, 1600, 700, pal.INK) + group_image(pal, prods[5:8], 1600, 700, 4).replace(f'<rect x="0.0" y="0.0" width="1600.0" height="700.0" fill=', '<rect x="0.0" y="0.0" width="1600.0" height="700.0" opacity=".9" fill=', 1), 'Promotion')
    for i in range(3):
        customer(cv, f'customer-{i+1}.svg', pal, i + seed)
    banner(cv, pal, ['grid', 'waves', 'city', 'field'][seed % 4])
    logos(cv, pal, logo_names or ['Vogue Lane', 'Shopwise', 'TrendTrail', 'Cartly', 'Daily Edit'])
