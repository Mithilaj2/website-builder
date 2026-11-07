# Installing {{SITE_NAME}}

Package exported {{EXPORT_DATE}} · website {{SITE_CODE}} · runtime {{RUNTIME_VERSION}} · template {{TEMPLATE}}

This package is a complete, independent website: its own files, its own database and its own admin panel. It does not need the website builder.

## What you need
- A cPanel hosting account (or any Apache hosting with PHP 8.1+ and MySQL 5.7+ / MariaDB 10.3+). See `REQUIREMENTS.md`.
- The **installation key** from `DEPLOYMENT_CHECKLIST.md`.

## Install (about 10 minutes)

1. **Choose the PHP version.** cPanel → *MultiPHP Manager* (or *Select PHP Version*) → choose **PHP 8.1, 8.2 or 8.3** for the domain.
2. **Upload the files.**
   - cPanel → *File Manager* → open `public_html` (or a subfolder such as `public_html/shop`; the website works in either).
   - Remove the host's placeholder `index.html` if there is one.
   - *Upload* the ZIP, then right-click it → *Extract*, then delete the ZIP.
   - Turn on *Settings → Show Hidden Files*, and check that `.htaccess` is there. It is required.
3. **Create the database.** cPanel → *MySQL Databases*:
   - create a database, e.g. `site`, which becomes `youraccount_site`
   - create a user with a strong password
   - *Add User To Database* and tick **ALL PRIVILEGES**
4. **Run the installer.** Open `https://{{DOMAIN}}/install.php` in the browser. Enter:
   - the installation key
   - the database name, user and password
   - the table prefix (default `{{PREFIX}}`)
   - the owner's name, email and password for the website admin

   The installer then:
   - checks the server
   - imports the database
   - creates the owner login
   - writes `config/` with a new secret key
   - deletes itself
5. **Done.** Open the website, then the admin at `https://{{DOMAIN}}/admin`.

## Manual install (if install.php cannot be used)
1. In phpMyAdmin, select the database → *Import* → `database/schema.sql`, then `database/data.sql`. Both use the table prefix `{{PREFIX}}`.
2. Copy `config/app.example.php` to `config/app.php`. Set `'key'` to a random value: 32 random bytes, base64-encoded (in a terminal: `php -r 'echo base64_encode(random_bytes(32));'`).
3. Copy `config/database.example.php` to `config/database.php` and fill in the database name, user and password.
4. Copy `config/payment.example.php` to `config/payment.php`.
5. Create the owner login by running `install.php` once, or ask Channel Softech.
6. Delete `install.php`, `config/install.php` and the `database/*.sql` files.

## After installing
- **SSL:** cPanel → *SSL/TLS Status* → *Run AutoSSL*, so the website opens on `https://`.
- **Email:** admin → Settings → Email → SMTP. Use a mailbox on your domain (cPanel → *Email Accounts* → *Connect Devices* shows the server and port). Then press *Send test email to me*.
- **Payments:** {{PAYMENTS}}
- **Content:** {{ENQUIRIES}} Administrator logins from the testing copy are not included.

## Moving or reinstalling
To reinstall, delete `config/app.php`, `config/database.php` and `storage/install.lock`, upload `install.php` again, and run it.

Support: Channel Softech (P) Ltd.
