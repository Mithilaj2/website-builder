<?php
declare(strict_types=1);

/**
 * Phase 7a end-to-end test: Education 01 (Lyceum Classic) and Hospital 01 (CareLine Health),
 * the new section types and the booking / appointment / admission forms.
 *
 *   php tests/phase7a_http_test.php <builder-url> <email> <password> <edu-website-id> <hospital-website-id> <mail-dir> <site-db-dsn> [smtp-host:port]
 *
 * Both websites must be generated from the demo content (education_template_01, hospital_template_01).
 * site-db-dsn: "mysql:host=…;dbname=…|user|pass" (the builder database), used to look up template
 *              status and to age earlier local form posts out of the rate-limit window.
 * mail-dir:    folder where tools/mailsink.py writes received messages.
 */

require __DIR__ . '/support.php';

[$_, $base, $email, $password, $eduId, $hosId, $mailDir, $dsn, $smtp] = $argv + [null, 'http://127.0.0.1:8080', 'info@channelsoftech.com', '', '33', '34', '/tmp/mailsink', '', '127.0.0.1:2525'];
$base = rtrim((string) $base, '/');
$eduId = (int) $eduId;
$hosId = (int) $hosId;
[$smtpHost, $smtpPort] = explode(':', (string) $smtp) + ['127.0.0.1', '2525'];
$sfx = substr(bin2hex(random_bytes(3)), 0, 6);
$pass = $fail = 0;
$started = microtime(true);

function mails(string $dir, string $rcpt, float $since, int $wait = 8): array
{
    for ($i = 0; $i < $wait * 4; $i++) {
        $out = [];
        foreach (glob(rtrim($dir, '/') . '/*.eml') ?: [] as $f) {
            if (filemtime($f) + 1 < (int) $since) {
                continue;
            }
            $raw = (string) file_get_contents($f);
            if (stripos(strtok($raw, "\r\n"), $rcpt) === false) {
                continue;
            }
            $text = quoted_printable_decode($raw);
            if (preg_match_all('/Content-Transfer-Encoding: base64\r?\n(?:[^\r\n]+\r?\n)*\r?\n([A-Za-z0-9+\/=\r\n]+)/', $raw, $m)) {
                foreach ($m[1] as $b) {
                    $text .= "\n" . base64_decode(preg_replace('/\s+/', '', $b));
                }
            }
            $out[] = $text;
        }
        if ($out) {
            return $out;
        }
        usleep(250000);
    }
    return [];
}

/** First section of a type on a page (by slug) of a website. */
function section(Client $a, int $wid, string $slug, string $type): array
{
    foreach ($a->req('GET', "websites/$wid/pages")['json']['pages'] ?? [] as $p) {
        if ($p['slug'] === $slug) {
            foreach ($a->req('GET', "websites/$wid/pages/{$p['id']}/sections")['json']['sections'] ?? [] as $s) {
                if ($s['section_type'] === $type) {
                    $full = $a->req('GET', "websites/$wid/sections/{$s['id']}")['json'];
                    return ['id' => (int) $s['id'], 'data' => $full['section']['data'] ?? []];
                }
            }
        }
    }
    return ['id' => 0, 'data' => []];
}

function saveSection(Client $a, int $wid, int $sid, array $data): array
{
    return $a->req('POST', "websites/$wid/sections/$sid", ['data' => json_encode($data), 'style' => '{}']);
}

/** The public form on a page: token, back-slug and absolute action URL. */
function formOn(Client $site, string $host, string $path, string $form): array
{
    $html = $site->req('GET', $path, [], false)['body'];
    preg_match('/<form class="[a-z]+-form" method="post" action="([^"]*\/form\/' . preg_quote($form, '/') . ')"/', $html, $act);
    preg_match('/name="_t" value="([^"]+)"/', $html, $t);
    preg_match('/name="_back" value="([^"]*)"/', $html, $b);
    return ['html' => $html, 'action' => isset($act[1]) ? $host . html_entity_decode($act[1]) : '', 't' => $t[1] ?? '', 'back' => $b[1] ?? ''];
}

[$d, $du, $dp] = explode('|', (string) $dsn) + ['', '', ''];
$pdo = new PDO($d, $du, $dp, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$b = new Client($base);
check('Builder login', ($b->login($email, $password)['json']['ok'] ?? false) === true);
preg_match('/font-monospace" value="([A-Za-z0-9]+)"/', $b->req('GET', 'settings', [], false)['body'], $m);
$qa = 'qa:' . ($m[1] ?? '');

echo "\n== Templates ==\n";
$tpl = $pdo->query("SELECT t.code, t.status, c.slug FROM wb_templates t JOIN wb_categories c ON c.id = t.category_id WHERE t.code IN ('education_template_01', 'hospital_template_01')")->fetchAll(PDO::FETCH_ASSOC);
$byCode = array_column($tpl, null, 'code');
check('Education 01 registered, active, in the education category', ($byCode['education_template_01']['status'] ?? '') === 'active' && ($byCode['education_template_01']['slug'] ?? '') === 'education');
check('Hospital 01 registered, active, in the hospital category', ($byCode['hospital_template_01']['status'] ?? '') === 'active' && ($byCode['hospital_template_01']['slug'] ?? '') === 'hospital');
$scan = $b->req('GET', 'templates/scan')['json'];
check('Template scan runs (new folders are validated before registration)', ($scan['ok'] ?? false) === true, substr(json_encode($scan), 0, 200));

$sites = [];
foreach (['edu' => $eduId, 'hos' => $hosId] as $k => $wid) {
    $r = $b->req('GET', "websites/$wid/admin-account")['json'];
    $sites[$k] = rtrim(substr(rtrim((string) ($r['url'] ?? ''), '/'), 0, -strlen('admin')), '/') . '/';
    $prefix = (string) $pdo->query('SELECT table_prefix FROM wb_websites WHERE id = ' . $wid)->fetchColumn();
    $pdo->exec("UPDATE {$prefix}contact_submissions SET created_at = created_at - INTERVAL 15 MINUTE WHERE ip IN ('127.0.0.1', '::1') AND created_at > NOW() - INTERVAL 15 MINUTE");
    $pdo->exec("DELETE FROM {$prefix}login_attempts WHERE ip IN ('127.0.0.1', '::1')");
}
$host = (string) preg_replace('#^(https?://[^/]+).*$#', '$1', $sites['hos']);
$edu = new Client($sites['edu']);
$edu->basic = $qa;
$hos = new Client($sites['hos']);
$hos->basic = $qa;

echo "\n== Generated pages ==\n";
$eduPages = ['', 'about', 'courses', 'admissions', 'events', 'gallery', 'contact', 'privacy-policy', 'terms-and-conditions'];
$ok = true;
foreach ($eduPages as $p) {
    $r = $edu->req('GET', $p, [], false);
    $ok = $ok && $r['code'] === 200 && str_contains($r['body'], 'class="lyc ') && !preg_match('/(Fatal error|Warning:|Notice:|Deprecated:)/', $r['body']);
}
check('Education site: all 9 pages render (200, no PHP notices)', $ok);
$hosPages = ['', 'about', 'departments', 'doctors', 'health-packages', 'appointment', 'contact', 'privacy-policy', 'terms-and-conditions'];
$ok = true;
foreach ($hosPages as $p) {
    $r = $hos->req('GET', $p, [], false);
    $ok = $ok && $r['code'] === 200 && str_contains($r['body'], 'class="crl ') && !preg_match('/(Fatal error|Warning:|Notice:|Deprecated:)/', $r['body']);
}
check('Hospital site: all 9 pages render (200, no PHP notices)', $ok);
$home = $edu->req('GET', '', [], false)['body'];
check('Education home: notice board with dated items and "New" badges', substr_count($home, '<time datetime="20') >= 6 && substr_count($home, 'lyc-new') === 2);
check('Education home: course cards with level, duration and fee', substr_count($home, 'class="lyc-card lyc-course"') === 3 && str_contains($home, '₹38,000 / year'));
check('Education: "Book appointment"-style menu not leaking from other templates', !str_contains($home, 'crl-'));
$hh = $hos->req('GET', '', [], false)['body'];
check('Hospital home: emergency strip links to the emergency number', str_contains($hh, 'class="crl-strip-emergency" href="tel:+914842345000"'));
check('Hospital home: quick-access bar, 8 departments, 4 doctors', str_contains($hh, 'class="crl-quick"') && substr_count($hh, 'class="crl-dept') === 8 && substr_count($hh, 'class="crl-doctor"') === 4);
check('Hospital home: OPD timetable (5 rows) and 24×7 highlight', substr_count($hh, 'class="crl-timetable-row"') === 5 && str_contains($hh, 'Open 24 × 7'));
check('Hospital home: insurance partner logos and NABH badge', substr_count($hh, 'partner-') >= 5 && str_contains($hh, 'NABH accredited'));
$doc = $hos->req('GET', 'doctors', [], false)['body'];
check('Doctors page: specialty, qualification, experience and timings on each card', substr_count($doc, 'class="crl-chip"') === 4 && str_contains($doc, 'MBBS, MS (Ortho), FRCS') && str_contains($doc, '22 years experience') && str_contains($doc, 'Tue, Thu, Sat · 4:00 – 7:00 pm'));
$assetsOk = true;
foreach ([$sites['hos'] . 'theme/assets/css/style.css', $sites['hos'] . 'theme/assets/images/hero-doctor.svg', $sites['hos'] . 'assets/fonts/plus-jakarta-sans/700.woff2', $sites['edu'] . 'assets/fonts/fraunces/600.woff2'] as $u) {
    $assetsOk = $assetsOk && $hos->req('GET', $u, [], false)['code'] === 200;
}
check('Theme CSS, images and the new bundled fonts (Plus Jakarta Sans, Fraunces) load', $assetsOk);

echo "\n== Site details: emergency number & highlight badge ==\n";
$st = $b->req('GET', "websites/$hosId/settings")['json'];
$s = $st['site'] ?? [];
$details = ['name' => $s['name'] ?? 'CareLine Hospital', 'tagline' => $s['tagline'] ?? '', 'email' => $s['email'] ?? '', 'phone' => $s['phone'] ?? '', 'whatsapp' => '', 'emergency_phone' => 'call 108!', 'address' => $s['address'] ?? '', 'hours' => $s['hours'] ?? '',
    'rating' => 'NABH accredited', 'rating_label' => $s['rating_label'] ?? '', 'footer_about' => $s['footer_about'] ?? '', 'footer_text' => $s['footer_text'] ?? '',
    'header_button_text' => 'Book appointment', 'header_button_url' => 'page:appointment', 'social_facebook' => 'https://www.facebook.com/', 'seo_description' => '', 'seo_keywords' => ''];
check('Invalid emergency number rejected', isset($b->req('POST', "websites/$hosId/settings/details", $details)['json']['errors']['emergency_phone']));
$details['rating'] = str_repeat('x', 41);
$details['emergency_phone'] = '+91 484 234 5000';
check('Badge title longer than 40 characters rejected', isset($b->req('POST', "websites/$hosId/settings/details", $details)['json']['errors']['rating']));
$details['emergency_phone'] = '+91 484 299 1122';
$details['rating'] = "JCI accredited $sfx";
$details['rating_label'] = '<b>International</b> patient-safety standards';
check('Save emergency number and badge', ($b->req('POST', "websites/$hosId/settings/details", $details)['json']['ok'] ?? false) === true);
$hh = $hos->req('GET', '', [], false)['body'];
check('New emergency number shown in the strip, hero card and footer', substr_count($hh, 'tel:+914842991122') >= 3);
check('Badge title shown, tags stripped from badge text', str_contains($hh, "JCI accredited $sfx") && str_contains($hh, 'International patient-safety standards') && !str_contains($hh, '<b>International'));
$s2 = $b->req('GET', "websites/$hosId/settings")['json']['site'] ?? [];
check('Settings API returns the new fields for the editor', ($s2['emergency_phone'] ?? '') === '+91 484 299 1122' && ($s2['rating'] ?? '') === "JCI accredited $sfx");
$details['emergency_phone'] = '';
$b->req('POST', "websites/$hosId/settings/details", $details);
$hh = $hos->req('GET', '', [], false)['body'];
check('Without an emergency number the strip, hero card and footer card adapt', !str_contains($hh, 'crl-strip-emergency') && !str_contains($hh, 'crl-float-emergency') && str_contains($hh, 'Appointments</span>'));
$details['emergency_phone'] = '+91 484 234 5000';
$details['rating'] = 'NABH accredited';
$details['rating_label'] = 'National quality standards for patient safety';
$b->req('POST', "websites/$hosId/settings/details", $details);

echo "\n== New section types: validation ==\n";
$docs = section($b, $hosId, '', 'doctors');
$dd = $docs['data'];
$dd['items'][0]['name'] = '';
check('Doctors: name is required', isset(saveSection($b, $hosId, $docs['id'], $dd)['json']['errors']));
$dd = $docs['data'];
$dd['items'][0]['link'] = ['text' => 'Book', 'url' => 'javascript:alert(1)'];
check('Doctors: unsafe button link rejected', isset(saveSection($b, $hosId, $docs['id'], $dd)['json']['errors']));
$dd = $docs['data'];
$dd['items'][0]['timings'] = "Daily <script>x</script> 9 – 5 $sfx";
check('Doctors: save valid change', (saveSection($b, $hosId, $docs['id'], $dd)['json']['ok'] ?? false) === true);
$hh = $hos->req('GET', '', [], false)['body'];
check('Doctor timings rendered escaped', str_contains($hh, "9 – 5 $sfx") && !str_contains($hh, '<script>x</script>'));
saveSection($b, $hosId, $docs['id'], $docs['data']);

$hrs = section($b, $hosId, '', 'opening_hours');
$hd = $hrs['data'];
$hd['rows'] = array_fill(0, 15, ['label' => 'Day', 'hours' => '9 – 5']);
check('Opening hours: more than 14 rows rejected', isset(saveSection($b, $hosId, $hrs['id'], $hd)['json']['errors']['rows']));
$hd['rows'] = [['label' => '', 'hours' => '9 – 5']];
check('Opening hours: row label required', isset(saveSection($b, $hosId, $hrs['id'], $hd)['json']['errors']));

$bf = section($b, $hosId, 'appointment', 'booking_form');
$bd = $bf['data'];
$bd['form_type'] = 'payment';
$r = saveSection($b, $hosId, $bf['id'], $bd);
check('Booking form: unknown form type is not stored (falls back to the default)', ($r['json']['data']['form_type'] ?? 'payment') !== 'payment' && in_array($r['json']['data']['form_type'] ?? '', ['appointment', 'admission', 'booking', 'enquiry'], true));
saveSection($b, $hosId, $bf['id'], $bf['data']);

$ev = section($b, $eduId, 'events', 'events');
$ed = $ev['data'];
$ed['items'][0]['date'] = 'next Friday';
check('Events: date must be YYYY-MM-DD', isset(saveSection($b, $eduId, $ev['id'], $ed)['json']['errors']));
$nt = section($b, $eduId, '', 'notices');
$ndt = $nt['data'];
$ndt['items'][0]['link'] = ['text' => 'x', 'url' => 'data:text/html,hi'];
check('Notices: unsafe link rejected', isset(saveSection($b, $eduId, $nt['id'], $ndt)['json']['errors']));

$ed = $ev['data'];
$ed['hide_past'] = true;
$ed['items'][] = ['date' => '2020-01-15', 'time' => '10 am', 'title' => "Old reunion $sfx", 'location' => 'Hall', 'description' => '', 'image' => '', 'link' => ['text' => '', 'url' => '']];
$ed['items'][] = ['date' => date('Y-m-d', strtotime('+400 days')), 'time' => '', 'title' => "Future fest $sfx", 'location' => '', 'description' => '', 'image' => '', 'link' => ['text' => '', 'url' => '']];
check('Events: save with a past and a future event, "hide past" on', (saveSection($b, $eduId, $ev['id'], $ed)['json']['ok'] ?? false) === true);
$evPage = $edu->req('GET', 'events', [], false)['body'];
check('Live site hides past events, keeps upcoming ones, sorted by date', !str_contains($evPage, "Old reunion $sfx") && str_contains($evPage, "Future fest $sfx") && strpos($evPage, 'Annual Science Exhibition') < strpos($evPage, "Future fest $sfx"));
$pid = 0;
foreach ($b->req('GET', "websites/$eduId/pages")['json']['pages'] as $p) {
    $pid = $p['slug'] === 'events' ? (int) $p['id'] : $pid;
}
check('Builder preview still shows past events (so they can be edited)', str_contains($b->req('GET', "websites/$eduId/preview", ['page' => 'events'], false)['body'], "Old reunion $sfx"));
saveSection($b, $eduId, $ev['id'], $ev['data']);

echo "\n== Owner admin & email settings (hospital) ==\n";
$owner = "owner$sfx@example.com";
$ownerPw = (string) ($b->req('POST', "websites/$hosId/admin-account", ['email' => $owner, 'name' => 'Hospital Owner'])['json']['password'] ?? '');
$o = new Client($sites['hos'] . 'admin');
$o->basic = $qa;
check('Hospital owner signs in to the website admin', ($o->login($owner, $ownerPw)['json']['ok'] ?? false) === true);
$notify = "desk$sfx@example.com";
check('Notification address and SMTP saved', ($o->req('POST', 'settings/mail', ['notify_to' => $notify, 'from_email' => "no-reply$sfx@example.com", 'from_name' => 'CareLine', 'smtp_enabled' => '1', 'smtp_host' => $smtpHost, 'smtp_port' => $smtpPort, 'smtp_encryption' => 'none', 'smtp_username' => '', 'smtp_password' => ''])['json']['ok'] ?? false) === true);

echo "\n== Appointment form ==\n";
$f = formOn($hos, $host, 'appointment', 'appointment');
check('Appointment form posts to /form/appointment with a signed token', $f['action'] !== '' && $f['t'] !== '' && $f['back'] === 'appointment', $f['action']);
check('Department and preferred-time dropdowns built from the section options', str_contains($f['html'], 'name="extra[Department]"') && str_contains($f['html'], '<option>Cardiology</option>') && str_contains($f['html'], 'name="extra[Preferred time]"'));
check('Preferred date uses a date input (no past dates offered)', preg_match('/type="date"[^>]+name="extra\[Preferred date\]" min="' . date('Y-m-d') . '"/', $f['html']) === 1);
check('Mobile number required, email optional by default', preg_match('/name="phone"[^>]*required/', $f['html']) === 1 && preg_match('/name="email"[^>]*required/', $f['html']) === 0);
sleep(3);
$date = date('Y-m-d', strtotime('+3 days'));
$post = ['_t' => $f['t'], '_back' => 'appointment', 'name' => "Latha $sfx", 'phone' => '+91 98470 22222', 'email' => '', 'message' => "Chest pain on climbing stairs $sfx",
    'extra' => ['Department' => 'Cardiology', 'Preferred time' => 'Morning (8 am – 12 pm)', 'Preferred date' => $date, 'Bad<label>' => 'x', 'Empty' => '']];
$r = $hos->req('POST', $f['action'], $post, false);
check('Appointment request with phone only is accepted', $r['code'] === 303 && str_contains($r['location'], 'form=appointment&status=sent') && str_ends_with($r['location'], '#appointment-form'), $r['code'] . ' ' . $r['location']);
$page = $hos->req('GET', 'appointment?' . (string) parse_url($r['location'], PHP_URL_QUERY), [], false)['body'];
check('Thank-you message from the section is shown after the redirect', str_contains($page, 'Our help desk will call you shortly'));
$r = $hos->req('POST', $f['action'], ['email' => ''] + ['phone' => ''] + $post, false);
check('Without phone and email the request is refused', str_contains($r['location'], 'status=error&reason=invalid'), $r['location']);
$bad = $post;
$bad['extra']['Preferred date'] = '2026-02-30';
check('Impossible date refused', str_contains($hos->req('POST', $f['action'], $bad, false)['location'], 'reason=invalid'));
$bad['extra']['Preferred date'] = 'tomorrow';
check('Non-ISO date refused', str_contains($hos->req('POST', $f['action'], $bad, false)['location'], 'reason=invalid'));
$bad = $post;
$bad['phone'] = 'call me';
check('Malformed phone refused', str_contains($hos->req('POST', $f['action'], $bad, false)['location'], 'reason=invalid'));
$r = $hos->req('POST', $f['action'], ['_t' => '1.' . str_repeat('a', 64)] + $post, false);
check('Forged token refused', str_contains($r['location'], 'reason=expired'));
$errPage = $hos->req('GET', 'appointment?form=appointment&status=error&reason=invalid', [], false)['body'];
check('Form-specific error message for booking forms', str_contains($errPage, 'crl-form-alert is-error') && stripos($errPage, 'phone') !== false);

$n = array_values(array_filter(mails($mailDir, $notify, $started), fn ($x) => str_contains($x, "Latha $sfx")));
check('Notification email sent to the hospital desk', count($n) === 1);
check('Email subject names the form kind (appointment request)', $n && preg_match('/Subject: (=\?UTF-8\?[^\r\n]+|New appointment request[^\r\n]*)/i', $n[0]) === 1 && str_contains($n[0], 'New appointment request'));
check('Email lists department, time and date answers', $n && str_contains($n[0], 'Department') && str_contains($n[0], 'Cardiology') && str_contains($n[0], 'Preferred date') && str_contains($n[0], $date));
check('No Reply-To when the visitor gave no email', $n && preg_match('/^Reply-To:/mi', $n[0]) === 0);
check('Invalid / empty extra labels dropped from the email', $n && !str_contains($n[0], 'Bad<label>') && !str_contains($n[0], 'Empty:'));

$rows = $o->req('GET', 'api/enquiries', ['q' => $sfx])['json']['rows'] ?? [];
$row = array_values(array_filter($rows, fn ($x) => $x['name'] === "Latha $sfx"))[0] ?? null;
$extra = json_decode((string) ($row['extra_json'] ?? ''), true) ?: [];
check('Enquiry stored with form type "appointment" and no email', $row && $row['form_key'] === 'appointment' && $row['email'] === null && $row['phone'] === '+91 98470 22222');
check('Extra answers stored (labels cleaned, empties dropped)', $extra === ['Department' => 'Cardiology', 'Preferred time' => 'Morning (8 am – 12 pm)', 'Preferred date' => $date, 'Badlabel' => 'x'], json_encode($extra));
$csv = $o->req('GET', 'api/enquiries/export', [], false)['body'];
check('CSV export has Form and Details columns', str_contains($csv, '"ID",Date,Form,') || str_contains($csv, 'ID,Date,Form,Name,Email,Phone,Subject,Message,Details,Status,Page'));
check('CSV row carries the form type and the answers', preg_match('/,appointment,"?Latha ' . $sfx . '"?,.*Department: Cardiology; Preferred time: Morning/u', $csv) === 1);

echo "\n== Email-required option ==\n";
$bd = $bf['data'];
$bd['email_required'] = true;
check('Turn on "email required" for the appointment form', (saveSection($b, $hosId, $bf['id'], $bd)['json']['ok'] ?? false) === true);
$f = formOn($hos, $host, 'appointment', 'appointment');
check('Email becomes required, mobile optional', preg_match('/name="email"[^>]*required/', $f['html']) === 1 && preg_match('/name="phone"[^>]*required/', $f['html']) === 0 && str_contains($f['html'], 'name="_email_required" value="1"'));
sleep(3);
$r = $hos->req('POST', $f['action'], ['_t' => $f['t'], '_back' => 'appointment', '_email_required' => '1', 'name' => "Nina $sfx", 'phone' => '+91 98470 33333'], false);
check('Phone without email refused when email is required', str_contains($r['location'], 'reason=invalid'));
$r = $hos->req('POST', $f['action'], ['_t' => $f['t'], '_back' => 'appointment', '_email_required' => '1', 'name' => "Nina $sfx", 'email' => "nina$sfx@example.com"], false);
check('With email it is accepted', str_contains($r['location'], 'status=sent'));
$n2 = array_values(array_filter(mails($mailDir, $notify, $started), fn ($x) => str_contains($x, "Nina $sfx")));
check('Reply-To set to the visitor when an email is given', $n2 && preg_match("/Reply-To: .*nina$sfx@example\\.com/i", $n2[0]) === 1);
saveSection($b, $hosId, $bf['id'], $bf['data']);

echo "\n== Admission form (education) ==\n";
$f = formOn($edu, $host, 'admissions', 'admission');
check('Admission enquiry form with programme and academic-year dropdowns', $f['action'] !== '' && str_contains($f['html'], 'name="extra[Programme]"') && str_contains($f['html'], 'name="extra[Academic year]"'));
check('Fee cards link to the form anchor', str_contains($f['html'], 'href="#admission-form"') && str_contains($f['html'], 'id="admission-form"'));
sleep(3);
$r = $edu->req('POST', $f['action'], ['_t' => $f['t'], '_back' => 'admissions', 'name' => "Parent $sfx", 'phone' => '+91 94470 44444', 'extra' => ['Programme' => 'BCA', 'Academic year' => '2026–27']], false);
check('Admission enquiry accepted', str_contains($r['location'], 'form=admission&status=sent'), $r['location']);
$ePrefix = (string) $pdo->query('SELECT table_prefix FROM wb_websites WHERE id = ' . $eduId)->fetchColumn();
$er = $pdo->query("SELECT form_key, extra_json, message FROM {$ePrefix}contact_submissions WHERE name = " . $pdo->quote("Parent $sfx"))->fetch(PDO::FETCH_ASSOC);
check('Stored as an admission enquiry with its answers, message optional', $er && $er['form_key'] === 'admission' && json_decode($er['extra_json'], true) === ['Programme' => 'BCA', 'Academic year' => '2026–27'] && $er['message'] === null);

echo "\n== Contact form unchanged ==\n";
$f = formOn($hos, $host, 'contact', 'contact');
sleep(3);
$r = $hos->req('POST', $f['action'], ['_t' => $f['t'], '_back' => 'contact', 'name' => "Visitor $sfx", 'phone' => '+91 98470 55555', 'message' => 'Hello'], false);
check('Contact form still needs an email', str_contains($r['location'], 'reason=invalid'));
$r = $hos->req('POST', $f['action'], ['_t' => $f['t'], '_back' => 'contact', 'name' => "Visitor $sfx", 'email' => "v$sfx@example.com", 'message' => ''], false);
check('Contact form still needs a message', str_contains($r['location'], 'reason=invalid'));

echo "\n" . str_repeat('-', 50) . "\nPhase 7a: $pass passed, $fail failed\n";
exit($fail ? 1 : 0);
