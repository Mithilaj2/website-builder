"""Builds demo.json (pages and settings) for a restaurant template."""
import json
import os
from restaurant_content import NICHES
from restaurant_images import DISH_LABELS

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__)))))
HOME_DEFAULT = ['hero', 'about', 'menu', 'features', 'gallery', 'testimonials', 'booking_form', 'cta']
SECTION_TYPES = ['hero', 'page_banner', 'about', 'menu', 'services', 'features', 'stats', 'testimonials', 'gallery', 'team', 'faq', 'cta', 'contact', 'logos', 'rich_text',
                 'process', 'booking_form', 'opening_hours', 'events']
FEATURES = ['contact']
OPTIONAL_FEATURES = ['payments']
ROOM_CAPTIONS = {'dining': 'The dining room', 'bar': 'The bar', 'kitchen': 'Our kitchen', 'cafe': 'The counter', 'terrace': 'The terrace', 'private': 'Private dining'}


def link(text, url):
    return {'text': text, 'url': url}


def legal_pages(name):
    src = json.load(open(os.path.join(ROOT, 'templates/restaurant/restaurant_template_01/demo.json')))
    out = {}
    for slug in ('privacy-policy', 'terms-and-conditions'):
        t = json.dumps(src['pages'][slug], ensure_ascii=False).replace('Saffron Table', name).replace('banner-spice.svg', 'banner.svg')
        out[slug] = json.loads(t)
    return out


def build(spec):
    c = NICHES[spec['niche']]
    d = spec.get('demo', {})
    img = lambda n: f'theme:images/{n}'
    name, hero = c['name'], c['hero']

    def dish(course, x):
        return {'course': course, 'name': x['name'], 'description': x['description'], 'price': x['price'], 'diet': x['diet'], 'spice': x['spice'], 'tag': x['tag'],
                'image': img(f'dish-{x["img"]}.svg') if x['img'] else ''}

    full = [dish(course, x) for course, xs in c['menu'].items() for x in xs]
    signature = [dish('Signatures', x) for xs in c['menu'].values() for x in xs if x['sig']][:6]
    gallery = [{'image': img(f'room-{r}.svg'), 'caption': ROOM_CAPTIONS[r]} for r in c['rooms']]
    seen = []
    for xs in c['menu'].values():
        for x in xs:
            if x['sig'] and x['img'] not in seen:
                seen.append(x['img'])
    gallery += [{'image': img(f'dish-{k}.svg'), 'caption': DISH_LABELS[k]} for k in seen][:6 - len(gallery)]
    b = c['booking']
    phone = c['phone']
    sections = {
        'hero': {'eyebrow': hero['eyebrow'], 'heading': hero['heading'], 'subheading': hero['sub'], 'image': img('hero.svg'), 'background_image': img('hero.svg') if d.get('hero_bg') else '',
                 'primary_button': link(d.get('book_label', 'Reserve a table'), 'page:reservations'), 'secondary_button': link('See the menu', 'page:menu'),
                 'highlights': [{'icon': ic, 'text': t} for ic, t in zip(['bi-clock', 'bi-star-fill', 'bi-geo-alt'], hero['highlights'])]},
        'about': {'eyebrow': 'Our story', 'heading': c['about']['heading'], 'description': ''.join(f'<p>{p}</p>' for p in c['about']['p']), 'image': img('about-1.svg'), 'image_2': img('about-2.svg'),
                  'experience_number': str(2026 - int(c['since'])), 'experience_label': c['about']['exp'], 'points': [{'text': t} for t in c['about']['points']], 'button': link('Our story', 'page:about')},
        'menu_home': {'eyebrow': 'Signature dishes', 'heading': d.get('menu_heading', 'What everyone orders'), 'description': 'A taste of the menu — see the full menu for everything we cook.',
                      'layout': 'list', 'show_images': True, 'items': signature, 'note': '', 'button': link('See the full menu', 'page:menu')},
        'menu': {'eyebrow': 'Our menu', 'heading': d.get('full_menu_heading', 'The menu'), 'description': '', 'layout': 'tabs', 'show_images': True, 'items': full,
                 'note': 'Prices include taxes. Please tell us about any allergies before you order.', 'button': {'text': '', 'url': ''}},
        'features': {'eyebrow': 'Why guests return', 'heading': d.get('features_heading', 'More than a meal'), 'description': '', 'image': '',
                     'items': [{'icon': i, 'title': t, 'description': x} for i, t, x in c['features']]},
        'gallery': {'eyebrow': 'Take a look', 'heading': d.get('gallery_heading', f'Inside {name}'), 'description': '', 'columns': '3', 'images': gallery},
        'testimonials': {'eyebrow': 'Guest book', 'heading': 'What our guests say', 'description': '',
                         'items': [{'quote': q, 'name': n, 'role': r, 'photo': img(f'guest-{i+1}.svg'), 'rating': 5} for i, (q, n, r) in enumerate(c['testimonials'])]},
        'booking_form': {'eyebrow': 'Reservations', 'heading': b['heading'], 'description': b['desc'], 'form_type': 'booking', 'form_heading': 'Table booking',
                         'select1_label': 'Guests', 'select1_options': b['guests'], 'select2_label': 'Time', 'select2_options': b['times'], 'date_label': 'Date',
                         'message_label': b['msg'], 'email_required': False, 'button_text': 'Request booking', 'success_message': b['success'], 'image': img(f'room-{c["rooms"][0]}.svg'),
                         'points': [{'icon': 'bi-clock', 'title': c['hours_rows'][0][0], 'text': c['hours_rows'][0][1]}, {'icon': 'bi-telephone', 'title': 'Large groups', 'text': f'Call {phone}'},
                                    {'icon': 'bi-whatsapp', 'title': 'Quick confirmation', 'text': 'We confirm every booking by phone or WhatsApp'}]},
        'cta': {'heading': c['cta'][0], 'description': c['cta'][1], 'background_image': img('cta.svg'), 'button': link('Plan an event', 'page:events'), 'secondary_button': link('Call us', 'tel:' + phone.replace(' ', ''))},
        'stats': {'heading': '', 'items': [{'number': n, 'suffix': s, 'label': l, 'icon': ''} for n, s, l in c['stats']]},
        'team': {'eyebrow': 'The kitchen', 'heading': 'The people behind the food', 'description': '',
                 'members': [dict(t, photo=img(f'chef-{i+1}.svg'), linkedin='') for i, t in enumerate(c['team'])]},
        'logos': {'heading': c.get('logos_heading', 'As featured in'), 'items': [{'image': img(f'logo-{i+1}.svg'), 'name': n, 'url': ''} for i, n in enumerate(c['logos'])]},
        'opening_hours': {'eyebrow': 'Opening hours', 'heading': d.get('hours_heading', 'Come hungry'), 'description': c['hours'], 'rows': [{'label': a, 'hours': h} for a, h in c['hours_rows']],
                          'highlight_label': 'Call us', 'highlight_value': phone, 'note': c['hours_note']},
        'faq': {'eyebrow': 'Good to know', 'heading': 'Before you visit', 'description': '', 'items': [{'question': q, 'answer': a} for q, a in c['faq']]},
        'services': {'eyebrow': 'Celebrate with us', 'heading': d.get('services_heading', 'Ways to host with us'), 'description': '', 'columns': '3',
                     'items': [{'icon': i, 'image': img(f'room-{c["rooms"][k % len(c["rooms"])]}.svg'), 'title': t, 'description': x, 'link': link('Enquire', 'page:contact')} for k, (i, t, x) in enumerate(c['services'])]},
        'events': {'eyebrow': 'What’s on', 'heading': 'Upcoming events', 'description': '', 'hide_past': True,
                   'items': [{'date': e['date'], 'time': e['time'], 'title': e['title'], 'location': e['location'], 'description': e['description'], 'image': img(f'room-{c["rooms"][k % len(c["rooms"])]}.svg'),
                              'link': link(e['button'], 'page:reservations')} for k, e in enumerate(c['events'])]},
    }

    def banner(heading, sub):
        return {'type': 'page_banner', 'data': {'heading': heading, 'subheading': sub, 'background_image': img('banner.svg')}}

    def sec(t):
        return {'type': 'menu' if t == 'menu_home' else t, 'data': json.loads(json.dumps(sections[t]))}

    home = [sec('menu_home' if t == 'menu' else t) for t in d.get('home', HOME_DEFAULT)]
    pages = {
        '': {'seo_title': '', 'sections': home},
        'menu': {'sections': [banner('Our menu', d.get('menu_sub', 'Made fresh every day.')), sec('menu'), sec('cta')]},
        'about': {'sections': [banner('About us', c['about_short']), sec('about'), sec('stats'), sec('team'), sec('logos')]},
        'events': {'sections': [banner('Events & private dining', 'Celebrations, gatherings and evenings to remember.'), sec('services'), sec('events'), sec('cta')]},
        'reservations': {'sections': [banner('Reservations', b['desc']), sec('booking_form'), sec('opening_hours'), sec('faq')]},
        'contact': {'sections': [banner('Contact', 'We would love to hear from you.'),
                                 {'type': 'contact', 'data': {'eyebrow': 'Get in touch', 'heading': 'Visit, call or write to us', 'description': 'For bookings please use the reservations page or call us. For events, catering and feedback, send us a message.',
                                                              'form_heading': 'Send a message', 'show_form': True, 'show_details': True, 'map_url': ''}}, sec('opening_hours')]},
    }
    pages.update(legal_pages(name))
    settings = {'site': {'name': name, 'tagline': c['tagline'], 'phone': phone, 'email': c['email'], 'address': c['address'], 'hours': c['hours'],
                         'footer_about': c['about_short'], 'footer_text': 'All rights reserved. ' + c['fssai'] + '.', 'header_button': link(d.get('header_button', 'Book a table'), 'page:reservations'),
                         'social': {'instagram': 'https://www.instagram.com/', 'facebook': 'https://www.facebook.com/', 'youtube': 'https://www.youtube.com/'}},
                'seo': {'description': f'{name}, {c["city"]}: {c["about_short"]} Book a table online.', 'keywords': f'{c["tagline"].lower()}, restaurant in {c["city"]}, book a table {c["city"]}'}}
    return {'settings': settings, 'pages': pages}


def page_types(demo):
    t = lambda slug: [s['type'] for s in demo['pages'][slug]['sections']]
    return {'home': t(''), 'menu': t('menu'), 'about': t('about'), 'events': t('events'), 'reservations': t('reservations'), 'contact': t('contact'),
            'legal': ['page_banner', 'rich_text'], 'generic': ['page_banner', 'rich_text', 'cta']}


DEFAULT_PAGES = [
    {'title': 'Home', 'slug': '', 'type': 'home', 'is_home': True},
    {'title': 'Menu', 'slug': 'menu', 'type': 'menu'},
    {'title': 'About', 'slug': 'about', 'type': 'about'},
    {'title': 'Events', 'slug': 'events', 'type': 'events'},
    {'title': 'Reservations', 'slug': 'reservations', 'type': 'reservations'},
    {'title': 'Contact', 'slug': 'contact', 'type': 'contact'},
    {'title': 'Privacy Policy', 'slug': 'privacy-policy', 'type': 'legal', 'in_menu': False, 'in_footer': True},
    {'title': 'Terms and Conditions', 'slug': 'terms-and-conditions', 'type': 'legal', 'in_menu': False, 'in_footer': True},
]
