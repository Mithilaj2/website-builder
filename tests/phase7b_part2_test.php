<?php
declare(strict_types=1);

/**
 * Phase 7b (part 2) end-to-end test: Restaurant 01 (Saffron Table: food menu, table bookings,
 * opening hours, events) and Hotel 01 (The Lakeview Haveli: rooms on the listings module).
 *
 *   php tests/phase7b_part2_test.php <builder-url> <email> <password> <restaurant-website-id> <hotel-website-id> <mail-dir> <db-dsn> [restaurant-template-id] [hotel-template-id]
 *
 * Both websites must be freshly generated with their template's demo content.
 * Needs tools/mailsink.py writing to <mail-dir> (SMTP 127.0.0.1:2525).
 */

require __DIR__ . '/support.php';

[$_, $base, $email, $password, $rsId, $htId, $mailDir, $dsn, $rsTpl, $htTpl] = $argv + [null, 'http://127.0.0.1:8080', 'info@channelsoftech.com', '', '0', '0', '/tmp/mailsink', '', '0', '0'];
$base = rtrim((string) $base, '/');
$rsId = (int) $rsId;
$htId = (int) $htId;
$sfx = substr(bin2hex(random_bytes(3)), 0, 6);
$pass = $fail = 0;
$started = microtime(true);

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
/** A public form: token, back-slug and absolute action URL. */
function formOn(Client $site, string $host, string $path, string $form): array
{
    $html = $site->req('GET', $path, [], false)['body'];
    preg_match('/<form class="[a-z]+-form" method="post" action="([^"]*\/form\/' . preg_quote($form, '/') . ')"/', $html, $act);
    preg_match('/name="_t" value="([^"]+)"/', $html, $t);
    preg_match('/name="_back" value="([^"]*)"/', $html, $b);
    return ['html' => $html, 'action' => isset($act[1]) ? $host . html_entity_decode($act[1]) : '', 't' => $t[1] ?? '', 'back' => $b[1] ?? ''];
}
function mailsTo(string $dir, string $rcpt, float $since, string $needle, int $wait = 8): string
{
    for ($i = 0; $i < $wait * 4; $i++) {
        foreach (glob(rtrim($dir, '/') . '/*.eml') ?: [] as $f) {
            if (filemtime($f) + 1 < (int) $since) {
                continue;
            }
            $raw = quoted_printable_decode((string) file_get_contents($f));
            if (stripos(strtok($raw, "\r\n"), $rcpt) !== false && str_contains($raw, $needle)) {
                return $raw;
            }
        }
        usleep(250000);
    }
    return '';
}
function cards(string $html, string $class): int
{
    return substr_count($html, '<article class="' . $class);
}

[$d, $du, $dp] = explode('|', (string) $dsn) + ['', '', ''];
$pdo = new PDO($d, $du, $dp, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$prefixOf = static fn (int $wid): string => (string) $pdo->query('SELECT table_prefix FROM wb_websites WHERE id = ' . $wid)->fetchColumn();
$rs = $prefixOf($rsId);
$ht = $prefixOf($htId);
foreach ([$rs, $ht] as $p) {
    $pdo->exec("DELETE FROM {$p}login_attempts WHERE ip IN ('127.0.0.1', '::1')");
    $pdo->exec("UPDATE {$p}contact_submissions SET created_at = created_at - INTERVAL 2 HOUR WHERE ip IN ('127.0.0.1', '::1')");
}

$b = new Client($base);
check('Builder login', ($b->login($email, $password)['json']['ok'] ?? false) === true);
preg_match('/font-monospace" value="([A-Za-z0-9]+)"/', $b->req('GET', 'settings', [], false)['body'], $m);
$qa = 'qa:' . ($m[1] ?? '');
$open = static function (int $wid, string $tag) use ($b, $qa, $sfx): array {
    $owner = "{$tag}owner$sfx@example.com";
    $r = $b->req('POST', "websites/$wid/admin-account", ['email' => $owner, 'name' => 'Owner']);
    $adminUrl = rtrim((string) ($r['json']['url'] ?? ''), '/');
    $siteUrl = substr($adminUrl, 0, -strlen('admin'));
    $o = new Client($adminUrl);
    $o->basic = $qa;
    $ok = ($o->login($owner, (string) ($r['json']['password'] ?? ''))['json']['ok'] ?? false) === true;
    $pub = new Client($siteUrl);
    $pub->basic = $qa;
    return [$o, $pub, $siteUrl, (string) preg_replace('#^(https?://[^/]+).*$#', '$1', $siteUrl), $ok];
};

echo "\n== Templates and fonts ==\n";
$tpl = $pdo->query("SELECT t.code, t.status, c.slug FROM wb_templates t JOIN wb_categories c ON c.id = t.category_id WHERE t.code IN ('restaurant_template_01', 'hotel_template_01')")->fetchAll();
$byCode = array_column($tpl, null, 'code');
check('Restaurant 01 registered, active, in the restaurant category', ($byCode['restaurant_template_01']['status'] ?? '') === 'active' && ($byCode['restaurant_template_01']['slug'] ?? '') === 'restaurant');
check('Hotel 01 registered, active, in the hotel category', ($byCode['hotel_template_01']['status'] ?? '') === 'active' && ($byCode['hotel_template_01']['slug'] ?? '') === 'hotel');
$fonts = json_decode((string) file_get_contents(__DIR__ . '/../runtime/fonts/fonts.json'), true);
check('New fonts registered with licences (DM Serif Display, Cormorant Garamond)', isset($fonts['dm-serif-display'], $fonts['cormorant-garamond']) && is_file(__DIR__ . '/../runtime/fonts/dm-serif-display/LICENSE') && is_file(__DIR__ . '/../runtime/fonts/cormorant-garamond/600.woff2'));
check('Menu section type available in the builder', str_contains(json_encode($b->req('GET', "websites/$rsId/section-types")['json'] ?? []), '"menu"') || is_file(__DIR__ . '/../runtime/section-types/menu.json'));
if ((int) $rsTpl > 0) {
    $prev = $b->req('GET', "templates/$rsTpl/preview", [], false)['body'];
    check('Restaurant preview shows the signature menu', str_contains($prev, 'class="sfr-dish') && str_contains($prev, 'Old Delhi butter chicken'));
}
if ((int) $htTpl > 0) {
    $prev = $b->req('GET', "templates/$htTpl/preview", [], false)['body'];
    check('Hotel preview shows rooms from demo data', cards($prev, 'lvh-room') >= 6 && str_contains($prev, 'Lake View Room'));
}

[$o, $pub, $siteUrl, $host, $ok] = $open($rsId, 'rs');
check('Restaurant owner signs in', $ok);
$notify = "orders$sfx@example.com";
$o->req('POST', 'settings/mail', ['notify_to' => $notify, 'from_email' => "web$sfx@example.com", 'from_name' => 'Saffron', 'smtp_enabled' => '1', 'smtp_host' => '127.0.0.1', 'smtp_port' => '2525', 'smtp_encryption' => 'none']);

echo "\n== Restaurant: pages ==\n";
$home = $pub->req('GET', '', [], false)['body'];
check('Dark theme and transparent header over the hero', str_contains($home, 'sfr-header-overlay') && preg_match('/--wb-bg:\s*#15110E/i', $home) === 1 && str_contains($home, "font-family:'DM Serif Display'"));
preg_match("#url\\('([^']*dm-serif-display/400\\.woff2[^']*)'\\)#", $home, $fm);
$fontUrl = isset($fm[1]) ? (str_starts_with($fm[1], 'http') ? $fm[1] : $host . $fm[1]) : '';
$code = 0;
if ($fontUrl !== '') {
    $c = curl_init($fontUrl);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_USERPWD => $qa]);
    curl_exec($c);
    $code = (int) curl_getinfo($c, CURLINFO_HTTP_CODE);
}
check('Heading font file is served by the generated site', $code === 200, "$code $fontUrl");
check('Homepage: signature dishes as a list (no tabs, no single-course title), 7 dishes with photos', substr_count($home, '<li class="sfr-dish') === 7 && !str_contains($home, 'role="tablist"') && !str_contains($home, 'sfr-menu-course-title') && substr_count($home, 'class="sfr-dish-img"') === 7);
check('Dish marks: veg / non-veg symbols with text for screen readers, spice level, labels', str_contains($home, 'sfr-diet is-veg') && str_contains($home, 'sfr-diet is-nonveg') && str_contains($home, '<span class="visually-hidden">Vegetarian</span>') && str_contains($home, 'Medium spicy') && str_contains($home, 'Chef’s special'));
check('Homepage booking form: guests, time and date', str_contains($home, 'name="extra[Guests]"') && str_contains($home, 'name="extra[Time]"') && str_contains($home, 'name="extra[Date]"'));
$menu = $pub->req('GET', 'menu', [], false)['body'];
check('Menu page: 5 courses as tabs with matching panels', substr_count($menu, 'role="tab"') === 5 && substr_count($menu, 'role="tabpanel"') === 5 && str_contains($menu, 'data-sfr-menu') && str_contains($menu, 'aria-controls='));
check('Menu page: 21 dishes, egg and vegan marks, prices, note', substr_count($menu, '<li class="sfr-dish') === 21 && str_contains($menu, 'is-egg') && str_contains($menu, 'is-vegan') && str_contains($menu, '₹545') && str_contains($menu, 'exclusive of 5% GST'));
check('Without JavaScript every course heading is in the page', substr_count($menu, 'class="sfr-menu-course-title"') === 5);
$res = $pub->req('GET', 'reservations', [], false)['body'];
check('Reservations: booking form, opening hours with highlight and call button, FAQ', str_contains($res, 'Table booking') && str_contains($res, 'Tuesday – Friday') && str_contains($res, 'Every day from 11 am') && str_contains($res, 'href="tel:+912249702244"') && str_contains($res, 'Is there a dress code?'));
$ev = $pub->req('GET', 'events', [], false)['body'];
check('Events page: private dining options and upcoming evenings', str_contains($ev, 'Private dining room') && str_contains($ev, 'New Year’s Eve dinner'));
check('No listings on a restaurant website: /rooms is 404', $pub->req('GET', 'rooms', [], false)['code'] === 404);

echo "\n== Restaurant: editing the menu ==\n";
$ms = section($b, $rsId, 'menu', 'menu');
check('Menu section found in the workspace', $ms['id'] > 0 && count($ms['data']['items'] ?? []) === 21);
$md = $ms['data'];
$md['items'][0]['name'] = '';
check('Dish name required', isset(saveSection($b, $rsId, $ms['id'], $md)['json']['errors']));
$md = $ms['data'];
$md['items'][0]['course'] = '';
check('Course required', isset(saveSection($b, $rsId, $ms['id'], $md)['json']['errors']));
$md = $ms['data'];
$md['items'] = array_fill(0, 81, $ms['data']['items'][0]);
check('At most 80 dishes', isset(saveSection($b, $rsId, $ms['id'], $md)['json']['errors']['items']));
$md = $ms['data'];
$md['items'][0]['diet'] = 'carnivore';
$md['items'][0]['spice'] = '9';
$r = saveSection($b, $rsId, $ms['id'], $md);
check('Unknown food type / spice level fall back to "not shown"', ($r['json']['ok'] ?? false) === true && ($r['json']['data']['items'][0]['diet'] ?? 'x') === '' && ($r['json']['data']['items'][0]['spice'] ?? 'x') === '0');
$md = $ms['data'];
$md['items'][] = ['course' => 'Chef’s table', 'name' => "Smoked jackfruit biryani $sfx", 'description' => 'Seasonal <b>special</b>', 'price' => '₹495', 'diet' => 'vegan', 'spice' => '3', 'tag' => 'New', 'image' => ''];
$md['show_images'] = false;
check('Add a dish in a new course, photos off', (saveSection($b, $rsId, $ms['id'], $md)['json']['ok'] ?? false) === true);
$menu = $pub->req('GET', 'menu', [], false)['body'];
check('New course becomes a sixth tab; dish shown with hot spice and no photos anywhere', substr_count($menu, 'role="tab"') === 6 && str_contains($menu, "Smoked jackfruit biryani $sfx") && str_contains($menu, 'Hot</span>') && !str_contains($menu, 'class="sfr-dish-img"'));
check('Description stays plain text (tags removed)', !str_contains($menu, '<b>special</b>') && (str_contains($menu, 'Seasonal special') || str_contains($menu, 'Seasonal &lt;b&gt;')));
$md['layout'] = 'list';
saveSection($b, $rsId, $ms['id'], $md);
$menu = $pub->req('GET', 'menu', [], false)['body'];
check('"All courses on the page" layout: no tabs, six course headings', !str_contains($menu, 'role="tablist"') && substr_count($menu, 'class="sfr-menu-course-title"') === 6);
saveSection($b, $rsId, $ms['id'], $ms['data']);

echo "\n== Restaurant: table booking ==\n";
$f = formOn($pub, $host, 'reservations', 'booking');
check('Booking form posts to the booking handler', $f['action'] !== '' && $f['back'] === 'reservations', $f['back']);
sleep(3);
$date = date('Y-m-d', strtotime('+3 days'));
$since = microtime(true);
$r = $pub->req('POST', $f['action'], ['_t' => $f['t'], '_back' => 'reservations', 'name' => "Kabir $sfx", 'phone' => '+91 98200 44444', 'message' => 'Anniversary dinner',
    'extra' => ['Guests' => '4', 'Time' => '8:00 pm', 'Date' => $date]], false);
check('Booking accepted → thank-you message from the section', str_contains($r['location'], 'status=sent') && str_contains($pub->req('GET', $r['location'], [], false)['body'], 'We will confirm your table'));
$row = $pdo->query("SELECT form_key, extra_json FROM {$rs}contact_submissions WHERE name = " . $pdo->quote("Kabir $sfx"))->fetch();
check('Stored as a booking with guests, time and date', $row && $row['form_key'] === 'booking' && json_decode($row['extra_json'], true) === ['Guests' => '4', 'Time' => '8:00 pm', 'Date' => $date], json_encode($row));
$mail = mailsTo($mailDir, $notify, $since, "Kabir $sfx");
check('Notification email lists the booking details', $mail !== '' && str_contains($mail, '8:00 pm') && str_contains($mail, $date));

echo "\n== Restaurant: events ==\n";
$es = section($b, $rsId, 'events', 'events');
$ed = $es['data'];
$ed['items'][] = ['date' => '2021-02-14', 'time' => '8 pm', 'title' => "Old Valentine night $sfx", 'location' => '', 'description' => '', 'image' => '', 'link' => ['text' => '', 'url' => '']];
check('Add a past event ("hide past" is on in the demo)', (saveSection($b, $rsId, $es['id'], $ed)['json']['ok'] ?? false) === true && !empty($ed['hide_past']));
check('Past event hidden on the live site', !str_contains($pub->req('GET', 'events', [], false)['body'], "Old Valentine night $sfx"));
saveSection($b, $rsId, $es['id'], $es['data']);

[$ho, $hpub, $hUrl, $hhost, $ok] = $open($htId, 'ht');
check('Hotel owner signs in; the admin menu says "Rooms"', $ok && str_contains($ho->req('GET', 'listings', [], false)['body'], 'Rooms'));
$ho->req('POST', 'settings/mail', ['notify_to' => $notify, 'from_email' => "web$sfx@example.com", 'from_name' => 'Haveli', 'smtp_enabled' => '1', 'smtp_host' => '127.0.0.1', 'smtp_port' => '2525', 'smtp_encryption' => 'none']);

echo "\n== Hotel: pages ==\n";
$hh = $hpub->req('GET', '', [], false)['body'];
check('Centred header with "Rooms" as the second menu item', str_contains($hh, 'lvh-header-top') && preg_match('#<li class="nav-item"><a class="nav-link[^"]*" href="[^"]*/"[^>]*>Home</a></li>\s*<li class="nav-item"><a class="nav-link[^"]*" href="[^"]*/rooms"#', $hh) === 1);
check('Stay finder: room type, guests and view', str_contains($hh, 'class="lvh-finder"') && str_contains($hh, 'name="c"') && str_contains($hh, 'name="a[guests]"') && str_contains($hh, 'name="a[view]"'));
check('Homepage: 6 featured rooms with nightly rates', cards($hh, 'lvh-room') === 6 && str_contains($hh, '<strong>₹13,500</strong>') && str_contains($hh, 'per night, with breakfast'));
$ri = $hpub->req('GET', 'rooms', [], false)['body'];
check('/rooms: 8 rooms, fully booked one marked and last, no "wing" filter', str_contains($ri, '8 rooms found') && cards($ri, 'lvh-room') === 8 && strpos($ri, 'Jharokha Room</a></h3>') > strpos($ri, 'Family Suite</a></h3>') && str_contains($ri, 'Fully booked') && !str_contains($ri, 'name="location"'));
$q = static fn (string $s): int => cards($hpub->req('GET', 'rooms?' . $s, [], false)['body'], 'lvh-room');
check('Filters: 3+ guests → 4; lake view → 4; suites → 3', $q('a%5Bguests%5D=3') === 4 && $q('a%5Bview%5D=Lake+view') === 4 && $q('c=suites') === 3, $q('a%5Bguests%5D=3') . '/' . $q('a%5Bview%5D=Lake+view') . '/' . $q('c=suites'));
$rd = $hpub->req('GET', 'rooms/lake-view-room', [], false)['body'];
check('Room page: 4-photo gallery, facts (guests, bed, size, view, breakfast), lists', str_contains($rd, 'wblist-gallery has-4') && str_contains($rd, '2 Guests') && str_contains($rd, 'King bed') && str_contains($rd, '380 sq ft') && str_contains($rd, 'Breakfast included') && str_contains($rd, 'In your room') && str_contains($rd, 'Good to know'));
check('Room page: availability form with check-in date and nights; similar rooms', str_contains($rd, 'name="extra[Check-in date]"') && str_contains($rd, 'name="extra[Nights]"') && str_contains($rd, 'Similar rooms'));
$f = formOn($hpub, $hhost, 'rooms/lake-view-room', 'enquiry');
sleep(3);
$ci = date('Y-m-d', strtotime('+20 days'));
$r = $hpub->req('POST', $f['action'], ['_t' => $f['t'], '_back' => $f['back'], 'name' => "Ira $sfx", 'phone' => '+91 99280 55555', 'message' => '2 adults',
    'extra' => ['Room' => 'Lake View Room', 'Nights' => '3 nights', 'Check-in date' => $ci]], false);
check('Availability request accepted → back to the room with the hotel’s message', str_contains($r['location'], 'rooms/lake-view-room') && str_contains($hpub->req('GET', $r['location'], [], false)['body'], 'Our reservations team will confirm availability'));
$row = $pdo->query("SELECT extra_json FROM {$ht}contact_submissions WHERE name = " . $pdo->quote("Ira $sfx"))->fetch();
check('Stored with the room, nights and check-in date', $row && json_decode($row['extra_json'], true) === ['Room' => 'Lake View Room', 'Nights' => '3 nights', 'Check-in date' => $ci], json_encode($row));

echo "\n== Hotel: admin ==\n";
$form = $ho->req('GET', 'listings/new', [], false)['body'];
check('Room form: guests, bed choices, breakfast yes/no, amenities list', str_contains($form, 'name="attr[guests]"') && str_contains($form, '<option>King bed + sofa bed</option>') && preg_match('/type="checkbox" name="attr\[breakfast\]"/', $form) === 1 && str_contains($form, 'name="attr[amenities]"'));
$suites = (int) $pdo->query("SELECT id FROM {$ht}listing_categories WHERE slug = 'suites'")->fetchColumn();
$r = $ho->req('POST', 'api/listings', ['title' => "Rani Suite $sfx", 'category_id' => $suites, 'location' => 'Palace wing', 'price' => '24,500', 'price_note' => 'per night, with breakfast', 'status' => 'active',
    'attr' => ['guests' => '3', 'bed' => 'King bed', 'size' => '700', 'view' => 'Lake view', 'breakfast' => '1', 'amenities' => "Bathtub\nTerrace"], 'images' => ['theme:images/suite-royal.svg']]);
$nid = (int) ($r['json']['id'] ?? 0);
$attrs = json_decode((string) $pdo->query("SELECT attributes_json FROM {$ht}listings WHERE id = $nid")->fetchColumn(), true) ?: [];
check('Add a suite: numbers, toggle and list stored', $nid > 0 && $attrs['guests'] === 3 && $attrs['breakfast'] === true && $attrs['amenities'] === ['Bathtub', 'Terrace'], json_encode($attrs));
check('New suite is live with ₹24,500 and in the suites filter', str_contains($hpub->req('GET', "rooms/rani-suite-$sfx", [], false)['body'], '₹24,500') && $q('c=suites') === 4);
$r = $ho->req('POST', "api/listings/$nid", ['title' => "Rani Suite $sfx", 'category_id' => $suites, 'status' => 'active', 'attr' => ['guests' => '3', 'bed' => 'Bunk bed']]);
check('Unknown bed type refused', $r['code'] === 422 && isset($r['json']['errors']['attr[bed]']));
$ho->req('POST', "api/listings/$nid/delete");
check('Delete the test suite', (int) $pdo->query("SELECT COUNT(*) FROM {$ht}listings WHERE id = $nid")->fetchColumn() === 0);

echo "\n== Hotel: event enquiry (email required) ==\n";
$f = formOn($hpub, $hhost, 'weddings', 'enquiry');
check('Weddings page: event form asks for an email', $f['action'] !== '' && str_contains($f['html'], 'name="_email_required" value="1"') && str_contains($f['html'], 'name="extra[Event type]"'));
sleep(3);
$r = $hpub->req('POST', $f['action'], ['_t' => $f['t'], '_back' => 'weddings', '_email_required' => '1', 'name' => "Tara $sfx", 'phone' => '+91 99280 66666'], false);
check('Refused without an email', str_contains($r['location'], 'reason=invalid'));
$r = $hpub->req('POST', $f['action'], ['_t' => $f['t'], '_back' => 'weddings', '_email_required' => '1', 'name' => "Tara $sfx", 'email' => "tara$sfx@example.com", 'extra' => ['Event type' => 'Wedding', 'Guests' => '150–300']], false);
check('Accepted with an email', str_contains($r['location'], 'status=sent'));
check('"rooms" is reserved on the hotel website only', isset($ho->req('POST', 'api/pages', ['title' => 'Rooms', 'slug' => 'rooms', 'page_type' => 'generic'])['json']['errors']['slug']));
$pg = $o->req('POST', 'api/pages', ['title' => 'Rooms', 'slug' => 'rooms', 'page_type' => 'generic']);
check('…and free for a normal page on the restaurant website', ($pg['json']['ok'] ?? false) === true);
$pid = (int) $pdo->query("SELECT id FROM {$rs}pages WHERE slug = 'rooms'")->fetchColumn();
if ($pid) {
    $o->req('POST', "api/pages/$pid/delete");
}

printf("\n%d passed, %d failed (%.1fs)\n", $pass, $fail, microtime(true) - $started);
exit($fail ? 1 : 0);
