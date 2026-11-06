# Installing the builder on cPanel

These steps install the Channel Softech Website Builder itself (not a client website; client sites get their own installer in Phase 6).

## Requirements

- PHP 8.1 or newer, with these extensions: `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `zip`, `openssl`, `sodium`
  - In cPanel, open **Select PHP Version** (or **MultiPHP Manager**) to choose the version and enable extensions.
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite` (standard on cPanel)

## 1. Create the database

1. Open **cPanel → MySQL® Databases**.
2. Create a database, e.g. `wbmaster`. cPanel names it `<cpaneluser>_wbmaster`.
3. Create a user with a strong password, e.g. `<cpaneluser>_wb`.
4. Add the user to the database with **ALL PRIVILEGES**.

## 2. Upload the files

Choose **one** of these two layouts.

**A. Subdomain pointed at `public/` (recommended)**
1. Create a subdomain, e.g. `builder.channelsoftech.com`.
2. Upload the whole project to a folder *outside* `public_html`, e.g. `/home/<user>/website-builder/`.
3. Set the subdomain's document root to `/home/<user>/website-builder/public`.

**B. Inside `public_html`**
1. Upload the whole project to `public_html/` or a subfolder such as `public_html/builder/`.
2. The project-root `.htaccess` routes every request into `public/`. The `app/`, `config/`, `storage/`, `database/` and `templates/` folders cannot be opened from a browser.

## 3. Folder permissions

These must be writable by PHP (755 for folders is usually enough on cPanel):
`config/`, `storage/` (and its subfolders), `public/uploads/`, `templates/`, `generated_websites/`

## 4. Run setup

1. Open `https://your-builder-address/setup.php`.
2. Check that every requirement shows **OK**.
3. Enter the database details, then the first Super Admin (the email is pre-filled with `info@channelsoftech.com`).
4. Click **Install**. Setup creates the tables, adds the 10 categories, creates the Super Admin, and writes `config/config.php`.
5. **Delete `public/setup.php`** from the server. (It also locks itself, but removing it is safer.)

## 5. Sign in and register the bundled templates

1. Open the builder address and sign in. Use **Users & Roles** to add team members.
2. Go to **Templates → Scan folders** and click **Register** for each bundled template (e.g. *Meridian Corporate*).
3. Open each template, check its live preview, and click **Activate**.

## 6. QA access to generated websites

Generated websites are real, standalone sites in `generated_websites/`. On the builder server they are password-protected for the team.

1. Open **Settings** (Super Admin). The QA username is `qa`; the password is shown there and can be regenerated.
2. **Layout B** (project inside `public_html`): nothing else to do. Generated sites open at `https://your-builder-address/generated_websites/site_…/`.
3. **Layout A** (document root = `public/`): create a subdomain (e.g. `qa.builder.yourdomain.com`) whose document root is the `generated_websites` folder, then enter its address under **Settings → Generated websites URL**.

The generator's website checks use this address and password. If the address cannot be reached, generation still completes: the pages are checked by rendering instead, and the log says so.

**PHP code cache:** if your PHP is configured with `opcache.validate_timestamps=0`, restart PHP (cPanel → MultiPHP Manager / PHP-FPM) after regenerating a website, or set the value to 1.

## Troubleshooting

| Symptom | Fix |
|---|---|
| Pages other than the dashboard show 404 | `mod_rewrite` is off or `.htaccess` files were not uploaded (they are hidden files, so enable "show hidden files" in File Manager or your FTP client) |
| "Setup is locked" | Already installed. To reinstall, delete `config/config.php` and `storage/setup.lock` |
| Upload errors for large ZIPs | Raise `upload_max_filesize` and `post_max_size` in **MultiPHP INI Editor** (e.g. 128M) |
| Errors after setup | Check `storage/logs/app-YYYY-MM-DD.log`. For temporary debugging only, set `'debug' => true` in `config/config.php` |

## Local development

```bash
php -S 127.0.0.1:8080 -t public tools/dev-router.php
php -S 127.0.0.1:8082 -t generated_websites tools/dev-sites-router.php   # serves generated websites
php tests/phase1_http_test.php http://127.0.0.1:8080 info@channelsoftech.com 'YourPassword'
php tests/phase2_http_test.php http://127.0.0.1:8080 info@channelsoftech.com 'YourPassword'
php tests/phase3_http_test.php http://127.0.0.1:8080 info@channelsoftech.com 'YourPassword' http://127.0.0.1:8082 "$(pwd)"
```
