# Development Progress

| Phase | Status | Notes |
|---|---|---|
| 0 Architecture | ✅ Approved | `docs/ARCHITECTURE.md` |
| 1 Core platform | ✅ Complete | PR #1 |
| 2 Template engine | ✅ Complete, awaiting review | See below |
| 3 Website generator | ⏳ Next | 8-step wizard around the Phase 2 workspace, folder generation, configuration files, staged generation with retry |
| 4 Generated site CMS | ⏳ Not started | |
| 5 Payment integration | ⏳ Not started | No sandbox account yet. Razorpay test-mode keys are free on signup. No gateway is marked complete until a sandbox payment is verified on the server |
| 6 Export & deployment | ⏳ Not started | Independence test on a second cPanel account to be run by Channel Softech |
| 7 Template library | ⏳ Not started | Order: Business, Education, Hospital, E-commerce, then the rest |

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

### 2026-11-06 (Phase 2)
- Template engine, site runtime, website workspace and Business Template 01 built and tested (see above).

### 2026-11-06
- Phase 0: requirements reviewed (30 questions answered), architecture approved. Database decision: one builder DB with a table prefix per site.
- Phase 1: core platform built and tested (see above).
