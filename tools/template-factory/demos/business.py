"""Builds demo.json for a business template from its spec and niche copy."""
import json
import os
from business_content import NICHES

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__)))))
HOME_DEFAULT = ['hero', 'logos', 'about', 'services', 'stats', 'features', 'process', 'testimonials', 'cta']


def link(text, url):
    return {'text': text, 'url': url}


def legal_pages(name):
    src = json.load(open(os.path.join(ROOT, 'templates/business/business_template_01/demo.json')))
    out = {}
    for slug in ('privacy-policy', 'terms-and-conditions'):
        out[slug] = json.loads(json.dumps(src['pages'][slug]).replace('Meridian Consulting', name).replace('Meridian', name))
    return out


def build(spec):
    c = NICHES[spec['niche']]
    d = spec.get('demo', {})
    img = lambda n: f'theme:images/{n}'
    name = c['name']
    hero = c['hero']
    services = c['services']
    svc_imgs = d.get('service_images', False)
    home_services = []
    for i, s in enumerate(services[:d.get('home_services', 4)]):
        item = dict(s, image=img(f'svc-{i+1}.svg') if svc_imgs and i < 4 else '', link=link('Learn more', 'page:services'))
        home_services.append(item)
    banner_bg = d.get('banner_image', '')
    sections = {
        'hero': {'eyebrow': hero['eyebrow'], 'heading': hero['heading'], 'subheading': hero['sub'], 'image': img('hero.svg'),
                 'background_image': '', 'primary_button': link(c['cta_button'], 'page:contact'),
                 'secondary_button': link('Explore services', 'page:services'),
                 'highlights': [{'icon': d.get('highlight_icon', 'bi-check2-circle'), 'text': t} for t in hero['highlights']]},
        'logos': {'heading': c['logos_heading'], 'items': [{'image': img(f'logo-{i+1}.svg'), 'name': n, 'url': ''} for i, n in enumerate(c['logos'])]},
        'about': {'eyebrow': c['about']['eyebrow'], 'heading': c['about']['heading'], 'description': ''.join(f'<p>{p}</p>' for p in c['about']['p']),
                  'image': img('about-1.svg'), 'image_2': img('about-2.svg'), 'experience_number': c['since'], 'experience_label': c['about']['exp_label'],
                  'points': [{'text': t} for t in c['about']['points']], 'button': link('More about us', 'page:about')},
        'services': {'eyebrow': d.get('services_eyebrow', 'What we do'), 'heading': d.get('services_heading', 'Services built around your business'),
                     'description': c['about_short'], 'columns': d.get('home_services_columns', '4' if not svc_imgs else '2'), 'items': home_services},
        'stats': {'heading': '', 'items': [{'number': n, 'suffix': s, 'label': l, 'icon': ''} for n, s, l in c['stats']]},
        'features': {'eyebrow': 'Why us', 'heading': c['features_heading'], 'description': '',
                     'image': img(d['features_image']) if d.get('features_image') else '', 'items': c['features']},
        'process': {'eyebrow': 'How it works', 'heading': d.get('process_heading', 'A simple, proven way of working'), 'description': '',
                    'steps': c['process']},
        'testimonials': {'eyebrow': 'Client stories', 'heading': d.get('testimonials_heading', 'What our clients say'),
                         'items': [{'quote': q, 'name': n, 'role': r, 'photo': '', 'rating': 5} for q, n, r in c['testimonials']]},
        'cta': {'heading': c['cta'][0], 'description': c['cta'][1], 'background_image': img(d['cta_image']) if d.get('cta_image') else '',
                'button': link(c['cta_button'], 'page:contact'), 'secondary_button': link('Call us', 'tel:' + c['phone'].replace(' ', '')) if d.get('cta_call') else {}},
    }
    if not sections['cta']['secondary_button']:
        del sections['cta']['secondary_button']

    def banner(heading, sub):
        return {'type': 'page_banner', 'data': {'heading': heading, 'subheading': sub, 'background_image': banner_bg}}

    def sec(t, **over):
        data = json.loads(json.dumps(sections[t]))
        data.update(over)
        return {'type': t, 'data': data}

    home = [sec(t) for t in d.get('home', HOME_DEFAULT)]
    about = [banner(f'About {name}', c['about_short']), sec('about', button=link('Get in touch', 'page:contact')), sec('stats'),
             {'type': 'team', 'data': {'eyebrow': 'Leadership', 'heading': 'The people behind our work', 'description': '',
                                       'members': [{'photo': img(f'team-{i+1}.svg'), 'name': n, 'role': r, 'bio': b, 'linkedin': ''} for i, (n, r, b) in enumerate(c['team'])]}},
             sec('cta')]
    services_page = [banner('Services', c['about_short']),
                     {'type': 'services', 'data': {'eyebrow': 'Our services', 'heading': d.get('services_page_heading', 'Everything we can do for you'), 'description': '',
                                                   'columns': '3', 'items': [dict(s, image='', link={}) for s in services]}},
                     sec('process'),
                     {'type': 'pricing', 'data': {'eyebrow': 'Engagements', 'heading': 'Simple ways to work with us', 'description': 'Every engagement is agreed in writing before work begins.',
                                                  'plans': [{'name': n, 'price': p, 'period': per, 'features': f, 'button': link('Enquire', 'page:contact'), 'featured': i == 1}
                                                            for i, (n, p, per, f) in enumerate(c['plans'])]}},
                     {'type': 'faq', 'data': {'eyebrow': 'FAQ', 'heading': 'Common questions', 'description': '', 'items': [{'question': q, 'answer': a} for q, a in c['faq']]}},
                     sec('cta')]
    gal_imgs = ['hero.svg', 'about-1.svg', 'svc-1.svg', 'svc-2.svg', 'about-2.svg', 'svc-3.svg']
    gallery = [banner('Gallery', f'A look inside {name} — our people, places and projects.'),
               {'type': 'gallery', 'data': {'eyebrow': '', 'heading': '', 'description': '', 'columns': '3',
                                            'images': [{'image': img(f), 'caption': cap} for f, cap in zip(gal_imgs, c['gallery'])]}},
               sec('cta')]
    contact = [banner('Contact Us', 'Tell us what you need and we will get back to you within one working day.'),
               {'type': 'contact', 'data': {'eyebrow': 'Get in touch', 'heading': 'We would love to hear from you',
                                            'description': f'Visit our {c["city"]} office, call us, or send a message using the form.',
                                            'form_heading': 'Send us a message', 'show_form': True, 'show_details': True, 'map_url': ''}},
               {'type': 'faq', 'data': {'eyebrow': 'FAQ', 'heading': 'Before you get in touch', 'description': '', 'items': [{'question': q, 'answer': a} for q, a in c['faq'][:2]]}}]
    pages = {'': {'seo_title': '', 'sections': home}, 'about': {'sections': about}, 'services': {'sections': services_page},
             'gallery': {'sections': gallery}, 'contact': {'sections': contact}}
    pages.update(legal_pages(name))
    settings = {'site': {'name': name, 'tagline': c['tagline'], 'phone': c['phone'], 'email': c['email'], 'address': c['address'],
                         'hours': c['hours'], 'footer_about': c['about_short'], 'footer_text': 'All rights reserved.',
                         'rating': c['rating'], 'rating_label': c['rating_label'], 'header_button': link(c['cta_button'], 'page:contact'),
                         'social': {'linkedin': 'https://www.linkedin.com/', 'facebook': 'https://www.facebook.com/', 'instagram': 'https://www.instagram.com/'}},
                'seo': {'description': f'{name} — {c["tagline"].lower()} in {c["city"]}. {c["about_short"]}',
                        'keywords': f'{c["tagline"].lower()}, {c["city"]}'}}
    return {'settings': settings, 'pages': pages}


def page_types(demo):
    t = lambda slug: [s['type'] for s in demo['pages'][slug]['sections']]
    return {'home': t(''), 'about': t('about'), 'services': t('services'), 'gallery': t('gallery'), 'contact': t('contact'),
            'legal': ['page_banner', 'rich_text'], 'generic': ['page_banner', 'rich_text', 'cta']}


DEFAULT_PAGES = [
    {'title': 'Home', 'slug': '', 'type': 'home', 'is_home': True},
    {'title': 'About Us', 'slug': 'about', 'type': 'about'},
    {'title': 'Services', 'slug': 'services', 'type': 'services'},
    {'title': 'Gallery', 'slug': 'gallery', 'type': 'gallery'},
    {'title': 'Contact Us', 'slug': 'contact', 'type': 'contact'},
    {'title': 'Privacy Policy', 'slug': 'privacy-policy', 'type': 'legal', 'in_menu': False, 'in_footer': True},
    {'title': 'Terms and Conditions', 'slug': 'terms-and-conditions', 'type': 'legal', 'in_menu': False, 'in_footer': True},
]
SECTION_TYPES = ['hero', 'page_banner', 'about', 'services', 'features', 'stats', 'testimonials', 'gallery', 'team', 'faq', 'cta', 'contact', 'logos', 'rich_text', 'process', 'pricing']
