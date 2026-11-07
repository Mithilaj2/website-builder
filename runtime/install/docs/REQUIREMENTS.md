# Server requirements — {{SITE_NAME}}

| Item | Required |
|---|---|
| PHP | **8.1, 8.2 or 8.3** |
| PHP extensions | `pdo_mysql`, `mbstring`, `json`, `openssl`, `sodium`, `gd`, `fileinfo`, `curl` (all standard on cPanel; enable under *Select PHP Version → Extensions*) |
| Database | MySQL 5.7+ or MariaDB 10.3+, utf8mb4; one database with a user that has ALL PRIVILEGES |
| Web server | Apache (or LiteSpeed) with `.htaccess` and `mod_rewrite`. Without mod_rewrite the website still works, but its addresses look like `index.php?page=about` |
| Disk space | About 20 MB for the code, plus the images |
| HTTPS | Required before taking live payments (cPanel AutoSSL is free) |
| Cron jobs | Not needed |
| Composer / SSH | Not needed: all libraries are included in `vendor/` |

**Folder permissions:** `config/`, `storage/` and `uploads/` must be writable (755 on cPanel). Files are 644.

**Protected folders:** `config/`, `core/`, `vendor/`, `storage/` and `database/` are blocked from the web by `.htaccess`. Uploaded files can never run as code.
