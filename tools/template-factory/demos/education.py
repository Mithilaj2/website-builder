"""Builds demo.json for an education template from its spec and niche copy."""
import json
import os
from education_content import NICHES
from education_images import NICHES as SCENES, LABELS

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__)))))
HOME_DEFAULT = ['hero', 'notices', 'about', 'courses', 'stats', 'features', 'events', 'testimonials', 'cta']
ENQUIRY_NICHES = {'coding', 'arts', 'language'}
SECTION_TYPES = ['hero', 'page_banner', 'about', 'services', 'features', 'stats', 'testimonials', 'gallery', 'team', 'faq', 'cta', 'contact', 'logos',
                 'rich_text', 'process', 'pricing', 'courses', 'events', 'notices', 'booking_form']
FEATURES = ['contact']
OPTIONAL_FEATURES = ['payments']


def link(text, url):
    return {'text': text, 'url': url}


def legal_pages(name):
    src = json.load(open(os.path.join(ROOT, 'templates/education/education_template_01/demo.json')))
    return {slug: json.loads(json.dumps(src['pages'][slug]).replace('Evergreen Valley Academy', name).replace('Evergreen', name.split()[0]))
            for slug in ('privacy-policy', 'terms-and-conditions')}


def form_type(spec):
    return 'enquiry' if spec['niche'] in ENQUIRY_NICHES else 'admission'


def build(spec):
    c = NICHES[spec['niche']]
    d = spec.get('demo', {})
    img = lambda n: f'theme:images/{n}'
    name, hero, ft = c['name'], c['hero'], form_type(spec)
    anchor = f'#{ft}-form'
    courses = [dict(x, image=img(f'svc-{i+1}.svg'), link=link('Details & fees', 'page:admissions')) for i, x in enumerate(c['courses'])]
    banner_bg = d.get('banner_image', '')
    notices = {'eyebrow': 'Notice board', 'heading': 'Latest announcements', 'description': 'Circulars, results and important dates.',
               'items': [{'date': dt, 'title': t, 'link': link('Read more', 'page:admissions') if i == 0 else {'text': '', 'url': ''}, 'is_new': new} for i, (dt, t, new) in enumerate(c['notices'])],
               'button': link('All updates', 'page:events')}
    events = {'eyebrow': 'Calendar', 'heading': 'Upcoming events', 'description': '', 'hide_past': False,
              'items': [{'date': dt, 'time': tm, 'title': t, 'location': loc, 'description': ds, 'image': img(f'svc-{[2, 4, 6][i]}.svg'), 'link': {'text': '', 'url': ''}} for i, (dt, tm, t, loc, ds) in enumerate(c['events'])]}
    sections = {
        'hero': {'eyebrow': hero['eyebrow'], 'heading': hero['heading'], 'subheading': hero['sub'], 'image': img('hero.svg'), 'background_image': '',
                 'primary_button': link(c['cta_button'], 'page:admissions'), 'secondary_button': link('Explore programmes', 'page:courses'),
                 'highlights': [{'icon': d.get('highlight_icon', 'bi-check2-circle'), 'text': t} for t in hero['highlights']]},
        'notices': notices,
        'logos': {'heading': c['logos_heading'], 'items': [{'image': img(f'logo-{i+1}.svg'), 'name': n, 'url': ''} for i, n in enumerate(c['logos'])]},
        'about': {'eyebrow': c['about']['eyebrow'], 'heading': c['about']['heading'], 'description': ''.join(f'<p>{p}</p>' for p in c['about']['p']),
                  'image': img('about-1.svg'), 'image_2': img('about-2.svg'), 'experience_number': c['since'], 'experience_label': c['about']['exp_label'],
                  'points': [{'text': t} for t in c['about']['points']], 'button': link('More about us', 'page:about')},
        'courses': {'eyebrow': d.get('courses_eyebrow', 'Programmes'), 'heading': d.get('courses_heading', 'Find the right programme'), 'description': c['about_short'],
                    'items': courses[:d.get('home_courses', 3)], 'button': link('View all programmes', 'page:courses')},
        'stats': {'heading': '', 'items': [{'number': n, 'suffix': s, 'label': l, 'icon': ''} for n, s, l in c['stats']]},
        'features': {'eyebrow': 'Why us', 'heading': c['features_heading'], 'description': '', 'image': img(d['features_image']) if d.get('features_image') else '', 'items': c['features']},
        'events': events,
        'testimonials': {'eyebrow': 'Voices', 'heading': d.get('testimonials_heading', 'What students and parents say'),
                         'items': [{'quote': q, 'name': n, 'role': r, 'photo': '', 'rating': 5} for q, n, r in c['testimonials']]},
        'process': {'eyebrow': 'How to join', 'heading': d.get('process_heading', 'Four simple steps'), 'description': '', 'steps': c['process']},
        'cta': {'heading': c['cta'][0], 'description': c['cta'][1], 'background_image': img(d['cta_image']) if d.get('cta_image') else '',
                'button': link(c['cta_button'], 'page:admissions'), 'secondary_button': link('Call us', 'tel:' + c['phone'].replace(' ', ''))},
    }

    def banner(heading, sub):
        return {'type': 'page_banner', 'data': {'heading': heading, 'subheading': sub, 'background_image': banner_bg}}

    def sec(t, **over):
        data = json.loads(json.dumps(sections[t]))
        data.update(over)
        return {'type': t, 'data': data}

    faq = {'type': 'faq', 'data': {'eyebrow': 'FAQ', 'heading': 'Frequently asked questions', 'description': '', 'items': [{'question': q, 'answer': a} for q, a in c['faq']]}}
    b = c['booking']
    for label in (b['select1'], b['select2']):
        if 'date' in label.lower():
            # The runtime validates any field whose label contains "date" as YYYY-MM-DD, which a dropdown can never satisfy.
            raise SystemExit(f'{spec["id"]}: dropdown label "{label}" must not contain "date"')
    booking = {'type': 'booking_form', 'data': {
        'eyebrow': c['apply_page'], 'heading': b['heading'], 'description': 'Share a few details and our team will get back to you within one working day.',
        'form_type': ft, 'form_heading': b['heading'], 'select1_label': b['select1'], 'select1_options': b['opts1'], 'select2_label': b['select2'], 'select2_options': b['opts2'],
        'date_label': b['date'], 'message_label': b['msg'], 'email_required': False, 'button_text': b['button'], 'success_message': b['success'],
        'image': img(d['booking_image']) if d.get('booking_image') else '',
        'points': [{'icon': 'bi-telephone', 'title': 'We call you back', 'text': 'Within one working day'}, {'icon': 'bi-geo-alt', 'title': 'Visit us', 'text': c['hours']},
                   {'icon': 'bi-envelope', 'title': 'Prefer email?', 'text': c['email']}]}}
    pricing = {'type': 'pricing', 'data': {'eyebrow': 'Fees', 'heading': 'Clear, all-inclusive fees', 'description': 'Fees are reviewed every year. Scholarships and instalment options are available.',
                                           'plans': [{'name': n, 'price': p, 'period': per, 'features': ft_, 'button': link('Enquire', anchor), 'featured': i == 1} for i, (n, p, per, ft_) in enumerate(c['plans'])]}}
    home = [sec(t) for t in d.get('home', HOME_DEFAULT)]
    about = [banner(f'About {name}', c['about_short']), sec('about', button=link(c['cta_button'], 'page:admissions')), sec('stats'),
             {'type': 'team', 'data': {'eyebrow': 'Our people', 'heading': d.get('team_heading', 'Meet our faculty'), 'description': '',
                                       'members': [{'photo': img(f'team-{i+1}.svg'), 'name': n, 'role': r, 'bio': bio, 'linkedin': ''} for i, (n, r, bio) in enumerate(c['team'])]}},
             sec('features')]
    courses_page = [banner('Programmes', c['about_short']), sec('courses', items=courses, button={'text': '', 'url': ''}, eyebrow='', heading='All programmes', description=''), faq, sec('cta')]
    admissions = [banner(c['apply_page'], 'Everything you need to join us — steps, fees and a quick enquiry form.'), sec('process'), pricing, booking, faq]
    events_page = [banner('News & Events', 'Upcoming events and the latest announcements.'), sec('events'), sec('notices', button={'text': '', 'url': ''}),
                   {'type': 'gallery', 'data': {'eyebrow': 'Moments', 'heading': 'From recent events', 'description': '', 'columns': '3',
                                                'images': [{'image': img(f'svc-{i}.svg'), 'caption': LABELS[SCENES[spec['niche']][1][i - 1]]} for i in (2, 4, 6)]}}]
    tiles = SCENES[spec['niche']][1]
    cap_of = lambda i: LABELS[tiles[i - 1]]
    gal = [('hero.svg', f'Life at {name.split()[0]}')] + [(f'svc-{i}.svg', cap_of(i)) for i in (1, 2, 3, 5, 6)]
    gallery = [banner('Gallery', f'Life at {name}.'), {'type': 'gallery', 'data': {'eyebrow': '', 'heading': '', 'description': '', 'columns': '3',
                                                                                  'images': [{'image': img(f_), 'caption': cap} for f_, cap in gal]}}, sec('cta')]
    contact = [banner('Contact Us', 'Questions about programmes, fees or visits? We are happy to help.'),
               {'type': 'contact', 'data': {'eyebrow': 'Get in touch', 'heading': 'We would love to hear from you', 'description': f'Visit us in {c["city"]}, call, or send a message.',
                                            'form_heading': 'Send us a message', 'show_form': True, 'show_details': True, 'map_url': ''}}]
    pages = {'': {'seo_title': '', 'sections': home}, 'about': {'sections': about}, 'courses': {'sections': courses_page}, 'admissions': {'sections': admissions},
             'events': {'sections': events_page}, 'gallery': {'sections': gallery}, 'contact': {'sections': contact}}
    pages.update(legal_pages(name))
    settings = {'site': {'name': name, 'tagline': c['tagline'], 'phone': c['phone'], 'email': c['email'], 'address': c['address'], 'hours': c['hours'],
                         'footer_about': c['about_short'], 'footer_text': 'All rights reserved.', 'rating': c['rating'], 'rating_label': c['rating_label'],
                         'header_button': link(c['cta_button'], 'page:admissions'),
                         'social': {'facebook': 'https://www.facebook.com/', 'instagram': 'https://www.instagram.com/', 'youtube': 'https://www.youtube.com/'}},
                'seo': {'description': f'{name} — {c["tagline"].lower()} in {c["city"]}. {c["about_short"]}', 'keywords': f'{c["tagline"].lower()}, {c["city"]}'}}
    return {'settings': settings, 'pages': pages}


def page_types(demo):
    t = lambda slug: [s['type'] for s in demo['pages'][slug]['sections']]
    return {'home': t(''), 'about': t('about'), 'courses': t('courses'), 'admissions': t('admissions'), 'events': t('events'), 'gallery': t('gallery'),
            'contact': t('contact'), 'legal': ['page_banner', 'rich_text'], 'generic': ['page_banner', 'rich_text', 'cta']}


def default_pages(spec):
    apply_title = NICHES[spec['niche']]['apply_page']
    return [
        {'title': 'Home', 'slug': '', 'type': 'home', 'is_home': True},
        {'title': 'About', 'slug': 'about', 'type': 'about'},
        {'title': 'Programmes', 'slug': 'courses', 'type': 'courses'},
        {'title': apply_title, 'slug': 'admissions', 'type': 'admissions'},
        {'title': 'Events', 'slug': 'events', 'type': 'events'},
        {'title': 'Gallery', 'slug': 'gallery', 'type': 'gallery'},
        {'title': 'Contact', 'slug': 'contact', 'type': 'contact'},
        {'title': 'Privacy Policy', 'slug': 'privacy-policy', 'type': 'legal', 'in_menu': False, 'in_footer': True},
        {'title': 'Terms and Conditions', 'slug': 'terms-and-conditions', 'type': 'legal', 'in_menu': False, 'in_footer': True},
    ]
