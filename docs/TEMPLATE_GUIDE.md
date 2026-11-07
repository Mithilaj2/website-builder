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
