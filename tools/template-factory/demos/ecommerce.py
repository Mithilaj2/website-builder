"""Builds demo.json (pages, settings and the shop catalogue) for an e-commerce template."""
import json
import os
from ecommerce_content import NICHES

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__)))))
HOME_DEFAULT = ['hero', 'product_categories', 'products', 'features', 'about', 'products_new', 'testimonials', 'cta']
SECTION_TYPES = ['hero', 'page_banner', 'about', 'features', 'stats', 'testimonials', 'gallery', 'faq', 'cta', 'contact', 'logos', 'rich_text', 'products', 'product_categories']
FEATURES = ['contact', 'ecommerce']
OPTIONAL_FEATURES = ['payments']


def link(text, url):
    return {'text': text, 'url': url}


def rupees(x):
    return int(round(x * 100))


def legal_pages(name):
    src = json.load(open(os.path.join(ROOT, 'templates/ecommerce/ecommerce_template_01/demo.json')))
    return {slug: json.loads(json.dumps(src['pages'][slug]).replace('Terra Market Crafts', name).replace('Terra Market', name))
            for slug in ('privacy-policy', 'terms-and-conditions')}


def shop_data(spec, c):
    img = lambda n: f'theme:images/{n}'
    cats = {k['slug']: k for k in c['categories']}
    products = []
    for i, p in enumerate(c['products']):
        cat = cats[p['cat']]
        item = {
            'name': p['name'], 'slug': p['slug'], 'category': p['cat'], 'price': rupees(p['price']),
            'image': img(f'product-{p["slug"]}.svg'), 'gallery': [img(f'product-{p["slug"]}-2.svg')],
            'gst_rate': cat['gst'], 'hsn': cat['hsn'], 'stock': p['stock'], 'featured': bool(p['featured']),
            'short_description': p['short'],
            'description': f'<p>{p["short"]}</p><h3>Details</h3><ul>' + ''.join(f'<li>{d}</li>' for d in cat['details']) + '</ul>',
            'sku': f'{c["prefix_code"]}-{p["cat"][:3].upper()}-{i+1:03d}',
        }
        if p['compare']:
            item['compare_price'] = rupees(p['compare'])
        if p['badge']:
            item['badge'] = p['badge']
        if p['variants']:
            share = max(1, p['stock'] // len(p['variants']))
            item['option_name'] = p['option']
            item['variants'] = [{'label': lab, 'price': rupees(pr), 'stock': share, 'sku': f'{item["sku"]}-{j+1}'} for j, (lab, pr) in enumerate(p['variants'])]
        products.append(item)
    flat, free, note = c['shipping']
    cod, cod_fee = c['cod']
    return {
        'settings': {'enabled': True, 'legal_name': c['name'], 'gstin': '', 'store_state': c['state'], 'invoice_address': c['address'].replace('\n', ', '),
                     'prices_include_tax': True, 'order_prefix': c['prefix_code'], 'invoice_prefix': c['prefix_code'] + '/', 'shipping_flat': rupees(flat),
                     'free_shipping_above': rupees(free), 'shipping_note': note, 'cod_enabled': cod, 'cod_fee': rupees(cod_fee), 'cod_max': 1000000,
                     'min_order': 0, 'low_stock': 5, 'return_policy': c['returns'], 'checkout_note': spec.get('demo', {}).get('checkout_note', '')},
        'categories': [{'name': k['name'], 'slug': k['slug'], 'description': k['description'], 'image': img(f'cat-{k["slug"]}.svg')} for k in c['categories']],
        'products': products,
        'coupons': [{'code': code, 'description': desc, 'type': t, 'value': v, 'min_order': rupees(mo), 'max_discount': rupees(mx)} for code, t, v, mo, mx, desc in c['coupons']],
    }


def build(spec):
    c = NICHES[spec['niche']]
    d = spec.get('demo', {})
    img = lambda n: f'theme:images/{n}'
    name, hero = c['name'], c['hero']
    sections = {
        'hero': {'eyebrow': hero['eyebrow'], 'heading': hero['heading'], 'subheading': hero['sub'], 'image': img('hero.svg'), 'background_image': '',
                 'primary_button': link('Shop now', 'page:shop'), 'secondary_button': link('Our story', 'page:about'),
                 'highlights': [{'icon': ic, 'text': t} for ic, t in zip(['bi-truck', 'bi-arrow-repeat', 'bi-cash-coin'], hero['highlights'])]},
        'product_categories': {'eyebrow': 'Browse', 'heading': 'Shop by category', 'description': '', 'limit': 4, 'show_counts': True},
        'products': {'eyebrow': 'Customer favourites', 'heading': d.get('featured_heading', 'Bestsellers'), 'description': '', 'source': 'featured', 'category': '', 'limit': 4,
                     'columns': '4', 'button': link('View all products', 'page:shop')},
        'products_new': {'eyebrow': 'Just in', 'heading': 'New arrivals', 'description': '', 'source': 'new', 'category': '', 'limit': 4, 'columns': '4', 'button': link('Shop new arrivals', 'page:shop')},
        'features': {'eyebrow': '', 'heading': '', 'description': '', 'image': '', 'items': c['features']},
        'about': {'eyebrow': c['about']['eyebrow'], 'heading': c['about']['heading'], 'description': ''.join(f'<p>{p}</p>' for p in c['about']['p']),
                  'image': img('about-1.svg'), 'image_2': img('about-2.svg'), 'experience_number': c['since'], 'experience_label': c['about']['exp_label'],
                  'points': [{'text': t} for t in c['about']['points']], 'button': link('Our story', 'page:about')},
        'stats': {'heading': '', 'items': [{'number': n, 'suffix': s, 'label': l, 'icon': ''} for n, s, l in c['stats']]},
        'testimonials': {'eyebrow': 'Reviews', 'heading': d.get('testimonials_heading', 'Loved by customers across India'),
                         'items': [{'quote': q, 'name': n, 'role': r, 'photo': img(f'customer-{i+1}.svg'), 'rating': 5} for i, (q, n, r) in enumerate(c['testimonials'])]},
        'logos': {'heading': 'As featured in', 'items': [{'image': img(f'logo-{i+1}.svg'), 'name': n, 'url': ''} for i, n in enumerate(c['logos'])]},
        'cta': {'heading': c['cta'][0], 'description': c['cta'][1], 'background_image': img('promo.svg') if d.get('cta_image', True) else '',
                'button': link('Start shopping', 'page:shop'), 'secondary_button': link('Read the FAQ', 'page:help')},
    }

    def banner(heading, sub):
        return {'type': 'page_banner', 'data': {'heading': heading, 'subheading': sub, 'background_image': d.get('banner_image', '')}}

    def sec(t, **over):
        data = json.loads(json.dumps(sections[t]))
        data.update(over)
        return {'type': 'products' if t == 'products_new' else t, 'data': data}

    faq = {'type': 'faq', 'data': {'eyebrow': 'Help', 'heading': 'Frequently asked questions', 'description': '', 'items': [{'question': q, 'answer': a} for q, a in c['faq']]
                                   + [{'question': 'Which payment methods do you accept?', 'answer': 'UPI, debit and credit cards, net banking' + (' and cash on delivery.' if c['cod'][0] else '.')},
                                      {'question': 'How long does delivery take?', 'answer': c['shipping'][2]}]}}
    flat, free, note = c['shipping']
    home = [sec(t) for t in d.get('home', HOME_DEFAULT)]
    about = [banner('Our Story', c['about_short']), sec('about', button={'text': '', 'url': ''}), sec('stats'),
             {'type': 'gallery', 'data': {'eyebrow': 'Inside', 'heading': f'A look inside {name}', 'description': '', 'columns': '3',
                                          'images': [{'image': img(f'cat-{k["slug"]}.svg'), 'caption': k['name']} for k in c['categories'][:3]]}}]
    help_page = [banner('Help & FAQ', 'Orders, delivery, payments and returns — answered.'), faq, sec('cta')]
    contact = [banner('Contact', 'Questions about an order or a product? We reply within one working day.'),
               {'type': 'contact', 'data': {'eyebrow': 'Get in touch', 'heading': 'We are happy to help', 'description': f'Write to us, call, or visit our {c["city"]} studio.',
                                            'form_heading': 'Send us a message', 'show_form': True, 'show_details': True, 'map_url': ''}}]
    ship_html = (f'<h2>Delivery</h2><p>{note}</p><ul><li>Delivery is free on orders above ₹{free:,}; otherwise a flat ₹{flat} applies.</li>'
                 + ('<li>Cash on delivery is available' + (f' for a ₹{c["cod"][1]} handling fee' if c['cod'][1] else '') + '.</li>' if c['cod'][0] else '<li>Cash on delivery is not available for this store.</li>')
                 + '<li>You will receive tracking details by email and SMS once your order ships.</li></ul>'
                 + f'<h2>Returns</h2><p>{c["returns"]}</p><p>To start a return, email {c["email"]} with your order number. Refunds are made to the original payment method within 5–7 working days of receiving the item.</p>')
    shipping = [banner('Shipping & Returns', 'How we deliver, and how returns work.'), {'type': 'rich_text', 'data': {'heading': '', 'content': ship_html, 'width': 'narrow'}}]
    pages = {'': {'seo_title': '', 'sections': home}, 'about': {'sections': about}, 'help': {'sections': help_page}, 'contact': {'sections': contact},
             'shipping-returns': {'sections': shipping}}
    pages.update(legal_pages(name))
    settings = {'site': {'name': name, 'tagline': c['tagline'], 'phone': c['phone'], 'email': c['email'], 'address': c['address'], 'hours': c['hours'],
                         'footer_about': c['about_short'], 'footer_text': 'All rights reserved.', 'header_button': {'text': '', 'url': ''},
                         'social': {'instagram': 'https://www.instagram.com/', 'facebook': 'https://www.facebook.com/', 'youtube': 'https://www.youtube.com/'}},
                'seo': {'description': f'{name}: {c["tagline"].lower()}. {c["about_short"]}', 'keywords': f'{c["tagline"].lower()}, online shop India'}}
    return {'settings': settings, 'shop': shop_data(spec, c), 'pages': pages}


def page_types(demo):
    t = lambda slug: [s['type'] for s in demo['pages'][slug]['sections']]
    return {'home': t(''), 'about': t('about'), 'help': t('help'), 'contact': t('contact'), 'legal': ['page_banner', 'rich_text'], 'generic': ['page_banner', 'rich_text', 'cta']}


DEFAULT_PAGES = [
    {'title': 'Home', 'slug': '', 'type': 'home', 'is_home': True},
    {'title': 'Our Story', 'slug': 'about', 'type': 'about'},
    {'title': 'Help & FAQ', 'slug': 'help', 'type': 'help'},
    {'title': 'Contact', 'slug': 'contact', 'type': 'contact'},
    {'title': 'Shipping & Returns', 'slug': 'shipping-returns', 'type': 'legal', 'in_menu': False, 'in_footer': True},
    {'title': 'Privacy Policy', 'slug': 'privacy-policy', 'type': 'legal', 'in_menu': False, 'in_footer': True},
    {'title': 'Terms and Conditions', 'slug': 'terms-and-conditions', 'type': 'legal', 'in_menu': False, 'in_footer': True},
]
