"""Restaurant scenes. generate(out_dir, niche, primary, secondary, seed, logo_names) writes:
dish-<kind>.svg (800x800 top-down plates, one per dish kind used), hero.svg (1600x1000 table spread), room-<kind>.svg (1200x900 interiors),
about-1.svg (1000x850 kitchen with chefs), about-2.svg (800x800), banner.svg (1600x500), cta.svg (1600x700), chef-1..4, guest-1..3, logo-1..4."""
import math
import random
from kit import *  # noqa: F401,F403
from business_images import logos

from restaurant_content import NICHES as CONTENT

WOOD = '#8A5A3B'
WOOD2 = '#6E4630'
PLATE = '#FBF8F2'
RIM = '#E9E1D3'
CURRY = '#D9822B'
CURRY2 = '#B85C1E'
RICE = '#F4E7C5'
GREEN = '#5E9E3E'
RED = '#C8402D'
CREAM = '#FFF4DC'
BROWN = '#7A4A2A'


def plate(cx, cy, r, fill=PLATE, rim=RIM):
    return circle(cx, cy + r * .06, r, '#000', .12) + circle(cx, cy, r, rim) + circle(cx, cy, r * .82, fill)


def bowl(cx, cy, r, fill, inner, rim='#F3EEE6'):
    return circle(cx, cy + r * .08, r, '#000', .14) + circle(cx, cy, r, rim) + circle(cx, cy, r * .84, fill) + circle(cx - r * .2, cy - r * .2, r * .5, inner, .55)


def blobs(cx, cy, r, n, c, size, seed, op=1):
    random.seed(seed)
    out = ''
    for _ in range(n):
        a, d = random.uniform(0, 6.283), random.uniform(0, r)
        out += circle(cx + math.cos(a) * d, cy + math.sin(a) * d, size * random.uniform(.6, 1.2), c, op)
    return out


def leaves(cx, cy, r, n, seed, c=GREEN):
    random.seed(seed)
    out = ''
    for _ in range(n):
        a, d = random.uniform(0, 6.283), random.uniform(0, r)
        x, y = cx + math.cos(a) * d, cy + math.sin(a) * d
        out += f'<ellipse cx="{x:.0f}" cy="{y:.0f}" rx="{r*.07:.0f}" ry="{r*.035:.0f}" fill="{c}" transform="rotate({random.randint(0,180)} {x:.0f} {y:.0f})"/>'
    return out


# ----------------------------------------------------------------------------- dishes (drawn on an 800x800 canvas, centred)

def d_curry(c=CURRY, c2=CURRY2):
    return bowl(400, 400, 260, c, '#F0B060') + f'<path d="M260 380 q140 -60 280 0" stroke="{CREAM}" stroke-width="16" fill="none" opacity=".85"/>' + leaves(400, 400, 160, 10, 1)


def d_dal():
    return bowl(400, 400, 260, '#7A3A22', '#A0522D') + f'<path d="M280 360 q120 60 240 0" stroke="{CREAM}" stroke-width="18" fill="none" opacity=".9"/>' + circle(400, 360, 30, '#F6E7A8')


def d_biryani():
    out = bowl(400, 400, 270, RICE, '#E8C77A', '#3A2A22')
    random.seed(3)
    out += ''.join(f'<ellipse cx="{400+random.uniform(-180,180):.0f}" cy="{400+random.uniform(-180,180):.0f}" rx="16" ry="6" fill="{random.choice([RICE,"#E9A23B","#F8F1DE"])}" transform="rotate({random.randint(0,180)} 400 400)"/>' for _ in range(160))
    return out + blobs(400, 400, 140, 6, '#8C4A2A', 34, 4) + leaves(400, 400, 170, 10, 5) + circle(500, 330, 26, '#F6F0E0') + circle(500, 330, 12, '#F2B632')


def d_dosa():
    out = plate(400, 400, 320) + f'<path d="M150 470 Q400 230 650 360 L640 420 Q400 330 160 520Z" fill="#D9A04E"/><path d="M160 500 Q400 300 645 395" stroke="#B57A2E" stroke-width="8" fill="none"/>'
    return out + bowl(260, 600, 70, '#F4F1E8', '#fff') + bowl(420, 640, 70, CURRY, '#E8A060') + bowl(570, 600, 70, '#C2503A', '#E07A5F')


def d_idli():
    out = plate(400, 400, 320)
    for x, y in ((300, 330), (450, 300), (380, 450)):
        out += f'<ellipse cx="{x}" cy="{y}" rx="95" ry="70" fill="#FBF6EC"/><ellipse cx="{x}" cy="{y-14}" rx="70" ry="40" fill="#fff" opacity=".7"/>'
    return out + bowl(560, 520, 80, CURRY, '#E8A060') + bowl(260, 560, 66, '#F4F1E8', '#fff') + leaves(560, 520, 50, 4, 7)


def d_thali():
    out = circle(400, 405, 340, '#000', .14) + circle(400, 400, 340, '#C9CDD2') + circle(400, 400, 315, '#DDE1E5')
    for i, (c, i2) in enumerate(((CURRY, '#F0B060'), ('#7A3A22', '#A0522D'), (GREEN, '#8BC46A'), ('#F4F1E8', '#fff'), ('#C2503A', '#E07A5F'))):
        a = i * 1.256 - 1.57
        out += bowl(400 + math.cos(a) * 210, 400 + math.sin(a) * 210, 72, c, i2, '#B9BEC4')
    return out + f'<ellipse cx="400" cy="400" rx="110" ry="80" fill="{RICE}"/>' + circle(400, 400, 60, '#E8C77A', .5)


def d_naan():
    out = f'<path d="M180 260 Q400 150 620 270 Q680 420 600 560 Q400 660 200 560 Q120 420 180 260Z" fill="#E7B976"/>'
    return out + blobs(400, 400, 200, 22, '#B5762E', 18, 8, .8) + blobs(400, 400, 200, 12, GREEN, 8, 9)


def d_kebab():
    out = plate(400, 400, 320) + ''
    for k, dy in enumerate((-90, 0, 90)):
        out += f'<path d="M140 {400+dy} L660 {400+dy-40}" stroke="#9A9A9A" stroke-width="8"/>' + ''.join(f'<ellipse cx="{220+i*95}" cy="{400+dy-7*i}" rx="48" ry="30" fill="{["#9A4A22", "#B5582A", "#7E3A1A"][(i+k)%3]}"/>' for i, _ in enumerate(range(5)))
    return out + leaves(400, 400, 260, 8, 10) + circle(600, 560, 40, '#F2D06B')


def d_paneer():
    out = plate(400, 400, 320)
    for i in range(6):
        x, y = 260 + (i % 3) * 140, 330 + (i // 3) * 130
        out += rect(x - 50, y - 40, 100, 80, '#F6E6C3', 12) + rect(x - 50, y - 40, 100, 30, '#C86A2A', 12, op=.8)
    return out + blobs(400, 400, 220, 10, '#5E9E3E', 20, 11) + blobs(400, 400, 220, 8, RED, 18, 12)


def d_pizza():
    out = circle(400, 410, 330, '#000', .12) + circle(400, 400, 330, '#D9A04E') + circle(400, 400, 290, '#C8402D') + blobs(400, 400, 250, 26, '#F6E7B8', 34, 13)
    out += blobs(400, 400, 240, 12, '#6E9A3E', 22, 14) + blobs(400, 400, 240, 10, '#7A1E1E', 20, 15)
    return out + ''.join(f'<path d="M400 400 L{400+330*math.cos(a)} {400+330*math.sin(a)}" stroke="#B8842E" stroke-width="4"/>' for a in [i * 1.047 for i in range(6)])


def d_pasta():
    out = plate(400, 400, 320) + circle(400, 400, 200, '#F2D37A')
    out += ''.join(f'<path d="M{260+i*14} {300+(i%5)*30} q{60} {-40} {120} 0 t120 0" stroke="#E9BE55" stroke-width="12" fill="none"/>' for i in range(14))
    return out + blobs(400, 400, 120, 8, RED, 26, 16) + leaves(400, 400, 140, 6, 17) + blobs(400, 400, 150, 14, '#F8F1DE', 8, 18)


def d_burger():
    out = plate(400, 420, 320)
    out += f'<path d="M190 430 Q400 160 610 430Z" fill="#D9963E"/>' + rect(180, 430, 440, 30, GREEN, 12) + rect(190, 460, 420, 40, '#6E3A1E', 14) + rect(180, 500, 440, 26, '#F2C14E', 8) + rect(195, 526, 410, 50, '#D9963E', 20)
    return out + blobs(400, 330, 140, 14, '#FFF3D6', 6, 19)


def d_sushi():
    out = rect(110, 260, 580, 300, '#2E2A26', 20)
    for i in range(4):
        x = 190 + i * 140
        out += circle(x, 400, 56, '#1E3A2A') + circle(x, 400, 44, '#FBF6EC') + circle(x, 400, 22, ['#F28B66', '#7DBB5A', '#F2C14E', '#F28B66'][i])
    return out + f'<path d="M150 580 L650 520" stroke="#C8A274" stroke-width="12" stroke-linecap="round"/><path d="M160 610 L660 550" stroke="#C8A274" stroke-width="12" stroke-linecap="round"/>'


def d_ramen():
    out = bowl(400, 400, 290, '#E8B66A', '#F2CC88', '#2E2A26')
    out += ''.join(f'<path d="M{250+i*20} 360 q40 30 80 0 t80 0" stroke="#F4E3A1" stroke-width="10" fill="none"/>' for i in range(8))
    out += circle(320, 300, 50, '#FBF6EC') + circle(320, 300, 24, '#F2B632') + rect(430, 280, 120, 70, '#C77A5A', 20) + leaves(400, 420, 160, 14, 20)
    return out + f'<path d="M560 180 L700 120 M580 200 L720 140" stroke="#C8A274" stroke-width="12" stroke-linecap="round"/>'


def d_dimsum():
    out = circle(400, 405, 320, '#000', .12) + circle(400, 400, 320, '#C8A274') + circle(400, 400, 290, '#E8D2A6')
    for x, y in ((300, 320), (500, 320), (300, 480), (500, 480), (400, 400)):
        out += circle(x, y, 70, '#F8F1E4') + ''.join(f'<path d="M{x} {y} L{x+60*math.cos(a)} {y+60*math.sin(a)}" stroke="#E6D8BE" stroke-width="5"/>' for a in [k * .785 for k in range(8)]) + circle(x, y, 14, '#F28B66')
    return out


def d_noodles():
    out = bowl(400, 400, 280, '#C97B2E', '#E09A4A', '#2E2A26')
    out += ''.join(f'<path d="M{230+i*12} {330+(i%6)*20} q60 40 120 0 t120 0" stroke="#E9BE55" stroke-width="9" fill="none"/>' for i in range(14))
    return out + blobs(400, 400, 160, 10, GREEN, 18, 21) + blobs(400, 400, 160, 8, RED, 14, 22) + blobs(400, 400, 160, 8, '#F2C14E', 14, 23)


def d_momos():
    out = plate(400, 400, 320)
    for x, y in ((290, 330), (430, 300), (520, 420), (380, 460), (260, 470)):
        out += f'<path d="M{x-60} {y+20} Q{x} {y-70} {x+60} {y+20} Q{x} {y+50} {x-60} {y+20}Z" fill="#F8F1E4"/>' + ''.join(f'<path d="M{x-30+k*15} {y-20} l5 30" stroke="#E6D8BE" stroke-width="4"/>' for k in range(5))
    return out + bowl(580, 580, 66, RED, '#E07A5F')


def d_fish():
    out = plate(400, 400, 320) + f'<path d="M180 400 Q330 280 520 360 L620 300 L600 400 L620 500 L520 440 Q330 520 180 400Z" fill="#C8642D"/>'
    out += ''.join(f'<path d="M{280+i*50} 340 q-20 60 0 120" stroke="#9A4A1A" stroke-width="5" fill="none"/>' for i in range(5)) + circle(230, 390, 12, '#2A1A12')
    return out + leaves(400, 400, 260, 10, 24) + f'<path d="M560 560 a40 40 0 0 1 80 0Z" fill="#F2D06B"/>'


def d_prawns():
    out = bowl(400, 400, 280, '#D9822B', '#F0B060')
    for i in range(6):
        a = i * 1.047
        x, y = 400 + math.cos(a) * 140, 400 + math.sin(a) * 140
        out += f'<path d="M{x-40} {y} a44 44 0 1 1 60 30" stroke="#F28B66" stroke-width="26" fill="none" stroke-linecap="round"/>'
    return out + leaves(400, 400, 180, 10, 25)


def d_steak():
    out = plate(400, 400, 320) + f'<path d="M220 330 Q380 240 560 320 Q600 440 480 500 Q300 540 220 450Z" fill="#6E2E1E"/>'
    out += ''.join(f'<path d="M{260+i*60} 310 l-60 160" stroke="#3E1A10" stroke-width="10" opacity=".6"/>' for i in range(5))
    return out + blobs(560, 540, 70, 8, '#F2D06B', 26, 26) + leaves(300, 560, 70, 8, 27)


def d_salad():
    out = bowl(400, 400, 280, '#F4F1E8', '#fff')
    return out + leaves(400, 400, 220, 40, 28) + leaves(400, 400, 200, 16, 29, '#8BC46A') + blobs(400, 400, 180, 10, RED, 22, 30) + blobs(400, 400, 180, 10, '#F2D06B', 16, 31)


def d_cake():
    out = plate(400, 420, 320) + f'<path d="M260 470 L400 220 L560 470Z" fill="#6E3A1E"/>' + f'<path d="M280 430 L400 220 L540 430" stroke="#F6E7C8" stroke-width="10" fill="none"/>'
    return out + circle(400, 250, 30, RED) + rect(290, 470, 260, 40, '#5A2E16', 6) + blobs(560, 560, 40, 6, '#F28B66', 16, 32)


def d_cheesecake():
    out = plate(400, 400, 320) + f'<path d="M230 470 L400 250 L580 470Z" fill="#F6E7B8"/>' + rect(230, 470, 350, 40, '#C89A5A', 6) + f'<path d="M230 470 Q300 420 350 470 Q420 420 480 470 Q540 430 580 470" stroke="#C8402D" stroke-width="22" fill="none"/>'
    return out + blobs(560, 560, 50, 6, '#C8402D', 18, 33) + leaves(560, 560, 50, 3, 34)


def d_croissant():
    out = plate(400, 400, 320) + f'<path d="M180 450 Q240 280 400 270 Q560 280 620 450 Q560 420 520 440 Q460 340 400 340 Q340 340 280 440 Q240 420 180 450Z" fill="#D99A3E"/>'
    return out + ''.join(f'<path d="M{300+i*50} 300 q10 60 -10 110" stroke="#A8692A" stroke-width="7" fill="none"/>' for i in range(5)) + bowl(570, 590, 60, '#C8402D', '#E07A5F')


def d_coffee():
    out = circle(400, 410, 300, '#000', .1) + circle(400, 400, 300, '#F4EEE4') + circle(400, 400, 170, '#fff') + circle(400, 400, 140, '#7A4A2A') + circle(400, 400, 120, '#C08A5A')
    out += f'<path d="M400 330 c-50 -40 -110 30 0 110 c110 -80 50 -150 0 -110Z" fill="#F6E7D0"/>'
    return out + f'<path d="M540 400 a50 40 0 0 1 80 0 a50 40 0 0 1 -80 0" stroke="#fff" stroke-width="22" fill="none"/>' + rect(170, 600, 120, 22, '#B88A5A', 10)


def d_filter_coffee():
    out = circle(400, 420, 280, '#000', .1) + circle(400, 400, 280, '#B9BEC4') + circle(400, 400, 250, '#D9DDE2')
    out += circle(400, 400, 130, '#9EA4AB') + circle(400, 400, 110, '#C8A06A') + circle(400, 400, 80, '#E3C18E', .7)
    return out + text(400, 620, '', 10, '#000')


def d_cocktail():
    out = circle(400, 400, 300, mix('#1E2A3A', '#000', .1)) + circle(400, 400, 170, '#F2F6F8', .35) + circle(400, 400, 150, '#F28B66', .85) + circle(400, 400, 120, '#F6B27A', .7)
    return out + circle(470, 330, 46, '#F2D06B') + circle(470, 330, 30, '#F6E7A8') + leaves(360, 440, 60, 5, 35) + blobs(400, 400, 110, 8, '#fff', 10, 36, .7)


def d_icecream():
    out = bowl(400, 400, 270, '#F4F1E8', '#fff')
    for x, y, c in ((330, 360, '#F6C6D8'), (470, 360, '#F6E7B8'), (400, 470, '#7A4A2A')):
        out += circle(x, y, 90, c) + circle(x - 20, y - 20, 40, '#fff', .35)
    return out + blobs(400, 400, 140, 14, '#C8402D', 10, 37) + leaves(400, 300, 30, 3, 38)


def d_tandoori():
    out = plate(400, 400, 320)
    for i in range(3):
        a = i * 2.09
        x, y = 400 + math.cos(a) * 110, 400 + math.sin(a) * 110
        out += f'<ellipse cx="{x:.0f}" cy="{y:.0f}" rx="110" ry="70" fill="#C8402D" transform="rotate({i*60} {x:.0f} {y:.0f})"/>' + f'<ellipse cx="{x:.0f}" cy="{y:.0f}" rx="70" ry="36" fill="#9A2A1A" opacity=".5" transform="rotate({i*60} {x:.0f} {y:.0f})"/>'
    return out + f'<ellipse cx="400" cy="400" rx="60" ry="18" fill="#F2F0E6"/>' + leaves(400, 400, 260, 10, 39) + circle(600, 560, 40, '#F2D06B')


def d_lassi():
    out = circle(400, 400, 300, '#8A5A3B') + circle(400, 400, 190, '#C9CDD2') + circle(400, 400, 160, '#FFF4DC') + circle(400, 400, 160, '#F6D27A', .3)
    return out + blobs(400, 400, 100, 8, '#E9A23B', 10, 40) + leaves(420, 380, 40, 3, 41)


def d_bread():
    out = rect(140, 220, 520, 360, WOOD, 40) + f'<path d="M210 460 Q220 280 400 270 Q580 280 590 460Z" fill="#C88A3E"/>'
    return out + ''.join(f'<path d="M{300+i*70} 300 q-20 60 0 130" stroke="#8A5A2A" stroke-width="10" fill="none"/>' for i in range(4)) + rect(240, 470, 320, 70, '#E8C48A', 20)


def d_waffle():
    out = plate(400, 400, 320) + rect(240, 240, 320, 320, '#D99A3E', 30)
    out += ''.join(f'<path d="M{240+i*64} 240 V560 M240 {240+i*64} H560" stroke="#A8692A" stroke-width="8"/>' for i in range(1, 5))
    return out + blobs(400, 360, 80, 8, '#4A6FA5', 22, 42) + blobs(400, 360, 80, 8, '#C8402D', 18, 43) + f'<path d="M300 300 q100 80 200 0" stroke="#7A3A12" stroke-width="12" fill="none" opacity=".8"/>'


def d_tacos():
    out = plate(400, 400, 320)
    for x in (260, 400, 540):
        out += f'<path d="M{x-80} 470 a80 110 0 0 1 160 0Z" fill="#E9BE55"/>' + blobs(x, 420, 50, 6, GREEN, 14, x) + blobs(x, 420, 50, 5, RED, 12, x + 1)
    return out


DISHES = {k[2:]: v for k, v in globals().items() if k.startswith('d_')}
DISH_LABELS = {'curry': 'A bowl of curry', 'dal': 'A bowl of dal', 'biryani': 'A pot of biryani', 'dosa': 'A dosa with chutneys', 'idli': 'Idli with sambar', 'thali': 'A thali',
               'naan': 'Naan bread', 'kebab': 'Kebab skewers', 'paneer': 'Paneer tikka', 'pizza': 'A pizza', 'pasta': 'A plate of pasta', 'burger': 'A burger', 'sushi': 'Sushi rolls',
               'ramen': 'A bowl of ramen', 'dimsum': 'A basket of dim sum', 'noodles': 'A bowl of noodles', 'momos': 'A plate of momos', 'fish': 'A whole grilled fish',
               'prawns': 'A bowl of prawns', 'steak': 'A grilled steak', 'salad': 'A salad bowl', 'cake': 'A slice of chocolate cake', 'cheesecake': 'A slice of cheesecake',
               'croissant': 'A croissant', 'coffee': 'A cup of coffee', 'filter_coffee': 'A tumbler of filter coffee', 'cocktail': 'A cocktail', 'icecream': 'A bowl of ice cream',
               'tandoori': 'Tandoori chicken', 'lassi': 'A glass of lassi', 'bread': 'A loaf of bread', 'waffle': 'A waffle with berries', 'tacos': 'Tacos'}


def dish_svg(kind, bg):
    return rect(0, 0, 800, 800, bg) + DISHES[kind]()


# ----------------------------------------------------------------------------- rooms

def pendant(x, y, len_, c, glow=True):
    return f'<path d="M{x} 0 V{y}" stroke="#333" stroke-width="3"/><path d="M{x-40} {y+40} Q{x} {y-10} {x+40} {y+40}Z" fill="{c}"/>' + (circle(x, y + 44, 60, '#FFD98A', .18) if glow else '')


def table_set(x, y, s, cloth, pal):
    t = f'<g transform="translate({x} {y}) scale({s})">'
    t += f'<ellipse cx="0" cy="0" rx="160" ry="40" fill="{cloth}"/>' + rect(-150, 0, 300, 70, cloth, 0) + rect(-10, 70, 20, 90, '#3A2A22')
    t += f'<ellipse cx="-70" cy="-6" rx="34" ry="10" fill="#fff"/><ellipse cx="70" cy="-6" rx="34" ry="10" fill="#fff"/>' + rect(-4, -60, 8, 50, '#F6E7C8') + circle(0, -66, 8, '#FFC966')
    for dx in (-200, 200):
        t += rect(dx - 34, -60, 68, 150, pal.P1, 16)
    return t + '</g>'


def room(kind, pal, w=1200, h=900, dark=False):
    wall = mix(pal.P, '#1B1512', .75) if dark else mix(pal.S, '#FFF8EE', .85)
    floor = '#3A2A22' if dark else '#B88A5E'
    b = rect(0, 0, w, h, wall) + rect(0, h * .7, w, h * .3, floor) + rect(0, h * .7 - 6, w, 10, mix(floor, '#000', .25))
    if kind == 'dining':
        b += ''.join(rect(80 + i * 380, 120, 260, 300, mix(wall, '#FFFFFF', .15) if not dark else '#2A201A', 140) for i in range(3))
        b += ''.join(pendant(260 + i * 340, 200, 200, pal.S) for i in range(3))
        b += table_set(300, 640, 1, '#F4EEE4', pal) + table_set(900, 660, 1.05, '#F4EEE4', pal) + plant(60, h * .7 + 4, 1.1, pal.S, '#4E8A4C', '#3B6E3A')
    elif kind == 'bar':
        b += rect(100, 140, 1000, 320, '#2A201A', 10) + ''.join(rect(140 + i * 60, 180 + (i % 3) * 90, 26, 70, ['#C8642D', '#5E9E3E', '#E9C46A', '#8A2E3A', '#2E6E9E'][i % 5], 8) for i in range(15))
        b += ''.join(rect(120, 250 + k * 90, 960, 8, '#5A4030') for k in range(3))
        b += rect(60, 520, 1080, 110, WOOD2, 10) + rect(40, 500, 1120, 30, WOOD) + ''.join(rect(160 + i * 220, 630, 16, 200, '#222') + circle(168 + i * 220, 630, 48, pal.S) for i in range(5))
        b += ''.join(pendant(240 + i * 240, 90, 90, '#E9C46A') for i in range(4))
    elif kind == 'kitchen':
        b += rect(0, 0, w, 420, '#D9DDE2') + ''.join(f'<path d="M{i*100} 0 V420 M0 {k*70} H{w}" stroke="#fff" stroke-width="3"/>' for i in range(13) for k in range(1))
        b += rect(80, 440, 1040, 180, '#9EA4AB') + rect(70, 420, 1060, 30, '#C9CDD2') + ''.join(circle(200 + i * 160, 420, 40, '#333') for i in range(6))
        b += circle(980, 300, 120, '#5A3A2A') + circle(980, 300, 80, '#F28B3A', .8) + text(980, 470, '', 10, '#000')
        b += figure(420, 900, 1.1, SKINS[2], HAIR[0], '#FFFFFF', 'short') + figure(720, 900, 1.05, SKINS[4], HAIR[3], '#FFFFFF', 'bun')
    elif kind == 'cafe':
        b += rect(0, 0, w, 120, mix(pal.P, '#fff', .6)) + text(w / 2, 80, 'MENU', 46, pal.P1, 800, 'middle')
        b += rect(80, 160, 520, 260, '#2A2A2A', 10) + ''.join(rect(110, 200 + k * 40, 300, 10, '#E9E9E9', 4, op=.7) for k in range(5)) + ''.join(rect(460, 200 + k * 40, 100, 10, '#F2C14E', 4) for k in range(5))
        b += rect(680, 200, 420, 220, '#fff', 10) + ''.join(circle(760 + i * 90, 340, 34, ['#C88A3E', '#6E3A1E', '#F6C6D8', '#D99A3E'][i]) for i in range(4))
        b += rect(60, 480, 1080, 170, WOOD, 12) + rect(40, 460, 1120, 30, '#E9DCC8') + rect(820, 360, 160, 110, '#5A5A5A', 10) + ''.join(pendant(240 + i * 360, 110, 60, pal.S) for i in range(3))
    elif kind == 'terrace':
        b = sky(w, h, mix(pal.P, '#1B2440', .6), mix(pal.S, '#FFC9A0', .5), 'tr') + skyline(w, h * .62, mix(pal.P, '#0B1430', .4), 4, 1, 80, 300) + rect(0, h * .62, w, h * .38, '#2A201A')
        b += ''.join(f'<path d="M0 {60+i*14} Q{w/2} {120+i*14} {w} {60+i*14}" stroke="#FFD98A" stroke-width="2" fill="none" opacity=".6"/>' for i in range(2))
        b += ''.join(circle(80 + i * 90, 80 + 40 * math.sin(i), 8, '#FFD98A') for i in range(13)) + table_set(330, 700, 1, pal.S, pal) + table_set(880, 720, 1, pal.S, pal)
        b += rect(0, h * .62, w, 20, '#5A4030') + plant(1140, 900, 1.2, pal.P, '#4E8A4C', '#3B6E3A')
    elif kind == 'private':
        b += rect(200, 140, 800, 300, '#2A201A' if dark else mix(pal.P, '#fff', .7), 8) + ''.join(rect(240 + i * 190, 180, 140, 220, mix(pal.S, '#fff', .5), 70) for i in range(4))
        b += rect(160, 560, 880, 60, '#F4EEE4', 10) + rect(150, 620, 900, 80, '#E9E1D3')
        b += ''.join(rect(200 + i * 110, 480, 70, 90, pal.P1, 14) for i in range(8)) + ''.join(rect(590 - 2, 500, 4, 50, '#F6E7C8') + circle(590, 495, 6, '#FFC966') for _ in range(1))
        b += pendant(600, 120, 0, '#E9C46A')
    return b


def hero_table(pal, w=1600, h=1000, kinds=None, cloth=None):
    b = rect(0, 0, w, h, cloth or WOOD) + ''.join(f'<path d="M0 {i*90} H{w}" stroke="#000" stroke-opacity=".08" stroke-width="3"/>' for i in range(12))
    spots = [(330, 300, .62), (800, 520, .78), (1260, 300, .6), (560, 850, .5), (1080, 860, .52), (130, 760, .42), (1470, 700, .45)]
    for (x, y, s), k in zip(spots, (kinds * 3)[:len(spots)]):
        b += f'<g transform="translate({x-400*s} {y-400*s}) scale({s})">{DISHES[k]()}</g>'
    b += f'<path d="M560 200 L700 120" stroke="#C9CDD2" stroke-width="14" stroke-linecap="round"/><path d="M1000 960 L1120 900" stroke="#C9CDD2" stroke-width="14" stroke-linecap="round"/>'
    return b + leaves(800, 500, 760, 26, 3)


def about_two(pal, kind):
    return rect(0, 0, 800, 800, pal.S3) + circle(400, 400, 310, pal.P4) + f'<g transform="translate(70 70) scale(.83)">{DISHES[kind]()}</g>'


def generate(out_dir, niche, primary, secondary, seed=1, logo_names=None):
    cv = Canvas(out_dir)
    pal = Palette(primary, secondary)
    c = CONTENT[niche]
    dark = c.get('dark', False)
    kinds = []
    for course in c['menu'].values():
        for d in course:
            if d['img'] and d['img'] not in kinds:
                kinds.append(d['img'])
    bg = mix(pal.P, '#15110E', .82) if dark else mix(pal.S, '#FFF8EE', .8)
    for k in kinds:
        cv.save(f'dish-{k}.svg', 800, 800, dish_svg(k, bg), DISH_LABELS[k])
    for r in c['rooms']:
        cv.save(f'room-{r}.svg', 1200, 900, room(r, pal, dark=dark), {'dining': 'The dining room', 'bar': 'The bar', 'kitchen': 'Chefs in the kitchen', 'cafe': 'The café counter',
                                                                     'terrace': 'The rooftop terrace', 'private': 'The private dining room'}[r])
    cv.save('hero.svg', 1600, 1000, hero_table(pal, kinds=c['hero_dishes'], cloth=c.get('table')), 'A table spread with signature dishes')
    cv.save('about-1.svg', 1000, 850, room('kitchen', pal, 1000, 850, dark), 'Chefs at work in the kitchen')
    cv.save('about-2.svg', 800, 800, about_two(pal, c['hero_dishes'][0]), DISH_LABELS[c['hero_dishes'][0]])
    cv.save('banner.svg', 1600, 500, hero_table(pal, 1600, 500, c['hero_dishes'][1:] + c['hero_dishes'][:1], c.get('table')), 'Decorative banner')
    cv.save('cta.svg', 1600, 700, room(c['rooms'][-1], pal, 1600, 700, dark), 'Private dining')
    for i in range(4):
        portrait(cv, f'chef-{i+1}.svg', pal, SKINS[(i * 2 + seed) % 6], HAIR[i % 5], ['#FFFFFF', pal.INK, '#FFFFFF', pal.P][i], ['short', 'bun', 'grey', 'long'][i], i + seed)
    for i in range(3):
        portrait(cv, f'guest-{i+1}.svg', pal, SKINS[(i * 3 + seed) % 6], HAIR[(i + 1) % 5], [pal.S, pal.P2, pal.P][i], ['long', 'short', 'bun'][i], i + 1, 400, 400)
    logos(cv, pal, logo_names or c['logos'])
