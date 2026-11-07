# Deployment checklist — {{SITE_NAME}} ({{SITE_CODE}})

**Installation key:** `{{INSTALL_KEY}}`
(Needed once by `install.php`. Keep it private until the website is installed.)

## Before going live
- [ ] Domain points to the hosting (DNS updated, website opens on `{{DOMAIN}}`)
- [ ] PHP 8.1+ selected for the domain (cPanel → MultiPHP Manager)
- [ ] Files extracted in `public_html` (or a subfolder) — hidden `.htaccess` files present
- [ ] Database and user created, user added with ALL PRIVILEGES
- [ ] `install.php` completed; it deleted itself (if not, delete it by hand)
- [ ] SSL active: the website opens on `https://` with a padlock

## Check the website
- [ ] Homepage and every menu page open; no page shows an error
- [ ] Logo, images and fonts load
- [ ] Contact form: send a test enquiry → it appears in admin → Enquiries
- [ ] Mobile view: open on a phone, menu works
- [ ] `https://{{DOMAIN}}/config/app.php` shows “Forbidden” (protection works)

## Admin
- [ ] Owner can sign in at `/admin`
- [ ] Settings → Email: SMTP set up, “Send test email” received
- [ ] Extra administrators added if needed (Settings → Administrators)

## Payments
{{PAYMENTS}}
- [ ] Test keys entered → *Save and test keys* succeeds
- [ ] Webhook address added in the gateway dashboard
- [ ] One test payment on `/pay` → receipt email received → listed as paid in admin → Payments
- [ ] Switch to **Live** with live keys only after the test payment works

## Hand-over
- [ ] Owner has the admin address, email and knows how to reset the password (“Forgot your password?” on the sign-in page)
- [ ] Website status set to **Deployed** in the builder
