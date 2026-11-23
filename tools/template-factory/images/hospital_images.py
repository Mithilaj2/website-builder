"""Hospital-category scenes. generate(out_dir, niche, primary, secondary, seed, logo_names) writes:
hero, about-1, about-2, svc-1..6 (department tiles), doctor-1..8 (portraits in white coats), team-1..4, banner, logo-1..5."""
import random
import re
from kit import *  # noqa: F401,F403
from business_images import portraits, logos, banner
from education_images import flask, microscope, kid, tile, ball

W, H = 800, 500
HW, HH = 1600, 1000
RED = '#D7263D'


def cross(cx, cy, s, c):
    return rect(cx - 12 * s, cy - 36 * s, 24 * s, 72 * s, c, 4 * s) + rect(cx - 36 * s, cy - 12 * s, 72 * s, 24 * s, c, 4 * s)


def stethoscope(cx, base, s, c='#2B3440'):
    g = f'<g transform="translate({cx:.1f} {base:.1f}) scale({s})" fill="none" stroke="{c}" stroke-width="7" stroke-linecap="round">'
    g += '<path d="M-48 -150 C-70 -110 -64 -70 -40 -58 M48 -150 C70 -110 64 -70 40 -58 M-40 -58 Q0 -40 40 -58 M0 -46 V-10"/>'
    return g + f'</g><g transform="translate({cx:.1f} {base:.1f}) scale({s})"><circle cx="0" cy="0" r="16" fill="{c}"/><circle cx="0" cy="0" r="8" fill="#C9D2DC"/></g>'


def doctor(cx, base, s, pal, i, coat='#FFFFFF'):
    styles = ['short', 'long', 'grey', 'bun', 'bald', 'long', 'short', 'bun']
    return figure(cx, base, s, SKINS[i % 6], HAIR[i % 6], coat, styles[i % 8], pal.P) + stethoscope(cx, base, s)


def doctor_portrait(cv, name, pal, i):
    bg = [pal.P4, pal.BG, pal.S3, mix(pal.P, '#FFFFFF', .8)][i % 4]
    b = rect(0, 0, 600, 660, bg) + [circle(470, 140, 120, pal.P3, .5), dots(40, 50, 180, 160, pal.P2, 24, 3, .5), f'<path d="M380 0 L600 0 L600 300Z" fill="{pal.S}" opacity=".3"/>', cross(500, 120, 1.2, pal.P3)][i % 4]
    cv.save(name, 600, 660, b + doctor(300, 670, 1.43, pal, i), 'Doctor portrait')


def bed(x, base, s, frame, sheet, pillow):
    return (rect(x, base - 90 * s, 300 * s, 40 * s, sheet, 10 * s) + rect(x, base - 110 * s, 70 * s, 30 * s, pillow, 12 * s) + rect(x - 10 * s, base - 170 * s, 14 * s, 170 * s, frame, 4)
            + rect(x + 296 * s, base - 120 * s, 14 * s, 120 * s, frame, 4) + rect(x, base - 52 * s, 300 * s, 12 * s, frame, 4))


def drip(x, base, s, c, bag):
    return f'<path d="M{x} {base} V{base-300*s} M{x-30*s} {base} h{60*s}" stroke="{c}" stroke-width="{6*s}"/>' + rect(x - 22 * s, base - 300 * s, 44 * s, 70 * s, bag, 10 * s) + f'<path d="M{x} {base-230*s} q{40*s} {60*s} {90*s} {80*s}" stroke="{c}" stroke-width="{3*s}" fill="none"/>'


def ecg(x, y, w, c, sw=6):
    pts = f'M{x} {y} h{w*0.25} l{w*0.04} -40 l{w*0.05} 90 l{w*0.05} -110 l{w*0.04} 60 h{w*0.57}'
    return f'<path d="{pts}" stroke="{c}" stroke-width="{sw}" fill="none" stroke-linejoin="round" stroke-linecap="round"/>'


def heart(cx, cy, s, c):
    return f'<path d="M{cx} {cy+60*s} C{cx-110*s} {cy-10*s} {cx-60*s} {cy-90*s} {cx} {cy-40*s} C{cx+60*s} {cy-90*s} {cx+110*s} {cy-10*s} {cx} {cy+60*s}Z" fill="{c}"/>'


def tooth(cx, cy, s, c='#fff', edge='#C9D2DC'):
    return f'<path d="M{cx-50*s} {cy-50*s} C{cx-60*s} {cy-90*s} {cx-10*s} {cy-80*s} {cx} {cy-66*s} C{cx+10*s} {cy-80*s} {cx+60*s} {cy-90*s} {cx+50*s} {cy-50*s} C{cx+44*s} {cy} {cx+40*s} {cy+60*s} {cx+22*s} {cy+60*s} C{cx+8*s} {cy+60*s} {cx+10*s} {cy+10*s} {cx} {cy+10*s} C{cx-10*s} {cy+10*s} {cx-8*s} {cy+60*s} {cx-22*s} {cy+60*s} C{cx-40*s} {cy+60*s} {cx-44*s} {cy} {cx-50*s} {cy-50*s}Z" fill="{c}" stroke="{edge}" stroke-width="{4*s}"/>'


def eye(cx, cy, s, iris, skin='#fff'):
    return f'<path d="M{cx-120*s} {cy} Q{cx} {cy-90*s} {cx+120*s} {cy} Q{cx} {cy+90*s} {cx-120*s} {cy}Z" fill="{skin}" stroke="#2B3440" stroke-width="{5*s}"/>' + circle(cx, cy, 42 * s, iris) + circle(cx, cy, 18 * s, '#1E1E1E') + circle(cx + 12 * s, cy - 12 * s, 7 * s, '#fff')


def pills(x, y, n, colors, seed=1):
    random.seed(seed)
    out = ''
    for i in range(n):
        c = random.choice(colors)
        out += rect(x + (i % 6) * 64, y + (i // 6) * 84, 44, 62, c, 8) + rect(x + (i % 6) * 64 - 2, y + (i // 6) * 84 - 10, 48, 16, '#fff', 4) + rect(x + (i % 6) * 64 + 8, y + (i // 6) * 84 + 22, 28, 18, '#fff', 3, .7)
    return out


def leaf(cx, cy, s, c, rot=0):
    return f'<path d="M{cx} {cy} C{cx+40*s} {cy-60*s} {cx+100*s} {cy-50*s} {cx+120*s} {cy-10*s} C{cx+80*s} {cy+30*s} {cx+30*s} {cy+30*s} {cx} {cy}Z" fill="{c}" transform="rotate({rot} {cx} {cy})"/>'


def ambulance(x, base, s, body, stripe):
    g = f'<g transform="translate({x} {base}) scale({s})">' + rect(0, -150, 250, 120, body, 10) + f'<path d="M250 -120 h70 l40 50 v40 h-110Z" fill="{body}"/>' + rect(268, -110, 46, 34, '#CFE3F0', 4)
    g += rect(0, -96, 360, 16, stripe) + cross(120, -115, .55, RED) + rect(60, -164, 50, 16, RED, 4)
    for wx in (70, 290):
        g += circle(wx, -24, 26, '#2B2B2B') + circle(wx, -24, 11, '#BBB')
    return g + '</g>'


def building(x, base, w, h, wall, glass, accent):
    out = rect(x, base - h, w, h, wall) + ''.join(rect(x + 24 + c * 64, base - h + 30 + r * 64, 44, 40, glass) for r in range(int((h - 110) // 64)) for c in range(int((w - 30) // 64)))
    return out + rect(x + w / 2 - 70, base - 90, 140, 90, accent) + rect(x + w / 2 - 60, base - 80, 120, 80, '#CFE3F0') + rect(x + w / 2 - 44, base - h - 70, 88, 70, '#fff', 8) + cross(x + w / 2, base - h - 35, .7, RED)


# --------------------------------------------------------------------------- tiles

def t_reception(pal):
    return tile(pal.P4, rect(150, 300, 500, 120, pal.P, 12) + rect(150, 290, 500, 20, pal.INK, 6) + doctor(400, 330, .3, pal, 1, '#fff') + monitor(470, 220, 110, 70, pal.INK, '#fff') + cross(220, 160, .8, RED) + plant(690, 420, .9, pal.S, pal.P, pal.P2))


def t_consult(pal):
    return tile(pal.BG, rect(170, 330, 460, 20, pal.INK, 6) + rect(190, 350, 16, 100, pal.INK) + rect(594, 350, 16, 100, pal.INK) + doctor(280, 500, .42, pal, 0) + figure(540, 500, .4, SKINS[4], HAIR[1], pal.S, 'long', '#fff') + card(360, 270, 80, 56, '#fff', 4, sh=False) + lines(370, 282, 2, 60, 16, pal.P3, 6))


def t_lab(pal):
    return tile(pal.S3, microscope(250, 400, 1.3, pal.INK, pal.P) + flask(440, 400, 1.4, pal.P4, pal.S) + ''.join(rect(520 + i * 34, 260, 22, 140, '#fff', 10) + rect(520 + i * 34, 330, 22, 70, [RED, pal.P, pal.S, pal.P2][i], 10) for i in range(4)) + rect(140, 400, 540, 16, pal.INK, 4))


def t_scan(pal):
    return tile(pal.P4, circle(360, 250, 160, '#fff') + circle(360, 250, 92, pal.P4) + f'<circle cx="360" cy="250" r="126" fill="none" stroke="{pal.P3}" stroke-width="10"/>' + rect(360, 232, 330, 40, pal.P, 10) + rect(230, 400, 300, 30, pal.INK, 8) + circle(220, 250, 8, pal.S))


def t_theatre(pal):
    b = rect(0, 0, W, H, mix(pal.P, '#FFFFFF', .82)) + f'<path d="M400 0 V70" stroke="{pal.INK}" stroke-width="10"/>' + f'<ellipse cx="400" cy="100" rx="160" ry="40" fill="{pal.INK}"/>' + f'<ellipse cx="400" cy="106" rx="140" ry="26" fill="#FFF7D6"/>'
    b += f'<path d="M260 130 L180 380 H620 L540 130Z" fill="#FFF7D6" opacity=".45"/>' + rect(200, 360, 400, 34, pal.P2, 8) + rect(380, 394, 40, 90, pal.INK) + doctor(160, 520, .34, pal, 3, pal.P) + doctor(650, 520, .34, pal, 6, pal.P)
    return b


def t_room(pal):
    return tile(pal.BG, bed(220, 420, 1.2, pal.INK, '#fff', pal.P4) + rect(260, 340, 260, 36, pal.P3, 8) + drip(640, 420, 1, pal.INK, pal.P4) + monitor(110, 140, 120, 80, pal.INK, '#0B2530', ecg(122, 190, 96, '#3EE07C', 3)))


def t_pharmacy(pal):
    return tile(pal.P4, ''.join(rect(160, 130 + r * 100, 480, 10, pal.INK) for r in range(3)) + pills(180, 56, 6, [pal.P, pal.S, pal.P2]) + pills(180, 156, 6, [pal.S2, pal.P, RED], 3) + pills(180, 256, 6, [pal.P2, pal.S, pal.P], 5) + cross(680, 120, .8, pal.S))


def t_dental(pal):
    b = tile(pal.S3, f'<path d="M220 420 L280 260 H420 L470 330 H600 L620 380 H300 L280 420Z" fill="{pal.P}"/>' + rect(240, 420, 380, 20, pal.INK, 6) + f'<path d="M560 330 V170 H660" stroke="{pal.INK}" stroke-width="10" fill="none"/>' + circle(660, 170, 34, '#FFF7D6') + tooth(170, 190, .9))
    return b


def t_eye(pal):
    b = tile(pal.BG, rect(150, 60, 220, 380, '#fff', 10) + ''.join(text(260, 130 + i * 70, t, [80, 60, 46, 34, 24][i], pal.INK, 700, 'middle') for i, t in enumerate(['E', 'F P', 'T O Z', 'L P E D', 'P E C F D'])) + eye(560, 250, 1.1, pal.P))
    return b


def t_paeds(pal):
    return tile(pal.WARM, kid(400, 560, .78, pal, 2, pal.S) + circle(170, 400, 46, RED) + rect(590, 330, 90, 90, pal.P, 12) + text(635, 392, 'A', 54, '#fff', 800, 'middle') + f'<path d="M140 150 q40 -60 80 0 q40 60 80 0" stroke="{pal.P}" stroke-width="10" fill="none"/>' + ball(650, 170, 40, pal.P))


def t_physio(pal):
    b = tile(pal.P4, circle(600, 380, 110, pal.S) + f'<path d="M100 140 q90 -100 180 0" stroke="{pal.P2}" stroke-width="12" fill="none" stroke-linecap="round"/>' + rect(120, 470, 560, 18, pal.P2, 8))
    return b + figure(360, 580, .72, SKINS[2], HAIR[0], pal.P, 'short', '#fff') + f'<path d="M190 330 L120 210 M530 330 L600 210" stroke="{SKINS[2]}" stroke-width="34" stroke-linecap="round"/>' + rect(80, 190, 560, 16, pal.INK, 8)


def t_ayur(pal):
    b = tile(pal.WARM, f'<path d="M300 280 Q300 430 400 430 Q500 430 500 280Z" fill="{mix(pal.S, "#000", .35)}"/>' + rect(290, 268, 220, 22, mix(pal.S, '#000', .5), 8) + f'<path d="M520 120 q-60 120 0 150 q60 -30 0 -150Z" fill="{pal.S}"/>')
    return b + leaf(180, 330, 1, pal.P, -30) + leaf(560, 380, 1, pal.P2, 20) + leaf(220, 200, .8, pal.P2, -60) + rect(140, 430, 520, 14, pal.INK, 6)


def t_maternity(pal):
    b = tile(pal.S3, heart(640, 150, .6, RED) + rect(560, 330, 170, 100, pal.P2, 20) + rect(560, 430, 12, 40, pal.INK) + rect(718, 430, 12, 40, pal.INK))
    return b + figure(330, 580, .72, SKINS[1], HAIR[1], pal.P, 'long', '#fff') + f'<ellipse cx="350" cy="520" rx="105" ry="62" fill="#fff"/>' + circle(400, 502, 36, SKINS[4]) + f'<path d="M260 522 q90 40 180 0" stroke="{pal.S}" stroke-width="10" fill="none"/>' 


def t_cardiac(pal):
    return tile(pal.BG, heart(300, 250, 1.4, RED) + monitor(430, 140, 260, 170, pal.INK, '#0B2530', ecg(445, 240, 230, '#3EE07C', 4)) + rect(130, 420, 560, 14, pal.INK, 6))


def t_emergency(pal):
    return rect(0, 0, W, H, pal.P4) + rect(0, 400, W, 100, mix(pal.INK, '#FFFFFF', .5)) + building(470, 400, 300, 280, '#fff', pal.P4, pal.P) + ambulance(60, 440, 1.05, '#fff', RED)


def t_xray(pal):
    return tile(pal.P4, rect(240, 80, 320, 340, '#0E1A22', 10) + f'<g stroke="#DDE6EC" stroke-width="8" fill="none" opacity=".9"><path d="M400 110 V390"/>' + ''.join(f'<path d="M400 {150+i*40} q-90 -10 -110 30 M400 {150+i*40} q90 -10 110 30"/>' for i in range(6)) + '</g>' + rect(560, 160, 120, 18, pal.P2, 6))


def t_vaccine(pal):
    return tile(pal.S3, f'<g transform="rotate(-35 400 250)">' + rect(260, 220, 260, 60, '#fff', 10) + rect(270, 230, 160, 40, pal.P2, 6) + rect(520, 240, 120, 20, pal.INK, 4) + rect(220, 210, 40, 80, pal.INK, 6) + rect(640, 247, 70, 6, pal.INK) + '</g>' + cross(640, 380, .7, pal.P))


def t_wellness(pal):
    b = tile(pal.BG, circle(400, 150, 80, pal.S, .8) + leaf(120, 420, 1.2, pal.P, -20) + leaf(680, 420, 1.2, pal.P2, 200))
    return b + figure(400, 580, .72, SKINS[3], HAIR[1], pal.P, 'bun', '#fff')


TILES = {'reception': t_reception, 'consult': t_consult, 'lab': t_lab, 'scan': t_scan, 'theatre': t_theatre, 'room': t_room, 'pharmacy': t_pharmacy,
         'dental': t_dental, 'eye': t_eye, 'paeds': t_paeds, 'physio': t_physio, 'ayur': t_ayur, 'maternity': t_maternity, 'cardiac': t_cardiac,
         'emergency': t_emergency, 'xray': t_xray, 'vaccine': t_vaccine, 'wellness': t_wellness}
LABELS = {'reception': 'Reception and help desk', 'consult': 'Consultation room', 'lab': 'Laboratory', 'scan': 'Imaging suite', 'theatre': 'Operation theatre',
          'room': 'Patient room', 'pharmacy': 'Pharmacy', 'dental': 'Dental chair', 'eye': 'Eye examination', 'paeds': 'Children’s ward', 'physio': 'Physiotherapy gym',
          'ayur': 'Ayurveda therapy', 'maternity': 'Maternity care', 'cardiac': 'Cardiac care', 'emergency': 'Emergency and ambulance', 'xray': 'X-ray and radiology',
          'vaccine': 'Vaccination', 'wellness': 'Wellness and yoga'}


# --------------------------------------------------------------------------- heroes

def ground(pal, y=780):
    return rect(0, y, HW, HH - y, mix(pal.P, '#FFFFFF', .78))


def h_multi(pal, seed):
    b = sky(HW, HH, pal.P4, pal.BG, 'hs') + clouds(seed, HW, 100, 3) + building(520, 780, 640, 520, '#fff', pal.P4, pal.P) + ground(pal)
    return b + ambulance(1150, 860, 1.2, '#fff', RED) + doctor(330, 1010, .72, pal, 0) + doctor(1450, 1010, .62, pal, 3) + tree(110, 790, 1.2, pal.P2, pal.P1)


def h_dental(pal, seed):
    b = rect(0, 0, HW, HH, pal.S3) + blob(800, 520, 520, '#fff', .9) + tooth(1180, 360, 2.6) + circle(1360, 220, 30, pal.S, .9) + circle(1000, 160, 18, pal.P2)
    b += f'<path d="M380 800 L480 520 H760 L840 640 H1060 L1090 720 H560 L520 800Z" fill="{pal.P}"/>' + rect(420, 800, 700, 30, pal.INK, 8)
    return b + doctor(1300, 1010, .75, pal, 1) + figure(640, 690, .36, SKINS[2], HAIR[0], pal.S, 'short', '#fff')


def h_eye(pal, seed):
    b = rect(0, 0, HW, HH, pal.BG) + circle(1130, 420, 360, pal.P4) + eye(1130, 420, 2.6, pal.P) + rect(180, 120, 300, 560, '#fff', 14)
    b += ''.join(text(330, 230 + i * 100, t, [120, 90, 70, 52, 38][i], pal.INK, 700, 'middle') for i, t in enumerate(['E', 'F P', 'T O Z', 'L P E D', 'P E C F D']))
    return b + ground(pal, 820) + doctor(660, 1010, .72, pal, 4)


def h_paeds(pal, seed):
    b = sky(HW, HH, pal.WARM, pal.S3, 'hs') + circle(1350, 190, 100, pal.S) + clouds(seed, HW, 100, 4) + ground(pal, 760)
    b += f'<path d="M140 760 L240 420 L340 760 M190 590 h100" stroke="{pal.P}" stroke-width="18" fill="none" stroke-linecap="round"/>' + ''.join(circle(500 + i * 90, 300 - (i % 2) * 40, 34, [RED, pal.P, pal.S, pal.P2][i % 4]) + f'<path d="M{500+i*90} {334-(i%2)*40} V{440}" stroke="{pal.INK}" stroke-width="2"/>' for i in range(4))
    return b + doctor(1150, 1010, .72, pal, 1) + kid(880, 1010, .55, pal, 2, pal.S) + kid(600, 1010, .5, pal, 0, pal.P) + ball(360, 900, 50, pal.P)


def h_diag(pal, seed):
    b = rect(0, 0, HW, HH, pal.P4) + circle(1020, 470, 340, '#fff') + circle(1020, 470, 190, pal.P4) + f'<circle cx="1020" cy="470" r="262" fill="none" stroke="{pal.P3}" stroke-width="22"/>'
    b += rect(1020, 430, 560, 80, pal.P, 20) + rect(700, 740, 640, 40, pal.INK, 10) + card(120, 160, 380, 300, '#fff', 16) + ''.join(rect(150, 200 + i * 44, [300, 220, 260, 180, 240][i], 16, [pal.P2, pal.S, pal.P3][i % 3], 8) for i in range(5))
    return b + ground(pal, 820) + doctor(330, 1010, .7, pal, 2) + badge(450, 520, 46, pal.S, '✓', fs=46)


def h_physio(pal, seed):
    b = rect(0, 0, HW, HH, pal.BG) + circle(1150, 330, 260, pal.P4) + circle(1180, 680, 150, pal.S) + ground(pal, 820)
    b += f'<path d="M180 820 L180 380 M180 380 H460 M460 380 V820" stroke="{pal.INK}" stroke-width="16" fill="none"/>' + ''.join(rect(220 + i * 56, 380, 12, 440, pal.P3, 4) for i in range(5))
    return b + doctor(860, 1010, .72, pal, 5) + figure(1250, 1010, .6, SKINS[3], HAIR[0], pal.P, 'short', '#fff') + f'<path d="M1060 520 q90 -110 180 0" stroke="{pal.S}" stroke-width="12" fill="none"/>'


def h_ayur(pal, seed):
    b = rect(0, 0, HW, HH, pal.WARM) + circle(1150, 300, 220, pal.S, .35) + ''.join(leaf(100 + i * 160, 160 + (i % 3) * 40, 1.6, [pal.P, pal.P2, pal.P1][i % 3], -40 + i * 15) for i in range(4))
    b += f'<path d="M820 560 Q820 820 1040 820 Q1260 820 1260 560Z" fill="{mix(pal.S, "#000", .35)}"/>' + rect(800, 540, 480, 36, mix(pal.S, '#000', .5), 12) + f'<path d="M1040 240 q-90 180 0 230 q90 -50 0 -230Z" fill="{pal.S}"/>'
    return b + ground(pal, 840) + doctor(450, 1010, .74, pal, 1, '#FFF7E8') + leaf(1300, 820, 1.4, pal.P, -20) + leaf(700, 840, 1.2, pal.P2, -160)


def h_maternity(pal, seed):
    b = rect(0, 0, HW, HH, pal.S3) + blob(820, 520, 520, '#fff', .85) + heart(1300, 230, 1.2, RED) + ground(pal, 830)
    b += figure(760, 1010, .85, SKINS[1], HAIR[1], pal.P, 'long', '#fff') + f'<ellipse cx="770" cy="660" rx="110" ry="70" fill="#fff"/>' + circle(820, 640, 40, SKINS[4])
    return b + doctor(1250, 1010, .66, pal, 3) + rect(260, 640, 300, 170, pal.P2, 40) + rect(260, 810, 20, 60, pal.INK) + rect(540, 810, 20, 60, pal.INK)


def h_cardiac(pal, seed):
    b = rect(0, 0, HW, HH, pal.INK) + dots(60, 60, 1480, 880, pal.P2, 40, 2, .25) + heart(1080, 420, 3.3, RED) + ecg(80, 470, 1440, '#fff', 8)
    return b + rect(0, 840, HW, 160, mix(pal.INK, '#000', .3)) + doctor(380, 1010, .76, pal, 0)


HEROES = {'multi': h_multi, 'dental': h_dental, 'eye': h_eye, 'paeds': h_paeds, 'diagnostics': h_diag, 'physio': h_physio, 'ayurveda': h_ayur,
          'maternity': h_maternity, 'cardiac': h_cardiac}
NICHES = {
    'multi': (['emergency', 'cardiac', 'consult', 'scan', 'theatre', 'paeds'], 'reception', 'room', 'grid'),
    'dental': (['dental', 'consult', 'xray', 'paeds', 'reception', 'vaccine'], 'dental', 'consult', 'waves'),
    'eye': (['eye', 'consult', 'theatre', 'paeds', 'scan', 'pharmacy'], 'eye', 'reception', 'grid'),
    'paeds': (['paeds', 'vaccine', 'consult', 'room', 'lab', 'emergency'], 'paeds', 'room', 'waves'),
    'diagnostics': (['lab', 'scan', 'xray', 'cardiac', 'vaccine', 'reception'], 'lab', 'scan', 'grid'),
    'physio': (['physio', 'consult', 'wellness', 'room', 'paeds', 'xray'], 'physio', 'wellness', 'waves'),
    'ayurveda': (['ayur', 'wellness', 'consult', 'pharmacy', 'room', 'physio'], 'ayur', 'wellness', 'field'),
    'maternity': (['maternity', 'paeds', 'scan', 'room', 'vaccine', 'consult'], 'maternity', 'room', 'waves'),
    'cardiac': (['cardiac', 'theatre', 'scan', 'emergency', 'room', 'lab'], 'cardiac', 'theatre', 'grid'),
}


def framed(inner, w, h, y):
    m = re.search(r'fill="(#[0-9A-Fa-f]{6})"', inner)
    top = m.group(1) if m and not inner.startswith('<defs') else '#EEF4F6'
    return rect(0, 0, w, h, top) + f'<svg x="{(w-1000)/2}" y="{y}" width="1000" height="625" viewBox="0 0 800 500">{inner}</svg>'


def generate(out_dir, niche, primary, secondary, seed=1, logo_names=None):
    cv = Canvas(out_dir)
    pal = Palette(primary, secondary)
    tiles, a1, a2, ban = NICHES[niche]
    cv.save('hero.svg', HW, HH, HEROES[niche](pal, seed), 'Illustration of the clinic and its doctors')
    for i, t in enumerate(tiles):
        cv.save(f'svc-{i+1}.svg', W, H, TILES[t](pal), LABELS[t])
    cv.save('about-1.svg', 1000, 850, framed(TILES[a1](pal), 1000, 850, 225), LABELS[a1])
    cv.save('about-2.svg', 800, 800, framed(TILES[a2](pal), 800, 800, 175), LABELS[a2])
    for i in range(8):
        doctor_portrait(cv, f'doctor-{i+1}.svg', pal, i + seed)
    banner(cv, pal, ban)
    portraits(cv, pal, seed)
    logos(cv, pal, logo_names or ['MediShield', 'CareSure', 'HealthFirst TPA', 'SecureLife', 'Arogya Plus'])
