# Development Progress

| Phase | Status | Notes |
|---|---|---|
| 0 Architecture | ✅ Approved | `docs/ARCHITECTURE.md` |
| 1 Core platform | ✅ Complete, awaiting review | See below |
| 2 Template engine | ⏳ Next | Runtime core, section schemas, section editor, media manager, theme customiser, Business template 01 |
| 3 Website generator | ⏳ Not started | |
| 4 Generated site CMS | ⏳ Not started | |
| 5 Payment integration | ⏳ Not started | No sandbox account yet. Razorpay test-mode keys are free on signup. No gateway is marked complete until a sandbox payment is verified on the server |
| 6 Export & deployment | ⏳ Not started | Independence test on a second cPanel account to be run by Channel Softech |
| 7 Template library | ⏳ Not started | Order: Business, Education, Hospital, E-commerce, then the rest |

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

### 2026-11-06
- Phase 0: requirements reviewed (30 questions answered), architecture approved. Database decision: one builder DB with a table prefix per site.
- Phase 1: core platform built and tested (see above).
