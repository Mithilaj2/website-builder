"""Builds demo.json (pages, settings and the listings) and the manifest's listings block for a real-estate template."""
import json
import os
from real_estate_content import NICHES

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__)))))
HOME_DEFAULT = ['hero', 'listing_categories', 'listings', 'about', 'stats', 'services', 'listings_new', 'testimonials', 'logos', 'cta']
SECTION_TYPES = ['hero', 'page_banner', 'about', 'services', 'features', 'stats', 'testimonials', 'gallery', 'team', 'faq', 'cta', 'contact', 'logos', 'rich_text', 'process',
                 'listings', 'listing_categories']
FEATURES = ['contact']
OPTIONAL_FEATURES = ['payments']
ROOM_GALLERY = {'tower': ['living', 'kitchen', 'bedroom', 'balcony', 'bath', 'clubhouse'], 'penthouse': ['living', 'balcony', 'bedroom', 'kitchen', 'pool', 'bath'],
                'villa': ['living', 'pool', 'bedroom', 'garden', 'kitchen', 'bath'], 'bungalow': ['living', 'garden', 'bedroom', 'kitchen', 'bath'],
                'townhouse': ['living', 'kitchen', 'bedroom', 'garden', 'bath'], 'cottage': ['living', 'balcony', 'bedroom', 'garden'],
                'farmhouse': ['pool', 'living', 'garden', 'bedroom'], 'heritage': ['living', 'bedroom', 'balcony', 'bath'],
                'office': ['office', 'cabin', 'clubhouse'], 'retail': ['road', 'office', 'cabin'], 'warehouse': ['road', 'office'],
                'plot': ['road', 'garden', 'clubhouse'], 'layout': ['road', 'garden', 'clubhouse']}


def link(text, url):
    return {'text': text, 'url': url}


def listings_config(spec):
    c = NICHES[spec['niche']]
    return {'path': c['path'], 'singular': c['singular'], 'plural': c['plural'], 'menu_label': c['menu_label'], 'icon': 'bi-buildings',
            'category_label': c['category_label'], 'location_label': c['location_label'], 'price_label': c.get('price_label', 'Price'),
            'price_format': c['price_format'], 'price_notes': ['onwards', 'per month', 'per night', 'per acre', 'negotiable', 'all inclusive'],
            'closed_label': {'rentals': 'Let out', 'commercial': 'Leased'}.get(spec['niche'], 'Sold'), 'attributes': c['attributes'], 'enquiry': c['enquiry']}


def legal_pages(name, c):
    src = json.load(open(os.path.join(ROOT, 'templates/real-estate/realestate_template_01/demo.json')))
    out = {}
    for slug in ('privacy-policy', 'terms-and-conditions', 'disclaimer'):
        t = json.dumps(src['pages'][slug], ensure_ascii=False).replace('Keystone Realty', name).replace('Keystone', name).replace('MahaRERA', 'RERA').replace('banner-skyline.svg', 'banner.svg')
        out[slug] = json.loads(t)
    return out


def listing_items(c):
    img = lambda n: f'theme:images/{n}'
    rooms = c['rooms']
    items = []
    for i, it in enumerate(c['listings']):
        gal = [r for r in ROOM_GALLERY[it['kind']] if r in rooms]
        gal += [r for r in rooms if r not in gal][:max(0, 3 - len(gal))]
        a = dict(it['attrs'])
        amen = c.get('amenities_by_kind', {}).get(it['kind'])
        if any(x['key'] == 'amenities' for x in c['attributes']):
            a['amenities'] = amen or {'office': ['24×7 access and security', 'Power backup', 'Food court', 'Visitor parking', 'High-speed lifts', 'Fire safety systems'],
                                      'retail': ['Visitor parking', 'Power backup', '24×7 security', 'Signage rights', 'Loading bay'],
                                      'warehouse': ['24 dock levellers', '12-m clear height', 'Fire sprinklers', 'Truck parking', '24×7 security'],
                                      'plot': ['Tar roads', 'Underground drainage', 'Street lights', 'Water connection', 'Parks', 'Compound wall'],
                                      'layout': ['Tar roads', 'Underground drainage', 'Street lights', 'Water connection', 'Clubhouse', 'Parks']}.get(it['kind'], [
                                          'Swimming pool', 'Clubhouse & gym', '24×7 security with CCTV', 'Power backup', "Children's play area", 'Covered car parking', 'Rainwater harvesting', 'Landscaped garden'])
        if any(x['key'] == 'nearby' for x in c['attributes']):
            a['nearby'] = [f'{it["loc"]} market — 5 min', 'School — 8 min', 'Hospital — 10 min', f'{c["city"]} railway station — 25 min']
        desc = ('<h3>Highlights</h3><ul>'
                + ''.join(f'<li>{h}</li>' for h in (it['highlights'] or [f'Located in {it["loc"]}, {c["city"]}', 'Documents verified by our team', 'Viewings on weekdays and weekends']))
                + '</ul><p>Registration, stamp duty and society charges are extra where applicable. Speak to our team for the full cost sheet.</p>')
        row = {'title': it['title'], 'slug': it['slug'], 'category': it['cat'], 'location': it['loc'], 'price': it['price'] * 100,
               'image': img(f'prop-{it["slug"]}.svg'), 'gallery': [img(f'room-{r}.svg') for r in gal], 'attributes': a,
               'short_description': it['short'], 'description': desc, 'featured': bool(it['featured']), 'status': 'closed' if it['closed'] else 'active'}
        if it['badge']:
            row['badge'] = it['badge']
        if it['note']:
            row['price_note'] = it['note']
        items.append(row)
    cats = [{'name': k['name'], 'slug': k['slug'], 'description': k['description'], 'image': img(f'cat-{k["slug"]}.svg')} for k in c['categories']]
    return {'categories': cats, 'items': items}


def build(spec):
    c = NICHES[spec['niche']]
    d = spec.get('demo', {})
    img = lambda n: f'theme:images/{n}'
    name, hero = c['name'], c['hero']
    plural = c['plural'].lower()
    sections = {
        'hero': {'eyebrow': hero['eyebrow'], 'heading': hero['heading'], 'subheading': hero['sub'], 'image': img('hero.svg'), 'background_image': '',
                 'primary_button': {'text': '', 'url': ''}, 'secondary_button': {'text': '', 'url': ''},
                 'highlights': [{'icon': ic, 'text': t} for ic, t in zip(['bi-patch-check', 'bi-camera-video', 'bi-bank'], hero['highlights'])]},
        'listing_categories': {'eyebrow': 'Explore', 'heading': d.get('types_heading', 'What are you looking for?'), 'description': '', 'limit': 4, 'show_counts': True},
        'listings': {'eyebrow': 'Handpicked', 'heading': d.get('featured_heading', f'Featured {plural}'), 'description': '', 'source': 'featured', 'category': '', 'limit': 6,
                     'button': link(f'View all {plural}', 'page:listings')},
        'listings_new': {'eyebrow': 'Just listed', 'heading': 'New on the market', 'description': '', 'source': 'new', 'category': '', 'limit': 3, 'button': link(f'See all {plural}', 'page:listings')},
        'about': {'eyebrow': c['about']['eyebrow'], 'heading': c['about']['heading'], 'description': ''.join(f'<p>{p}</p>' for p in c['about']['p']),
                  'image': img('about-1.svg'), 'image_2': img('about-2.svg'), 'experience_number': str(2026 - int(c['since'])), 'experience_label': c['about']['exp_label'],
                  'points': [{'text': t} for t in c['about']['points']], 'button': link('About us', 'page:about')},
        'stats': {'heading': '', 'items': [{'number': n, 'suffix': s, 'label': l, 'icon': ''} for n, s, l in c['stats']]},
        'services': {'eyebrow': 'What we do', 'heading': d.get('services_heading', 'Everything you need, under one roof'), 'description': '', 'columns': '3',
                     'items': [dict(s, image='', link={'text': '', 'url': ''}) for s in c['services']]},
        'process': {'eyebrow': 'How it works', 'heading': 'Simple, from first call to keys', 'description': '', 'steps': [{'icon': i, 'title': t, 'description': x} for i, t, x in c['process']]},
        'testimonials': {'eyebrow': 'Client stories', 'heading': d.get('testimonials_heading', f'Trusted across {c["city"]}'), 'description': '',
                         'items': [{'quote': q, 'name': n, 'role': r, 'photo': img(f'customer-{i+1}.svg'), 'rating': 5} for i, (q, n, r) in enumerate(c['testimonials'])]},
        'logos': {'heading': c.get('logos_heading', 'Home loans arranged with'), 'items': [{'image': img(f'logo-{i+1}.svg'), 'name': n, 'url': ''} for i, n in enumerate(c['logos'])]},
        'cta': {'heading': c['cta'][0], 'description': c['cta'][1], 'background_image': img('cta.svg') if d.get('cta_image', True) else '',
                'button': link('Talk to us', 'page:contact'), 'secondary_button': link(f'Browse {plural}', 'page:listings')},
        'team': {'eyebrow': 'Our team', 'heading': 'The people who will help you', 'description': c['rera_text'],
                 'members': [dict(t, photo=img(f'agent-{i+1}.svg'), linkedin='https://www.linkedin.com/') for i, t in enumerate(c['team'])]},
        'faq': {'eyebrow': 'FAQ', 'heading': 'Questions we are often asked', 'description': 'Can’t find your answer? Call us — we are happy to help.',
                'items': [{'question': q, 'answer': a} for q, a in c['faq']]},
        'features': {'eyebrow': f'Why {name.split()[0]}', 'heading': 'What makes us different', 'description': '', 'image': '',
                     'items': [{'icon': s['icon'], 'title': s['title'], 'description': s['description']} for s in c['services'][:4]]},
    }

    def banner(heading, sub):
        return {'type': 'page_banner', 'data': {'heading': heading, 'subheading': sub, 'background_image': d.get('banner_image', img('banner.svg'))}}

    def sec(t, **over):
        data = json.loads(json.dumps(sections[t]))
        data.update(over)
        return {'type': 'listings' if t == 'listings_new' else t, 'data': data}

    home = [sec(t) for t in d.get('home', HOME_DEFAULT)]
    about = [banner('About us', c['about_short']), sec('about', button={'text': '', 'url': ''}), sec('stats'), sec('features'), sec('team')]
    services = [banner('Services', 'How we help you buy, sell, rent and invest.'), sec('services'), sec('process'), sec('faq'), sec('cta')]
    team = [banner('Our team', c['rera_text']), sec('team'), sec('testimonials'), sec('cta')]
    contact = [banner('Contact', f'Visit our {c["city"]} office or send us a message.'),
               {'type': 'contact', 'data': {'eyebrow': 'Get in touch', 'heading': 'Let’s talk about what you are looking for', 'description': 'Tell us what you need and our team will call you within one working day.',
                                            'form_heading': 'Request a call back', 'show_form': True, 'show_details': True, 'map_url': ''}}]
    pages = {'': {'seo_title': '', 'sections': home}, 'about': {'sections': about}, 'services': {'sections': services}, 'team': {'sections': team}, 'contact': {'sections': contact}}
    pages.update(legal_pages(name, c))
    settings = {'site': {'name': name, 'tagline': c['tagline'], 'phone': c['phone'], 'email': c['email'], 'address': c['address'], 'hours': c['hours'],
                         'footer_about': c['about_short'], 'footer_text': 'All rights reserved. ' + c['rera_text'], 'header_button': link(d.get('header_button', 'Book a visit'), 'page:contact'),
                         'social': {'facebook': 'https://www.facebook.com/', 'instagram': 'https://www.instagram.com/', 'youtube': 'https://www.youtube.com/', 'linkedin': 'https://www.linkedin.com/'}},
                'seo': {'description': f'{name}, {c["city"]}: {c["about_short"]}', 'keywords': f'{plural} in {c["city"]}, real estate {c["city"]}, {c["tagline"].lower()}'}}
    return {'settings': settings, 'listings': listing_items(c), 'pages': pages}


def page_types(demo):
    t = lambda slug: [s['type'] for s in demo['pages'][slug]['sections']]
    return {'home': t(''), 'about': t('about'), 'services': t('services'), 'team': t('team'), 'contact': t('contact'), 'legal': ['page_banner', 'rich_text'], 'generic': ['page_banner', 'rich_text', 'cta']}


DEFAULT_PAGES = [
    {'title': 'Home', 'slug': '', 'type': 'home', 'is_home': True},
    {'title': 'About', 'slug': 'about', 'type': 'about'},
    {'title': 'Services', 'slug': 'services', 'type': 'services'},
    {'title': 'Team', 'slug': 'team', 'type': 'team'},
    {'title': 'Contact', 'slug': 'contact', 'type': 'contact'},
    {'title': 'Privacy Policy', 'slug': 'privacy-policy', 'type': 'legal', 'in_menu': False, 'in_footer': True},
    {'title': 'Terms and Conditions', 'slug': 'terms-and-conditions', 'type': 'legal', 'in_menu': False, 'in_footer': True},
    {'title': 'Disclaimer', 'slug': 'disclaimer', 'type': 'legal', 'in_menu': False, 'in_footer': True},
]
