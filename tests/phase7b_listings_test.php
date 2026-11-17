<?php
declare(strict_types=1);

/**
 * Phase 7b (part 1) end-to-end test: the Listings module on websites generated from
 * Real Estate 01 (Keystone Realty, /properties) and Travel 01 (Horizon Trails, /packages).
 *
 *   php tests/phase7b_listings_test.php <builder-url> <email> <password> <realestate-website-id> <travel-website-id> <mail-dir> <db-dsn> [realestate-template-id] [travel-template-id]
 *
 * Both websites must be freshly generated with their template's demo content.
 * Needs tools/mailsink.py writing to <mail-dir> (SMTP 127.0.0.1:2525).
 */

require __DIR__ . '/support.php';

[$_, $base, $email, $password, $reId, $trId, $mailDir, $dsn, $reTpl, $trTpl] = $argv + [null, 'http://127.0.0.1:8080', 'info@channelsoftech.com', '', '0', '0', '/tmp/mailsink', '', '0', '0'];
$base = rtrim((string) $base, '/');
$reId = (int) $reId;
$trId = (int) $trId;
$sfx = substr(bin2hex(random_bytes(3)), 0, 6);
$pass = $fail = 0;
$started = microtime(true);

function cards(string $html, string $class): int
{
    return substr_count($html, '<article class="' . $class);
}
/** The enquiry form on a listing page: token, back-slug and absolute action URL. */
function enquiryForm(Client $site, string $host, string $path): array
{
    $html = $site->req('GET', $path, [], false)['body'];
    preg_match('/<form class="wblist-form" method="post" action="([^"]*\/form\/enquiry)"/', $html, $act);
    preg_match('/name="_t" value="([^"]+)"/', $html, $t);
    preg_match('/name="_back" value="([^"]*)"/', $html, $b);
    return ['html' => $html, 'action' => isset($act[1]) ? $host . html_entity_decode($act[1]) : '', 't' => $t[1] ?? '', 'back' => $b[1] ?? ''];
}
function mailsTo(string $dir, string $rcpt, float $since, int $wait = 8): array
{
    for ($i = 0; $i < $wait * 4; $i++) {
        $out = [];
        foreach (glob(rtrim($dir, '/') . '/*.eml') ?: [] as $f) {
            if (filemtime($f) + 1 < (int) $since) {
                continue;
            }
            $raw = (string) file_get_contents($f);
            if (stripos(strtok($raw, "\r\n"), $rcpt) !== false) {
                $out[] = quoted_printable_decode($raw);
            }
        }
        if ($out) {
            return $out;
        }
        usleep(250000);
    }
    return [];
}

[$d, $du, $dp] = explode('|', (string) $dsn) + ['', '', ''];
$pdo = new PDO($d, $du, $dp, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$prefixOf = static fn (int $wid): string => (string) $pdo->query('SELECT table_prefix FROM wb_websites WHERE id = ' . $wid)->fetchColumn();
$re = $prefixOf($reId);
$tr = $prefixOf($trId);
foreach ([$re, $tr] as $p) {
    $pdo->exec("DELETE FROM {$p}login_attempts WHERE ip IN ('127.0.0.1', '::1')");
    $pdo->exec("UPDATE {$p}contact_submissions SET created_at = created_at - INTERVAL 2 HOUR WHERE ip IN ('127.0.0.1', '::1')");
}

$b = new Client($base);
check('Builder login', ($b->login($email, $password)['json']['ok'] ?? false) === true);
preg_match('/font-monospace" value="([A-Za-z0-9]+)"/', $b->req('GET', 'settings', [], false)['body'], $m);
$qa = 'qa:' . ($m[1] ?? '');

/** Owner account + clients for one website. */
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

echo "\n== Templates ==\n";
$tpl = $pdo->query("SELECT t.code, t.status, c.slug FROM wb_templates t JOIN wb_categories c ON c.id = t.category_id WHERE t.code IN ('realestate_template_01', 'travel_template_01')")->fetchAll();
$byCode = array_column($tpl, null, 'code');
check('Real Estate 01 registered, active, in the real-estate category', ($byCode['realestate_template_01']['status'] ?? '') === 'active' && ($byCode['realestate_template_01']['slug'] ?? '') === 'real-estate');
check('Travel 01 registered, active, in the travel category', ($byCode['travel_template_01']['status'] ?? '') === 'active' && ($byCode['travel_template_01']['slug'] ?? '') === 'travel');
foreach (['real-estate/realestate_template_01', 'travel/travel_template_01'] as $dir) {
    $m = json_decode((string) file_get_contents(__DIR__ . "/../templates/$dir/manifest.json"), true);
    check("$dir: manifest declares a listings module with attributes and an enquiry form", isset($m['listings']['path'], $m['listings']['attributes'], $m['listings']['enquiry']) && in_array('listings', $m['section_types'], true));
}
// manifest validation rejects a bad listings block
$z = makeTemplateZip("zz_listings_$sfx", 'real-estate', '1.0.0', static function (string $dir): void {
    $m = json_decode((string) file_get_contents("$dir/manifest.json"), true);
    $m['listings'] = ['path' => 'shop', 'singular' => 'X', 'plural' => 'Xs', 'attributes' => [['key' => 'a b', 'label' => 'A', 'type' => 'colour']]];
    file_put_contents("$dir/manifest.json", json_encode($m));
});
$r = $b->req('POST', 'templates/upload', ['package' => new CURLFile($z, 'application/zip', 'bad.zip')]);
$errs = implode(' | ', (array) ($r['json']['report']['errors'] ?? []));
check('Upload validation refuses an invalid listings block', $r['code'] === 422 && stripos($errs, 'listings') !== false, $errs);
@unlink($z);

if ((int) $reTpl > 0) {
    $prev = $b->req('GET', "templates/$reTpl/preview", [], false)['body'];
    check('Template preview (no database) shows demo properties and property types', cards($prev, 'kst-prop') >= 6 && str_contains($prev, 'Sky Terrace Residences') && str_contains($prev, 'class="kst-type'));
}
if ((int) $trTpl > 0) {
    $prev = $b->req('GET', "templates/$trTpl/preview", [], false)['body'];
    check('Travel preview shows demo packages and destinations', cards($prev, 'hzt-trip') >= 6 && str_contains($prev, 'Kerala Backwaters &amp; Hills'));
}

[$o, $pub, $siteUrl, $host, $ok] = $open($reId, 're');
check('Real-estate site owner signs in', $ok);
$notify = "leads$sfx@example.com";
$o->req('POST', 'settings/mail', ['notify_to' => $notify, 'from_email' => "web$sfx@example.com", 'from_name' => 'Keystone', 'smtp_enabled' => '1', 'smtp_host' => '127.0.0.1', 'smtp_port' => '2525', 'smtp_encryption' => 'none']);

echo "\n== Public pages: properties ==\n";
$home = $pub->req('GET', '', [], false)['body'];
check('Homepage: hero search posts to /properties with type, locality, budget and buy/rent', str_contains($home, 'class="kst-search"') && preg_match('#action="[^"]*/properties"#', $home) === 1
    && str_contains($home, 'name="a[listing_for]"') && str_contains($home, 'name="c"') && str_contains($home, 'name="location"') && str_contains($home, 'name="max"'));
check('Homepage: 4 property-type tiles with counts, 6 featured + 3 new properties', preg_match_all('/class="kst-type(?: is-lead)?"/', $home) === 4 && str_contains($home, '3 properties') && cards($home, 'kst-prop') === 9);
check('Header: Properties menu with a type dropdown', str_contains($home, 'kst-nav-drop') && str_contains($home, 'properties?c=villas'));
check('Card: price in lakh/crore, facts without decimals, "For sale" tag', str_contains($home, '₹1.85 Cr') && str_contains($home, '<span>3 BHK</span>') && str_contains($home, '1,640 sq ft') && str_contains($home, 'For sale'));
$idx = $pub->req('GET', 'properties', [], false);
check('/properties lists all 11 (sold ones last) inside the template', $idx['code'] === 200 && str_contains($idx['body'], '11 properties found') && cards($idx['body'], 'kst-prop') === 11 && str_contains($idx['body'], 'class="kst '));
check('Sold properties come last and are marked', strrpos($idx['body'], 'is-closed') > strpos($idx['body'], 'High-street retail showroom') && substr_count($idx['body'], 'kst-prop is-closed') === 2);
check('Filters: type, locality, available for, bedrooms, possession, min/max price, sort', str_contains($idx['body'], 'name="c"') && str_contains($idx['body'], 'name="location"') && str_contains($idx['body'], 'name="a[listing_for]"')
    && str_contains($idx['body'], 'name="a[bedrooms]"') && str_contains($idx['body'], 'name="a[possession]"') && str_contains($idx['body'], 'name="min"') && str_contains($idx['body'], 'name="sort"'));
$f = static fn (string $q): string => $pub->req('GET', 'properties?' . $q, [], false)['body'];
check('Filter by type (villas → 3, incl. 1 sold)', cards($v = $f('c=villas'), 'kst-prop') === 3 && str_contains($v, 'Aranya Villas'));
check('Filter: for rent → 2', cards($f('a%5Blisting_for%5D=Rent'), 'kst-prop') === 2);
check('Filter: 3+ bedrooms (number filters are a minimum) → 6', cards($f('a%5Bbedrooms%5D=3'), 'kst-prop') === 6, (string) cards($f('a%5Bbedrooms%5D=3'), 'kst-prop'));
check('Filter: locality Baner, Pune → 1', cards($f('location=Baner%2C+Pune'), 'kst-prop') === 1);
check('Filter: budget up to ₹50 lakh → 3 (plot + 2 rentals); from ₹2 Cr → 6', cards($f('max=5000000'), 'kst-prop') === 3 && cards($f('min=20000000'), 'kst-prop') === 6, cards($f('max=5000000'), 'kst-prop') . '/' . cards($f('min=20000000'), 'kst-prop'));
check('Search: "villa" finds the villas', cards($f('q=villa'), 'kst-prop') >= 2 && !str_contains($f('q=villa'), 'Riverside Row Houses'));
$asc = $f('sort=price_asc');
check('Sort by price (low → high): rentals first, then ₹36 Lakh plot', strpos($asc, 'Residential plot in a gated layout') < strpos($asc, 'Sky Terrace Residences') && strpos($asc, '2 BHK apartment for rent') < strpos($asc, 'Residential plot'));
check('Combined filters, no results → friendly message', str_contains($f('c=plots&a%5Bbedrooms%5D=4'), '0 properties found') || str_contains($f('c=plots&a%5Bbedrooms%5D=4'), 'No properties'));
check('Unknown type → 404; junk filters ignored safely', $pub->req('GET', 'properties?c=castles', [], false)['code'] === 404 && $pub->req('GET', 'properties?a%5Bnope%5D=1&min=abc&sort=evil', [], false)['code'] === 200);
check('XSS in the search box is escaped', !str_contains($f('q=%3Cscript%3Ealert(1)%3C%2Fscript%3E'), '<script>alert(1)</script>'));

$det = $pub->req('GET', 'properties/sky-terrace-3-bhk', [], false);
$dh = $det['body'];
check('Property page: title, price, breadcrumb, gallery with 5 photos and lightbox', $det['code'] === 200 && str_contains($dh, 'Sky Terrace Residences — 3 BHK') && str_contains($dh, '₹1.85 Cr') && substr_count($dh, 'data-wblist-open=') === 5 && str_contains($dh, 'wblist-lightbox'));
check('Property page: specs (RERA, floor, facing), amenities and nearby lists', str_contains($dh, 'P52100012345') && str_contains($dh, '9th of 18') && str_contains($dh, 'Amenities') && str_contains($dh, 'Jupiter Hospital'));
check('Property page: site-visit form with visit date, "I am" dropdown, call button', str_contains($dh, 'Schedule a site visit') && str_contains($dh, 'name="extra[Preferred visit date]"') && str_contains($dh, 'name="extra[I am]"') && str_contains($dh, 'href="tel:'));
check('Property page: similar properties (same type)', str_contains($dh, 'Similar properties') && str_contains($dh, 'Skyline Penthouse'));
check('SEO: title and meta description from the property', str_contains($dh, '<title>Sky Terrace Residences — 3 BHK') && preg_match('/<meta name="description" content="A bright, east-facing/', $dh) === 1);
check('Unknown property → 404', $pub->req('GET', 'properties/no-such-home', [], false)['code'] === 404);
check('Sold property: page still opens, marked sold, form asks for requirements instead', str_contains($sold = $pub->req('GET', 'properties/corner-plot-chakan', [], false)['body'], 'Sold') && str_contains($sold, 'is no longer available'));

echo "\n== Site-visit enquiry ==\n";
$e = enquiryForm($pub, $host, 'properties/sky-terrace-3-bhk');
check('Enquiry form posts to the enquiry handler with a back link to the property', $e['action'] !== '' && $e['back'] === 'properties/sky-terrace-3-bhk', $e['back']);
sleep(3);
$since = microtime(true);
$date = date('Y-m-d', strtotime('+4 days'));
$r = $pub->req('POST', $e['action'], ['_t' => $e['t'], '_back' => $e['back'], 'name' => "Rohit $sfx", 'phone' => '+91 98220 11111', 'email' => '', 'message' => 'Weekend visit please',
    'extra' => ['Property' => 'Sky Terrace Residences — 3 BHK', 'I am' => 'Looking to buy', 'Preferred visit date' => $date]], false);
check('Enquiry accepted → back to the property page', $r['code'] === 303 && str_contains($r['location'], 'properties/sky-terrace-3-bhk') && str_contains($r['location'], 'status=sent'), $r['code'] . ' ' . $r['location']);
$after = $pub->req('GET', $r['location'], [], false)['body'];
check('Thank-you message from the manifest shown on the property page', str_contains($after, 'Our property advisor will call you within one working day'));
$row = $pdo->query("SELECT form_key, extra_json FROM {$re}contact_submissions WHERE name = " . $pdo->quote("Rohit $sfx"))->fetch();
check('Stored as an enquiry with the property, buyer type and visit date', $row && $row['form_key'] === 'enquiry' && json_decode($row['extra_json'], true) === ['Property' => 'Sky Terrace Residences — 3 BHK', 'I am' => 'Looking to buy', 'Preferred visit date' => $date], json_encode($row));
$n = mailsTo($mailDir, $notify, $since);
check('Notification email names the property', $n && str_contains($n[0], 'Sky Terrace Residences'));
$r = $pub->req('POST', $e['action'], ['_t' => $e['t'], '_back' => '../../etc', 'name' => "X $sfx", 'phone' => '+91 98220 22222'], false);
check('A tampered back link is not followed', !str_contains($r['location'], 'etc'), $r['location']);

echo "\n== Website admin ==\n";
$page = $o->req('GET', 'listings', [], false);
check('Admin: "Properties" in the sidebar and a list page', $page['code'] === 200 && str_contains($page['body'], 'Properties') && str_contains($page['body'], 'id="listingTable"'));
$list = $o->req('GET', 'api/listings', ['q' => 'Terrace Residences'])['json'];
check('Admin list API: search, price text and public link', ($list['total'] ?? 0) === 1 && ($list['rows'][0]['price_text'] ?? '') === '₹1.85 Cr' && str_contains((string) ($list['rows'][0]['url'] ?? ''), '/properties/sky-terrace-3-bhk'));
check('Admin list: status filter (sold → 2)', (int) ($o->req('GET', 'api/listings', ['status' => 'closed'])['json']['total'] ?? 0) === 2);
$form = $o->req('GET', 'listings/new', [], false)['body'];
check('Add form: fields generated from the manifest (select, number, list, toggle-free)', str_contains($form, 'name="attr[listing_for]"') && str_contains($form, '<option>Under construction</option>') && str_contains($form, 'name="attr[amenities]"') && str_contains($form, 'Bedrooms (BHK)'));
$r = $o->req('POST', 'api/listings', ['title' => '', 'price' => 'abc', 'attr' => ['bedrooms' => 'many', 'listing_for' => 'Lease']]);
$er = $r['json']['errors'] ?? [];
check('Validation: title, price, number and select attributes', $r['code'] === 422 && isset($er['title'], $er['price'], $er['attr[bedrooms]'], $er['attr[listing_for]']), json_encode($er));
$villas = (int) $pdo->query("SELECT id FROM {$re}listing_categories WHERE slug = 'villas'")->fetchColumn();
$add = ['title' => "Lakeview Villa $sfx", 'category_id' => $villas, 'location' => 'Lavasa, Pune', 'price' => '1,25,00,000', 'price_note' => 'onwards', 'short_description' => 'Test <b>villa</b>',
    'description' => '<p>Nice <script>alert(1)</script>home</p>', 'status' => 'active', 'is_featured' => '1', 'badge' => 'New',
    'attr' => ['listing_for' => 'Sale', 'bedrooms' => '4', 'bathrooms' => '4', 'area' => '3,600', 'amenities' => "Lake view\n\nPrivate pool\n", 'rera' => 'P5210009999'],
    'images' => ['theme:images/villa-skyline.svg', 'theme:images/interior-living.svg', 'http://evil.example/x.jpg']];
$r = $o->req('POST', 'api/listings', $add);
check('Images from outside the media library are refused', $r['code'] === 422 && isset($r['json']['errors']['image']));
$add['images'] = ['theme:images/villa-skyline.svg', 'theme:images/interior-living.svg'];
$r = $o->req('POST', 'api/listings', $add);
$newId = (int) ($r['json']['id'] ?? 0);
check('Add a property → redirected to its edit page', ($r['json']['ok'] ?? false) === true && $newId > 0 && str_contains((string) ($r['json']['redirect'] ?? ''), 'listings/' . $newId), json_encode($r['json']));
$row = $pdo->query("SELECT * FROM {$re}listings WHERE id = $newId")->fetch();
$attrs = json_decode((string) ($row['attributes_json'] ?? ''), true) ?: [];
check('Stored: price in paise, slug, numbers parsed, list split, short description as plain text', $row && (int) $row['price'] === 1250000000 && $row['slug'] === "lakeview-villa-$sfx" && $attrs['area'] === 3600 && $attrs['amenities'] === ['Lake view', 'Private pool'] && $row['short_description'] === 'Test villa', json_encode([$row['price'] ?? null, $row['slug'] ?? null, $attrs]));
check('Description HTML cleaned (no script)', !str_contains((string) $row['description'], '<script'));
check('Images: first is the cover, the rest the gallery', $row['image'] === 'theme:images/villa-skyline.svg' && json_decode((string) $row['gallery_json'], true) === ['theme:images/interior-living.svg'], (string) $row['gallery_json']);
$pp = $pub->req('GET', "properties/lakeview-villa-$sfx", [], false)['body'];
check('New property is live: page, ₹1.25 Cr onwards, featured on the homepage', str_contains($pp, "Lakeview Villa $sfx") && str_contains($pp, '₹1.25 Cr') && str_contains($pub->req('GET', '', [], false)['body'], "Lakeview Villa $sfx"));
$r = $o->req('POST', "api/listings/$newId", ['title' => "Lakeview Villa $sfx", 'category_id' => $villas, 'price' => '', 'status' => 'draft', 'attr' => ['listing_for' => 'Sale']]);
check('Edit: empty price = "on request"; draft hides it from the website', ($r['json']['ok'] ?? false) === true && $pdo->query("SELECT price FROM {$re}listings WHERE id = $newId")->fetchColumn() === null
    && $pub->req('GET', "properties/lakeview-villa-$sfx", [], false)['code'] === 404);
$r = $o->req('POST', 'api/listings', ['title' => "Lakeview Villa $sfx", 'status' => 'active']);
check('Same title again gets a unique address', ($r['json']['ok'] ?? false) === true && (string) $pdo->query("SELECT slug FROM {$re}listings WHERE id = " . (int) ($r['json']['id'] ?? 0))->fetchColumn() === "lakeview-villa-$sfx-2");
$dupId = (int) ($r['json']['id'] ?? 0);
$o->req('POST', "api/listings/$dupId/delete");
$o->req('POST', "api/listings/$newId/delete");
check('Delete removes the properties', (int) $pdo->query("SELECT COUNT(*) FROM {$re}listings WHERE id IN ($newId, $dupId)")->fetchColumn() === 0);

echo "\n== Property types ==\n";
$cats = $o->req('GET', 'api/listing-categories')['json']['rows'] ?? [];
check('Types API with counts (Apartments 3)', count($cats) === 5 && (int) (array_column($cats, 'listing_count', 'slug')['apartments'] ?? 0) === 3);
$r = $o->req('POST', 'api/listing-categories', ['name' => "Farmhouses $sfx", 'description' => 'Weekend homes', 'status' => 'active', 'sort_order' => '9']);
$cid = (int) ($r['json']['id'] ?? $pdo->query("SELECT id FROM {$re}listing_categories WHERE name = " . $pdo->quote("Farmhouses $sfx"))->fetchColumn());
check('Add a type → appears in the header dropdown', $cid > 0 && str_contains($pub->req('GET', '', [], false)['body'], "Farmhouses $sfx"));
check('Type name required', isset($o->req('POST', 'api/listing-categories', ['name' => ''])['json']['errors']['name']));
$o->req('POST', "api/listing-categories/$cid", ['name' => "Farmhouses $sfx", 'status' => 'hidden']);
check('Hidden type disappears from the website', !str_contains($pub->req('GET', '', [], false)['body'], "Farmhouses $sfx"));
$o->req('POST', "api/listing-categories/$cid/delete");
check('Delete a type', (int) $pdo->query("SELECT COUNT(*) FROM {$re}listing_categories WHERE id = $cid")->fetchColumn() === 0);
check('"properties" is a reserved page address', isset($o->req('POST', 'api/pages', ['title' => 'Properties', 'slug' => 'properties', 'page_type' => 'generic'])['json']['errors']['slug']));

echo "\n== Travel: packages ==\n";
[$to, $tpub, $tUrl, $thost, $ok] = $open($trId, 'tr');
check('Travel site owner signs in; admin says "Packages"', $ok && str_contains($to->req('GET', 'listings', [], false)['body'], 'Packages'));
$th = $tpub->req('GET', '', [], false)['body'];
check('Homepage: trip finder (destination, trip type, budget), 5 destinations, 6 trending + 3 international', str_contains($th, 'class="hzt-search"') && str_contains($th, 'name="a[trip_type]"')
    && preg_match_all('/class="hzt-type"/', $th) === 5 && cards($th, 'hzt-trip') === 9);
check('Card: duration chip, trip-type chip, "From ₹24,999 per person"', str_contains($th, '<strong>6D</strong> / 5N') && str_contains($th, 'Family') && str_contains($th, '<strong>₹24,999</strong><small>per person</small>'));
$tp = $tpub->req('GET', 'packages', [], false)['body'];
check('/packages: 10 packages, no route filter (turned off in the manifest), duration/trip type/region filters', str_contains($tp, '10 packages found') && !str_contains($tp, 'name="location"') && str_contains($tp, 'name="a[duration]"') && str_contains($tp, 'name="a[region]"'));
check('Filter: honeymoon → 4 (incl. sold out); international → 3', cards($tpub->req('GET', 'packages?a%5Btrip_type%5D=Honeymoon', [], false)['body'], 'hzt-trip') === 4 && cards($tpub->req('GET', 'packages?c=international', [], false)['body'], 'hzt-trip') === 3);
$kd = $tpub->req('GET', 'packages/kerala-backwaters-hills', [], false)['body'];
check('Package page: itinerary, highlights, inclusions/exclusions, trip enquiry with travel date', str_contains($kd, 'Day-by-day itinerary') && str_contains($kd, 'What’s included') && str_contains($kd, 'Not included')
    && str_contains($kd, 'name="extra[Travel date]"') && str_contains($kd, 'name="extra[Travellers]"') && str_contains($kd, 'Plan this trip'));
$e = enquiryForm($tpub, $thost, 'packages/kerala-backwaters-hills');
sleep(3);
$r = $tpub->req('POST', $e['action'], ['_t' => $e['t'], '_back' => $e['back'], 'name' => "Anu $sfx", 'phone' => '+91 94470 11111',
    'extra' => ['Package' => 'Kerala Backwaters & Hills', 'Travellers' => '2 travellers', 'Travel date' => date('Y-m-d', strtotime('+30 days'))]], false);
check('Trip enquiry accepted and stored with the package', str_contains($r['location'], 'status=sent') && ($tr !== '' && (string) $pdo->query("SELECT JSON_UNQUOTE(JSON_EXTRACT(extra_json, '$.Package')) FROM {$tr}contact_submissions WHERE name = " . $pdo->quote("Anu $sfx"))->fetchColumn() === 'Kerala Backwaters & Hills'));
$pg = $to->req('POST', 'api/pages', ['title' => 'Projects', 'slug' => "projects-$sfx", 'page_type' => 'generic']);
check('Other listing words stay free for normal pages on a travel site', ($to->req('POST', 'api/pages', ['title' => 'Properties', 'slug' => 'properties', 'page_type' => 'generic'])['json']['ok'] ?? false) === true);
$pid = (int) $pdo->query("SELECT id FROM {$tr}pages WHERE slug = 'properties'")->fetchColumn();
if ($pid) {
    $to->req('POST', "api/pages/$pid/delete");
}
if (!empty($pg['json']['id'])) {
    $to->req('POST', 'api/pages/' . (int) $pg['json']['id'] . '/delete');
}
check('Travel: "packages" is reserved; properties URL is not a page here', isset($to->req('POST', 'api/pages', ['title' => 'Packages', 'slug' => 'packages', 'page_type' => 'generic'])['json']['errors']['slug']) && $tpub->req('GET', 'properties', [], false)['code'] === 404);

echo "\n== Sites without listings ==\n";
$biz = (int) $pdo->query("SELECT w.id FROM wb_websites w JOIN wb_templates t ON t.id = w.template_id WHERE t.code = 'business_template_01' AND w.status <> 'deleted' ORDER BY w.id LIMIT 1")->fetchColumn();
if ($biz) {
    $bsite = basename((string) $pdo->query("SELECT folder_path FROM wb_websites WHERE id = $biz")->fetchColumn());
    $bu = preg_replace('#site_[^/]+/$#', $bsite . '/', $siteUrl);
    $bp = new Client((string) $bu);
    $bp->basic = $qa;
    check('A website whose template has no listings: /properties is 404', $bp->req('GET', 'properties', [], false)['code'] === 404);
}

printf("\n%d passed, %d failed (%.1fs)\n", $pass, $fail, microtime(true) - $started);
exit($fail ? 1 : 0);
