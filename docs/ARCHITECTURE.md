# Channel Softech Website Builder — Architecture

Version: 1.0 (Phase 0 — awaiting approval)
Owner: Channel Softech (P) Ltd.
Date: 6 Nov 2026

This document covers the ten items required by the specification (Section 21) before any code is written. It records the decisions agreed with the team, and it explains where the design deliberately differs from the original specification and why.

---

## 0. Agreed decisions (from the Q&A)

| # | Topic | Decision |
|---|---|---|
| 1 | Project | New project, GitHub repo `Mithilaj2/website-builder` |
| 3 | Users | Internal tool for the Channel Softech team (no public signup/billing) |
| 4 | Roles | Super Admin, Admin, Designer, Viewer |
| 5 | Workflow | Architecture approval first, then phase by phase with a review after each phase |
| 6 | Routing | Single front controller (`index.php`) + `.htaccess` clean URLs; pages are rendered from the database, not one PHP file per page |
| 7 | Source of truth | The builder owns site content until the site is marked **Deployed**. After that, regeneration updates code only and never touches content |
| 8/11 | Builder-side data | **One builder database.** Each site's data is in its own **table prefix** (e.g. `s000001_pages`). Every export contains a clean SQL file for that site only |
| 9 | Updates | The site runtime is versioned. Deployed sites receive a downloadable *update package* (no automatic remote pushes) |
| 10 | Hosting | cPanel shared hosting (builder and client sites) |
| 12 | Versions | PHP 8.1+, MySQL 5.7+ / MariaDB 10.3+ |
| 13 | Dependencies | Composer allowed. `vendor/` is bundled inside every export |
| 14 | Long tasks | No cron. Generation runs as AJAX-driven, resumable steps |
| 15 | Deployment | ZIP + SQL export only (no FTP push) |
| 16 | Templates | Designed by Claude: 10 distinct templates per category |
| 17 | Template order | Business, Education, Hospital and E-commerce first |
| 18 | Client limits | Clients cannot switch templates or add new section types |
| 19 | Category features | v1: listing modules with enquiry forms (doctors, rooms, properties, packages, courses, menu items). Full booking/availability systems come later |
| 20 | Payments | E-commerce checkout, plus a simple "Pay amount" flow for other categories |
| 21 | Gateways | Razorpay first, then PhonePe, PayU, Cashfree and Stripe, one at a time |
| 22 | Secrets | Payment secrets are never exported. The client enters keys in their own admin, where they are stored encrypted with a per-site key |
| 23 | Commerce | INR only, GST with configurable rates, flat or zone shipping, no courier API in v1 |
| 24 | Install | Web installer (`install.php`) that locks itself after use. No default passwords in the ZIP |
| 25 | Assets | Everything bundled locally (Bootstrap, icons, fonts). No CDNs |
| 26 | Language | English, with UI strings in language files so other languages can be added later |
| 27 | Editor | TinyMCE (self-hosted) and SortableJS |
| 28 | Blog | Optional feature, toggled per template/site |
| 29 | SVG | Allowed only after sanitization with `enshrined/svg-sanitize` |
| 30 | Branding | "Channel Softech Website Builder" with the company logo |

---

## 1. Requirements analysis — what we are really building

There are **three separate products** inside this project, and the design keeps them apart:

1. **The Builder** (Channel Softech internal tool): categories, templates, clients, the wizard, generation, export. It runs only on Channel Softech's server.
2. **The Site Runtime** (the CMS that every generated site carries): front controller, page and section renderer, client admin panel, and modules (contact, blog, listings, e-commerce, payments). It lives in the builder repo under `runtime/` and is **copied** into every generated site. A generated site never calls back to the builder.
3. **The Template Library**: presentation only (layouts, section views, CSS/JS, images, manifest). Each template is self-contained.

> Why a shared runtime that gets copied? Each site needs its own CMS (Spec §8), but writing 100 different CMSs is not maintainable. So one versioned CMS core is **copied** into each site at generation time, and each template supplies its own distinct look. This is how the spec's "independent website" and "own admin panel" requirements are both met without duplicating business logic by hand.

### Deviations from the original specification (with reasons)

| Spec says | Design does | Reason |
|---|---|---|
| §6/§7: create a new MySQL database per site on the builder server | One builder DB with a table prefix per site; a separate DB only on the client's host (made by the installer) | Agreed in Q&A (cPanel limits database creation and prefixes database names). Each site's data still stays separate and exports as its own SQL file |
| §6: generate `about.php`, `services.php` … | `index.php` + router; slugs come from the `pages` table | Clients must be able to add pages after deployment (§8) without creating PHP files |
| §6 steps 7–10: create the DB and insert data **at generation** | Site tables are created and seeded **when the template is selected** (wizard Step 2). Generation then only builds files and validates | Lets live preview (Step 8) run on the real data and code. Preview and output are always identical |
| §5 Step 7: enter payment keys in the builder | Builder records the gateway and mode; test keys are optional (for QA only) and are stripped on export | Decision 22: secrets are never exported |

---

## 2. Database architecture

### 2.1 Builder database (`<cpanel>_wbmaster`)

The cPanel username prefix (e.g. `chsoft_`) is handled by configuration. Builder tables use the prefix `wb_`. Site tables use `sNNNNNN_`. Both kinds live in the same database, and the builder's code never queries a site table except through `SiteDataRepository`, which always applies the prefix.

```
wb_users              id, name, email, password_hash, role ENUM(super_admin,admin,designer,viewer),
                      status, last_login_at, created_at, updated_at
wb_login_attempts     id, email, ip, attempted_at, success                 -- rate limiting
wb_sessions_meta      id, user_id, session_hash, ip, user_agent, created_at, last_seen_at
wb_settings           key, value                                           -- platform settings

wb_categories         id, name, slug, description, image, status, sort_order, created_at, updated_at
wb_templates          id, category_id, code (e.g. business_template_01), name, description,
                      preview_image, status, current_version, features (JSON text),
                      created_by, created_at, updated_at
wb_template_versions  id, template_id, version, manifest_hash, changelog, folder_path, created_at
wb_template_screens   id, template_id, image, caption, sort_order

wb_clients            id, business_name, contact_person, email, phone, address, notes,
                      status, created_at, updated_at
wb_websites           id, site_code (000001), client_id, category_id, template_id, template_version,
                      runtime_version, name, slug, domain, description, table_prefix,
                      folder_path, status ENUM(draft, config_pending, generating, generated,
                      testing, ready, deployed, archived, failed),
                      wizard_step, features (JSON text), payment_gateway, payment_mode,
                      deployed_at, created_by, created_at, updated_at
wb_generation_runs    id, website_id, type ENUM(generate, regenerate, export), status,
                      current_step, started_at, finished_at, triggered_by, error_message
wb_generation_steps   id, run_id, step_key, status, attempts, started_at, finished_at, log TEXT
wb_exports            id, website_id, run_id, zip_path, sql_path, size_bytes, checksum, created_at
wb_activity_log       id, user_id, action, entity_type, entity_id, details, ip, created_at
```

**Compatibility rule:** JSON is stored as `LONGTEXT`, not as the MySQL `JSON` type. MariaDB treats `JSON` differently, and many cPanel hosts run MariaDB. Columns that need filtering are real indexed columns.

### 2.2 Site database (prefix `sNNNNNN_` on the builder; any prefix or none on the client host)

Core tables (every site):

```
settings          key, value                   -- name, contact info, theme, SEO defaults, mail, features
admin_users       id, name, email, password_hash, role(owner,editor), status, last_login_at, ...
login_attempts    id, email, ip, attempted_at, success
pages             id, title, slug, page_type, is_home, status, sort_order,
                  seo_title, seo_description, seo_keywords, og_image, created_at, updated_at
sections          id, page_id, section_type, variant, title_internal, data LONGTEXT (field values),
                  style LONGTEXT (bg image/colour, spacing), is_enabled, sort_order, created_at, updated_at
global_sections   id, section_type, data, style          -- header and footer, shared by all pages
menus             id, location (header, footer), name
menu_items        id, menu_id, parent_id, label, page_id NULL, url NULL, target, sort_order
media             id, folder (logo, banners, gallery, pages, products), file_name, path,
                  mime, size, width, height, alt_text, created_at
contact_submissions id, form_key, name, email, phone, subject, message, ip, status(new,read,replied),
                  email_sent TINYINT, created_at
```

Optional modules (their tables are created only if the feature is enabled):

```
blog              blog_categories, blog_posts
listings          listing_types (doctor, room, property, package, course, menu_item ...),
                  listing_items (title, slug, summary, body, image, price, location, category,
                                 attributes LONGTEXT, status, sort_order),
                  listing_enquiries
payments          payment_settings (gateway, mode, encrypted credentials),
                  payment_transactions (gateway, gateway_order_id, gateway_payment_id, amount,
                                        currency, status ENUM(created,pending,success,failed,refunded),
                                        raw_response, verified_at, purpose, reference_id),
                  payment_requests (for the "Pay amount" flow)
ecommerce         product_categories, products, product_images, product_variants,
                  customers, customer_addresses, carts, cart_items, coupons,
                  orders, order_items, order_status_history,
                  shipping_zones, shipping_rates, tax_rates, invoices
```

Each module ships a `schema.sql` written with a `{{prefix}}` placeholder. The same file is used on the builder (`{{prefix}}` → `s000001_`) and by the client installer (`{{prefix}}` → whatever the client chooses, default empty).

**Isolation:**
- **On the client host**, isolation is physical: each site has its own database and credentials.
- **On the builder**, isolation is logical (table prefixes). Generated sites on the builder server are QA copies behind HTTP Basic Auth, not public sites. This is an accepted risk and is listed in §8.

---

## 3. Folder structure

### 3.1 Builder repository

```
website-builder/
├── public/                     ← cPanel document root points here (or public_html symlink)
│   ├── index.php               ← builder front controller
│   ├── preview.php             ← live preview endpoint (builder login required)
│   ├── assets/                 ← builder admin CSS/JS/images (Bootstrap 5, jQuery, SortableJS, TinyMCE)
│   └── .htaccess
├── app/                        ← builder code (not web-accessible)
│   ├── Core/                   ← Router, Request, Response, DB (PDO), Session, Csrf, Auth, Validator,
│   │                              View, Logger, Uploader, SvgSanitizer, FileSystem, Zip
│   ├── Modules/
│   │   ├── Auth/  Dashboard/  Users/  Categories/  Templates/  Clients/
│   │   ├── Wizard/             ← 8 steps
│   │   ├── SectionEditor/      ← dynamic forms from field schemas
│   │   ├── Media/
│   │   ├── Generator/          ← staged steps, run state machine
│   │   ├── Export/             ← ZIP + SQL dumper
│   │   └── Payments/           ← gateway definitions (fields, validation)
│   └── Views/                  ← layouts and partials for the builder admin
├── runtime/                    ← THE SITE CMS (copied into every site)
│   ├── VERSION
│   ├── core/                   ← bootstrap, router, DB, auth, csrf, renderer, mailer, crypto
│   ├── admin/                  ← client admin panel (pages, sections, media, menus, settings, SEO, ...)
│   ├── modules/
│   │   ├── contact/  blog/  listings/  payments/  ecommerce/
│   │   └── <module>/{module.json, schema.sql, controllers, admin views, public handlers}
│   ├── section-types/          ← field schemas for the core section library (hero.json, about.json ...)
│   ├── installer/              ← install.php + steps
│   └── skeleton/               ← index.php, .htaccess, config templates, error pages
├── templates/
│   └── <category>/<template_code>/   ← see §4.1
├── generated_websites/         ← output (.htaccess Basic Auth for the whole folder)
│   └── site_000001_acme/
├── storage/                    ← not web-accessible
│   ├── logs/  temporary/  backups/  staging/  exports/
├── database/
│   ├── schema.sql              ← builder tables
│   └── seeds/                  ← categories (the 10), roles, first super admin via setup script
├── composer.json  vendor/
├── docs/  ARCHITECTURE.md  PROGRESS.md  TEMPLATE_GUIDE.md
└── setup.php                   ← one-time builder installer (removed or locked after use)
```

On cPanel, `public/` is the document root (or `public_html` is pointed at it). If the host does not allow that, the builder's `.htaccess` files deny direct access to `app/`, `runtime/`, `storage/` and `database/`.

### 3.2 A generated site

```
site_000001_acme/
├── index.php                  ← front controller
├── install.php                ← only in the export package; locks after use
├── .htaccess                  ← clean URLs, deny config/, database/, storage/, core/
├── core/                      ← runtime core (copied, versioned)
├── modules/                   ← ONLY the enabled modules
├── admin/                     ← client admin panel
├── theme/                     ← the selected template (layouts, sections, partials)
│   ├── manifest.json
│   ├── layouts/  sections/  partials/
│   └── assets/css  js  images  fonts
├── assets/                    ← generated: theme.css (colour/font variables), favicon
├── uploads/                   ← logo/ banners/ gallery/ pages/ products/  (PHP execution disabled)
├── config/                    ← app.php (APP_KEY, base URL), database.php, mail.php — never web-accessible
├── storage/                   ← logs, cache, rate-limit data
├── database/schema.sql  database/data.sql
├── vendor/                    ← PHPMailer, gateway SDKs, dompdf (e-commerce only)
├── INSTALLATION.md
└── VERSION                    ← runtime + template version
```

---

## 4. How the template engine works

### 4.1 Template package

```
templates/business/business_template_01/
├── manifest.json
├── preview.jpg  screenshots/
├── layouts/        default.php  full-width.php
├── partials/       header.php  footer.php  breadcrumb.php
├── sections/       hero.php  about.php  services.php  stats.php  testimonials.php  cta.php ...
│                   (one view per supported section type; optional variants: hero.split.php)
└── assets/         css/style.css  js/main.js  images/  fonts/  vendor/bootstrap/
```

A template contains **no** business logic and **no** database code. It only receives clean, escaped data from the runtime and outputs HTML. It never references files outside its own folder (the validator checks this).

### 4.2 Manifest (abridged)

```json
{
  "id": "business_template_01",
  "name": "Meridian Corporate",
  "category": "business",
  "version": "1.0.0",
  "runtime": ">=1.0",
  "features": ["contact", "blog"],
  "optional_features": ["payments"],
  "theme": {
    "defaults": { "primary": "#0B3D91", "secondary": "#F2A900", "bg": "#FFFFFF", "text": "#1F2937",
                  "heading_font": "Manrope", "body_font": "Inter", "radius": "8px",
                  "button_style": "solid", "header_style": "sticky-light", "footer_style": "dark-4col" },
    "header_styles": ["sticky-light", "transparent-dark"],
    "footer_styles": ["dark-4col", "minimal"]
  },
  "section_types": ["hero", "about", "services", "why_us", "stats", "testimonials",
                    "gallery", "team", "faq", "cta", "contact", "blog_latest", "rich_text",
                    "meridian_timeline"],
  "custom_section_types": { "meridian_timeline": "sections/meridian_timeline.schema.json" },
  "page_types": {
    "home":    { "default_sections": ["hero", "about", "services", "why_us", "stats", "testimonials", "cta"] },
    "about":   { "default_sections": ["page_banner", "about", "team", "stats"] },
    "contact": { "default_sections": ["page_banner", "contact"] }
  },
  "default_pages": [
    { "title": "Home", "slug": "", "type": "home", "is_home": true },
    { "title": "About Us", "slug": "about", "type": "about" },
    { "title": "Services", "slug": "services", "type": "generic" },
    { "title": "Contact Us", "slug": "contact", "type": "contact" },
    { "title": "Privacy Policy", "slug": "privacy-policy", "type": "legal" }
  ],
  "demo_content": "demo.json"
}
```

### 4.3 Section types and dynamic editing fields

- **Core library** (`runtime/section-types/*.json`) defines common types: `hero, page_banner, about, services, why_us, stats, testimonials, gallery, team, faq, pricing, cta, contact, clients_logos, blog_latest, rich_text, video, map`, plus category types such as `doctors_list, rooms_list, properties_list, packages_list, courses_list, menu_list, products_featured, product_grid`.
- **Template-specific types** can add their own schema (e.g. `meridian_timeline`).
- **Each schema** lists fields. Field types are `text, textarea, richtext, image, images, color, link (text+url), number, select, toggle, icon, repeater (with sub-fields)`. Example `hero.json`: heading, subheading, description, background_image, primary_button {text,url}, secondary_button {text,url}.
- **The section editor** reads the schema and builds the form dynamically (Spec §5 Step 4). Saving validates against the same schema on the server.
- **Style options** are common to every section: background colour or image, overlay, spacing, and visibility.

### 4.4 Rendering

`Request → Router (slug → page) → load enabled sections ordered by sort_order → for each section: theme/sections/{type}[.{variant}].php with $data → wrap in theme/layouts/{layout}.php with header/footer partials → output`. If a template has no view for a type, the section is skipped and logged (the validator reports it at template upload).

### 4.5 Theme customization

Templates must use CSS custom properties: `--wb-primary, --wb-secondary, --wb-bg, --wb-text, --wb-heading-font, --wb-body-font, --wb-radius`, and so on. The runtime writes `assets/theme.css` from the site settings. Header and footer styles are partial variants declared in the manifest. Fonts come from a bundled library of about 20 open-licensed (OFL) web fonts, self-hosted as WOFF2, and only the fonts the site selects are copied.

### 4.6 How templates are made distinct

Each of the 10 templates in a category gets a distinct design direction before it is built: grid system, typography pairing, header pattern, hero composition, card style and motion. For example, Business 01 "Meridian" is classic corporate, 02 is an editorial split layout, 03 is a dark-tech style, 04 is a minimal Swiss style, and so on. Directions are recorded in `docs/TEMPLATE_GUIDE.md` so no two templates share a layout skeleton. All templates use the Bootstrap 5 grid internally (bundled per template) with fully custom CSS on top.

### 4.7 Template management

The admin uploads a template as a ZIP (or a Designer adds it to `templates/`). The validator checks the manifest schema, that every declared section has a view, that there are no external or `../` references, that there is no PHP outside `layouts/`, `partials/` and `sections/`, and that there are no dangerous functions (`eval`, `exec`, `system` …) in the views. **Duplicate** copies the folder under a new code. **Versions** are stored as `templates/<cat>/<code>/` (current) plus snapshot ZIPs in `storage/backups/templates/`, and a site records which template version it was generated from.

---

## 5. Website creation and generation

### 5.1 Wizard → data flow

| Step | What happens | Where data goes |
|---|---|---|
| 1 Client info | Client picked or created; site name, domain, description, category | `wb_clients`, `wb_websites` (status Draft) |
| 2 Template | Pick from the category's templates (cards + live preview). On confirm: assign `site_code`, prefix `sNNNNNN_`, create `generated_websites/site_NNNNNN_slug/uploads/`, **create site tables** (core + template features) and **seed** default pages, sections and demo content from the manifest | site tables |
| 3 Pages | Add, remove, rename, slug, homepage, order, active, SEO | `sNNNNNN_pages` |
| 4 Sections | Per page: add, remove, toggle, drag-and-drop order, duplicate, dynamic field editor, preview | `sNNNNNN_sections` |
| 5 Media | Upload (multi), replace, delete, alt text, folders per site | site `uploads/` + `sNNNNNN_media` |
| 6 Theme | Colours, fonts, button style, radius, header and footer style, logo, favicon | `sNNNNNN_settings` |
| 7 Payments | On or off; gateway; test or live; optional test keys for QA | `sNNNNNN_payment_settings` (encrypted) |
| 8 Preview | `preview.php?site=ID&page=slug` renders through the **real runtime** with the site's prefix; device-width toggles; checklist (missing images, empty sections, broken links, menus, payment config) | — |

Changing the template after Step 2 shows a warning and reseeds (content of section types the new template does not support is discarded).

Live preview boots `runtime/core` with an injected configuration (prefix, `templates/<cat>/<code>` as theme, the site's uploads path, preview base URL). That is the same code that will be copied, so the preview is exact.

### 5.2 Generation (staged, resumable, no cron)

The browser calls `POST /generator/step` repeatedly. Each call runs one step, which is idempotent and stays within the PHP time limit, saves its result to `wb_generation_steps`, and returns progress. If a step fails, the run stops with the exact error and the failed step, and **Retry** resumes from that step.

```
1  validate        configuration complete, template valid, required pages present, slugs unique
2  create_run      wb_generation_runs row; website status → Generating
3  stage_folder    storage/staging/site_NNNNNN_run_R/
4  copy_runtime    runtime/core, admin, skeleton → staging
5  copy_modules    only the enabled modules (contact always; blog/listings/payments/ecommerce if enabled)
6  copy_theme      templates/<cat>/<code> → staging/theme (fonts filtered to those selected)
7  migrate_db      run module schema.sql for any newly enabled modules (CREATE TABLE IF NOT EXISTS),
                   check all expected tables exist with the prefix
8  write_config    config/app.php (site code, base URL, APP_KEY), database.php (builder QA connection),
                   mail.php, theme.css, .htaccess (with correct RewriteBase), robots.txt
9  vendor          copy only the needed vendor packages (PHPMailer; gateway SDK; dompdf for e-commerce)
10 validate_files  manifest of expected files exists; PHP lint (php -l) on generated files if exec allowed
11 swap            move current code dirs → storage/backups/site_NNNNNN/<timestamp>/, move staging in.
                   uploads/ is NEVER touched by generation
12 smoke_test      HTTP requests to home + each page + admin login page; expect 200 and no PHP errors
13 finish          status → Generated; QA admin created with a random password shown once to the operator
```

If anything fails before step 11, the staging folder is deleted and the live site folder is untouched. If step 12 fails, the backup is restored automatically. A site is never marked Generated unless every step passed.

**Regeneration**: before **Deployed**, it reruns steps 1–13 (content is preserved because it lives in the DB and `uploads/`). After **Deployed**, it runs code steps only, and the result is offered as an *update package* (code without `config/`, `uploads/` or data).

---

## 6. Separate databases

- **On the builder (cPanel):** one database, one MySQL user. Site tables are created with the `sNNNNNN_` prefix by the builder, using the module `schema.sql` files. No `CREATE DATABASE` permission is needed.
- **On the client host:** the client (or Channel Softech) creates one database in their cPanel. `install.php` asks for the host, DB name, user, password and table prefix; tests the connection; imports `database/schema.sql` and then `database/data.sql`; creates the owner admin account; writes `config/database.php`; generates `APP_KEY`; and writes `storage/install.lock`. After that it refuses to run again (and asks the operator to delete it).
- **Manual option:** `INSTALLATION.md` also documents importing the two SQL files through phpMyAdmin and editing `config/database.php` by hand.

---

## 7. Export and deployment

1. **Preconditions:** site status is Generated, Testing or Ready.
2. **SQL dump** (done in PHP, because `mysqldump` and `exec` are often disabled on cPanel): `schema.sql` (the `CREATE TABLE`s with a `{{prefix}}` placeholder) + `data.sql` (`INSERT`s, chunked). **Excluded:** `admin_users`, `login_attempts`, payment credentials (gateway and mode are kept, keys are blanked), test transactions, and test contact submissions (optional checkbox).
3. **Package** into `storage/exports/site_NNNNNN_<date>.zip` via ZipArchive (also streamed in chunks over AJAX for large media): the code, theme, uploads, vendor, `install.php`, `database/`, `INSTALLATION.md`, `REQUIREMENTS.md` and `DEPLOYMENT_CHECKLIST.md`. `config/*.php` is replaced by `*.example.php` templates, so no builder credentials or APP_KEY leave the builder.
4. **The Export screen shows:** downloads (full ZIP, SQL only), PHP version, required extensions (`pdo_mysql, mbstring, json, openssl, sodium, zip, gd or imagick, fileinfo, curl`), DB requirements, payment configuration status, and the deployment checklist.
5. **Client deploy:** upload the ZIP → extract into `public_html` (or a subfolder; the base path is auto-detected) → create the DB → open `/install.php` → done. Payment keys are entered in `/admin → Payments`.
6. **The final independence test** (Spec §20, item 18): a clean Docker PHP 8.1 + MariaDB stack in CI that has no access to the builder, which installs the exported ZIP and runs smoke tests. The team then repeats it on a real separate cPanel account.

---

## 8. Technical limitations and risks

| Risk | Impact | Mitigation |
|---|---|---|
| **Scope**: 100 hand-designed templates | Most of the total effort is template design and front-end build | Engine first. One template per priority category to prove the flow, then batches of templates per category |
| Shared builder DB user can read every site's tables | A bug in QA copies could cross sites (builder only) | `SiteDataRepository` enforces the prefix. Generated QA sites sit behind Basic Auth. Real isolation happens after export |
| cPanel limits (`max_execution_time` 30–60s, memory 128–256MB, upload 2–64MB) | Long generations and exports, big uploads | Step-based generation, chunked ZIP, client-side image resize before upload, limits shown on the settings screen |
| `exec()` disabled on many hosts | No `php -l` or `mysqldump` | Pure-PHP SQL dumper. Lint check is skipped gracefully (the HTTP smoke test still runs) |
| `mod_rewrite` missing on some hosts | Clean URLs fail | Fallback to `index.php?r=slug` is detected automatically |
| MariaDB vs MySQL differences | JSON type, strict mode | `LONGTEXT` for JSON, explicit column defaults, tested on both |
| Payment gateways need merchant sandbox accounts | Cannot verify flows without them | Each gateway is marked "Verified" only after its sandbox test passes. Credentials are needed from Channel Softech |
| Stripe in India is invite-only for some businesses; PhonePe requires business onboarding | Gateway may be unusable for some clients | Shown as gateway notes in the wizard |
| Runtime updates for deployed sites | Bug fixes don't reach live sites automatically | Versioned update packages + changelog. The site admin shows the runtime version |
| Template Designer ZIP uploads contain PHP | Code execution risk | Validator + restricted roles (Designer, Super Admin only) + dangerous-function scan |
| Email deliverability from shared hosting | Notifications land in spam | SMTP configuration (PHPMailer). Enquiries are always saved to the DB first |
| Uploaded files | Web-shell upload | Extension allow-list + MIME sniff (`finfo`) + re-encode raster images with GD + SVG sanitizer + PHP execution disabled in `uploads/` + random file names |

---

## 9. Security design (summary)

Security follows Spec §15 in both the builder and every generated site:
- **Passwords and sessions:** `password_hash` (bcrypt or argon2id). Session cookie is HttpOnly, Secure and SameSite=Lax. The session ID is regenerated on login. Idle timeout.
- **Roles:** checked in every controller action (builder: four roles; site: owner and editor).
- **CSRF:** a token on every POST, including AJAX via a header.
- **Database:** PDO prepared statements only. Table and column names come from allow-lists, never from input.
- **Output:** escaped with `e()` (htmlspecialchars). Richtext is sanitized with an allow-list (HTML Purifier).
- **Rate limits:** on login (5 per 15 minutes per IP+email) and public forms (honeypot + time-trap + IP rate limit; optional reCAPTCHA or Turnstile).
- **Paths:** every file operation is resolved and checked with `realpath()` to stay inside its allowed base directory (prevents directory traversal).
- **Secrets:** encrypted with libsodium `secretbox` using the per-site `APP_KEY`, and never sent to the browser. Only publishable keys (e.g. Razorpay Key ID, Stripe publishable key) are rendered.
- **Payments:** verified on the server (signature or HMAC check, then a status fetch from the gateway API), webhooks with signature validation, idempotent transaction updates.
- **Locked-down folders:** `.htaccess` denies `config/`, `core/`, `storage/`, `database/` and `vendor/`. PHP is disabled in `uploads/`. File permissions are 644/755, and the config files 600 where the host allows.

---

## 10. Development roadmap

Each phase ends with: working code pushed to GitHub, `docs/PROGRESS.md` updated, a test checklist run, and **your review before the next phase**.

| Phase | Deliverables | Done when |
|---|---|---|
| **0 Architecture** | This document, repo skeleton | You approve this document |
| **1 Core platform** | Builder setup script, login (rate-limited), 4 roles, users, dashboard (cards, recent sites, quick actions), categories CRUD (image, status, order), template registry CRUD + manifest validator + duplicate + versions, clients CRUD, search, filters and pagination, activity log | All CRUD works through AJAX on a real DB on cPanel-like PHP 8.1/MariaDB |
| **2 Template engine** | Runtime core (router, renderer, DB with prefix, theme.css), section-type schemas, dynamic section editor, media manager (secure uploads, SVG sanitize), theme customizer, **first template: Business 01** | Business 01 renders from the DB with editable sections |
| **3 Website generator** | Full 8-step wizard, table provisioning per site, live preview, staged generator with logs, retry and rollback, smoke tests | Generate 2 Business sites; separate folders and prefixes; preview = output |
| **4 Site CMS** | Client admin: login, dashboard, pages, sections, media, menus, SEO, settings, contact forms + SMTP, blog, listings module, update-package support | A generated site is fully managed from its own `/admin` |
| **5 Payments** | Gateway framework + Razorpay (orders, checkout, signature verify, webhook, refunds status), "Pay amount" flow, transactions admin; then PhonePe, PayU, Cashfree, Stripe in turn | Each gateway marked Verified only after a sandbox payment succeeds end to end |
| **6 Export & deployment** | SQL dumper, ZIP packager, installer, requirements page, checklist, Docker independence test | Exported site installs and runs on a clean server with no builder access |
| **7a Priority templates** | Education 01, Hospital 01, E-commerce 01 (with full e-commerce module: catalog, variants, cart, checkout, coupons, GST, shipping, invoices PDF, order emails) | 4 categories proven end to end |
| **7b Remaining categories** | Real Estate, Travel, Restaurant, Hotel, Portfolio, Service — template 01 each | 10 categories live |
| **7c Library expansion** | Templates 02–10 for every category, in batches by category | 100 templates |

### Things needed from Channel Softech along the way
- Phase 1: a cPanel account (or details of the PHP and MariaDB versions) for real-host testing; the first Super Admin email.
- Phase 5: sandbox credentials for Razorpay (then the other gateways).
- Phase 6: a second, separate cPanel account for the independence test.

---

*Next step after approval: Phase 1 — Core Platform.*
