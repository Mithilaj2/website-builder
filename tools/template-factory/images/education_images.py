"""Education-category scenes. generate(out_dir, niche, primary, secondary, seed, logo_names) writes the image set:
hero, about-1, about-2, svc-1..6 (course / event tiles), banner, team-1..4 and logo-1..5."""
import math
import random
import re
from kit import *  # noqa: F401,F403
from business_images import portraits, logos, banner, office_window

W, H = 800, 500
HW, HH = 1600, 1000


def tile(bgc, body, blob_c='#fff'):
    return rect(0, 0, W, H, bgc) + blob(400, 250, 210, blob_c, .85) + body


def kid(x, base, s, pal, i, top=None):
    styles = ['short', 'bun', 'long', 'short', 'bun']
    return figure(x, base, s, SKINS[i % 6], HAIR[i % 4], top or [pal.P, pal.S, pal.P2][i % 3], styles[i % 5], '#fff')


def board(x, y, w, h, frame, surface, body):
    return rect(x - 10, y - 10, w + 20, h + 20, frame, 6) + rect(x, y, w, h, surface, 3) + body + rect(x + w * .1, y + h + 10, w * .8, 10, frame, 4)


def chalk(x, y, items, c='#fff'):
    return ''.join(text(x, y + i * 46, t, 30, c, 600, op=.9) for i, t in enumerate(items))


def flask(x, base, s, glass, liquid):
    return (f'<path d="M{x-12*s} {base-110*s} h{24*s} v{40*s} l{38*s} {62*s} q{6*s} {12*s} -{8*s} {12*s} h-{84*s} q-{14*s} 0 -{8*s} -{12*s} l{38*s} -{62*s}Z" fill="{glass}" opacity=".9"/>'
            + f'<path d="M{x-30*s} {base-30*s} h{60*s} l{14*s} {22*s} q{4*s} {8*s} -{6*s} {8*s} h-{76*s} q-{10*s} 0 -{6*s} -{8*s}Z" fill="{liquid}"/>' + circle(x + 6 * s, base - 46 * s, 5 * s, liquid) + circle(x - 4 * s, base - 64 * s, 4 * s, liquid, .7))


def microscope(x, base, s, c, c2):
    return (rect(x - 50 * s, base - 16 * s, 100 * s, 16 * s, c, 4) + f'<path d="M{x+20*s} {base-16*s} q{30*s} -{60*s} -{10*s} -{120*s}" stroke="{c}" stroke-width="{16*s}" fill="none"/>'
            + rect(x - 30 * s, base - 170 * s, 28 * s, 80 * s, c2, 6, extra=f' transform="rotate(-25 {x-16*s} {base-130*s})"') + rect(x - 40 * s, base - 70 * s, 60 * s, 10 * s, c, 3))


def easel(x, base, s, wood, canvas_body):
    return (f'<path d="M{x-70*s} {base} L{x} {base-330*s} L{x+70*s} {base} M{x} {base-330*s} V{base}" stroke="{wood}" stroke-width="{10*s}" fill="none"/>'
            + rect(x - 110 * s, base - 300 * s, 220 * s, 170 * s, '#fff', 4) + canvas_body + rect(x - 120 * s, base - 132 * s, 240 * s, 12 * s, wood, 3))


def piano(x, y, w, h, n=14, c='#1E1E1E'):
    kw = w / n
    out = rect(x - 10, y - 30, w + 20, h + 40, c, 8) + ''.join(rect(x + i * kw + 1, y, kw - 2, h, '#fff', 2) for i in range(n))
    return out + ''.join(rect(x + i * kw + kw * .65, y, kw * .7, h * .6, c, 2) for i in range(n - 1) if i % 7 not in (2, 6))


def guitar(x, y, s, body, neck):
    return (f'<g transform="rotate(-30 {x} {y})">' + rect(x - 8 * s, y - 230 * s, 16 * s, 190 * s, neck, 3) + rect(x - 14 * s, y - 260 * s, 28 * s, 40 * s, neck, 6)
            + f'<ellipse cx="{x}" cy="{y}" rx="{70*s}" ry="{60*s}" fill="{body}"/><ellipse cx="{x}" cy="{y-70*s}" rx="{52*s}" ry="{44*s}" fill="{body}"/>' + circle(x, y - 40 * s, 18 * s, '#2A1B14') + '</g>')


def notes(x, y, c, n=3):
    return ''.join(f'<g fill="{c}">' + f'<ellipse cx="{x+i*60}" cy="{y+(i%2)*30}" rx="14" ry="10" transform="rotate(-20 {x+i*60} {y+(i%2)*30})"/>' + rect(x + i * 60 + 10, y + (i % 2) * 30 - 60, 4, 60, c) + '</g>' for i in range(n))


def cap(x, y, s, c, tassel):
    return (f'<path d="M{x-80*s} {y} L{x} {y-34*s} L{x+80*s} {y} L{x} {y+34*s}Z" fill="{c}"/>' + rect(x - 40 * s, y + 10 * s, 80 * s, 36 * s, c, 6)
            + f'<path d="M{x} {y} L{x+56*s} {y+18*s} V{y+60*s}" stroke="{tassel}" stroke-width="{4*s}" fill="none"/>' + circle(x + 56 * s, y + 64 * s, 7 * s, tassel))


def campus(x, base, w, h, wall, roof, glass, clock=True):
    out = rect(x, base - h, w, h, wall) + f'<path d="M{x-20} {base-h} L{x+w/2} {base-h-90} L{x+w+20} {base-h}Z" fill="{roof}"/>'
    cols = int((w - 40) // 70)
    out += ''.join(rect(x + 30 + c * 70, base - h + 40 + r * 90, 40, 60, glass, 20 if r == 0 else 2) for r in range(int((h - 60) // 90)) for c in range(cols))
    out += rect(x + w / 2 - 40, base - 110, 80, 110, roof, 40)
    if clock:
        out += rect(x + w / 2 - 50, base - h - 220, 100, 140, wall) + f'<path d="M{x+w/2-60} {base-h-220} L{x+w/2} {base-h-290} L{x+w/2+60} {base-h-220}Z" fill="{roof}"/>' + circle(x + w / 2, base - h - 160, 32, '#fff') + f'<path d="M{x+w/2} {base-h-160} V{base-h-182} M{x+w/2} {base-h-160} H{x+w/2+16}" stroke="{roof}" stroke-width="5" stroke-linecap="round"/>'
    return out


def blocks(x, base, s, colors, letters='ABC'):
    return ''.join(rect(x + i * 74 * s, base - 70 * s - (60 * s if i == 1 else 0), 66 * s, 66 * s, colors[i % len(colors)], 8 * s) + text(x + i * 74 * s + 33 * s, base - 22 * s - (60 * s if i == 1 else 0), ch, 44 * s, '#fff', 800, 'middle') for i, ch in enumerate(letters))


def ball(x, y, r, c):
    return circle(x, y, r, '#fff') + f'<path d="M{x-r} {y} Q{x} {y-r*0.5} {x+r} {y} M{x} {y-r} Q{x+r*0.5} {y} {x} {y+r}" stroke="{c}" stroke-width="{r*0.12:.0f}" fill="none"/>' + f'<circle cx="{x}" cy="{y}" r="{r}" fill="none" stroke="{c}" stroke-width="3"/>'


def bubble(x, y, w, h, c, txt='', tc='#fff', tail='left'):
    t = f'<path d="M{x+30} {y+h} l-10 30 l40 -30Z" fill="{c}"/>' if tail == 'left' else f'<path d="M{x+w-30} {y+h} l10 30 l-40 -30Z" fill="{c}"/>'
    return rect(x, y, w, h, c, h / 2.5) + t + (text(x + w / 2, y + h / 2 + 12, txt, 34, tc, 800, 'middle') if txt else '')


def laptop(x, y, w, h, frame, screen, body=''):
    return rect(x, y, w, h, frame, 12) + rect(x + 14, y + 14, w - 28, h - 28, screen, 6) + body + f'<path d="M{x-40} {y+h} h{w+80} l-30 24 h{-w-20}Z" fill="{frame}"/>'


def robot(x, base, s, c, c2):
    return (rect(x - 50 * s, base - 160 * s, 100 * s, 110 * s, c, 18 * s) + rect(x - 40 * s, base - 250 * s, 80 * s, 70 * s, c2, 16 * s) + circle(x - 16 * s, base - 215 * s, 9 * s, '#fff') + circle(x + 16 * s, base - 215 * s, 9 * s, '#fff')
            + f'<path d="M{x} {base-250*s} v-24" stroke="{c}" stroke-width="{5*s}"/>' + circle(x, base - 280 * s, 8 * s, c2) + rect(x - 40 * s, base - 50 * s, 26 * s, 50 * s, c2, 6) + rect(x + 14 * s, base - 50 * s, 26 * s, 50 * s, c2, 6)
            + f'<path d="M{x-50*s} {base-130*s} l-50 40 M{x+50*s} {base-130*s} l50 -40" stroke="{c2}" stroke-width="{14*s}" stroke-linecap="round"/>')


def mountains(w, base, c1, c2, seed):
    random.seed(seed)
    out, x = '', -100
    while x < w:
        mw = random.randint(260, 420)
        out += f'<path d="M{x} {base} L{x+mw/2} {base-random.randint(180,320)} L{x+mw} {base}Z" fill="{c1}"/>'
        x += mw * .6
    return out + f'<path d="M0 {base} Q{w/3} {base-80} {w*0.6} {base-20} T{w} {base-40} V{base+400} H0Z" fill="{c2}"/>'


def pines(x0, base, n, s, c, seed):
    random.seed(seed)
    return ''.join(f'<path d="M{x0+i*55*s} {base} l{22*s} -{random.randint(70,110)*s} l{22*s} {random.randint(70,110)*s}Z" fill="{c}"/>' for i in range(n))


def code_lines(x, y, n, pal, w=300):
    random.seed(n * 7)
    return ''.join(rect(x + (i % 3) * 24, y + i * 24, random.randint(80, w), 10, [pal.S, pal.P2, '#fff', pal.A][i % 4], 5, .75) for i in range(n))


# --------------------------------------------------------------------------- tiles shared by niches

def t_classroom(pal, grade='x² + 3x = 10'):
    b = board(170, 70, 460, 220, pal.INK, mix(pal.P, '#000', .45), chalk(200, 130, [grade, 'Today: revision'], '#fff'))
    b += ''.join(rect(120 + i * 200, 390, 160, 18, pal.INK, 4) + kid(200 + i * 200, 520, .42, pal, i) for i in range(3))
    return tile(pal.P4, b)


def t_lab(pal):
    return tile(pal.S3, microscope(260, 410, 1.4, pal.INK, pal.P) + flask(450, 410, 1.5, pal.P4, pal.S) + flask(580, 410, 1.1, pal.P4, pal.P2) + rect(140, 410, 520, 16, pal.INK, 4))


def t_library(pal, seed=3):
    b = ''.join(rect(130, 140 + r * 120, 540, 12, mix(pal.S, '#000', .35)) + book_row(140, 140 + r * 120, 18, seed + r, [pal.P, pal.S, pal.P2, mix(pal.S, '#000', .3), pal.P1]) for r in range(3))
    return tile(pal.BG, b)


def t_computer(pal):
    b = ''.join(monitor(150 + i * 190, 140, 160, 120, pal.INK, '#fff', code_lines(170 + i * 190, 160, 3, pal, 100)) for i in range(3))
    return tile(pal.P4, b + rect(120, 330, 560, 16, pal.INK, 4))


def t_sports(pal):
    b = rect(0, 300, W, 200, mix(pal.P, '#5E9E4A', .55)) + ''.join(f'<path d="M{-100+i*160} 500 L{200+i*90} 300" stroke="#fff" stroke-opacity=".35" stroke-width="5"/>' for i in range(8))
    b += f'<path d="M520 300 V180 H700 V300" stroke="#fff" stroke-width="8" fill="none"/>' + ''.join(f'<path d="M{530+i*20} 190 V300" stroke="#fff" stroke-opacity=".4" stroke-width="2"/>' for i in range(9)) + ball(330, 380, 46, pal.INK) + kid(200, 540, .38, pal, 1, pal.S)
    return rect(0, 0, W, H, pal.P4) + clouds(4, W, 60, 2) + b


def t_art(pal):
    canvas = circle(370, 230, 40, pal.S) + f'<path d="M300 320 L360 250 L420 300 L470 240 L510 320Z" fill="{pal.P}"/>'
    return tile(pal.WARM, easel(400, 460, 1.2, mix(pal.S, '#000', .4), canvas) + f'<ellipse cx="620" cy="400" rx="80" ry="54" fill="#fff"/>' + ''.join(circle(590 + i * 26, 390 + (i % 2) * 18, 12, c) for i, c in enumerate((pal.P, pal.S, '#E4572E', pal.P2))))


def t_music(pal):
    return tile(pal.P4, piano(150, 300, 360, 120, 14, pal.INK) + guitar(620, 380, 1.2, pal.S, mix(pal.S, '#000', .5)) + notes(220, 160, pal.P))


def t_dance(pal):
    b = rect(0, 0, W, H, pal.INK) + f'<path d="M0 0 H{W} V60 Q{W*0.75} 120 {W/2} 60 Q{W*0.25} 120 0 60Z" fill="{pal.S}" opacity=".85"/>' + rect(0, 0, 90, H, pal.P1) + rect(W - 90, 0, 90, H, pal.P1)
    b += f'<path d="M400 0 L250 420 H550Z" fill="#fff" opacity=".12"/>' + rect(0, 420, W, 80, mix(pal.S, '#000', .5)) + kid(330, 520, .42, pal, 2, pal.S) + kid(470, 520, .42, pal, 4, pal.P2)
    return b


def t_graduation(pal):
    b = sky(W, H, pal.P4, pal.BG, 'g') + ''.join(cap(150 + i * 130, 100 + (i % 2) * 50, .6, pal.INK, pal.S) for i in range(5))
    b += ''.join(figure(220 + i * 180, 540, .5, SKINS[i * 2], HAIR[i], pal.INK, ['short', 'long', 'bun'][i], pal.S) for i in range(3))
    return b


def t_lecture(pal, screen_text='Lecture 12'):
    b = rect(0, 0, W, H, pal.BG) + rect(200, 50, 400, 210, pal.INK, 10) + rect(215, 65, 370, 180, '#fff', 4) + text(400, 140, screen_text, 30, pal.P, 800, 'middle') + bars(300, 230, 5, 30, 16, [30, 50, 70, 60, 90], pal.P2, pal.S)
    b += ''.join(rect(40 + r * 30, 330 + r * 56, 720 - r * 60, 40, mix(pal.P, '#FFFFFF', .55 - r * .1), 8) for r in range(3))
    b += ''.join(circle(110 + c * 90 + r * 30, 318 + r * 56, 20, SKINS[(c + r) % 6]) for r in range(3) for c in range(7 - r))
    return b


def t_online(pal):
    tiles = ''.join(rect(240 + (i % 3) * 110, 130 + (i // 3) * 90, 100, 80, [pal.P4, pal.S3, pal.BG][i % 3], 6) + circle(290 + (i % 3) * 110, 165 + (i // 3) * 90, 18, SKINS[i]) for i in range(6))
    return tile(pal.S3, laptop(220, 110, 360, 230, pal.INK, '#fff', tiles))


def t_robotics(pal):
    return tile(pal.P4, robot(400, 450, 1.25, pal.P, pal.S) + gear(170, 160, 60, pal.P2) + gear(640, 380, 50, pal.S2, 8))


def t_language(pal):
    return tile(pal.WARM, bubble(130, 80, 220, 100, pal.P, 'Hello!') + bubble(430, 150, 240, 100, pal.S, 'Bonjour', pal.INK, 'right') + bubble(190, 290, 260, 100, pal.P2, 'नमस्ते') + circle(600, 380, 70, pal.P3) + f'<path d="M530 380 H670 M600 310 V450 M545 340 Q600 380 655 340 M545 420 Q600 380 655 420" stroke="#fff" stroke-width="5" fill="none"/>')


def t_maths(pal):
    b = board(140, 70, 520, 280, pal.INK, '#fff', text(180, 150, 'f(x) = ax² + bx + c', 34, pal.P, 700) + text(180, 210, 'E = mc²', 34, pal.S, 700) + f'<path d="M180 320 Q300 200 420 290 T640 230" stroke="{pal.P2}" stroke-width="5" fill="none"/>')
    return tile(pal.P4, b)


def t_campus(pal, seed=2):
    return sky(W, H, pal.P4, pal.BG, 'c') + clouds(seed, W, 40, 2) + campus(190, 470, 420, 150, '#fff', pal.P, pal.P3) + rect(0, 470, W, 30, mix(pal.P, '#5E9E4A', .55)) + tree(110, 450, .9, pal.P2, pal.P) + tree(700, 450, .9, pal.P2, pal.P)


def t_play(pal):
    b = sky(W, H, pal.WARM, pal.S3, 'p') + circle(650, 100, 50, pal.S) + rect(0, 400, W, 100, mix(pal.P, '#7CC46A', .5))
    b += f'<path d="M140 400 L220 170 L300 400 M190 260 h60" stroke="{pal.P}" stroke-width="12" fill="none" stroke-linecap="round"/>' + f'<path d="M220 180 V300 M220 300 h-36 v8 h72 v-8 Z" stroke="{pal.INK}" stroke-width="4" fill="{pal.S}"/>'
    return b + blocks(400, 400, 1, [pal.P, pal.S, '#E4572E']) + kid(650, 540, .38, pal, 3)


def t_hostel(pal, seed=6):
    return sky(W, H, pal.P4, pal.WARM, 'h') + mountains(W, 380, pal.P3, pal.P2, seed) + pines(40, 470, 13, 1, pal.P1, seed) + campus(280, 470, 260, 140, '#fff', pal.S, pal.P3, False)


def t_business(pal):
    return tile(pal.BG, rect(170, 80, 460, 280, '#fff', 12) + bars(220, 320, 6, 40, 24, [60, 90, 80, 130, 170, 200], pal.P2, pal.S) + f'<path d="M220 200 L300 170 L380 180 L460 120 L560 100" stroke="{pal.P}" stroke-width="6" fill="none"/>' + figure(640, 520, .4, SKINS[1], HAIR[0], pal.P, 'short', '#fff'))


def t_exam(pal):
    b = ''.join(rect(160 + c * 170, 120 + r * 150, 140, 18, pal.INK, 4) + card(170 + c * 170, 60 + r * 150, 120, 56, '#fff', 6, sh=False) + lines(180 + c * 170, 72 + r * 150, 2, 90, 18, pal.P3, 8) for r in range(2) for c in range(3))
    return tile(pal.P4, b + badge(560, 410, 50, pal.S, 'A+', pal.INK, 40))


TILES = {'classroom': t_classroom, 'lab': t_lab, 'library': t_library, 'computer': t_computer, 'sports': t_sports, 'art': t_art,
         'music': t_music, 'dance': t_dance, 'graduation': t_graduation, 'lecture': t_lecture, 'online': t_online, 'robotics': t_robotics,
         'language': t_language, 'maths': t_maths, 'campus': t_campus, 'play': t_play, 'hostel': t_hostel, 'business': t_business, 'exam': t_exam}
LABELS = {'classroom': 'Classroom', 'lab': 'Science laboratory', 'library': 'Library', 'computer': 'Computer lab', 'sports': 'Sports field', 'art': 'Art studio',
          'music': 'Music room', 'dance': 'Stage performance', 'graduation': 'Graduation day', 'lecture': 'Lecture hall', 'online': 'Live online class', 'robotics': 'Robotics lab',
          'language': 'Language class', 'maths': 'Mathematics', 'campus': 'Campus', 'play': 'Play area', 'hostel': 'Residential campus', 'business': 'Business analytics', 'exam': 'Examination hall'}


# --------------------------------------------------------------------------- heroes

def h_preschool(pal, seed):
    b = sky(HW, HH, pal.WARM, pal.S3, 'hs') + circle(1350, 200, 110, pal.S) + clouds(seed, HW, 120, 4) + f'<path d="M0 720 Q400 640 800 700 T1600 680 V1000 H0Z" fill="{mix(pal.P, "#7CC46A", .5)}"/>'
    b += f'<path d="M200 720 L320 360 L440 720 M270 520 h100" stroke="{pal.P}" stroke-width="18" fill="none" stroke-linecap="round"/>' + f'<path d="M1100 720 V420 H1400 V720" stroke="{pal.S}" stroke-width="16" fill="none"/>' + f'<path d="M1180 420 V600 M1320 420 V600" stroke="{pal.INK}" stroke-width="4"/>' + rect(1150, 600, 220, 20, pal.P2, 8)
    b += blocks(560, 760, 1.6, [pal.P, pal.S, '#E4572E', pal.P2], 'ABCD') + ''.join(kid(500 + i * 230, 1020, .62, pal, i) for i in range(4))
    return b


def h_coaching(pal, seed):
    b = rect(0, 0, HW, HH, pal.BG) + board(260, 90, 1080, 420, pal.INK, mix(pal.P, '#000', .5), ''.join(text(800, 190 + i * 80, t, 44, '#fff', 600, 'middle', .92) for i, t in enumerate(['∫ sin x dx = −cos x + C', 'v = u + at', 'pH = −log[H⁺]', 'JEE · NEET 2027'])))
    b += ''.join(rect(60 + r * 40, 600 + r * 120, 1480 - r * 80, 60, mix(pal.P, '#FFFFFF', .6 - r * .12), 10) for r in range(3))
    b += ''.join(kid(160 + c * 200 + r * 60, 690 + r * 120 + 60, .32, pal, c + r) for r in range(3) for c in range(7))
    return b


def h_engineering(pal, seed):
    b = sky(HW, HH, pal.P4, pal.BG, 'hs') + clouds(seed, HW, 100, 3)
    b += rect(300, 260, 1000, 480, pal.P2) + ''.join(rect(330 + c * 120, 290 + r * 110, 100, 80, pal.P4 if (r + c) % 3 else '#fff') for r in range(4) for c in range(8)) + rect(700, 560, 200, 180, pal.INK)
    b += rect(0, 740, HW, 260, mix(pal.P, '#5E9E4A', .5)) + robot(180, 960, 1.5, pal.S, pal.P) + gear(1420, 820, 90, pal.S, 10) + tree(1250, 760, 1.2, pal.P2, pal.P1)
    b += ''.join(figure(600 + i * 200, 1000, .5, SKINS[i], HAIR[i], [pal.P, pal.S, pal.INK][i % 3], ['short', 'long', 'bun', 'short'][i], '#fff') for i in range(4))
    return b


def h_international(pal, seed):
    b = sky(HW, HH, pal.P4, pal.WARM, 'hs') + clouds(seed, HW, 80, 3) + campus(380, 760, 840, 300, '#fff', pal.P, pal.P3)
    b += rect(0, 760, HW, 240, mix(pal.P, '#5E9E4A', .5)) + tree(200, 780, 1.6, pal.P2, pal.P1) + tree(1420, 780, 1.6, pal.P2, pal.P1, kind='tall')
    b += ''.join(kid(560 + i * 170, 1020, .5, pal, i, [pal.P, pal.S][i % 2]) for i in range(4)) + f'<path d="M1320 160 v80 M1320 160 h80 l-20 20 l20 20 h-80" fill="{pal.S}" stroke="{pal.INK}" stroke-width="3"/>'
    return b


def h_coding(pal, seed):
    panel = mix(pal.INK, '#FFFFFF', .08)
    b = rect(0, 0, HW, HH, pal.INK) + dots(40, 40, 1520, 920, pal.P2, 40, 2, .2) + circle(1250, 250, 300, pal.P, .35) + circle(300, 850, 240, pal.S, .2)
    b += card(220, 160, 760, 560, panel, 20, '#000') + ''.join(circle(260 + i * 26, 190, 8, c) for i, c in enumerate(('#FF6B6B', '#FFD93D', '#6BCB77'))) + code_lines(260, 240, 16, pal, 560)
    b += card(1040, 300, 380, 300, '#fff', 20, '#000') + text(1080, 370, 'Lesson 7 of 24', 26, pal.INK, 700) + rect(1080, 400, 300, 14, pal.P4, 7) + rect(1080, 400, 210, 14, pal.S, 7) + ''.join(circle(1100 + i * 70, 500, 22, [pal.P, pal.S, pal.P2, pal.S2][i]) for i in range(4))
    b += figure(1260, 1000, .7, SKINS[2], HAIR[0], pal.P, 'short', pal.S)
    return b


def h_arts(pal, seed):
    b = rect(0, 0, HW, HH, pal.INK) + f'<path d="M0 0 H{HW} V120 Q{HW*0.75} 240 {HW/2} 120 Q{HW*0.25} 240 0 120Z" fill="{pal.S}" opacity=".9"/>' + rect(0, 0, 170, HH, pal.P1) + rect(HW - 170, 0, 170, HH, pal.P1)
    b += f'<path d="M500 0 L320 860 H900Z" fill="#fff" opacity=".1"/><path d="M1100 0 L860 860 H1380Z" fill="#fff" opacity=".08"/>' + rect(0, 840, HW, 160, mix(pal.S, '#000', .55))
    b += piano(300, 700, 360, 110, 12, '#111') + guitar(1180, 760, 1.6, pal.S, mix(pal.S, '#000', .5)) + kid(760, 1000, .7, pal, 2, pal.P2) + notes(420, 360, pal.S, 4) + notes(980, 300, '#fff', 3)
    return b


def h_mba(pal, seed):
    b = sky(HW, HH, pal.P4, pal.BG, 'hs') + skyline(HW, 760, pal.P3, seed, 1, 140, 420)
    b += rect(520, 200, 560, 560, pal.P) + ''.join(rect(540 + c * 66, 220 + r * 60, 56, 48, mix(pal.P, '#FFFFFF', .35 if (r * c) % 4 else .6)) for r in range(9) for c in range(8))
    b += rect(0, 760, HW, 240, mix(pal.INK, '#FFFFFF', .7)) + ''.join(figure(330 + i * 240, 1000, .55, SKINS[i], HAIR[i], [pal.INK, pal.S, pal.P, pal.P2, pal.INK][i], ['short', 'long', 'grey', 'bun', 'short'][i], '#fff') for i in range(5))
    return b


def h_language(pal, seed):
    b = rect(0, 0, HW, HH, pal.WARM) + circle(800, 470, 330, pal.P3, .6) + circle(800, 470, 250, pal.P) + f'<path d="M550 470 H1050 M800 220 V720 M590 340 Q800 450 1010 340 M590 600 Q800 490 1010 600" stroke="#fff" stroke-width="7" fill="none" opacity=".8"/><ellipse cx="800" cy="470" rx="120" ry="250" fill="none" stroke="#fff" stroke-width="7" opacity=".8"/>'
    b += bubble(180, 140, 300, 120, pal.S, 'Hello', pal.INK) + bubble(1120, 160, 320, 120, pal.P1, 'Hallo', '#fff', 'right') + bubble(120, 620, 320, 120, pal.P2, 'Bonjour', '#fff') + bubble(1150, 640, 300, 120, pal.S2, 'Hola', pal.INK, 'right')
    b += figure(800, 1010, .75, SKINS[4], HAIR[1], pal.S, 'long', '#fff')
    return b


def h_boarding(pal, seed):
    b = sky(HW, HH, pal.P4, pal.WARM, 'hs') + clouds(seed, HW, 80, 3) + mountains(HW, 620, pal.P3, pal.P2, seed) + pines(0, 760, 30, 1.1, pal.P1, seed)
    b += campus(460, 760, 680, 260, '#fff', pal.S, pal.P3) + rect(0, 760, HW, 240, mix(pal.P, '#5E9E4A', .45)) + ''.join(kid(380 + i * 220, 1010, .5, pal, i, pal.P) for i in range(5))
    return b


NICHES = {
    'preschool': (h_preschool, ['play', 'art', 'music', 'classroom', 'sports', 'library'], 'play', 'classroom', 'waves'),
    'coaching': (h_coaching, ['maths', 'lab', 'exam', 'lecture', 'online', 'library'], 'lecture', 'exam', 'grid'),
    'engineering': (h_engineering, ['robotics', 'computer', 'lab', 'lecture', 'library', 'graduation'], 'campus', 'robotics', 'grid'),
    'international': (h_international, ['classroom', 'lab', 'library', 'art', 'sports', 'music'], 'campus', 'library', 'city'),
    'coding': (h_coding, ['computer', 'online', 'robotics', 'business', 'lecture', 'graduation'], 'online', 'computer', 'grid'),
    'arts': (h_arts, ['music', 'dance', 'art', 'classroom', 'online', 'graduation'], 'dance', 'music', 'waves'),
    'mba': (h_mba, ['business', 'lecture', 'library', 'online', 'graduation', 'computer'], 'lecture', 'business', 'city'),
    'language': (h_language, ['language', 'classroom', 'online', 'exam', 'library', 'graduation'], 'language', 'classroom', 'waves'),
    'boarding': (h_boarding, ['classroom', 'sports', 'hostel', 'lab', 'music', 'library'], 'hostel', 'sports', 'field'),
}


def about_scene(pal, kind, seed):
    """1000×850 about image: the tile drawn full-width at the bottom, with its own background colour above it,
    so photo frames that crop to portrait or 4:3 keep the whole subject."""
    inner = TILES[kind](pal)
    m = re.search(r'fill="(#[0-9A-Fa-f]{6})"', inner)
    top = m.group(1) if m and not inner.startswith('<defs') else pal.P4
    return rect(0, 0, 1000, 850, top) + f'<svg x="0" y="225" width="1000" height="625" viewBox="0 0 800 500">{inner}</svg>'


def generate(out_dir, niche, primary, secondary, seed=1, logo_names=None):
    cv = Canvas(out_dir)
    pal = Palette(primary, secondary)
    hero, tiles, a1, a2, ban = NICHES[niche]
    cv.save('hero.svg', HW, HH, hero(pal, seed), f'Illustration: {LABELS[tiles[0]].lower()} and students')
    for i, t in enumerate(tiles):
        cv.save(f'svc-{i+1}.svg', W, H, TILES[t](pal), LABELS[t])
    cv.save('about-1.svg', 1000, 850, about_scene(pal, a1, seed), LABELS[a1])
    sq = TILES[a2](pal)
    m = re.search(r'fill="(#[0-9A-Fa-f]{6})"', sq)
    top = m.group(1) if m and not sq.startswith('<defs') else pal.P4
    cv.save('about-2.svg', 800, 800, rect(0, 0, 800, 800, top) + f'<svg x="-100" y="200" width="1000" height="625" viewBox="0 0 800 500">{sq}</svg>', LABELS[a2])
    banner(cv, pal, ban)
    portraits(cv, pal, seed)
    logos(cv, pal, logo_names or ['Learnwell', 'Brightmind', 'Scholaris', 'EduTrust', 'Kidspire'])
