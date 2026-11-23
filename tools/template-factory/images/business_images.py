"""Business-category scenes. generate(out_dir, niche, primary, secondary, seed) writes the image set."""
import random
from kit import *  # noqa: F401,F403

W, H = 800, 500          # service / gallery tiles (16:10)
HW, HH = 1600, 1000      # hero
AW, AH = 1000, 850       # about-1


def spot(pal, bgc, body, blob_c='#fff'):
    return rect(0, 0, W, H, bgc) + blob(400, 250, 200, blob_c, .85) + body




def office_window(pal, x, y, w, h, seed):
    inner = skyline(w, h, pal.P3, seed, 1, int(h * .3), int(h * .85))
    return (f'<svg x="{x}" y="{y}" width="{w}" height="{h}" viewBox="0 0 {w} {h}">' + rect(0, 0, w, h, pal.P4) + clouds(seed, w, 20, 2, .8) + inner + '</svg>'
            + f'<rect x="{x-8}" y="{y-8}" width="{w+16}" height="{h+16}" fill="none" stroke="{pal.INK}" stroke-opacity=".25" stroke-width="10"/>' + rect(x + w / 2 - 3, y, 6, h, '#fff', op=.8))


def meeting(pal, screen_body, seed=3):
    b = rect(0, 0, AW, AH, pal.BG) + rect(0, 620, AW, 230, mix(pal.P, '#FFFFFF', .8))
    b += office_window(pal, 70, 80, 360, 300, seed)
    b += monitor(520, 90, 400, 260, pal.INK, '#fff', screen_body)
    b += figure(230, 860, .8, SKINS[0], HAIR[0], pal.P, 'short', pal.S) + figure(500, 880, .82, SKINS[4], HAIR[1], pal.S, 'long') + figure(780, 860, .8, SKINS[1], HAIR[2], pal.P2, 'bun')
    b += rect(80, 690, 840, 34, pal.INK, 8) + rect(110, 724, 18, 140, pal.INK) + rect(872, 724, 18, 140, pal.INK)
    b += rect(300, 662, 120, 28, '#fff', 4) + rect(580, 660, 90, 30, pal.S2, 4) + circle(760, 676, 14, '#fff')
    return b


def chart_screen(pal, x=532, y=102, w=376, h=214):
    return (bars(x + 30, y + h - 20, 6, 34, 18, [50, 80, 70, 110, 140, 170], pal.P2, pal.S)
            + f'<path d="M{x+40} {y+120} L{x+110} {y+100} L{x+180} {y+110} L{x+250} {y+60} L{x+330} {y+40}" stroke="{pal.P}" stroke-width="6" fill="none" stroke-linecap="round"/>')


def portraits(cv, pal, seed):
    random.seed(seed)
    styles = [('short', 0), ('long', 1), ('grey', 4), ('bun', 2)]
    tops = [pal.P, pal.S, pal.P2, pal.INK]
    for i, (st, hi) in enumerate(styles):
        portrait(cv, f'team-{i+1}.svg', pal, SKINS[(i * 2 + seed) % 6], HAIR[hi], tops[(i + seed) % 4], st, i + seed)


def logos(cv, pal, names):
    marks = ['circle', 'square', 'diamond', 'ring', 'bars', 'tri', 'leaf']
    for i, n in enumerate(names[:5]):
        wordmark(cv, f'logo-{i+1}.svg', n, marks[(i * 3) % len(marks)], '#4B5563')


def banner(cv, pal, kind):
    b = rect(0, 0, 1600, 500, pal.INK)
    if kind == 'grid':
        b += ''.join(f'<path d="M{i*80} 0 V500" stroke="#fff" stroke-opacity=".05"/>' for i in range(21)) + ''.join(f'<path d="M0 {i*80} H1600" stroke="#fff" stroke-opacity=".05"/>' for i in range(7))
        b += circle(1350, 120, 260, pal.P, .55) + circle(200, 520, 220, pal.S, .35)
    elif kind == 'city':
        b += skyline(1600, 500, mix(pal.INK, '#FFFFFF', .12), 9, 1, 120, 380) + skyline(1600, 500, mix(pal.INK, '#FFFFFF', .2), 4, 1, 60, 220) + circle(1300, 140, 70, pal.S, .7)
    elif kind == 'waves':
        for i in range(8):
            b += f'<path d="M0 {260+i*30} Q400 {200+i*30} 800 {260+i*30} T1600 {250+i*30}" stroke="{pal.S if i % 3 == 0 else "#fff"}" stroke-opacity="{.5 if i % 3 == 0 else .08}" stroke-width="3" fill="none"/>'
    else:  # field
        b += rect(0, 0, 1600, 500, pal.P1) + ''.join(f'<path d="M{-200+i*120} 500 L{700+i*30} 260" stroke="{pal.P2}" stroke-opacity=".25" stroke-width="16"/>' for i in range(20)) + circle(1250, 160, 90, pal.S, .75)
    cv.save('banner.svg', 1600, 500, b, 'Decorative banner')


# ----------------------------------------------------------------------------- niches

def logistics(cv, pal, seed):
    road = mix(pal.INK, '#FFFFFF', .15)
    h = sky(HW, HH, pal.P4, pal.WARM, 'hs') + circle(1260, 230, 110, pal.S, .8) + clouds(seed, HW, 120, 4, .9)
    h += skyline(HW, 640, pal.P3, seed, 1, 90, 260)
    h += rect(140, 380, 640, 260, pal.P2) + f'<path d="M120 390 L460 280 L800 390Z" fill="{pal.P}"/>' + ''.join(rect(190 + i * 150, 480, 110, 160, pal.INK, op=.8) for i in range(4))
    h += ''.join(container(880 + (i % 3) * 230, 520 - (i // 3) * 90, 220, 86, [pal.S, pal.P, pal.P2, pal.S2, pal.P1, pal.S][i]) for i in range(6))
    h += rect(0, 640, HW, 360, road) + ''.join(rect(i * 200 + 40, 800, 110, 12, '#fff', 4, .6) for i in range(8))
    h += truck(240, 790, 1.5, pal.S, pal.P, label='EXPRESS') + truck(980, 950, 1.3, pal.P, '#fff', label='CARGO', lc=pal.P)
    cv.save('hero.svg', HW, HH, h, 'Illustration of a logistics hub with trucks and containers')
    cv.save('about-1.svg', AW, AH, meeting(pal, chart_screen(pal) + text(560, 140, 'ON-TIME 98.6%', 22, pal.INK), seed), 'Team reviewing delivery performance')
    a2 = rect(0, 0, 800, 800, pal.BG) + rect(0, 560, 800, 240, mix(pal.P, '#FFFFFF', .75))
    a2 += ''.join(rect(60, 120 + r * 140, 680, 12, pal.INK, op=.6) for r in range(4)) + ''.join(crate(80 + c * 110, 40 + r * 140, 1.2, pal.S if (r + c) % 3 else pal.P2, pal.INK) for r in range(3) for c in range(6) if (r * 6 + c) % 4)
    a2 += figure(520, 820, .7, SKINS[3], HAIR[0], pal.P, 'short', pal.S, helmet=pal.S) + card(150, 520, 220, 140, '#fff', 12) + lines(175, 560, 3, 160) + badge(340, 540, 30, pal.P, '✓', fs=28)
    cv.save('about-2.svg', 800, 800, a2, 'Warehouse team checking stock')
    cv.save('svc-1.svg', W, H, spot(pal, pal.P4, truck(170, 400, 1.3, pal.S, pal.P, label='FTL')), 'Road freight')
    cv.save('svc-2.svg', W, H, spot(pal, pal.WARM, rect(150, 360, 520, 30, pal.INK) + ''.join(container(170 + (i % 3) * 170, 300 - (i // 3) * 64, 160, 60, [pal.P, pal.S, pal.P2][i % 3], 6) for i in range(6)) + f'<path d="M120 390 h580 l-60 70 h-460Z" fill="{pal.P1}"/>'), 'Sea freight')
    cv.save('svc-3.svg', W, H, spot(pal, pal.S3, rect(160, 140, 480, 280, pal.P2) + f'<path d="M140 150 L400 70 L660 150Z" fill="{pal.P}"/>' + ''.join(crate(190 + (i % 4) * 110, 340 - (i // 4) * 70, 1, pal.S, pal.INK) for i in range(8))), 'Warehousing')
    cv.save('svc-4.svg', W, H, spot(pal, pal.BG, monitor(220, 90, 360, 240, pal.INK, '#fff', f'<path d="M250 260 Q330 140 420 200 T560 130" stroke="{pal.P}" stroke-width="6" fill="none" stroke-dasharray="12 10"/>' + circle(250, 260, 12, pal.S) + circle(560, 130, 12, pal.S))), 'Shipment tracking')
    banner(cv, pal, 'grid')


def it(cv, pal, seed):
    bgc, panel = pal.INK, mix(pal.INK, '#FFFFFF', .08)
    h = rect(0, 0, HW, HH, bgc) + dots(40, 40, 1520, 920, pal.P2, 40, 2, .25) + circle(1200, 280, 300, pal.P, .35) + circle(380, 820, 260, pal.S, .18)
    h += card(260, 180, 1080, 640, panel, 22, '#000') + rect(260, 180, 1080, 56, mix(panel, '#FFFFFF', .06), 22) + ''.join(circle(300 + i * 28, 208, 8, c) for i, c in enumerate(('#FF6B6B', '#FFD93D', '#6BCB77')))
    h += card(300, 270, 360, 240, mix(panel, '#FFFFFF', .05), 14, '#000', False) + bars(330, 480, 6, 36, 18, [60, 90, 80, 120, 160, 190], pal.P2, pal.S)
    h += card(690, 270, 610, 240, mix(panel, '#FFFFFF', .05), 14, '#000', False) + f'<path d="M720 470 C800 400 860 440 930 380 S1080 320 1150 340 S1250 300 1280 290" stroke="{pal.S}" stroke-width="6" fill="none"/>' + f'<path d="M720 480 C800 450 900 470 980 430 S1150 420 1280 380" stroke="{pal.P2}" stroke-width="4" fill="none" opacity=".6"/>'
    h += ''.join(rect(300, 540 + i * 34, [520, 380, 460, 300, 420, 260][i], 14, [pal.P2, pal.S, '#fff'][i % 3], 7, .55) for i in range(6))
    h += card(840, 540, 460, 240, mix(panel, '#FFFFFF', .05), 14, '#000', False) + ''.join(circle(900 + i * 90, 640, 30, [pal.P, pal.S, pal.P2, pal.S2][i % 4]) for i in range(5)) + text(870, 730, 'uptime 99.98%', 26, '#fff', 600, op=.8)
    cv.save('hero.svg', HW, HH, h, 'Illustration of a software dashboard')
    cv.save('about-1.svg', AW, AH, meeting(pal, ''.join(rect(550, 120 + i * 26, [260, 180, 300, 140, 220, 280, 120][i], 12, [pal.P, pal.S, pal.P2][i % 3], 6) for i in range(7)), seed), 'Engineers in a code review')
    a2 = rect(0, 0, 800, 800, pal.BG) + blob(400, 420, 300, pal.P4) + monitor(170, 160, 460, 300, pal.INK, mix(pal.INK, '#FFFFFF', .1), ''.join(rect(200, 190 + i * 28, [300, 220, 360, 180, 260, 320, 140, 240][i], 12, [pal.S, pal.P2, "#fff"][i % 3], 6, .7) for i in range(8)))
    a2 += figure(400, 820, .62, SKINS[2], HAIR[0], pal.P, 'short', pal.S) + plant(700, 560, 1.1, pal.S, pal.P, pal.P2)
    cv.save('about-2.svg', 800, 800, a2, 'Developer at a workstation')
    cv.save('svc-1.svg', W, H, spot(pal, pal.P4, monitor(230, 80, 340, 230, pal.INK, '#fff', ''.join(rect(255, 105 + i * 24, [220, 160, 260, 120, 200, 240, 100, 180][i], 10, [pal.P, pal.S, pal.P2][i % 3], 5) for i in range(8)))), 'Custom software')
    cv.save('svc-2.svg', W, H, spot(pal, pal.S3, f'<path d="M260 320 C200 320 200 230 270 230 C280 160 380 150 410 200 C440 150 560 170 550 240 C620 240 620 320 560 320Z" fill="{pal.P}"/>' + ''.join(f'<path d="M{330+i*70} 320 V400" stroke="{pal.INK}" stroke-width="5"/>' + rect(305 + i * 70, 400, 50, 34, pal.S, 6) for i in range(3))), 'Cloud services')
    cv.save('svc-3.svg', W, H, spot(pal, pal.BG, f'<path d="M400 90 L540 140 V260 C540 340 470 390 400 420 C330 390 260 340 260 260 V140Z" fill="{pal.P}"/>' + f'<path d="M345 255 l40 40 l80 -90" stroke="#fff" stroke-width="18" fill="none" stroke-linecap="round" stroke-linejoin="round"/>'), 'Cybersecurity')
    cv.save('svc-4.svg', W, H, spot(pal, pal.WARM, ''.join(rect(250, 110 + i * 100, 300, 80, pal.INK, 10) + ''.join(circle(290 + j * 26, 150 + i * 100, 8, pal.S if j == 0 else pal.P2) for j in range(3)) + rect(420, 142 + i * 100, 100, 14, pal.P3, 7) for i in range(3))), 'Managed IT and servers')
    banner(cv, pal, 'grid')


def agro(cv, pal, seed):
    G1, G2, G3 = mix(pal.P, '#000', .1), pal.P, pal.P2
    h = sky(HW, HH, pal.WARM, pal.S3, 'hs') + circle(1180, 260, 120, pal.S, .85)
    h += f'<path d="M0 560 Q400 470 800 540 T1600 500 V1000 H0Z" fill="{G3}"/>' + f'<path d="M0 640 Q500 560 1000 640 T1600 610 V1000 H0Z" fill="{G2}"/>'
    h += ''.join(f'<path d="M{-300+i*120} 1000 Q{500+i*40} 720 {800+i*20} 640" stroke="{G1}" stroke-width="10" fill="none" opacity=".45"/>' for i in range(16))
    h += tree(140, 600, 1.4, G3, G1) + tree(1480, 560, 1.2, G3, G1, kind='tall')
    h += rect(1040, 460, 260, 160, pal.S, 4) + f'<path d="M1020 470 L1170 380 L1320 470Z" fill="{pal.INK}"/>' + rect(1140, 540, 60, 80, pal.INK)
    h += ''.join(crate(300 + i * 100, 860, 1.2, pal.S, mix(pal.S, '#000', .3)) for i in range(4)) + ''.join(circle(316 + i * 100 + j * 20, 856, 12, ['#E4572E', '#F2A541', '#7CB342'][(i + j) % 3]) for i in range(4) for j in range(3))
    h += figure(860, 1000, .9, SKINS[1], HAIR[0], pal.S, 'short', '#fff')
    cv.save('hero.svg', HW, HH, h, 'Illustration of farm fields and produce crates')
    cv.save('about-1.svg', AW, AH, meeting(pal, chart_screen(pal) + text(560, 140, 'EXPORTS +32%', 22, pal.INK), seed), 'Export planning meeting')
    a2 = sky(800, 800, pal.WARM, pal.BG, 'a2') + f'<path d="M0 520 Q400 440 800 520 V800 H0Z" fill="{G2}"/>' + ''.join(f'<path d="M{80+i*90} 800 Q{120+i*80} 640 {200+i*70} 540" stroke="{G1}" stroke-width="8" fill="none" opacity=".5"/>' for i in range(10))
    a2 += figure(400, 820, .7, SKINS[4], HAIR[1], pal.P2, 'long', '#fff') + ''.join(crate(80 + i * 100, 700, 1, pal.S, mix(pal.S, '#000', .3)) for i in range(2))
    cv.save('about-2.svg', 800, 800, a2, 'Farmer partner in the field')
    cv.save('svc-1.svg', W, H, spot(pal, pal.S3, ''.join(f'<ellipse cx="{280+i*60}" cy="{300-(i%2)*30}" rx="22" ry="60" fill="{[G1, G2, G3][i % 3]}" transform="rotate({-30+i*12} {280+i*60} 360)"/>' for i in range(5)) + rect(250, 340, 300, 90, pal.S, 10)), 'Fresh produce sourcing')
    cv.save('svc-2.svg', W, H, spot(pal, pal.P4, ''.join(crate(200 + (i % 4) * 100, 330 - (i // 4) * 76, 1.1, pal.S if i % 2 else pal.S2, pal.INK) for i in range(8))), 'Grading and packing')
    cv.save('svc-3.svg', W, H, spot(pal, pal.BG, f'<path d="M120 330 h560 l-70 90 h-420Z" fill="{pal.INK}"/>' + ''.join(container(170 + (i % 3) * 160, 270 - (i // 3) * 60, 150, 56, [pal.P, pal.S, pal.P2][i % 3], 6) for i in range(6)) + f'<path d="M0 430 Q200 410 400 430 T800 430 V500 H0Z" fill="{pal.P3}"/>'), 'Export shipping')
    cv.save('svc-4.svg', W, H, spot(pal, pal.WARM, card(250, 90, 300, 330, '#fff', 14) + text(280, 150, 'CERTIFIED', 26, pal.P) + lines(280, 180, 5, 220) + badge(470, 360, 48, pal.S, '✓', fs=44)), 'Quality certification')
    banner(cv, pal, 'field')


def engineering(cv, pal, seed):
    steel = mix(pal.INK, '#FFFFFF', .3)
    h = rect(0, 0, HW, HH, mix(pal.INK, '#FFFFFF', .88)) + rect(0, 760, HW, 240, mix(pal.INK, '#FFFFFF', .7))
    h += ''.join(rect(100 + i * 280, 120, 16, 640, steel) for i in range(6)) + rect(80, 110, 1440, 26, steel) + ''.join(f'<path d="M{108+i*280} 136 L{248+i*280} 300 L{388+i*280} 136" stroke="{steel}" stroke-width="6" fill="none"/>' for i in range(5))
    h += rect(200, 560, 520, 200, pal.P, 10) + rect(240, 600, 160, 110, mix(pal.P, '#000', .3), 6) + ''.join(circle(470 + i * 70, 650, 26, pal.S) for i in range(3)) + gear(1180, 520, 150, pal.P) + gear(1380, 690, 90, pal.S, 8)
    h += f'<path d="M820 760 V600 L900 520 L1000 560" stroke="{pal.S}" stroke-width="34" fill="none" stroke-linecap="round" stroke-linejoin="round"/>' + circle(820, 760, 40, pal.INK) + circle(900, 520, 26, pal.INK)
    h += ''.join(f'<path d="M1000 560 l{20*math.cos(a):.0f} {20*math.sin(a):.0f}" stroke="{pal.S}" stroke-width="5" stroke-linecap="round"/>' for a in [i * .7 for i in range(9)])
    h += figure(620, 1000, .85, SKINS[3], HAIR[0], pal.INK, 'short', pal.S, helmet=pal.S)
    cv.save('hero.svg', HW, HH, h, 'Illustration of a fabrication shop floor')
    cv.save('about-1.svg', AW, AH, meeting(pal, f'<g opacity=".9">' + gear(640, 220, 70, pal.P) + gear(760, 190, 44, pal.S, 8) + '</g>' + rect(560, 290, 300, 10, pal.P3, 5), seed), 'Engineers reviewing a design')
    a2 = rect(0, 0, 800, 800, pal.BG) + ''.join(f'<path d="M0 {120+i*80} H800" stroke="{pal.P3}" stroke-width="2"/>' for i in range(9)) + card(120, 160, 560, 380, '#fff', 8)
    a2 += f'<path d="M180 460 V260 H420 V340 H620 V460Z" fill="none" stroke="{pal.P}" stroke-width="6"/>' + circle(300, 360, 50, pal.S, .6) + ''.join(f'<path d="M{180+i*55} 490 v14" stroke="{pal.INK}" stroke-width="3"/>' for i in range(9))
    a2 += figure(620, 820, .6, SKINS[0], HAIR[4], pal.P, 'grey', '#fff')
    cv.save('about-2.svg', 800, 800, a2, 'Engineering drawing')
    cv.save('svc-1.svg', W, H, spot(pal, pal.P4, ''.join(rect(220 + i * 90, 160, 30, 260, pal.INK if i % 2 else steel) for i in range(5)) + rect(200, 140, 420, 30, pal.INK) + rect(200, 400, 420, 30, pal.INK)), 'Structural fabrication')
    cv.save('svc-2.svg', W, H, spot(pal, pal.S3, gear(330, 250, 110, pal.P) + gear(500, 200, 70, pal.S, 8) + gear(500, 360, 50, pal.INK, 8)), 'Precision machining')
    cv.save('svc-3.svg', W, H, spot(pal, pal.BG, f'<path d="M220 420 V300 L340 200 L470 250" stroke="{pal.S}" stroke-width="36" fill="none" stroke-linecap="round" stroke-linejoin="round"/>' + circle(220, 420, 46, pal.INK) + rect(470, 230, 120, 60, pal.P, 8) + rect(160, 430, 160, 30, pal.INK, 6)), 'Automation')
    cv.save('svc-4.svg', W, H, spot(pal, pal.WARM, card(230, 90, 340, 330, '#fff', 10) + text(260, 150, 'QC REPORT', 26, pal.P) + ''.join(rect(260, 180 + i * 40, 240, 12, '#D5DEE8', 6) + badge(530, 186 + i * 40, 12, pal.S, '✓', fs=14) for i in range(5))), 'Quality assurance')
    banner(cv, pal, 'grid')


def hr(cv, pal, seed):
    h = rect(0, 0, HW, HH, pal.BG) + blob(800, 520, 520, pal.P4) + circle(1350, 200, 120, pal.S3) + dots(100, 120, 260, 200, pal.P2, 26, 3, .5)
    for i, (x, y) in enumerate(((280, 300), (1180, 320), (360, 700), (1120, 720))):
        h += card(x - 150, y - 110, 300, 220, '#fff', 20) + circle(x - 70, y - 20, 50, [pal.P3, pal.S3][i % 2]) + figure(x - 70, y + 30, .2, SKINS[i], HAIR[i], [pal.P, pal.S][i % 2], ['short', 'long', 'bun', 'grey'][i]) + rect(x, y - 40, 110, 14, pal.P2, 7) + rect(x, y - 10, 80, 12, '#D5DEE8', 6) + badge(x + 100, y + 60, 22, pal.S, '✓', fs=22)
    h += figure(800, 1000, 1.15, SKINS[2], HAIR[1], pal.P, 'long', pal.S) + f'<path d="M520 520 Q660 440 800 470 T1080 520" stroke="{pal.S}" stroke-width="5" fill="none" stroke-dasharray="10 12"/>'
    cv.save('hero.svg', HW, HH, h, 'Illustration of a recruiter connecting candidates')
    cv.save('about-1.svg', AW, AH, meeting(pal, ''.join(circle(600 + i * 90, 190, 32, [pal.P2, pal.S, pal.P3][i % 3]) for i in range(4)) + rect(560, 260, 300, 12, pal.P3, 6), seed), 'HR team planning hiring')
    a2 = rect(0, 0, 800, 800, pal.WARM) + rect(0, 560, 800, 240, pal.S3) + rect(140, 520, 520, 26, pal.INK, 8) + figure(240, 820, .55, SKINS[1], HAIR[0], pal.P, 'short', '#fff') + figure(560, 820, .55, SKINS[4], HAIR[1], pal.S, 'long', '#fff')
    a2 += card(330, 380, 140, 140, '#fff', 8) + lines(350, 410, 4, 100, 24, pal.P3)
    cv.save('about-2.svg', 800, 800, a2, 'Interview in progress')
    cv.save('svc-1.svg', W, H, spot(pal, pal.P4, card(270, 80, 260, 340, '#fff', 14) + circle(400, 160, 46, pal.S3) + figure(400, 200, .14, SKINS[0], HAIR[0], pal.P, 'short') + lines(310, 240, 6, 180, 26)), 'Recruitment')
    cv.save('svc-2.svg', W, H, spot(pal, pal.S3, ''.join(figure(250 + i * 150, 400, .35, SKINS[i + 1], HAIR[i], [pal.P, pal.S, pal.P2][i], ['bun', 'short', 'long'][i]) for i in range(3)) + rect(180, 400, 440, 20, pal.INK, 6)), 'Training and workshops')
    cv.save('svc-3.svg', W, H, spot(pal, pal.BG, card(220, 110, 360, 290, '#fff', 14) + text(250, 160, 'PAYROLL · MAY', 24, pal.P) + ''.join(rect(250, 190 + i * 40, 300, 28, pal.P4 if i % 2 else pal.BG, 6) + rect(470, 198 + i * 40, 60, 12, pal.S, 6) for i in range(5))), 'Payroll and compliance')
    cv.save('svc-4.svg', W, H, spot(pal, pal.WARM, ''.join(rect(260 + i * 70, 400 - hh, 46, hh, [pal.P2, pal.P, pal.S][i % 3], 6) for i, hh in enumerate((120, 170, 140, 220, 260))) + f'<path d="M250 260 L330 220 L400 240 L470 150 L560 110" stroke="{pal.INK}" stroke-width="6" fill="none"/>'), 'HR analytics')
    banner(cv, pal, 'waves')


def solar(cv, pal, seed):
    h = sky(HW, HH, pal.P4, pal.WARM, 'hs') + circle(1300, 200, 110, pal.S, .9) + clouds(seed, HW, 140, 3, .9)
    h += f'<path d="M0 620 Q500 540 1000 600 T1600 560 V1000 H0Z" fill="{mix(pal.P, "#FFFFFF", .55)}"/>' + f'<path d="M0 700 Q600 640 1100 700 T1600 680 V1000 H0Z" fill="{mix(pal.P, "#FFFFFF", .3)}"/>'
    h += ''.join(turbine(1100 + i * 150, 600 - i * 10, 240 - i * 30, '#fff', '#fff', i * 37) for i in range(3))
    h += ''.join(solar_panel(80 + c * 210, 720 + r * 90, 180, 70, pal.P1 if r % 2 else pal.P, '#fff', 40) + rect(160 + c * 210, 790 + r * 90, 8, 30, pal.INK) for r in range(3) for c in range(5))
    h += figure(1250, 1000, .75, SKINS[3], HAIR[0], pal.S, 'short', '#fff', helmet='#fff')
    cv.save('hero.svg', HW, HH, h, 'Illustration of a solar farm with wind turbines')
    cv.save('about-1.svg', AW, AH, meeting(pal, bars(560, 300, 6, 34, 18, [40, 70, 90, 120, 150, 180], pal.P2, pal.S) + circle(840, 140, 26, pal.S), seed), 'Energy team reviewing output')
    a2 = sky(800, 800, pal.P4, pal.BG, 'a2') + circle(620, 160, 70, pal.S) + f'<path d="M100 520 L400 330 L700 520Z" fill="{pal.INK}"/>' + rect(140, 520, 520, 280, '#fff') + ''.join(solar_panel(220 + i * 140, 400 - i * 0, 120, 60, pal.P, '#fff', 30) for i in range(3)) + rect(360, 640, 80, 160, pal.S)
    a2 += figure(660, 820, .45, SKINS[1], HAIR[2], pal.P, 'bun', '#fff', helmet=pal.S)
    cv.save('about-2.svg', 800, 800, a2, 'Rooftop solar installation')
    cv.save('svc-1.svg', W, H, spot(pal, pal.P4, f'<path d="M180 330 L400 180 L620 330Z" fill="{pal.INK}"/>' + rect(210, 330, 380, 130, '#fff') + solar_panel(280, 230, 200, 70, pal.P, '#fff', 40) + circle(640, 110, 50, pal.S)), 'Rooftop solar')
    cv.save('svc-2.svg', W, H, spot(pal, pal.S3, ''.join(solar_panel(140 + c * 180, 220 + r * 90, 150, 60, pal.P if r % 2 else pal.P1, '#fff', 34) for r in range(3) for c in range(3))), 'Solar parks')
    cv.save('svc-3.svg', W, H, spot(pal, pal.BG, rect(310, 120, 180, 300, pal.INK, 24) + rect(340, 150, 120, 240, '#fff', 10) + ''.join(rect(355, 330 - i * 50, 90, 38, pal.S if i < 3 else pal.P4, 6) for i in range(4)) + rect(370, 98, 60, 24, pal.INK, 6)), 'Battery storage')
    cv.save('svc-4.svg', W, H, spot(pal, pal.WARM, ''.join(turbine(260 + i * 140, 440, 260 - i * 30, pal.P, pal.P2, i * 40) for i in range(3))), 'Wind energy')
    banner(cv, pal, 'waves')


def construction(cv, pal, seed):
    h = sky(HW, HH, pal.P4, pal.WARM, 'hs') + clouds(seed, HW, 120, 3, .9) + skyline(HW, 800, pal.P3, seed, 1, 120, 300)
    bx, by = 520, 220
    h += rect(bx, by, 520, 580, mix(pal.INK, '#FFFFFF', .2)) + ''.join(rect(bx + 20 + c * 125, by + 30 + r * 90, 100, 60, pal.P4 if r > 1 else pal.S3) for r in range(6) for c in range(4))
    h += ''.join(f'<path d="M{bx-20} {by+r*96} H{bx+540}" stroke="{pal.S}" stroke-width="6"/>' for r in range(7)) + ''.join(f'<path d="M{bx-20+c*112} {by} V800" stroke="{pal.S}" stroke-width="5"/>' for c in range(6))
    h += crane(1180, 800, 640, 520, pal.S) + crane(220, 800, 520, 380, mix(pal.S, '#000', .15))
    h += rect(0, 800, HW, 200, mix(pal.INK, '#FFFFFF', .55)) + figure(400, 1000, .85, SKINS[0], HAIR[0], pal.P, 'short', pal.S, helmet=pal.S) + figure(1250, 1000, .8, SKINS[3], HAIR[0], pal.INK, 'short', pal.S, helmet='#fff')
    cv.save('hero.svg', HW, HH, h, 'Illustration of a building under construction with tower cranes')
    cv.save('about-1.svg', AW, AH, meeting(pal, f'<path d="M570 290 V170 L700 110 L830 170 V290Z" fill="none" stroke="{pal.P}" stroke-width="6"/>' + rect(670, 220, 60, 70, pal.S), seed), 'Project review with architects')
    a2 = rect(0, 0, 800, 800, pal.P4) + ''.join(f'<path d="M0 {100+i*100} H800 M{100+i*100} 0 V800" stroke="#fff" stroke-width="2"/>' for i in range(8)) + card(120, 140, 560, 380, '#fff', 6) + f'<path d="M180 460 V240 H360 V320 H620 V460 Z M360 320 V460" fill="none" stroke="{pal.P}" stroke-width="6"/>'
    a2 += figure(560, 820, .6, SKINS[2], HAIR[0], pal.S, 'short', '#fff', helmet=pal.S)
    cv.save('about-2.svg', 800, 800, a2, 'Site engineer with drawings')
    cv.save('svc-1.svg', W, H, spot(pal, pal.P4, rect(250, 160, 300, 270, mix(pal.INK, '#FFFFFF', .25)) + ''.join(rect(270 + c * 70, 180 + r * 60, 50, 40, pal.P4) for r in range(4) for c in range(4)) + crane(560, 430, 330, 160, pal.S)), 'Commercial construction')
    cv.save('svc-2.svg', W, H, spot(pal, pal.S3, f'<path d="M210 280 L400 150 L590 280Z" fill="{pal.P}"/>' + rect(240, 280, 320, 150, '#fff') + rect(370, 340, 60, 90, pal.S) + rect(270, 310, 70, 50, pal.P4) + rect(460, 310, 70, 50, pal.P4)), 'Residential projects')
    cv.save('svc-3.svg', W, H, spot(pal, pal.BG, ''.join(rect(220 + i * 70, 420 - (i + 2) * 40, 60, (i + 2) * 40, [pal.S, pal.P, pal.P2][i % 3]) for i in range(5)) + f'<path d="M200 420 h400" stroke="{pal.INK}" stroke-width="8"/>'), 'Civil and infrastructure')
    cv.save('svc-4.svg', W, H, spot(pal, pal.WARM, rect(240, 160, 320, 260, '#fff', 8) + rect(240, 160, 320, 50, pal.P, 8) + ''.join(rect(270 + c * 100, 240 + r * 60, 70, 40, pal.S3 if (r + c) % 2 else pal.P4) for r in range(3) for c in range(3))), 'Interior fit-outs')
    banner(cv, pal, 'city')


def legal(cv, pal, seed):
    stone = mix(pal.S, '#FFFFFF', .7)
    h = rect(0, 0, HW, HH, pal.INK) + rect(0, 760, HW, 240, mix(pal.INK, '#000', .2))
    h += f'<path d="M360 300 L800 120 L1240 300Z" fill="{stone}"/>' + rect(340, 300, 920, 34, stone) + ''.join(column(400 + i * 170, 740, 80, 400, stone, mix(stone, '#000', .12)) for i in range(5)) + rect(320, 740, 960, 30, stone) + rect(290, 770, 1020, 30, mix(stone, '#000', .08))
    h += circle(800, 220, 40, pal.S) + clouds(seed, HW, 100, 2, .08)
    h += ''.join(circle(x, y, 2, '#fff', .4) for x, y in ((120, 120), (240, 400), (1400, 160), (1500, 420), (1300, 80)))
    cv.save('hero.svg', HW, HH, h, 'Illustration of a classical courthouse facade')
    cv.save('about-1.svg', AW, AH, meeting(pal, f'<path d="M720 130 V290 M640 160 H800 M640 160 L610 230 H670Z M800 160 L770 230 H830Z" stroke="{pal.INK}" stroke-width="6" fill="{pal.S2}"/>', seed), 'Partners in a case meeting')
    a2 = rect(0, 0, 800, 800, mix(pal.INK, '#FFFFFF', .12)) + ''.join(rect(60, 150 + r * 170, 680, 14, mix(pal.S, '#000', .3)) + book_row(70, 150 + r * 170, 26, seed + r, [pal.P, pal.S, mix(pal.S, '#000', .3), '#7A2E3A', pal.P2]) for r in range(4))
    cv.save('about-2.svg', 800, 800, a2, 'Law library shelves')
    cv.save('svc-1.svg', W, H, spot(pal, pal.P4, f'<path d="M400 100 V400 M280 140 H520 M280 140 L230 260 H330Z M520 140 L470 260 H570Z" stroke="{pal.INK}" stroke-width="8" fill="{pal.S2}"/>' + rect(320, 400, 160, 26, pal.INK, 6), pal.WARM), 'Litigation')
    cv.save('svc-2.svg', W, H, spot(pal, pal.S3, card(250, 80, 300, 360, '#fff', 8) + text(280, 140, 'AGREEMENT', 26, pal.P) + lines(280, 170, 7, 230) + f'<path d="M300 400 q30 -40 60 0 t60 0" stroke="{pal.P}" stroke-width="4" fill="none"/>'), 'Corporate and contracts')
    cv.save('svc-3.svg', W, H, spot(pal, pal.BG, f'<path d="M220 420 V260 L400 150 L580 260 V420Z" fill="{pal.P}"/>' + rect(360, 320, 80, 100, pal.S) + card(470, 120, 160, 110, '#fff', 8) + text(490, 170, 'TITLE', 22, pal.P) + lines(490, 190, 2, 110)), 'Property law')
    cv.save('svc-4.svg', W, H, spot(pal, pal.WARM, ''.join(figure(300 + i * 200, 400, .38, SKINS[i * 2], HAIR[i], [pal.P, pal.S][i], ['short', 'long'][i], '#fff') for i in range(2)) + f'<path d="M340 230 Q400 190 460 230" stroke="{pal.S}" stroke-width="6" fill="none"/>'), 'Family and mediation')
    banner(cv, pal, 'city')


def agency(cv, pal, seed):
    h = rect(0, 0, HW, HH, pal.WARM) + circle(1250, 300, 260, pal.S, .9) + blob(420, 640, 360, pal.P3, .7)
    h += card(200, 200, 560, 380, '#fff', 24) + rect(230, 230, 500, 220, pal.P, 14) + circle(380, 340, 60, pal.S) + f'<path d="M230 450 L420 330 L560 410 L730 300 V450Z" fill="{pal.P1}"/>' + rect(230, 480, 260, 18, pal.INK, 9, .8) + rect(230, 512, 180, 14, '#D5DEE8', 7)
    h += card(860, 420, 420, 300, '#fff', 24) + bars(900, 680, 6, 40, 20, [60, 100, 80, 140, 190, 230], pal.P2, pal.S) + text(900, 480, '+184% reach', 34, pal.INK)
    h += card(560, 660, 340, 200, pal.INK, 24) + ''.join(circle(610 + i * 60, 740, 20, [pal.S, pal.P2, '#fff', pal.S2, pal.P3][i]) for i in range(5)) + rect(600, 790, 200, 14, '#fff', 7, .5)
    h += ''.join(rect(1180 + (i % 3) * 70, 150 + (i // 3) * 70, 56, 56, [pal.S2, '#fff', pal.P3][i % 3], 10) for i in range(6)) + f'<path d="M140 160 l30 -30 l30 30 l-30 30Z" fill="{pal.S}"/>'
    cv.save('hero.svg', HW, HH, h, 'Illustration of campaign designs and growth charts')
    cv.save('about-1.svg', AW, AH, meeting(pal, rect(550, 120, 160, 180, pal.S2, 8) + rect(730, 120, 160, 80, pal.P3, 8) + rect(730, 220, 160, 80, pal.S, 8), seed), 'Creative team brainstorm')
    a2 = rect(0, 0, 800, 800, pal.BG) + ''.join(rect(90 + (i % 4) * 160, 100 + (i // 4) * 150, 130, 120, [pal.S2, pal.P3, pal.S3, '#fff'][i % 4], 6, extra=f' transform="rotate({(i%3-1)*4} {155+(i%4)*160} {160+(i//4)*150})"') for i in range(8))
    a2 += figure(400, 820, .6, SKINS[5], HAIR[0], pal.S, 'short', '#fff')
    cv.save('about-2.svg', 800, 800, a2, 'Idea wall with sticky notes')
    cv.save('svc-1.svg', W, H, spot(pal, pal.P4, card(230, 90, 340, 320, '#fff', 18) + circle(400, 210, 70, pal.S) + rect(300, 310, 200, 16, pal.INK, 8) + rect(330, 340, 140, 12, '#D5DEE8', 6)), 'Brand identity')
    cv.save('svc-2.svg', W, H, spot(pal, pal.S3, rect(300, 80, 200, 350, pal.INK, 30) + rect(315, 110, 170, 290, '#fff', 10) + rect(330, 130, 140, 100, pal.P3, 8) + ''.join(circle(350 + i * 50, 270, 14, [pal.S, pal.P, pal.P2][i]) for i in range(3)) + lines(330, 310, 3, 140)), 'Social media')
    cv.save('svc-3.svg', W, H, spot(pal, pal.BG, monitor(200, 80, 400, 250, pal.INK, '#fff', rect(225, 105, 350, 40, pal.P, 4) + rect(225, 160, 160, 120, pal.S3, 4) + lines(400, 165, 4, 170, 26))), 'Websites and landing pages')
    cv.save('svc-4.svg', W, H, spot(pal, pal.WARM, f'<path d="M260 220 L460 140 V380 L260 300Z" fill="{pal.P}"/>' + rect(220, 220, 50, 80, pal.INK, 6) + f'<path d="M500 200 q40 60 0 120 M540 170 q70 90 0 180" stroke="{pal.S}" stroke-width="10" fill="none" stroke-linecap="round"/>'), 'Performance marketing')
    banner(cv, pal, 'waves')


import math  # noqa: E402  (used by engineering sparks)

NICHES = {'logistics': logistics, 'it': it, 'agro': agro, 'engineering': engineering, 'hr': hr,
          'solar': solar, 'construction': construction, 'legal': legal, 'agency': agency}


def generate(out_dir, niche, primary, secondary, seed=1, logo_names=None):
    cv = Canvas(out_dir)
    pal = Palette(primary, secondary)
    NICHES[niche](cv, pal, seed)
    portraits(cv, pal, seed)
    logos(cv, pal, logo_names or ['Aster', 'Vantage', 'Corvio', 'Pillar', 'Northline'])
