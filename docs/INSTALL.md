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

## 5. Sign in

Open the builder address and sign in. Use **Users & Roles** to add team members.

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
php tests/phase1_http_test.php http://127.0.0.1:8080 info@channelsoftech.com 'YourPassword'
```
