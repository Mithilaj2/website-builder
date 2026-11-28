"""Builds demo.json (pages, settings and the packages) and the manifest's listings block for a travel template."""
import json
import os
from travel_content import NICHES
from travel_images import LABELS

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__)))))
HOME_DEFAULT = ['hero', 'listing_categories', 'listings', 'features', 'stats', 'listings_new', 'gallery', 'testimonials', 'cta']
SECTION_TYPES = ['hero', 'page_banner', 'about', 'services', 'features', 'stats', 'testimonials', 'gallery', 'team', 'faq', 'cta', 'contact', 'logos', 'rich_text', 'process',
                 'listings', 'listing_categories']
FEATURES = ['contact']
OPTIONAL_FEATURES = ['payments']


def link(text, url):
    return {'text': text, 'url': url}


def listings_config(spec):
    c = NICHES[spec['niche']]
    return {'path': c['path'], 'singular': c['singular'], 'plural': c['plural'], 'menu_label': c['menu_label'], 'icon': 'bi-airplane',
            'category_label': c['category_label'], 'location_label': c['location_label'], 'location_filter': False, 'price_label': 'Price', 'price_format': 'inr',
            'price_notes': ['per person', 'per couple', 'per person, twin sharing', 'per student', 'onwards'], 'closed_label': 'Sold out',
            'attributes': c['attributes'], 'enquiry': c['enquiry']}


def legal_pages(name):
    src = json.load(open(os.path.join(ROOT, 'templates/travel/travel_template_01/demo.json')))
    out = {}
    for slug in ('privacy-policy', 'terms-and-conditions', 'cancellation-policy'):
        t = json.dumps(src['pages'][slug], ensure_ascii=False).replace('Horizon Trails', name).replace('banner-hills.svg', 'banner.svg')
        out[slug] = json.loads(t)
    return out


def items(c):
    img = lambda n: f'theme:images/{n}'
    gal = c['gallery']
    out = []
    std_inc = ['Stays as listed, on twin-sharing basis', 'Daily breakfast and dinner', 'Transfers and sightseeing by private vehicle', 'Tour manager or local guide', '24×7 on-trip support']
    std_exc = ['Personal expenses and tips', 'Entry tickets not listed', 'Travel insurance', '5% GST']
    for i, p in enumerate(c['listings']):
        others = [g for g in gal if g != p['scene']]
        gallery = [img(f'scene-{g}.svg') for g in ([p['scene']] + others)[:4]]
        days = ''.join(f'<h4>Day {d+1}: {t}</h4>' for d, t in enumerate(p['itinerary']))
        a = {'duration': p['days'], 'nights': p['days'] - 1, 'trip_type': p['trip'], 'region': p['region'], 'group_size': p['group'], 'best_time': p['best'],
             'start_city': p['start'], 'flights': p['flights'], 'highlights': p['highlights'],
             'inclusions': (['Return flights in economy class'] if p['flights'] else []) + std_inc, 'exclusions': ([] if p['flights'] else ['Flights or trains to the start city']) + std_exc}
        row = {'title': p['title'], 'slug': p['slug'], 'category': p['cat'], 'location': p['route'], 'price': p['price'] * 100, 'price_note': p['note'],
               'image': img(f'trip-{p["slug"]}.svg'), 'gallery': gallery, 'attributes': a, 'short_description': p['short'],
               'description': f'<h3>Day-by-day itinerary</h3>{days}<p>Every itinerary can be customised — add days, change hotels or travel on your own dates.</p>',
               'featured': bool(p['featured']), 'status': 'closed' if p['closed'] else 'active'}
        if p['badge']:
            row['badge'] = p['badge']
        out.append(row)
    cats = [{'name': k['name'], 'slug': k['slug'], 'description': k['description'], 'image': img(f'dest-{k["slug"]}.svg')} for k in c['categories']]
    return {'categories': cats, 'items': out}


def build(spec):
    c = NICHES[spec['niche']]
    d = spec.get('demo', {})
    img = lambda n: f'theme:images/{n}'
    name, hero = c['name'], c['hero']
    plural = c['plural'].lower()
    sections = {
        'hero': {'eyebrow': hero['eyebrow'], 'heading': hero['heading'], 'subheading': hero['sub'], 'image': img('hero.svg'), 'background_image': '',
                 'primary_button': {'text': '', 'url': ''}, 'secondary_button': {'text': '', 'url': ''},
                 'highlights': [{'icon': ic, 'text': t} for ic, t in zip(['bi-patch-check', 'bi-people', 'bi-headset'], hero['highlights'])]},
        'listing_categories': {'eyebrow': 'Explore', 'heading': d.get('types_heading', 'Where would you like to go?'), 'description': '', 'limit': 4, 'show_counts': True},
        'listings': {'eyebrow': 'Popular', 'heading': d.get('featured_heading', f'Popular {plural}'), 'description': '', 'source': 'featured', 'category': '', 'limit': 6,
                     'button': link(f'All {plural}', 'page:listings')},
        'listings_new': {'eyebrow': 'Just added', 'heading': d.get('new_heading', 'New this season'), 'description': '', 'source': 'new', 'category': '', 'limit': 3, 'button': link(f'See all {plural}', 'page:listings')},
        'features': {'eyebrow': f'Why {name.split()[0]}', 'heading': d.get('features_heading', 'Travel with people who have been there'), 'description': '', 'image': '',
                     'items': [{'icon': s['icon'], 'title': s['title'], 'description': s['description']} for s in c['services'][:4]]},
        'stats': {'heading': '', 'items': [{'number': n, 'suffix': s, 'label': l, 'icon': ''} for n, s, l in c['stats']]},
        'gallery': {'eyebrow': 'From our travellers', 'heading': d.get('gallery_heading', 'Moments worth the journey'), 'description': '', 'columns': '3',
                    'images': [{'image': img(f'scene-{g}.svg'), 'caption': LABELS[g]} for g in c['gallery'][:6]]},
        'testimonials': {'eyebrow': 'Traveller reviews', 'heading': d.get('testimonials_heading', 'Stories from the road'), 'description': '',
                         'items': [{'quote': q, 'name': n, 'role': r, 'photo': img(f'traveller-{i+1}.svg'), 'rating': 5} for i, (q, n, r) in enumerate(c['testimonials'])]},
        'cta': {'heading': c['cta'][0], 'description': c['cta'][1], 'background_image': img('cta.svg'), 'button': link(d.get('cta_button', 'Plan my trip'), 'page:contact'),
                'secondary_button': link(f'Browse {plural}', 'page:listings')},
        'about': {'eyebrow': c['about']['eyebrow'], 'heading': c['about']['heading'], 'description': ''.join(f'<p>{p}</p>' for p in c['about']['p']),
                  'image': img('about-1.svg'), 'image_2': img('about-2.svg'), 'experience_number': str(2026 - int(c['since'])), 'experience_label': c['about']['exp_label'],
                  'points': [{'text': t} for t in c['about']['points']], 'button': {'text': '', 'url': ''}},
        'team': {'eyebrow': 'Our team', 'heading': 'The people behind your trip', 'description': c['licence'],
                 'members': [dict(t, photo=img(f'guide-{i+1}.svg'), linkedin='https://www.linkedin.com/') for i, t in enumerate(c['team'])]},
        'logos': {'heading': c.get('logos_heading', 'Our partners'), 'items': [{'image': img(f'logo-{i+1}.svg'), 'name': n, 'url': ''} for i, n in enumerate(c['logos'])]},
        'services': {'eyebrow': 'What we do', 'heading': d.get('services_heading', 'Everything for a stress-free trip'), 'description': '', 'columns': '3',
                     'items': [dict(s, image='', link={'text': '', 'url': ''}) for s in c['services']]},
        'process': {'eyebrow': 'How it works', 'heading': 'Four easy steps', 'description': '', 'steps': [{'icon': i, 'title': t, 'description': x} for i, t, x in c['process']]},
        'faq': {'eyebrow': 'FAQ', 'heading': 'Before you book', 'description': '', 'items': [{'question': q, 'answer': a} for q, a in c['faq']]},
    }

    def banner(heading, sub):
        return {'type': 'page_banner', 'data': {'heading': heading, 'subheading': sub, 'background_image': img('banner.svg')}}

    def sec(t, **over):
        data = json.loads(json.dumps(sections[t]))
        data.update(over)
        return {'type': 'listings' if t == 'listings_new' else t, 'data': data}

    home = [sec(t) for t in d.get('home', HOME_DEFAULT)]
    about = [banner('About us', c['about_short']), sec('about'), sec('stats'), sec('team'), sec('logos')]
    services = [banner('Services', 'From the first idea to the trip home.'), sec('services'), sec('process'), sec('faq'), sec('cta')]
    contact = [banner('Contact', 'Tell us where, when and who is travelling.'),
               {'type': 'contact', 'data': {'eyebrow': 'Get in touch', 'heading': 'Where shall we take you?', 'description': 'Send us a message and a planner will reply within one working day.',
                                            'form_heading': 'Send an enquiry', 'show_form': True, 'show_details': True, 'map_url': ''}}]
    pages = {'': {'seo_title': '', 'sections': home}, 'about': {'sections': about}, 'services': {'sections': services}, 'contact': {'sections': contact}}
    pages.update(legal_pages(name))
    settings = {'site': {'name': name, 'tagline': c['tagline'], 'phone': c['phone'], 'email': c['email'], 'address': c['address'], 'hours': c['hours'],
                         'footer_about': c['about_short'], 'footer_text': 'All rights reserved. ' + c['licence'], 'header_button': link(d.get('header_button', 'Plan my trip'), 'page:contact'),
                         'social': {'facebook': 'https://www.facebook.com/', 'instagram': 'https://www.instagram.com/', 'youtube': 'https://www.youtube.com/'}},
                'seo': {'description': f'{name}: {c["about_short"]}', 'keywords': f'{plural}, {c["tagline"].lower()}, tour packages'}}
    return {'settings': settings, 'listings': items(c), 'pages': pages}


def page_types(demo):
    t = lambda slug: [s['type'] for s in demo['pages'][slug]['sections']]
    return {'home': t(''), 'about': t('about'), 'services': t('services'), 'contact': t('contact'), 'legal': ['page_banner', 'rich_text'], 'generic': ['page_banner', 'rich_text', 'cta']}


DEFAULT_PAGES = [
    {'title': 'Home', 'slug': '', 'type': 'home', 'is_home': True},
    {'title': 'About', 'slug': 'about', 'type': 'about'},
    {'title': 'Services', 'slug': 'services', 'type': 'services'},
    {'title': 'Contact', 'slug': 'contact', 'type': 'contact'},
    {'title': 'Privacy Policy', 'slug': 'privacy-policy', 'type': 'legal', 'in_menu': False, 'in_footer': True},
    {'title': 'Terms and Conditions', 'slug': 'terms-and-conditions', 'type': 'legal', 'in_menu': False, 'in_footer': True},
    {'title': 'Cancellation Policy', 'slug': 'cancellation-policy', 'type': 'legal', 'in_menu': False, 'in_footer': True},
]
