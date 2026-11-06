# Development Progress

| Phase | Status | Notes |
|---|---|---|
| 0 Architecture | ✅ Approved | `docs/ARCHITECTURE.md` |
| 1 Core platform | ✅ Complete | PR #1 |
| 2 Template engine | ✅ Complete | PR #2 |
| 3 Website generator | ✅ Complete, awaiting review | See below |
| 4 Generated site CMS | ⏳ Next | Website's own /admin: login, pages, sections, media, menus, SEO, enquiries + email (SMTP), settings |
| 5 Payment integration | ⏳ Not started | No sandbox account yet. Razorpay test-mode keys are free on signup. No gateway is marked complete until a sandbox payment is verified on the server |
| 6 Export & deployment | ⏳ Not started | Independence test on a second cPanel account to be run by Channel Softech |
| 7 Template library | ⏳ Not started | Order: Business, Education, Hospital, E-commerce, then the rest |

## Phase 3: Website generator

### Delivered
- **8-step creation wizard** (spec §5), on the website page with a stepper, Back/Next buttons and progress saved per website:
  1. **Client & website:** pick a client or create one inline (atomic: no orphan client if validation fails); name, domain, description, category.
  2. **Template:** cards with live preview. Switching template re-seeds pages and sections from the new template, keeps media, site details, logo and favicon, and takes a full backup first.
  3. **Pages**, 4. **Sections**, 5. **Media**, 6. **Theme & site details:** the Phase 2 workspace, split into separate steps.
  7. **Payments:**
     - choose online payment yes/no, then the gateway (Razorpay, PhonePe, PayU, Cashfree, Stripe) with the credentials each needs, and test or live mode
     - credentials are never stored by the builder or exported (decision 22); they're entered in the website's own admin
     - the payment flow itself is built in Phase 5
  8. **Review & generate:**
     - checklist: errors block generation, warnings don't; each item has a "Fix" link to its step
     - preview at three widths
     - Generate/Regenerate button with live step-by-step progress
     - generation history; QA link and login
     - status actions
- **Staged generator** (spec §6/§17), with no cron: the browser runs one step per request, and every step is logged.
  - **Steps:** validate → staging folder → copy engine → copy template → copy fonts/libraries → check tables → write config → verify files (checksums + `php -l` where `exec()` is allowed) → publish → website checks over HTTP → finish.
  - **Safe failure:** any failure before "publish" removes the staging folder and leaves the existing website untouched. If the checks after publishing fail, the previous version is restored automatically (or, on a first generation, the broken code is removed). The website is marked *Generation Failed* with the exact step and error, and **Retry** resumes.
  - **Concurrency:** a second generation of the same website is refused while one is running.
  - **Uploads** are never touched. Each regeneration keeps the previous code in `storage/backups/sites/` (the 3 most recent are kept).
- **Standalone website** (`generated_websites/site_NNNNNN_<slug>/`):
  - front controller and clean URLs with `.htaccess`, plus a fallback `index.php?page=` when mod_rewrite is missing
  - works at a domain root or in any subfolder
  - its own `core/`, `theme/`, `assets/` (fonts and Bootstrap), `uploads/`, `config/` (app, database, payment), `storage/`, `VERSION` and `generation.json` (file checksums)
  - `.htaccess` protection for `config/`, `core/`, `storage/` and template source files; PHP disabled in `uploads/`, and SVGs sandboxed
- **Contact forms work** on generated sites. Submissions are stored in the site's own database, with these protections (no session cookie on public pages):
  - signed timestamp token: rejects forged posts and posts sent less than 3 seconds after page load
  - honeypot field
  - per-IP rate limit
  - server-side validation

  Visitors see a thank-you or error message. Email notifications come with the site admin in Phase 4.
- **Statuses** (spec §14): Draft → Configuration Pending (reaching the review step) → Generating → Generated / Generation Failed. Then manually Testing → Ready for Deployment → Deployed (content locked in the builder; no regeneration or deletion) → Archived / restore.
- **Settings** (Super Admin): the QA address for generated websites and a generated QA password. On the builder server, generated sites are protected with HTTP Basic Auth (`generated_websites/.htaccess` + `storage/qa/.htpasswd`). The generator's website checks log in with it.

### Tests run
- `tests/phase3_http_test.php`, **79 end-to-end checks**, covering:
  - QA settings; website + new client in one step
  - all 8 steps rendering; payment validation; the checklist blocking generation
  - full generation (all 11 steps); the standalone site serving every page by clean URL, 404 for unknown pages, assets loading, 6 private paths blocked
  - contact form: bot-speed post rejected, valid enquiry stored, invalid email rejected
  - regeneration keeping uploads
  - **deliberately broken template:** generation fails at the website checks, the previous version is restored and keeps serving, the website is marked failed, and retry succeeds after the fix
  - concurrent generation refused; viewer permissions
  - every status transition, with locks while deployed
  - template switch keeping media and details
  - delete removing the generated site
- **Independence check:** a generated site was copied to an unrelated folder (no builder files) and run on its own. Every page returned 200, assets loaded, the contact form stored submissions, and no builder paths are referenced anywhere in its code.
- **Apache** (cPanel-style subfolder install): Phase 3 78/78, Phase 1 80/80, Phase 2 99/99. Also verified there:
  - the QA site returns 401 without the password and 401 with a wrong one
  - PHP placed in a generated site's `uploads/` returns 403
  - builder routes are unaffected
- Headless Chrome: all wizard steps and the live generation progress, with no JavaScript errors.

### Bugs found and fixed during testing
- **PHP code cache (OPcache) can serve the previously compiled template files for a few seconds after regeneration.** The checks were passing against old code, so a broken template went undetected in testing. The generator now discards PHP's cached copies of the published files and waits out the cache's recheck interval before checking. Note for hosting: on servers that never recheck files (`opcache.validate_timestamps=0`), a PHP restart is needed after regeneration. Documented in `INSTALL.md`.
- Two stray closing tags in the wizard view pushed steps 7–8 out of the tab container. Caught by the browser check.
- Starting a generation while one was running returned a generic 422; it now returns a clear 409 "already in progress".

### Notes
- On the builder server, a generated site's `config/database.php` points at the builder database with its own table prefix (so the QA copy works). The Phase 6 export replaces this with the installer, so builder credentials never leave the server.

## Phase 2: Template engine

### Delivered
- **Site runtime** (`runtime/`): the CMS core every generated site will carry. It has no dependency on the builder.
  - prefixed database access, so a site can only address its own tables
  - page and section renderer that follows each template's own layouts
  - `$view` API for templates; theme CSS (bundled fonts + `--wb-*` variables)
  - seeder that builds new sites from the manifest and demo content
  - strict in-house SVG sanitiser
- **Section type library:** 16 core section types (hero, page banner, about, services, why choose us, statistics, testimonials, gallery, team, FAQ, call to action, contact, client logos, text block, process, pricing). Each has its own configurable fields: text, rich text, images, colours, links, icons, toggles, selects and repeaters with min/max items.
- **Business Template 01 "Meridian Corporate":** a complete, original design.
  - 16 section views, 7 default pages, two header and two footer styles
  - realistic demo content for a consulting firm
  - original SVG illustrations (no stock photos)
  - responsive from 390 px to desktop; count-up numbers; gentle scroll reveal
- **Template live preview:** any valid template renders with its demo content on its detail page.
- **Websites:**
  - **Create:** a "New website" form (client, name, category, template with preview cards). This creates the site's tables (`sNNNNNN_` prefix) and its folder (`generated_websites/site_NNNNNN_<slug>/uploads`), then seeds the template's pages and demo content.
  - **Workspace, Pages & sections tab:**
    - pages: add, rename, change URL, page layout, set homepage, activate/deactivate, show in menu or footer, SEO title/description/keywords, drag-and-drop order (drives the menu), delete
    - sections: add from the template's types (at the end or after a section), drag-and-drop order, show/hide, duplicate, delete
    - section editor: form generated from the schema, repeaters, rich text (TinyMCE), media picker, link picker (`page:slug`), icon picker, and style options (background colour/image, overlay, spacing, text tone, anchor), with a side-by-side live preview that refreshes on save
  - **Media library** (per website):
    - multi-upload into folders; JPG, PNG, WEBP and GIF are re-encoded, SVG is sanitised
    - alt text, folder changes, search and paging
    - replace (keeps the path, so every use updates)
    - delete with a "used in…" warning
  - **Theme:** primary/accent/background/text colours, heading and body fonts (10 bundled families), button style, corner rounding, header and footer style, logo, favicon, reset to template defaults.
  - **Site details:** name, tagline, contact details, WhatsApp, hours, address, header button, footer texts, social links (https only), default SEO.
  - **Preview tab:** any page at desktop, tablet or mobile width.
  - **Delete website:** you must type the site code to confirm. A ZIP backup (folder + JSON dump of every site table) is taken first, then only that site's tables are dropped.
- **Navigation:** Websites menu entry, "Create New Website" on the dashboard, "New website" on the client page.

### Tests run
- `tests/phase2_http_test.php`: 99–100 end-to-end checks. Highlights:
  - template validation and live preview of every page
  - assets served without any external CDN; path traversal blocked
  - website creation and seeding
  - page rules: reserved, duplicate and invalid URLs; homepage protection; ordering
  - section editing with validation: `javascript:` links, foreign or traversal image paths, and repeater limits rejected; HTML stripped; rich text sanitised
  - drag-and-drop order reflected in output
  - media: upload, SVG sanitising, fake-JPEG/PHP rejection, alt text, replace, in-use delete
  - isolation between two websites (files, images, pages)
  - theme and details reaching the rendered page
  - viewer and guest permissions
  - delete with backup
- Phase 1 suite re-run: 80/80.
- Both suites pass on the PHP dev server **and** on Apache with the builder in a `public_html` subfolder.
- Headless Chrome: rendered every page of the template at desktop and mobile widths. Drove the workspace (media upload, section edit with media picker, rich-text editor, theme change, preview) with no JavaScript errors.

### Bugs found and fixed during testing
- `SHOW TABLES LIKE ?` cannot be a native prepared statement on MariaDB. Switched to `information_schema`.
- HTML Purifier's autoloader needed its bootstrap file loaded first.
- A curly quote directly after `$slug` in a message string made PHP treat it as part of the variable name, causing a 500 error on reserved URLs. Fixed and scanned the codebase for the same pattern.
- The test client sent arrays (`ids[]`) unencoded, so reorder checks passed without testing anything. The Phase 1 category-reorder check now verifies the actual order.

### Known limitations (planned)
- Contact forms render but are disabled in the builder preview. Submissions, storage and email arrive with the site's own runtime in Phase 4.
- Navigation menus are built from pages (order + "show in menu/footer"). A separate menu editor with custom links comes with the client admin in Phase 4.
- Generated-site folders currently hold only `uploads/`. Code, configuration and the standalone front controller are produced by the generator in Phase 3.

## Phase 1: Core platform

### Delivered
- **Setup** (`public/setup.php`): requirement checks, schema + seeds, first Super Admin, writes `config/config.php`, then locks itself.
- **Authentication:**
  - bcrypt passwords (rehashed automatically when the algorithm changes)
  - session ID regenerated on login, 30-minute idle timeout, browser fingerprint check
  - rate limiting: 5 failed attempts per email+IP, or 20 per IP, within 15 minutes
- **Roles:** Super Admin, Admin, Designer, Viewer, enforced on every route on the server. Menus and buttons are hidden to match.
- **Dashboard:**
  - 8 stat cards (clients, websites, templates, categories, deployed, archived, under development, generations)
  - recent websites table with search, status filter and pagination
  - quick actions; templates per category
- **Categories:** CRUD with secure image upload, activate/deactivate, drag-and-drop ordering, search, filter, sort. Deletion is blocked while templates or websites exist. The slug is locked once templates exist (it is their folder name).
- **Templates:**
  - upload as ZIP, or register folders copied in by FTP or File Manager ("Scan folders")
  - manifest validator plus PHP security scan
  - edit (including moving to another category), activate only if valid
  - preview image and screenshots
  - duplicate, upload a new version, restore or download any version
  - delete with a backup archive
- **Clients:** CRUD, detail page with the client's websites (folder, table prefix, status) and activity, search, filter, sort, pagination.
- **Users & Roles** (Super Admin): CRUD and password reset. The system prevents removing the last Super Admin and prevents self-demotion or self-deletion.
- **My profile:** edit name and email, change password (current password required).
- **Activity log:** every create, update, delete, login and template operation, with filters.
- **Security:**
  - CSRF token on every POST; prepared statements only
  - output escaping
  - strict Content-Security-Policy with no inline scripts
  - uploads: MIME sniffing and GD re-encoding; PHP execution disabled in `public/uploads`
  - ZIP extraction: zip-slip, symlink and size guards
  - template code scan
  - private folders denied by `.htaccess`
- **cPanel support:** works with the document root on `public/`, or with the whole project in `public_html` or a subfolder.

### Not in Phase 1 (by design)
- Live template preview: needs the runtime (Phase 2).
- Website creation wizard, generation and export screens (Phases 3 and 6). The dashboard does not show buttons for features that don't exist yet.
- Real templates: Business Template 01 is built in Phase 2 as the first template that renders.

### Tests run
- `tests/phase1_http_test.php`: 80 end-to-end checks covering login, CSRF, rate limiting, every CRUD action, role enforcement, XSS escaping, malicious image upload, malicious template (security scan), zip-slip, template versions, restore, duplicate, move, delete and logout.
  - **80/80 passed** on the PHP built-in server (PHP 8.3, MariaDB 10.11).
  - **80/80 passed** on Apache 2.4 + mod_php with the project in a `public_html/wb/` subfolder (a cPanel-style install). This also confirmed that `config/`, `app/`, `storage/`, `database/`, `templates/`, `.git` and `README.md` return 403, and that PHP files in `uploads/` are not executed.
- Manual UI check in headless Chrome (desktop and mobile): no JavaScript errors.

### Bugs found and fixed during testing
- Restoring a version saved before the template's category changed failed validation. Fixed: on restore, the version now adopts the template's current category.
- Under Apache, `/templates` (a builder page) was blocked by the rule protecting the `templates/` folder. Fixed in the root `.htaccess` and `templates/.htaccess`.
- Apache turned HTTP 419 (CSRF) into 500. Fixed: CSRF failures now return 403 with a clear message.

## Log

### 2026-11-06 (Phase 3)
- Wizard, staged generator, standalone generated websites, QA access and statuses built and tested (see above).

### 2026-11-06 (Phase 2)
- Template engine, site runtime, website workspace and Business Template 01 built and tested (see above).

### 2026-11-06
- Phase 0: requirements reviewed (30 questions answered), architecture approved. Database decision: one builder DB with a table prefix per site.
- Phase 1: core platform built and tested (see above).
