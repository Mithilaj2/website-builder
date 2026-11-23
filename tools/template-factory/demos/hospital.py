"""Builds demo.json for a hospital template from its spec and niche copy."""
import json
import os
from hospital_content import NICHES
from hospital_images import NICHES as SCENES, LABELS

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__)))))
HOME_DEFAULT = ['hero', 'services', 'about', 'stats', 'doctors', 'opening_hours', 'testimonials', 'logos', 'cta']
SECTION_TYPES = ['hero', 'page_banner', 'about', 'services', 'features', 'stats', 'testimonials', 'gallery', 'team', 'faq', 'cta', 'contact', 'logos',
                 'rich_text', 'process', 'pricing', 'doctors', 'opening_hours', 'booking_form']
FEATURES = ['contact']
OPTIONAL_FEATURES = ['payments']


def link(text, url):
    return {'text': text, 'url': url}


def legal_pages(name):
    src = json.load(open(os.path.join(ROOT, 'templates/hospital/hospital_template_01/demo.json')))
    return {slug: json.loads(json.dumps(src['pages'][slug]).replace('CareLine Hospital', name).replace('CareLine', name.split()[0]))
            for slug in ('privacy-policy', 'terms-and-conditions')}


def build(spec):
    c = NICHES[spec['niche']]
    d = spec.get('demo', {})
    img = lambda n: f'theme:images/{n}'
    name, hero, pg = c['name'], c['hero'], c['pages']
    tiles = SCENES[spec['niche']][0]
    svc_imgs = d.get('service_images', False)
    services = [dict(x, image=img(f'svc-{i+1}.svg') if svc_imgs else '', link=link('Learn more', 'page:departments')) for i, x in enumerate(c['services'])]
    doctors = [dict(x, photo=img(f'doctor-{i+1}.svg'), link=link('Book appointment', 'page:appointment')) for i, x in enumerate(c['doctors'])]
    banner_bg = d.get('banner_image', '')
    hrs = c['timetable']
    sections = {
        'hero': {'eyebrow': hero['eyebrow'], 'heading': hero['heading'], 'subheading': hero['sub'], 'image': img('hero.svg'), 'background_image': '',
                 'primary_button': link(c['cta_button'], 'page:appointment'), 'secondary_button': link(f'Explore {pg["services"].lower()}', 'page:departments'),
                 'highlights': [{'icon': d.get('highlight_icon', 'bi-check2-circle'), 'text': t} for t in hero['highlights']]},
        'services': {'eyebrow': pg['services'], 'heading': d.get('services_heading', 'Specialist care under one roof'), 'description': c['about_short'],
                     'columns': d.get('home_services_columns', '3'), 'items': services[:d.get('home_services', 6)]},
        'about': {'eyebrow': c['about']['eyebrow'], 'heading': c['about']['heading'], 'description': ''.join(f'<p>{p}</p>' for p in c['about']['p']),
                  'image': img('about-1.svg'), 'image_2': img('about-2.svg'), 'experience_number': c['since'], 'experience_label': c['about']['exp_label'],
                  'points': [{'text': t} for t in c['about']['points']], 'button': link('More about us', 'page:about')},
        'stats': {'heading': '', 'items': [{'number': n, 'suffix': s, 'label': l, 'icon': ''} for n, s, l in c['stats']]},
        'doctors': {'eyebrow': pg['doctors'], 'heading': d.get('doctors_heading', 'Meet our specialists'), 'description': '', 'items': doctors[:d.get('home_doctors', 4)],
                    'button': link('Meet the whole team', 'page:doctors')},
        'opening_hours': {'eyebrow': 'Timings', 'heading': d.get('hours_heading', 'When we are open'), 'description': '',
                          'rows': [{'label': a, 'hours': b} for a, b in hrs['rows']], 'highlight_label': hrs['label'], 'highlight_value': hrs['value'], 'note': hrs['note']},
        'features': {'eyebrow': 'Why us', 'heading': c['features_heading'], 'description': '', 'image': img(d['features_image']) if d.get('features_image') else '', 'items': c['features']},
        'testimonials': {'eyebrow': 'Patient stories', 'heading': d.get('testimonials_heading', 'What our patients say'),
                         'items': [{'quote': q, 'name': n, 'role': r, 'photo': '', 'rating': 5} for q, n, r in c['testimonials']]},
        'logos': {'heading': c['logos_heading'], 'items': [{'image': img(f'logo-{i+1}.svg'), 'name': n, 'url': ''} for i, n in enumerate(c['logos'])]},
        'process': {'eyebrow': 'How it works', 'heading': d.get('process_heading', 'Your visit, step by step'), 'description': '', 'steps': c['process']},
        'cta': {'heading': c['cta'][0], 'description': c['cta'][1], 'background_image': img(d['cta_image']) if d.get('cta_image') else '',
                'button': link(c['cta_button'], 'page:appointment'),
                'secondary_button': link('Call ' + ('emergency' if c['emergency'] else 'us'), 'tel:' + (c['emergency'] or c['phone']).replace(' ', ''))},
    }

    def banner(heading, sub):
        return {'type': 'page_banner', 'data': {'heading': heading, 'subheading': sub, 'background_image': banner_bg}}

    def sec(t, **over):
        data = json.loads(json.dumps(sections[t]))
        data.update(over)
        return {'type': t, 'data': data}

    faq = {'type': 'faq', 'data': {'eyebrow': 'FAQ', 'heading': 'Questions patients ask', 'description': '', 'items': [{'question': q, 'answer': a} for q, a in c['faq']]}}
    b = c['booking']
    for label in (b['select1'], b['select2']):
        if 'date' in label.lower():
            raise SystemExit(f'{spec["id"]}: dropdown label "{label}" must not contain "date"')
    booking = {'type': 'booking_form', 'data': {
        'eyebrow': pg['appointment'], 'heading': b['heading'], 'description': 'Choose a service and a convenient time — our team will call to confirm.',
        'form_type': 'appointment', 'form_heading': b['heading'], 'select1_label': b['select1'], 'select1_options': b['opts1'], 'select2_label': b['select2'], 'select2_options': b['opts2'],
        'date_label': b['date'], 'message_label': b['msg'], 'email_required': False, 'button_text': b['button'], 'success_message': b['success'],
        'image': img(d['booking_image']) if d.get('booking_image') else '',
        'points': [{'icon': 'bi-telephone', 'title': 'We confirm by phone', 'text': 'Usually within an hour'}, {'icon': 'bi-clock', 'title': 'Timings', 'text': c['hours']},
                   {'icon': 'bi-file-earmark-medical', 'title': 'Bring your reports', 'text': 'Previous prescriptions and test results'}]}}
    pricing = {'type': 'pricing', 'data': {'eyebrow': pg['packages'], 'heading': d.get('packages_heading', 'Clear, upfront prices'), 'description': 'Prices include taxes. Packages can be booked online or at the front desk.',
                                           'plans': [{'name': n, 'price': p, 'period': per, 'features': f_, 'button': link('Book now', 'page:appointment'), 'featured': i == 1} for i, (n, p, per, f_) in enumerate(c['plans'])]}}
    home = [sec(t) for t in d.get('home', HOME_DEFAULT)]
    about = [banner(f'About {name}', c['about_short']), sec('about', button=link(c['cta_button'], 'page:appointment')), sec('stats'), sec('features'),
             {'type': 'team', 'data': {'eyebrow': 'Leadership', 'heading': 'The people who lead our care', 'description': '',
                                       'members': [{'photo': img(f'team-{i+1}.svg'), 'name': n, 'role': r, 'bio': bio, 'linkedin': ''} for i, (n, r, bio) in enumerate(c['team'])]}},
             {'type': 'gallery', 'data': {'eyebrow': 'Facilities', 'heading': 'Inside our hospital', 'description': '', 'columns': '3',
                                          'images': [{'image': img(f'svc-{i+1}.svg'), 'caption': LABELS[t]} for i, t in enumerate(tiles)]}}]
    departments = [banner(pg['services'], c['about_short']), sec('services', items=services, eyebrow='', heading=f'All {pg["services"].lower()}', description='', columns='3'), sec('process'), faq, sec('cta')]
    doctors_page = [banner(pg['doctors'], 'Experienced specialists, with their timings — book directly with the doctor you prefer.'), sec('doctors', items=doctors, button={'text': '', 'url': ''}, eyebrow='', heading='Find your specialist'), sec('cta')]
    packages = [banner(pg['packages'], 'Clear prices for check-ups and common treatments.'), pricing, faq, sec('cta')]
    appointment = [banner(pg['appointment'], 'Request an appointment online — we will call you to confirm.'), booking, sec('process'), sec('opening_hours')]
    contact = [banner('Contact Us', 'Visit us, call us or send a message — we are here to help.'),
               {'type': 'contact', 'data': {'eyebrow': 'Get in touch', 'heading': 'We are here to help', 'description': f'Reach our {c["city"]} team by phone, email or the form.',
                                            'form_heading': 'Send us a message', 'show_form': True, 'show_details': True, 'map_url': ''}}, sec('opening_hours')]
    pages = {'': {'seo_title': '', 'sections': home}, 'about': {'sections': about}, 'departments': {'sections': departments}, 'doctors': {'sections': doctors_page},
             'health-packages': {'sections': packages}, 'appointment': {'sections': appointment}, 'contact': {'sections': contact}}
    pages.update(legal_pages(name))
    settings = {'site': {'name': name, 'tagline': c['tagline'], 'phone': c['phone'], 'emergency_phone': c['emergency'], 'email': c['email'], 'address': c['address'], 'hours': c['hours'],
                         'footer_about': c['about_short'], 'footer_text': 'All rights reserved.', 'rating': c['rating'], 'rating_label': c['rating_label'],
                         'header_button': link(c['cta_button'], 'page:appointment'),
                         'social': {'facebook': 'https://www.facebook.com/', 'instagram': 'https://www.instagram.com/', 'youtube': 'https://www.youtube.com/'}},
                'seo': {'description': f'{name} — {c["tagline"].split(" · ")[0].lower()} in {c["city"]}. {c["about_short"]}', 'keywords': f'{c["tagline"].split(" · ")[0].lower()}, {c["city"]}'}}
    return {'settings': settings, 'pages': pages}


def page_types(demo):
    t = lambda slug: [s['type'] for s in demo['pages'][slug]['sections']]
    return {'home': t(''), 'about': t('about'), 'departments': t('departments'), 'doctors': t('doctors'), 'packages': t('health-packages'), 'appointment': t('appointment'),
            'contact': t('contact'), 'legal': ['page_banner', 'rich_text'], 'generic': ['page_banner', 'rich_text', 'cta']}


def default_pages(spec):
    pg = NICHES[spec['niche']]['pages']
    return [
        {'title': 'Home', 'slug': '', 'type': 'home', 'is_home': True},
        {'title': 'About', 'slug': 'about', 'type': 'about'},
        {'title': pg['services'], 'slug': 'departments', 'type': 'departments'},
        {'title': pg['doctors'], 'slug': 'doctors', 'type': 'doctors'},
        {'title': pg['packages'], 'slug': 'health-packages', 'type': 'packages'},
        {'title': pg['appointment'], 'slug': 'appointment', 'type': 'appointment'},
        {'title': 'Contact', 'slug': 'contact', 'type': 'contact'},
        {'title': 'Privacy Policy', 'slug': 'privacy-policy', 'type': 'legal', 'in_menu': False, 'in_footer': True},
        {'title': 'Terms and Conditions', 'slug': 'terms-and-conditions', 'type': 'legal', 'in_menu': False, 'in_footer': True},
    ]
