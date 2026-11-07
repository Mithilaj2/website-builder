# Development Progress

| Phase | Status | Notes |
|---|---|---|
| 0 Architecture | ✅ Approved | `docs/ARCHITECTURE.md` |
| 1 Core platform | ✅ Complete | PR #1 |
| 2 Template engine | ✅ Complete | PR #2 |
| 3 Website generator | ✅ Complete | PR #3 |
| 4 Generated site CMS | ✅ Complete | PR #4 | See below. Blog and listing modules arrive with the templates that need them (Phase 7); payment keys screen with Phase 5 |
| 5 Payment integration | ✅ Built, awaiting review · ⏳ sandbox verification | All 5 gateways built and tested against a local gateway emulator. Each gateway is marked **Verified** only after a real sandbox payment on cPanel (checklist below) |
| 6 Export & deployment | ⏳ Not started | Independence test on a second cPanel account to be run by Channel Softech |
| 7 Template library | ⏳ Not started | Order: Business, Education, Hospital, E-commerce, then the rest |

## Phase 5: Online payments

### Gateway status

| Gateway | API used | Built | Emulator tests | Real sandbox payment |
|---|---|---|---|---|
| Razorpay | Orders API + Standard Checkout (redirect mode), signature check, capture, webhooks | ✅ | ✅ | ⏳ Not yet verified |
| Stripe | Checkout Sessions (hosted page), webhooks (signed, 5-minute replay window) | ✅ | ✅ | ⏳ Not yet verified |
| Cashfree | PG API version 2025-01-01, JS checkout v3, webhooks | ✅ | ✅ | ⏳ Not yet verified |
| PhonePe | Standard Checkout **v2** (OAuth client credentials, `/checkout/v2/pay`, order status), webhooks | ✅ | ✅ | ⏳ Not yet verified |
| PayU | Hosted checkout (SHA-512 hash), reverse-hash check, `verify_payment` API, webhooks | ✅ | ✅ | ⏳ Not yet verified |

The build environment cannot reach any gateway's servers, so the flows were tested against `tools/payment-emulator.php`. It imitates each gateway's published API, hosted payment page and signatures. A gateway moves to **Verified** only after one real sandbox payment succeeds on the cPanel server (see "Sandbox verification checklist" below).

### Delivered
- **"Pay online" page** on every website at `/pay`, shown inside the website's own template, header and footer:
  - payment options set by the owner, each with a fixed price (e.g. "Consultation fee | 500") or a free amount within a minimum and maximum
  - the customer enters name, mobile and email (for the receipt), plus an optional note; a "Test mode" banner shows in test mode
  - buttons and menus link to it with `page:pay` (offered in the link picker and the Navigation editor); links can preselect an option (`/pay?option=1`) or suggest an amount (`/pay?amount=1500&purpose=Advance`)
  - protected by a signed form token, a honeypot field and a limit of 20 attempts per IP per 10 minutes
- **Payment flow:** the server creates the order at the gateway; the customer pays on the gateway's own page (the website never sees card or bank details); on return, the website:
  1. verifies what the gateway sent (signature or hash)
  2. **confirms with the gateway's API** (the API is the source of truth)
  3. checks that **the amount paid equals the amount asked**; a mismatch is never marked paid
  4. shows the receipt
- **Payment states:** paid, pending (for example a UPI approval still waiting; the receipt page re-checks by itself), failed, cancelled.
- **Safe status updates:**
  - a paid payment never changes again
  - forged or unverifiable messages are logged and ignored
  - repeated returns or webhooks change nothing and never send a second email
- **Webhooks** at `/pay/webhook/<gateway>`, checked with each gateway's own method, and then confirmed with the gateway's API. They catch payments where the customer closed the browser before returning to the website.
- **Emails:** a receipt to the customer and a notification to the owner, sent once per payment.
- **Receipt page:** reached through a link with an unguessable token; includes a print button.
- **Website admin → Payments:**
  - **Settings (owner):** turn payments on/off, choose the gateway, test or live mode, and separate test and live keys:
    - secrets are encrypted with the website's own key and never shown again; a blank field keeps the saved value
    - "Save and test keys" checks the keys with the gateway
    - each gateway's webhook address and setup steps are shown
    - a warning appears when the website is not on HTTPS
  - **Transactions (owner and editor):**
    - search, filter by status, mode and dates; total paid
    - details for each payment, with its full history (start, return, webhook, re-check)
    - "Re-check with gateway" (for when a customer says money was deducted); link to the customer's receipt
    - CSV export (spreadsheet-safe)
  - **Dashboard:** "Received this month" tile (live mode), with a reminder while in test mode or when keys are missing.
- **Builder:**
  - wizard step 7 reads the same gateway list as the websites, and choosing a gateway there switches the website's own settings over immediately (keys entered in the website admin are kept)
  - generated websites get the payment tables (migration 004)
  - the QA password no longer blocks the signature-checked webhook addresses, so gateway webhooks can reach QA websites
- **Runtime version 1.2.0.**
- **Hosting:** the website's `.htaccess` passes the `Authorization` header to PHP, which cPanel's PHP-FPM otherwise drops. PhonePe signs its webhooks with that header.

### Tests run
- `tests/phase5_http_test.php`: **68 end-to-end checks**, against the gateway emulator and a local mail server:
  - **settings:** keys required and checked against the mode; secrets never shown again; connection tests for all 5 gateways; wrong keys reported; **live mode never uses the emulator**
  - **pay page:** template, fixed-price options, validation, forged token
  - **Razorpay:**
    - success, with the option price used (the amount posted by the browser is ignored)
    - receipt and owner emails, sent once
    - payment history; replayed return; receipt token
    - failure; exact free amount; manual-capture account (captured automatically)
    - **amount tampering**, a **forged signature**, cancel
    - a "paid but never returned" payment confirmed by webhook (wrong signature rejected, repeated webhook harmless)
  - **Stripe:** success, cancel, webhook signature and replay window
  - **Cashfree:** success (mobile reduced to 10 digits), customer drop, webhook signature
  - **PhonePe:** success; pending UPI approval later confirmed by webhook; wrong webhook authorization rejected
  - **PayU:** hash accepted by the gateway; success; tampered response ignored; failure
  - **admin:** filters, search, CSV, dashboard, editor permissions, `page:pay` in menus, builder wizard list
- Results: dev server 68/68; **Apache 68/68**.
- Regression on both servers: Phase 1 80/80, Phase 2 99/99, Phase 3 79/79, Phase 4 105/105.
- Headless Chrome, desktop and mobile, with no JavaScript errors:
  - the real browser flow through Razorpay's and Cashfree's checkout scripts (fixed-price option locks the amount, "something else" unlocks it) → gateway page → back to the receipt
  - the admin screens, and "Save and test keys" from the browser

### Bugs found and fixed during testing
- **Mobile layout (from your screenshot):** every page of Business Template 01 scrolled sideways by 12 px on phones. Rows with `g-5` gutters overhang their container; they now use `g-4 g-lg-5`. A template rule also removed the payment page's side padding. Every page was checked at 360, 390 and 768 px with no overflow. Template version **1.1.1**.
- Emails from a website reached by IP address (QA) had an invalid sender (`no-reply@127.0.0.1`). The sender now falls back to the website's own address.
- On Apache, the QA password blocked gateway webhooks, and PHP-FPM dropped the `Authorization` header (both fixed, see above).
- A first version marked a pending payment as failed when someone posted a forged return to it. Unverifiable messages are now ignored.

### Sandbox verification checklist (to do on cPanel, one gateway at a time)
1. Generate the website, create its admin login (step 8), and make sure the QA address is on HTTPS.
2. Website admin → Payments → Settings: turn payments on, choose the gateway, keep **Test** mode, enter the gateway's **test** keys, then press **Save and test test keys**.
3. Add the webhook address shown there to the gateway's dashboard (with the same secret or username/password).
4. Open `/pay`, pay with the gateway's test card or UPI ID, and check:
   - the receipt shows "Payment successful"
   - Payments shows it as paid with a payment ID, and its history includes a webhook entry
   - the receipt and owner emails arrive
5. Pay once more and close the gateway window → the payment shows as cancelled.
6. Send me the results (or a screenshot of the payment's history). I then mark that gateway **Verified** here.

Test credentials: Razorpay test keys appear right after signup. Stripe test keys are in every account. Cashfree, PayU and PhonePe sandbox keys come from their dashboards; PhonePe sets up sandbox webhooks through their integration team.

### Notes
- Currency is INR. Refunds are made from the gateway's dashboard (the admin shows the payment ID to search for).
- E-commerce checkout (cart and orders) arrives with the e-commerce templates in Phase 7, on this same payment framework.
- `payment_emulator` in `config/config.php` is a developer-only setting. The Phase 6 export must never include it (noted for Phase 6).

## Phase 4: The generated website's own admin panel

Every generated website now has its own admin panel at **`/admin`** (for example `https://clientdomain.com/admin`). It runs entirely from the website's own files and database; it never contacts the builder.

### Delivered
- **Sign-in and security:**
  - email + password (bcrypt); sign-in is per website (own session cookie, scoped to `/admin`, HttpOnly, SameSite=Strict); session ID regenerated at sign-in; 30-minute idle timeout
  - lockout after 5 failed attempts per email+IP, or 20 per IP, within 15 minutes; the same "Invalid email or password" for unknown emails
  - CSRF token on every change; strict Content-Security-Policy (no inline scripts), `X-Frame-Options`, `no-store`, `noindex`
  - **Forgotten password** by email: the link holds a random token (only its hash is stored), is valid for one hour and works once. The answer is the same whether or not the email exists, and requests are rate-limited
- **Roles:** **Owner** (everything) and **Editor** (pages, sections, media, menus, appearance, enquiries; no settings or administrators).
- **Dashboard:** new enquiries, active pages, visible sections and images; latest enquiries; quick actions.
- **Pages & sections, Media library, Appearance (theme + site details + SEO):** the same editor as the builder. The editing logic now lives in the website runtime (`ContentService`, `MediaService`, `SettingsService`), and the builder and every website use that one shared code, so they cannot drift apart. Rich text, the media picker, the link and icon pickers and drag-and-drop ordering all work the same in both.
- **Navigation:** header menu and footer links, set to **Automatic** (built from the pages, as before) or **Custom**:
  - choose pages and custom links (https, `#anchor`, `tel:`, `mailto:`), rename labels, drag to reorder, open in a new tab (`rel="noopener"`)
  - `javascript:` and other unsafe links are refused
  - links to a hidden page drop out of the menu; deleting a page removes its links
- **Enquiries:** every contact form submission is listed with search and a status filter (New, Read, Replied, Spam):
  - opening one shows the full message and marks it read; "Reply by email" opens the mail app and marks it replied
  - delete; CSV export (opens in Excel with the right characters; cells starting with `=`, `+`, `-` or `@` are neutralised so a visitor cannot plant a spreadsheet formula)
  - a badge in the menu shows the number of new enquiries
- **Email:** the visitor's enquiry is emailed to the notification address(es), with Reply-To set to the visitor:
  - by default through PHP `mail()`; an **SMTP** server can be configured (host, port, TLS/SSL, username, password)
  - the SMTP password is stored encrypted with the website's own key and never shown back
  - "Send test email"
  - the enquiry is always saved first; if email fails, it is logged in `storage/logs` and the list marks it with an icon
- **Settings (owner):** email settings and administrators (add, edit, deactivate, delete). Owners cannot demote, deactivate or delete themselves. Deactivating a user ends their session immediately.
- **My profile:** name, email and password (current password required).
- **Builder, review step: "Website admin" card.** It shows the admin address and existing logins, and creates the owner login (by default with the client's email) or sets a new password for an existing one:
  - the password is generated (16 characters, easy to read), shown **once** and never stored by the builder
  - setting a new password also clears a lockout
- **Generator:** copies the admin, the shared editor and the PHP libraries (`vendor/`: HTML Purifier, PHPMailer) into each website, plus its admin scripts (`assets/admin/`). `vendor/` is blocked from the web. The website checks now also open `/admin/login` and confirm `vendor/` is private.
- **Database updates for existing websites:** a migration system (`runtime/schema/migrations/NNN_*.sql`, version in the `settings` table) brings older websites up to date automatically: on regeneration, when the builder opens them, and when their admin is first opened. New in this phase: `menu_items`, and password-reset columns on `admin_users`.
- **Runtime version 1.1.0.**

### Tests run
- `tests/phase4_http_test.php`: **105 end-to-end checks** against a generated website, using a local SMTP server (`tools/mailsink.py`) to receive and inspect the emails. Covers:
  - builder card: owner login created, password format, nothing stored
  - guest redirects and 401s; security headers and cookie flags; CSRF; wrong and unknown logins; session regeneration
  - every admin page; admin scripts served from the website itself; 6 private paths blocked (`vendor/`, `core/Admin`, `core/editor`, `config/`)
  - pages and sections (live on the website immediately), media (including a fake image with PHP inside), theme, details validation
  - navigation: custom header and footer menus, order, labels, new-tab links, unsafe links refused, hidden and deleted pages, back to automatic
  - email: SMTP settings, encrypted password, test email received
  - enquiries: visitor submission → notification email received with Reply-To; listing, read/replied/spam, filters, CSV with formula neutralising, delete
  - administrators and roles: editor can edit content, but gets 403 on settings and administrators; deactivation ends the session; self-demotion refused
  - profile and password change; forgotten-password email → link → new password → link works only once
  - lockout after 5 failures (even the right password is refused); the builder's "set new password" unlocks
  - logout
- Regression: Phase 1 80/80, Phase 2 99/99, Phase 3 79/79.
- **Apache** (cPanel-style subfolder install, QA password in front of the website): Phase 4 105/105, Phase 1 80/80, Phase 2 99/99, Phase 3 79/79.
- Headless Chrome: builder card and every admin page (desktop and mobile) with no JavaScript or Content-Security-Policy errors.

### Bugs found and fixed during testing
- Password-reset requests were counted as failed logins, so a few reset requests could lock everyone on that IP out. They are now rate-limited separately.
- The admin sidebar showed the website name in white on a white background. Caught in the screenshots.

### Notes
- **Who edits what:** until a website is marked Deployed, both the builder and the website admin can edit it (they share one code path, so either sees the other's changes). After deployment, the builder no longer edits content (decision 7).
- **Blog, listings, payment keys:** these screens arrive with the modules that need them: the payment keys screen in Phase 5, blog and listings with the templates that use them in Phase 7. The admin is built so modules add their own menu entries.
- Shared hosting: for reliable delivery, set up SMTP with an address on the client's own domain (cPanel → Email Accounts). PHP `mail()` works but often lands in spam.

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

  Visitors see a thank-you or error message. Email notifications arrived in Phase 4.
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
