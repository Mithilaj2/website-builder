# Template Guide

Every template is a self-contained folder under `templates/<category-slug>/<template_id>/`. The builder validates and security-scans each template when it is uploaded, registered, activated, or given a new version (`app/Modules/Templates/ManifestValidator.php`). A template that fails validation cannot be activated.

Reference implementation: `templates/business/business_template_01` (Meridian Corporate).

## Folder structure

```
business_template_01/
├── manifest.json            required
├── demo.json                optional: demo content for previews and new websites (see below)
├── preview.jpg              optional (jpg/png/webp); imported as the template's preview image
├── layouts/
│   └── default.php          required; more layouts allowed (referenced by page_types.*.layout)
├── partials/
│   ├── header.php           required
│   └── footer.php           required
├── sections/
│   └── <type>.php           one view for every entry in section_types
│   └── <type>.schema.json   field schema for template-specific (custom) section types
└── assets/                  required: css/, js/, images/, fonts/, vendor/ (e.g. bootstrap)
```

## manifest.json

| Key | Required | Rules |
|---|---|---|
| `id` | yes | lowercase letters, numbers and underscores. Must equal the folder name, e.g. `business_template_01` |
| `name` | yes | 1–150 characters |
| `category` | yes | an existing category slug (`business`, `education`, `hospital`, `real-estate`, `ecommerce`, `travel`, `restaurant`, `hotel`, `portfolio`, `services`) |
| `version` | yes | `MAJOR.MINOR.PATCH`. A new upload must use a higher version |
| `description` | no | shown in the builder |
| `libraries` | no | runtime-provided libraries to load: `bootstrap`, `bootstrap-icons`. They are bundled once in `runtime/assets/lib`, not in every template |
| `theme.header_styles` / `theme.footer_styles` | no | `{ "key": "Label" }`. The key becomes the body class `wb-header-<key>` / `wb-footer-<key>` |
| `features` | no | subset of `contact, blog, listings, payments, ecommerce`: modules this template always needs |
| `optional_features` | no | same values: modules the admin may switch on |
| `theme.defaults` | yes | `primary`, `secondary`, `bg`, `text` as `#RRGGBB`. May also set `heading_font`, `body_font`, `radius`, `button_style`, `header_style`, `footer_style` |
| `section_types` | yes | list of section type names. Each needs `sections/<type>.php` |
| `custom_section_types` | no | `{ "type": "sections/type.schema.json" }` for types not in the core library |
| `page_types` | yes | `{ "home": { "default_sections": [...], "layout": "default" }, ... }` |
| `default_pages` | yes | `[{ "title", "slug", "type", "is_home", "in_menu", "in_footer" }]`. Exactly one home page. Slugs are lowercase with hyphens (empty for home) |

## Security rules (enforced)

Templates contain **presentation only**. These rules are enforced:

- PHP files may exist only in `layouts/`, `partials/` and `sections/`.
- The following are not allowed:
  - `include` / `require` (use `$view->partial('name')` instead)
  - `eval`, `global`, backtick shell commands, or variable function calls
  - request superglobals (`$_GET`, `$_POST`, `$_SERVER`, `$_SESSION`, …): use the data passed to the view
  - file, network, shell, mail, header and decode functions (`file_put_contents`, `fopen`, `curl_*`, `exec`, `system`, `mail`, `header`, `base64_decode`, …)
- These files are rejected: `.htaccess`, `.user.ini`, `.phar`, `.phtml`, shell scripts, executables, and symbolic links.
- CSS, JS and HTML that load external resources (CDNs, web fonts) produce a warning. Generated sites must bundle every asset locally.
- ZIP uploads are rejected if they contain absolute paths, `..` segments, symlinks, more than 3,000 files, or more than 200 MB uncompressed.

## Theme variables

Use CSS custom properties so the theme customiser can recolour a template without breaking its layout:

```css
:root {
  --wb-primary: #0B3D91;  --wb-secondary: #F2A900;
  --wb-bg: #FFFFFF;       --wb-text: #1F2937;
  --wb-heading-font: 'Manrope', sans-serif;  --wb-body-font: 'Inter', sans-serif;
  --wb-radius: 8px;
}
```

The runtime (Phase 2) writes the site's chosen values into `assets/theme.css`, which is loaded after the template's own CSS.

## Versions

- **Upload new version:** same `id`, higher `version`. The previous files are kept as a ZIP snapshot in `storage/backups/templates/<id>/`.
- **Restore:** any snapshot can be restored as the current version.
- **Duplicate:** copies the folder under a new id at version `1.0.0`. Use it as the starting point for a new design.
- **Delete:** the folder is archived to `storage/backups/templates/deleted/` before removal. Templates used by websites cannot be deleted.

## Writing views

Every layout, partial and section file receives:

| Variable | Contents |
|---|---|
| `$view` | the rendering API (below) |
| `$data` | (sections only) the section's field values, as defined by its schema |
| `$section` | (sections only) `id`, `section_type`, `style` |
| `$page` | the current page: `title`, `slug`, `page_type`, `is_home`, SEO fields |
| `$site` | site details: `name`, `tagline`, `email`, `phone`, `address`, `hours`, `social`, … |
| `$content` | (layouts only) the rendered sections |

### `$view` API

| Method | Use |
|---|---|
| `e($v)` / `text($v)` | escape text / escape and keep line breaks. **Always** escape field values |
| `rich($html)` | output a rich-text field (already sanitised when saved) |
| `head()` | `<title>`, meta description/keywords, favicon and the theme `<style>` (fonts + `--wb-*` variables). Put it inside `<head>` |
| `bodyClass()` | classes for the chosen button, header and footer styles |
| `libStyles()` / `libScripts()` | `<link>`/`<script>` tags for the declared `libraries` |
| `asset('css/style.css')` | URL of a file in the template's `assets/` |
| `img($ref, 'theme:images/x.svg')` | URL for an image field (`uploads/…` or `theme:…`), with an optional fallback |
| `alt($ref, $default)` | alt text from the media library |
| `partial('header', [...])` | render `partials/header.php` |
| `attrs($section, 'extra-class')` | `id`, classes and background/spacing styles for a section's root element. Every section must use it |
| `menu('header' \| 'footer')` | menu items `[title, url, active, external]`: the client's custom menu from the website admin (Navigation) if set, otherwise built from the pages. Add `target="_blank" rel="noopener"` when `external` is true |
| `url('slug')` / `href($link)` / `hasLink($link)` / `linkAttrs($link)` | page URLs and link fields (`page:slug`, https, #anchor, mailto:, tel:) |
| `site('key', $default)` / `setting('group', 'key')` / `social()` / `logo()` | site details |
| `icon('bi-briefcase')` | a Bootstrap icon |
| `formAction('contact')` / `formGuard()` / `formsEnabled()` / `formStatus('contact')` | public forms. A form posts to `formAction()`, must print `formGuard()` (signed token, honeypot, return page), and should show `formStatus()` (`['status' => 'sent'\|'error', 'message' => …]`) after the redirect. Give the form heading `id="contact-form"` so the redirect lands on it. In the builder preview, forms are shown but disabled |

## Section types

Core section types (fields defined in `runtime/section-types/*.json`): `hero`, `page_banner`, `about`, `services`, `features`, `stats`, `testimonials`, `gallery`, `team`, `faq`, `cta`, `contact`, `logos`, `rich_text`, `process`, `pricing`.

Category section types (Phase 7a), available to any template that lists them:

| Type | For | Notes |
|---|---|---|
| `courses` | Education | Cards with image, level, duration, fee and link |
| `events` | Education, any | `date` must be `YYYY-MM-DD` (text field with a `pattern`). With `hide_past` on, past events are hidden on the live site but still shown in the builder preview (`$view->isPreview()`). Sort by date in the view |
| `notices` | Education, any | Dated announcements with an optional link and a "New" flag |
| `doctors` | Hospital | Photo, name, qualification, specialty, experience, consultation timings, button |
| `opening_hours` | Hospital, any | Timing rows plus one highlighted line (e.g. Emergency · 24 × 7) and a note |
| `booking_form` | Any | Appointment / admission / booking / enquiry form, see below. Restaurant 01 uses it for table bookings (Guests, Time, Date) and Hotel 01 for event enquiries |
| `menu` | Restaurant, café, bar | Dishes in one list (up to 80), each with a `course`, name, description, price text, food type (`veg`, `nonveg`, `egg`, `vegan` or none), spice level 0–3, a label and an optional photo. Group dishes by `course` in order of first appearance. `layout` is `tabs` or `list`; with tabs, render every course with its heading and let JavaScript turn them into tabs, so the menu still works without JavaScript. Show a key for the marks and the `note` |

**`booking_form`.** Post it to `$view->formAction($form)` where `$form` is the section's `form_type` (`appointment`, `admission`, `booking` or `enquiry`), include `$view->formGuard()`, and read the result with `$view->formStatus($form)`. Give the form card `id="<form>-form"`: the redirect after posting jumps to that anchor, and buttons elsewhere can link to it (`#admission-form`). Field rules, enforced by the runtime:
- `name` is required, plus a valid mobile number **or** email. If `email_required` is on, post `_email_required=1` and mark the email input `required`. `message` is optional.
- Up to two dropdowns and a date, posted as `extra[Label]=value` (max 10 extras, labels up to 40 characters). A label containing "date" must be a real `YYYY-MM-DD` date; use `<input type="date" min="today">`.
- Extras are stored in `contact_submissions.extra_json`, listed in the admin's Enquiries screen and CSV (Form, Details columns) and included in the notification email, whose subject names the kind ("New appointment request: …").
- The contact form (`contact`) keeps its own rule: email and message required.

**Site details used by templates (Phase 7a).** Besides name, tagline, phone, email, WhatsApp, address and hours, the client can now edit `emergency_phone` (shown by templates with an emergency strip) and a highlight badge: `rating` (title, max 40) and `rating_label` (text, max 90). Hide each element when its value is empty.

Field types: `text`, `textarea`, `richtext`, `image`, `color`, `link`, `number`, `select`, `toggle`, `icon`, `repeater` (with sub-fields). The builder generates the editing form from the schema and validates every save against it on the server.

A template supports any subset of these, plus its own custom types (`custom_section_types`, using the same JSON format).

Every section also has these style options, applied through `$view->attrs()`:
- background colour
- background image with an optional dark overlay
- spacing: `wb-pad-none`, `-sm`, `-md` or `-lg`
- text tone: `wb-tone-light` or `wb-tone-dark`
- an anchor id

Templates should style those classes.

## demo.json

```json
{
  "settings": { "site": { "name": "…", "phone": "…", "social": { "linkedin": "https://…" } }, "seo": { "description": "…" } },
  "pages": {
    "":      { "sections": [ { "type": "hero", "data": { "heading": "…", "image": "theme:images/hero.svg" } } ] },
    "about": { "seo_title": "…", "sections": [ … ] }
  }
}
```

Keys under `pages` are page slugs (`""` is the homepage). A page without demo sections gets its page type's `default_sections` filled with schema defaults.

When a website is created, the client's name, email, phone and address replace the demo values. Demo images must be the template's own files (`theme:` references). Never use stock photos without a licence.

## Lessons from testing

- **Gutters on phones:** a Bootstrap `.row.g-5` overhangs a `.container` by 12 px on small screens, so the whole page scrolls sideways. Use `g-4 g-lg-5` (or smaller) inside containers. Check every page at 360 px for `document.documentElement.scrollWidth > innerWidth`.
- **Runtime pages:** the website runtime renders some pages itself inside your `layouts/default.php` (the "Pay online" pages at `/pay`, styled by `assets/pay/pay.css` with your `--wb-*` colours). Your layout must not assume a page from the database: it must work when `$view->page['id']` is 0.
- **Link to payments:** buttons and menus can use `page:pay`.
- **Button colours vs link colours (found on Education 01):** a rule like `.lyc a { color: … }` is more specific than a single-class `.lyc-btn-primary { color: … }`, so button text took the link colour and disappeared on dark buttons. Prefix every button colour rule with the template class (`.lyc .lyc-btn-primary`, including `:hover` and `:focus-visible`). The screenshot check now flags any `.btn` whose text colour equals its background.
- **No closures or variable functions in views.** The validator rejects `$fn(...)`; precompute values (e.g. `$telPhone = 'tel:' . preg_replace(...)`) or arrays instead.
- **Lazy images and full-page screenshots:** headless full-page captures do not load `loading="lazy"` images below the fold. Scroll through the page before capturing, and check `naturalWidth` of every image.
- **Long words on phones:** emails and URLs in footers and contact lists need `overflow-wrap: anywhere`.

## Shop templates (e-commerce)

Declare `"ecommerce"` in the manifest's `features`: the website admin then shows Orders, Products and Coupons, and the shop can be switched on. Put the shop in `demo.json` under `"shop"`: `settings` (same keys as Shop settings, money in paise, `"enabled": true`), `categories` (`name`, `slug`, `description`, `image`), `products` (`name`, `slug`, `category` slug, `price` in paise, `compare_price`, `gst_rate`, `hsn`, `stock`, `featured`, `badge`, `image`, `gallery`, `short_description`, `description`, `sku`, and optionally `option_name` + `variants`: `label`, `price`, `stock`, `sku`) and `coupons`.

The runtime renders `/shop`, product, cart, checkout and order pages inside your `layouts/default.php`, styled by `assets/shop/shop.css` with the `--wb-*` variables:
- restyle any `.wbshop-*` class in your stylesheet, scoped with your prefix (`.trm .wbshop-title`);
- add `partials/product-card.php` and the shop grids use your tile (it receives `$product`);
- or replace a whole page with `theme/shop/{list|product|cart|checkout|order}.php`.

`$view` helpers for headers, footers and sections:
- `shopEnabled()`, `shopUrl($sub, $query)`, `productUrl($p)`, `categoryUrl($c)`;
- `products(['featured' => true, 'category' => 'slug', 'sort' => 'new', 'limit' => 8])` and `productCategories()`;
- `money($paise)` gives `₹1,299`; `cartCount()`;
- `shopToken()`, the hidden `_t` value for an "Add to cart" form posting `action=add`, `product_id`, `qty` (and `variant_id`) to `shopUrl('cart')`.

Never link to the shop with a page called "shop": `page:shop` already points to it, and `shop` is a reserved address.

## Listing templates (properties, packages, rooms, projects…)

Templates whose content is a catalogue of things people enquire about, rather than buy, declare a `listings` block in `manifest.json`. The runtime then adds:
- a public index at `/{path}` with filters, search, sorting and pagination, and a detail page at `/{path}/{slug}` with a photo gallery and lightbox, specifications, lists, an enquiry form and similar items;
- a menu entry in the website admin (named after `plural`) with an add/edit form generated from your attributes, photos from the media library, and a categories screen;
- the `listings` and `listing_categories` section types.

```json
"listings": {
  "path": "properties",              // one of: properties, packages, tours, rooms, projects, portfolio, work, services, listings, destinations, vehicles (reserved on that website only)
  "singular": "Property", "plural": "Properties", "menu_label": "Properties", "icon": "bi-buildings",
  "category_label": "Property type", "location_label": "Locality", "location_filter": true,
  "price_label": "Price", "price_format": "lakh",      // "inr" (₹24,999), "lakh" (₹85 Lakh, ₹1.25 Cr) or "none" (no prices anywhere: portfolios)
  "price_notes": ["onwards", "per month"],             // suggestions in the admin
  "closed_label": "Sold",                              // status shown for closed items (they stay visible, sorted last)
  "attributes": [
    { "key": "bedrooms", "label": "Bedrooms", "type": "number", "suffix": "BHK", "filter": true, "card": true, "icon": "bi-door-closed" },
    { "key": "listing_for", "label": "Available for", "type": "select", "options": ["Sale", "Rent"], "filter": true },
    { "key": "amenities", "label": "Amenities", "type": "list" }
  ],
  "enquiry": { "heading": "Schedule a site visit", "button": "Request a visit", "date_label": "Preferred visit date",
               "select_label": "I am", "select_options": ["Looking to buy", "Looking to rent"], "message_label": "…", "success": "…" }
}
```

- Attribute types: `number` (filter = minimum, e.g. "3+ bedrooms"), `text`, `select` (with `options`), `toggle` (yes/no), `list` (one per line in the admin; shown as a checklist). `filter: true` adds it to the index filters (number, select and toggle only). `card: true` marks it for listing cards (`facts` with `card`).
- Enquiries go to the normal enquiries inbox and notification email as form type `enquiry`, with the item title (labelled with `singular`), the select answer and the date as extra fields.
- Put demo data in `demo.json` under `"listings"`: `categories` (`name`, `slug`, `description`, `image`) and `items` (`title`, `slug`, `category` slug, `location`, `price` in paise or `null` for "on request", `price_note`, `image`, `gallery`, `attributes` {key: value}, `short_description`, `description` (HTML), `badge`, `featured`, `status` active/closed/draft).

Styling and overrides:
- The pages use `assets/listings/listings.css` (`.wblist-*` classes, `--wb-*` variables). Restyle with your prefix (`.kst .wblist-filters`).
- Add `partials/listing-card.php` and every grid (index, similar items, the `listings` section) uses your card; it receives `$item` with `title`, `slug`, `location`, `price_text`, `price_note`, `badge`, `is_closed`, `category_name`, `image`, `gallery`, `attrs` (raw values), `facts` (`key`, `label`, `icon`, `value` formatted with its suffix, `card`) and `lists`.
- Or replace a whole page with `theme/listings/index.php` or `theme/listings/detail.php`.
- Set `"location_filter": false` when the location field is free text that makes a poor filter (travel routes, hotel wings, a portfolio's client). The detail page then shows it as "`location_label`: value" instead of with a map pin.
- `menu_label` is the name used in the menu and breadcrumbs when it should differ from `plural` (Portfolio 01: menu "Work", counts "10 projects found").
- Leave `enquiry.date_label` empty to drop the date field (Services 01 call-back form).
- Detail galleries adapt to 1, 2, 3, 4 or 5+ photos.

`$view` helpers: `listingsEnabled()`, `listingConfig()`, `listingsUrl(['c' => 'villas', 'a' => ['bedrooms' => 3], 'max' => 5000000])` (budget filters in rupees), `listingUrl($item)`, `listings(['featured' => true, 'category' => 'slug', 'sort' => 'new|price_asc|price_desc', 'limit' => 6, 'include_closed' => false])`, `listingCategories()` (with `listing_count`), `listingLocations()`, `listingPrice($paise)`. Link to the index from content with `page:listings`.

## Design directions (so templates in a category do not look alike)

| Template | Prefix | Palette | Type | Signature motif |
|---|---|---|---|---|
| Business 01 Meridian Corporate | `mrd` | Navy + amber | Manrope + Inter | Corporate grid, numbered process |
| Business 02 Northbridge Freight | `nbf` | Deep teal + amber on white, dark teal footer | Sora + Inter | Industrial and dependable: full-bleed photo hero with a stat strip, dark info bar, amber accents, image service cards and a dark four-column footer. |
| Business 03 Vertex Nightshift | `vtx` | Dark navy page, violet + cyan | Space Grotesk + Inter | Dark, technical and precise: floating pill navigation, centred hero over a glowing panorama, tinted cards, outlined numbers and a centred footer. |
| Business 04 Greenfield Harvest | `gfh` | Forest green + harvest gold on cream | Fraunces + DM Sans | Warm and earthy: centred masthead, split colour-panel hero, serif headings on cream, numbered service rows, a timeline and contact cards above the footer. |
| Business 05 Ironclad Works | `icw` | Graphite + red-orange on white | Montserrat + Open Sans | Heavy-duty and engineered: dark navigation, oversized uppercase editorial hero, sharp corners, top-accent cards, outlined step numbers and a red-orange call-to-action band. |
| Business 06 Brightpath Talent | `bpt` | Violet + sunflower on white, light footer | Plus Jakarta Sans + Inter | Friendly and optimistic: split hero with layered frame, violet and sunflower palette, soft shadowed cards, pill labels, round team portraits and a light footer. |
| Business 07 Suncrest Renewables | `scr` | Ocean blue + sun yellow on white | Poppins + Inter | Bright and clean-tech: floating pill navigation over a full-bleed cover hero, ocean blue and sun yellow, image service rows, a timeline and contact cards in the footer. |
| Business 08 Crestmark Construct | `cmc` | Charcoal + construction yellow on white | Jost + Roboto | Solid and confident: charcoal navigation, split panel hero with a yellow edge, accent-topped image cards, large inline numbers and a yellow call-to-action band. |
| Business 09 Harbor Chambers | `hbc` | Navy + bronze on ivory | Cormorant Garamond + DM Sans | Classic and composed: centred masthead, oversized serif editorial hero, navy and bronze on ivory, hairline cards, numbered practice rows and a centred footer. |
| Business 10 Pulse Studio | `pls` | Ink + coral on warm paper | DM Serif Display + DM Sans | Playful and editorial: floating pill navigation, split hero with big rounded frames, display serif headings on warm paper, coral accents and a coral call-to-action band. |
| Education 01 Lyceum Classic | `lyc` | Forest green + saffron on paper | Fraunces + DM Sans | Arch frames, school crest, notice board, dark hero |
| Education 02 Acorn Playschool | `lac` | Tomato + sunshine on warm cream, light footer | Poppins + DM Sans | Bright and cheerful: floating pill navigation, split hero with round frame, tomato and sunshine palette on warm cream, tinted rounded cards, round teacher portraits. |
| Education 03 Apex Rankers | `apx` | Navy + signal red on white | Montserrat + Inter | Driven and results-focused: dark navigation, colour-panel hero, navy and signal red, accent-topped cards, course list with fees, red numbers band. |
| Education 04 Circuit Campus | `mit` | Deep teal + amber on white | Sora + Inter | Modern and technical: info bar and clean navbar, full-bleed campus hero with a highlights strip, deep teal and amber, hairline cards, large inline numbers, a timeline admissions process. |
| Education 05 Cedar Global | `cis` | Plum + antique gold on ivory | Playfair Display + DM Sans | Refined and international: centred masthead, centred hero over a wide campus panorama, plum and antique gold on ivory, serif headings, soft shadows, a gold call-to-action band. |
| Education 06 Forge Terminal | `cfa` | Dark green-black page, emerald + highlighter yellow | Space Grotesk + Inter | Dark and hands-on: floating pill navigation on a near-black green page, terminal emerald and highlighter yellow, split hero, course rows, outlined step numbers and a centred footer. |
| Education 07 Raga Stage | `rsp` | Wine + marigold on warm cream | Lora + Jost | Expressive and theatrical: centred masthead, full-bleed stage hero, wine and marigold on warm cream, Lora serif headings, hairline cards, a marigold numbers band. |
| Education 08 Westbridge Executive | `wsm` | Ink + sky blue on white | Manrope + Inter | Corporate and editorial: dark navigation, oversized editorial hero, ink and sky blue on white, near-square corners, programme rows, inline numbers and a timeline admissions process. |
| Education 09 Lingua Bridge | `lgb` | Teal + coral on white, light footer | Plus Jakarta Sans + Inter | Friendly and conversational: info bar, split hero, teal and coral on white, pill labels, soft shadowed cards, round trainer portraits and a light footer. |
| Education 10 Pinecrest Heritage | `pcr` | Slate + rust on parchment | Merriweather + Open Sans | Heritage and outdoors: centred masthead, colour-panel hero with a mountain campus, slate and rust on parchment, Merriweather headings, programme rows, a timeline and a dark footer. |
| E-commerce 01 Terra Market | `trm` | Terracotta + sage on cream, ink footer | Playfair Display + Inter | Capsule (pill) frames, scrolling announcement bar, round category tiles, hover second image, rotating stamp |
| Hospital 01 CareLine Health | `crl` | Teal + coral on mint, navy footer | Plus Jakarta Sans + DM Sans | Leaf frames (two opposite corners rounded), heartbeat line, 24×7 emergency strip, quick-access bar, light hero |
| Real Estate 01 Keystone Realty | `kst` | Burgundy + champagne on warm stone, ink footer | Sora + DM Sans | Square-cornered frames with champagne rules, full-bleed dark hero with a tabbed buy/rent search panel, price set on the photo, three-cell fact strip on cards |
| Restaurant 01 Saffron Table | `sfr` | Dark charcoal page, saffron + chilli accents | DM Serif Display + Manrope | The only dark template so far: header floating over a table-spread hero, thin gold rules, menu with dotted price leaders and Indian veg / non-veg marks, uppercase letter-spaced buttons |
| Hotel 01 The Lakeview Haveli | `lvh` | Forest green + antique brass on ivory | Cormorant Garamond + Montserrat | Rajasthani arch-topped photo frames everywhere, centred two-row header, diamond ornaments in headings, lake-palace hero with a white stay finder |
| Portfolio 01 Studio Kiln | `skl` | Black + electric blue + acid lime on warm grey paper | Space Grotesk + Inter | Oversized tight headlines, a scrolling ticker of disciplines, hairline rules, two-column project grid with a lime arrow on hover, disciplines as a typographic index, giant email address in the footer, no rounded corners |
| Services 01 Ledgerline Advisors | `ldg` | Navy + emerald on white with blue-grey and mint bands | Poppins + Open Sans | Split hero with an illustration and a checklist, flat spot illustrations per service, fee and turnaround chips on cards, pricing packages with a highlighted middle plan, numbered step cards |
| Travel 01 Horizon Trails | `hzt` | Deep ocean + sunset orange on sand | Jost + Plus Jakarta Sans | Generous rounding and pill buttons, centred sunset hero with a capsule trip finder, mosaic destination tiles, duration chips and "From ₹… per person" card footers |

Business 02–10 and Education 02–10 were generated with the template factory (below); each changes the hero, header, card style, palette and heading typeface.

## Template factory (templates 02–10)

Templates 02–10 of each category are generated from short design specs by `tools/template-factory` (see its README). A spec picks a prefix, theme defaults, a variant for each component (5 heroes, 4 headers, 3 footers, 4 card styles, 4 heading styles, and variants for banners, about, services, stats, process, testimonials, CTA, team, gallery, FAQ and logos), a niche for the demo copy and original SVG illustrations recoloured to the palette. The output is an ordinary, self-contained template with its own prefix: it passes the same validator, can be edited by hand afterwards and does not depend on the factory. Rebuild with `python3 tools/template-factory/factory.py --category business`.

Later templates in the same category should change at least the palette family, the heading typeface, the frame/shape motif and the hero layout (light vs dark, image side, full-bleed vs boxed).

