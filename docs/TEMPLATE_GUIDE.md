# Template Guide

Every template is a self-contained folder under `templates/<category-slug>/<template_id>/`. The builder validates and security-scans each template when it is uploaded, registered, activated, or given a new version (`app/Modules/Templates/ManifestValidator.php`). A template that fails validation cannot be activated.

> **Status:** Phase 1 enforces the folder structure, the manifest, and the security rules below. The rendering API available inside views (`$view`, `$data`, `$site`) is finalised in Phase 2 together with the runtime. This guide will be extended then.

## Folder structure

```
business_template_01/
├── manifest.json            required
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
| `features` | no | subset of `contact, blog, listings, payments, ecommerce`: modules this template always needs |
| `optional_features` | no | same values: modules the admin may switch on |
| `theme.defaults` | yes | `primary`, `secondary`, `bg`, `text` as `#RRGGBB`. May also set `heading_font`, `body_font`, `radius`, `button_style`, `header_style`, `footer_style` |
| `section_types` | yes | list of section type names. Each needs `sections/<type>.php` |
| `custom_section_types` | no | `{ "type": "sections/type.schema.json" }` for types not in the core library |
| `page_types` | yes | `{ "home": { "default_sections": [...], "layout": "default" }, ... }` |
| `default_pages` | yes | `[{ "title", "slug", "type", "is_home" }]`. Exactly one home page. Slugs are lowercase with hyphens (empty for home) |

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
