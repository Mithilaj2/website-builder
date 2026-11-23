# Updating {{SITE_NAME}} ({{SITE_CODE}})

Update package exported {{EXPORT_DATE}} · runtime {{RUNTIME_VERSION}} · template {{TEMPLATE}}

This package contains **code only**. It never contains `config/`, `uploads/`, `storage/` or database data, so the website's content, images, settings, admin logins, payments and enquiries are not touched.

1. **Back up first.**
   - cPanel → *Backup* → download a home directory backup, or at least the website folder.
   - phpMyAdmin → *Export* the database.
2. **Upload the update.**
   - cPanel → *File Manager* → the website folder → *Upload* this ZIP → *Extract*.
   - When asked, choose to **overwrite** the existing files.
   - Then delete the ZIP.
3. **Open the admin** (`/admin`) once. Any database changes in this version are applied automatically on the first visit.
4. **Check:**
   - the homepage, a few pages and the contact form
   - the admin dashboard
   - payments, if they are used

If something goes wrong, restore the backup from step 1 and contact Channel Softech.
