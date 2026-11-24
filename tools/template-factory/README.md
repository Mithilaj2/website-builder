# Template factory

A development tool that generates standalone templates from short design specs. It is how
templates 02–10 of each category are produced. The factory is **not** part of the builder or of
any generated website. Nothing it creates depends on it at runtime, and it is never exported.

```
python3 tools/template-factory/factory.py --category business           # build every spec in specs/business/
python3 tools/template-factory/factory.py specs/business/business_template_04.json
```

Each run rewrites `templates/<category>/<id>/` completely, keeping only an existing `preview.jpg`.
After building, register the template in the builder (Templates → Scan) as usual. The builder's
validator checks factory output exactly like a hand-made template.

## What a generated template contains

The same layout as any hand-made template:

- `manifest.json` with `page_types` derived from the demo
- `layouts/default.php`
- `partials/`
- `sections/`
- `assets/css/style.css`
- `assets/js/main.js`
- `assets/images/*.svg`
- `demo.json`

Every class name uses the template's own prefix (e.g. `nbf-hero`), so the template can be edited by
hand afterwards like any other.

## Folder layout

| Path | What it holds |
|---|---|
| `factory.py` | Reads a spec, chooses the variant files, fills `{{p}}` (prefix), `{{mood}}` and `{{name}}`, writes the template |
| `lib/layouts`, `lib/partials` | Shared markup. `header.<variant>.php` and `footer.<variant>.php` become `header.php` / `footer.php` |
| `lib/sections` | `<type>.php`, or `<type>.<variant>.php` for sections that have variants |
| `lib/css/base.css` | Token-driven base styles (`--{{p}}-*` variables built on the site's `--wb-*` theme variables) |
| `lib/css/<component>.<variant>.css` | One module per variant; only the chosen ones are concatenated into `style.css` |
| `lib/css/sec.<type>.css` | Styles for a section type without variants (e.g. `booking_form`), added only when the category uses that type |
| `lib/js/main.js` | Sticky-header shadow, count-up numbers, reveal on scroll (all off for reduced motion) |
| `images/kit.py` | Palette-driven SVG primitives (figures, portraits, buildings, trucks, panels, monitors, logos…) |
| `images/<category>_images.py` | Category scenes. `generate(out_dir, niche, primary, secondary, seed, logo_names)` |
| `demos/<category>.py` | Builds `demo.json`, the page-type lists and the default pages for a category |
| `demos/<category>_content.py` | Demo copy per niche (company, services, stats, team, FAQ, pricing…) |
| `specs/<category>/*.json` | One spec per template |

## Spec reference

```json
{
  "id": "business_template_04", "category": "business", "version": "1.0.0",
  "name": "Greenfield Harvest", "prefix": "gfh", "niche": "agro", "seed": 4, "mood": "light",
  "description": "Shown in the template library…",
  "direction": "One line for the stylesheet header and the design-directions table",
  "theme": { "primary": "#2F5D3A", "secondary": "#D9A441", "bg": "#FBF8F1", "text": "#22301F",
             "heading_font": "fraunces", "body_font": "dm-sans", "radius": "24px", "button_style": "pill" },
  "variants": { "header": "center", "hero": "panel", "banner": "solid", "head": "serif", "card": "tint",
                "about": "single", "services": "rows", "stats": "cards", "process": "timeline",
                "testimonials": "cards", "cta": "split", "team": "round", "gallery": "masonry",
                "faq": "split", "logos": "boxed", "footer": "band" },
  "surface": ["features", "testimonials"],
  "footer_style": "dark",
  "tokens": { "accent": "secondary", "hl": "…", "icon": "soft", "heading_weight": 600 },
  "extra_css": "optional template-specific rules",
  "demo": { "service_images": true, "home_services": 4, "home": ["hero", "about", "services", "…"] }
}
```

### Variants

| Component | Options |
|---|---|
| header | `bar` (info bar + navbar), `center` (centred masthead, nav below), `dark` (dark bar with phone), `pill` (floating rounded bar) |
| hero | `split`, `cover` (full-bleed image + highlight strip), `stack` (centred text, wide panorama), `panel` (colour panel + half-width image), `editorial` (oversized heading, index list) |
| banner | `solid`, `light` (left-aligned, optional thumbnail), `image` (photo with overlay) |
| head | `rule`, `pill`, `bar`, `serif` |
| card | `line`, `shadow`, `tint`, `top` (accent top border) |
| about | `layered`, `single`, `grid` |
| services | `cards`, `rows` (numbered rows, sticky heading) |
| stats | `band`, `cards`, `inline` |
| process | `line`, `cards`, `timeline` |
| testimonials | `cards`, `featured` |
| cta | `box`, `band`, `split` |
| team | `cards`, `round` |
| gallery | `grid`, `masonry` |
| faq | `split`, `center` |
| logos | `strip`, `boxed` |
| footer | `columns`, `centered`, `band` (contact cards above the columns) |
| courses | `cards` (image cards with level tag, duration and fee), `list` (rows with the fee and a button on the right) |
| events | `list` (date tile, details, thumbnail), `cards` (image cards with a date badge) |
| notices | `board` (label panel + list), `strip` (the latest three in a slim bar, for under the hero) |
| doctors | `cards` (portrait cards with speciality tag, experience, timings, booking button), `list` (photo-left horizontal cards, two per row) |
| opening_hours | `panel` (card with the timetable and an emergency highlight), `band` (dark band, two-column timetable) |

### Tokens

All optional:

| Token | Meaning |
|---|---|
| `accent` | Which theme colour fills primary buttons (`secondary` or `primary`) |
| `accent_hover`, `on_accent_hover` | Button hover colours |
| `hl` | Colour of eyebrows, numbers and highlights. Use a darkened mix when the secondary colour is too light for text, e.g. `color-mix(in srgb, var(--wb-secondary) 55%, #000)` |
| `link`, `hero_ink`, `brand_color`, `nav_active` | Text colours |
| `icon` | Icon-tile style: `solid`, `soft`, `accent` or `tint` |
| `icon_radius`, `mark_radius`, `radius_lg` | Corner radii |
| `heading_weight`, `hero_weight`, `track`, `case` | Heading typography (`case: uppercase` for industrial looks) |
| `hero_scale` | Hero heading size multiplier |
| `btn_case`, `btn_track`, `btn_font`, `nav_font` | Button and navigation typography |
| `surface`, `card`, `deep`, `header_bg` | Background colours (CSS values) |
| `band`, `on_band` | Colours of the `cta: band` variant |
| `pad` | Vertical section padding |
| `shadow`, `shadow_sm` | Shadows |

`mood: "dark"` switches the derived surfaces, cards, form fields and accordion icons to work on a dark
background (set `bg`/`text` in `theme` accordingly).

## Rules the factory keeps

- Colours are never hard-coded in the stylesheet: everything goes through `--wb-*` theme variables,
  so a client's colour and font changes in the theme customiser apply everywhere.
- No template shares a prefix with another (the factory refuses duplicates in one run; the Phase 7c
  tests also compare against existing templates).
- Mobile safety from earlier lessons: `minmax(0, 1fr)` grids, `min-width: 0` on flex children and
  fieldsets, `overflow-wrap: anywhere` on emails and phone numbers, gutters no wider than the
  container padding on phones.
- Only functions the template validator allows. Repeated markup lives in partials
  (`brand`, `quote`, `member`, `accordion`…), never in closures assigned to variables.
- Generated demo images are original SVG illustrations; no photographs or third-party artwork.
- Demo partner and "affiliation" logos use invented names, never real boards, universities or companies.
- The emergency number (site setting `emergency_phone`) appears in every header variant and in the timings
  highlight only when the site sets one, so templates of other categories are unaffected.
- Menu links never wrap on desktop; between 992 and 1400px they tighten and the emergency link shows only
  its number. Keep page titles short (one or two words) so seven menu items fit.
- Shop chrome (announcement bar, shop icons, Shop menu, trust strip) renders only when the site's shop is
  enabled, so other categories built from the same library are unaffected. Choose the product card with the
  `product-card` variant (`overlay` or `plain`) and the category display with `product_categories`
  (`tiles` or `circles`). Prefer non-`bar` headers for shops: the announcement bar already sits on top.
- Demo shops never include payment keys or other secrets; prices are in paise.
- Booking-form dropdown labels must not contain the word "date": the runtime validates any field whose
  label contains "date" as a calendar date, so such a dropdown could never be submitted. The education
  demo builder refuses to build if one does.

## Categories built so far

| Category | Image scenes | Demo niches |
|---|---|---|
| Business | `images/business_images.py` | logistics, IT, agro exports, engineering, HR, solar, construction, legal, agency |
| Hospital | `images/hospital_images.py` (18 clinical tiles, nine heroes, doctor portraits in white coats) | multi-speciality, dental, eye, children’s, diagnostics, physiotherapy, Ayurveda, maternity, cardiac |
| E-commerce | `images/ecommerce_images.py` (about 40 product drawings: two images per product, category groups, shelf scenes, promo banners, customers) | fashion, electronics, grocery, beauty, jewellery, toys, coffee, sports, books |
| Education | `images/education_images.py` (19 reusable tiles: classroom, lab, library, sports, music, stage, campus…) | preschool, coaching, engineering college, international school, coding bootcamp, performing arts, business school, language centre, boarding school |

## Adding a category

1. Write `images/<category>_images.py` with a `generate()` function, reusing `kit.py`.
2. Write `demos/<category>.py` (exporting `build(spec)`, `page_types(demo)`, `SECTION_TYPES`, and either
   `DEFAULT_PAGES` or `default_pages(spec)`; optionally `FEATURES` / `OPTIONAL_FEATURES`) and its content file.
3. Add sections the category needs to `lib/sections` (with prefix placeholders) and their CSS modules.
4. Write the specs. Vary at least the palette family, the heading typeface, the hero, the header
   and the card style between templates of the same category.
