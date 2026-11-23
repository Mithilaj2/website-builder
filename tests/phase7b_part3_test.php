<?php
declare(strict_types=1);

/**
 * Phase 7b (part 3) end-to-end test: Portfolio 01 (Studio Kiln: projects on the listings module without
 * prices) and Services 01 (Ledgerline Advisors: services with fees, pricing packages, consultation booking).
 *
 *   php tests/phase7b_part3_test.php <builder-url> <email> <password> <portfolio-website-id> <services-website-id> <mail-dir> <db-dsn> [portfolio-template-id] [services-template-id]
 *
 * Both websites must be freshly generated with their template's demo content.
 */

require __DIR__ . '/support.php';

[$_, $base, $email, $password, $pfId, $svId, $mailDir, $dsn, $pfTpl, $svTpl] = $argv + [null, 'http://127.0.0.1:8080', 'info@channelsoftech.com', '', '0', '0', '/tmp/mailsink', '', '0', '0'];
$base = rtrim((string) $base, '/');
$pfId = (int) $pfId;
$svId = (int) $svId;
$sfx = substr(bin2hex(random_bytes(3)), 0, 6);
$pass = $fail = 0;
$started = microtime(true);

function formOn(Client $site, string $host, string $path, string $form): array
{
    $html = $site->req('GET', $path, [], false)['body'];
    preg_match('/<form class="[a-z]+-form" method="post" action="([^"]*\/form\/' . preg_quote($form, '/') . ')"/', $html, $act);
    preg_match('/name="_t" value="([^"]+)"/', $html, $t);
    preg_match('/name="_back" value="([^"]*)"/', $html, $b);
    return ['html' => $html, 'action' => isset($act[1]) ? $host . html_entity_decode($act[1]) : '', 't' => $t[1] ?? '', 'back' => $b[1] ?? ''];
}
function cards(string $html, string $class): int
{
    return substr_count($html, '<article class="' . $class);
}

[$d, $du, $dp] = explode('|', (string) $dsn) + ['', '', ''];
$pdo = new PDO($d, $du, $dp, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$prefixOf = static fn (int $wid): string => (string) $pdo->query('SELECT table_prefix FROM wb_websites WHERE id = ' . $wid)->fetchColumn();
$pf = $prefixOf($pfId);
$sv = $prefixOf($svId);
foreach ([$pf, $sv] as $p) {
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

echo "\n== Templates ==\n";
$tpl = $pdo->query("SELECT t.code, t.status, c.slug FROM wb_templates t JOIN wb_categories c ON c.id = t.category_id WHERE t.code IN ('portfolio_template_01', 'services_template_01')")->fetchAll();
$byCode = array_column($tpl, null, 'code');
check('Portfolio 01 registered, active, in the portfolio category', ($byCode['portfolio_template_01']['status'] ?? '') === 'active' && ($byCode['portfolio_template_01']['slug'] ?? '') === 'portfolio');
check('Services 01 registered, active, in the services category', ($byCode['services_template_01']['status'] ?? '') === 'active' && ($byCode['services_template_01']['slug'] ?? '') === 'services');
$fonts = json_decode((string) file_get_contents(__DIR__ . '/../runtime/fonts/fonts.json'), true);
check('Space Grotesk font bundled with its licence', isset($fonts['space-grotesk']) && is_file(__DIR__ . '/../runtime/fonts/space-grotesk/LICENSE') && is_file(__DIR__ . '/../runtime/fonts/space-grotesk/700.woff2'));
if ((int) $pfTpl > 0) {
    $prev = $b->req('GET', "templates/$pfTpl/preview", [], false)['body'];
    check('Portfolio preview shows demo projects', cards($prev, 'skl-work') >= 6 && str_contains($prev, 'Kaapi Co.'));
}
if ((int) $svTpl > 0) {
    $prev = $b->req('GET', "templates/$svTpl/preview", [], false)['body'];
    check('Services preview shows demo services with fees', cards($prev, 'ldg-svc') >= 6 && str_contains($prev, '₹1,499'));
}

[$o, $pub, $siteUrl, $host, $ok] = $open($pfId, 'pf');
check('Portfolio owner signs in; admin menu says "Projects"', $ok && str_contains($o->req('GET', 'listings', [], false)['body'], 'Projects'));

echo "\n== Portfolio: projects without prices ==\n";
$home = $pub->req('GET', '', [], false)['body'];
check('Hero with status line and a ticker (repeated items hidden from screen readers)', str_contains($home, 'skl-hero-status') && substr_count($home, '<li aria-hidden="true">') === 4 && str_contains($home, 'skl-ticker-track'));
check('"Work" is the second menu item', preg_match('#>Home</a></li>\s*<li class="nav-item"><a class="nav-link[^"]*" href="[^"]*/work"#', $home) === 1);
check('Homepage: 6 featured projects with discipline, year and industry; no prices anywhere', cards($home, 'skl-work') === 6 && preg_match('#<span>Branding</span>\s*<span>2026</span>\s*<span>Food &amp; drink</span>#', $home) === 1 && !str_contains($home, '₹'));
$idx = $pub->req('GET', 'work', [], false)['body'];
check('/work: "10 projects found", archived one last', str_contains($idx, '10 projects found') && cards($idx, 'skl-work') === 10 && strpos($idx, 'The New Normal') > strpos($idx, 'Book covers for Ink'));
check('No price filters or price sorting when the template has no prices', !str_contains($idx, 'name="min"') && !str_contains($idx, 'name="max"') && !str_contains($idx, 'price_asc') && str_contains($idx, 'name="a[industry]"') && !str_contains($idx, 'name="location"'));
$q = static fn (string $s): int => cards($pub->req('GET', 'work?' . $s, [], false)['body'], 'skl-work');
check('Filters: branding → 3; industry Education → 2; search "cans" → 1', $q('c=branding') === 3 && $q('a%5Bindustry%5D=Education') === 2 && $q('q=cans') === 1, $q('c=branding') . '/' . $q('a%5Bindustry%5D=Education') . '/' . $q('q=cans'));
$dt = $pub->req('GET', 'work/kaapi-co', [], false)['body'];
check('Case study: no price block, "Client:" label, timeline and team, both lists', !str_contains($dt, 'wblist-head-price') && str_contains($dt, 'Client:</span> Kaapi Co.') && str_contains($dt, '12 weeks') && str_contains($dt, 'What we did') && str_contains($dt, 'Outcomes'));
check('Brief form with budget and ideal start date; 3-photo gallery; similar work', str_contains($dt, 'name="extra[Budget]"') && str_contains($dt, 'name="extra[Ideal start date]"') && str_contains($dt, 'wblist-gallery has-3') && str_contains($dt, 'Similar projects'));
$f = formOn($pub, $host, 'work/kaapi-co', 'enquiry');
sleep(3);
$start = date('Y-m-d', strtotime('+45 days'));
$r = $pub->req('POST', $f['action'], ['_t' => $f['t'], '_back' => $f['back'], 'name' => "Maya $sfx", 'phone' => '+91 98450 11111', 'email' => "maya$sfx@example.com", 'message' => 'Tea brand',
    'extra' => ['Project' => 'Kaapi Co. — a coffee brand from the hills', 'Budget' => '₹5–15 lakh', 'Ideal start date' => $start]], false);
check('Brief accepted with the studio’s thank-you message', str_contains($r['location'], 'work/kaapi-co') && str_contains($pub->req('GET', $r['location'], [], false)['body'], 'We read every one'));
$row = $pdo->query("SELECT extra_json FROM {$pf}contact_submissions WHERE name = " . $pdo->quote("Maya $sfx"))->fetch();
check('Stored with project, budget and start date', $row && json_decode($row['extra_json'], true) === ['Project' => 'Kaapi Co. — a coffee brand from the hills', 'Budget' => '₹5–15 lakh', 'Ideal start date' => $start], json_encode($row));
$form = $o->req('GET', 'listings/new', [], false)['body'];
check('Admin project form has no price fields; client and industry fields present', !str_contains($form, 'name="price"') && !str_contains($form, 'name="price_note"') && str_contains($form, 'Client') && str_contains($form, 'name="attr[industry]"'));
$branding = (int) $pdo->query("SELECT id FROM {$pf}listing_categories WHERE slug = 'branding'")->fetchColumn();
$r = $o->req('POST', 'api/listings', ['title' => "Monsoon Tea $sfx", 'category_id' => $branding, 'location' => 'Monsoon Tea Co.', 'price' => '50000', 'status' => 'active', 'attr' => ['year' => '2026', 'industry' => 'Food & drink', 'services' => "Identity\nPackaging"]]);
$nid = (int) ($r['json']['id'] ?? 0);
check('A posted price is ignored for a template without prices', $nid > 0 && $pdo->query("SELECT price FROM {$pf}listings WHERE id = $nid")->fetchColumn() === null);
check('New project appears under Branding', $q('c=branding') === 4);
$o->req('POST', "api/listings/$nid/delete");
$sp = $pub->req('GET', 'services', [], false);
check('On the portfolio site /services is a normal page with the disciplines index', $sp['code'] === 200 && str_contains($sp['body'], 'class="skl-disc-list"') && str_contains($sp['body'], '3 projects'));

[$so, $spub, $sUrl, $shost, $ok] = $open($svId, 'sv');
check('Services owner signs in; admin menu says "Services"', $ok && str_contains($so->req('GET', 'listings', [], false)['body'], 'Services'));

echo "\n== Services: catalogue with fees ==\n";
$sh = $spub->req('GET', '', [], false)['body'];
check('Hero button jumps to the consultation form on the same page', str_contains($sh, 'href="#appointment-form"') && str_contains($sh, 'id="appointment-form"'));
check('Service groups with counts (Startups: 4 services)', preg_match_all('/class="ldg-type"/', $sh) === 3 && str_contains($sh, '4 services'));
check('6 popular services with fee, note, turnaround and mode', cards($sh, 'ldg-svc') === 6 && str_contains($sh, '<strong>₹1,499</strong><small>per return</small>') && str_contains($sh, '2–3 working days') && str_contains($sh, 'Online'));
$si = $spub->req('GET', 'services', [], false)['body'];
check('/services: 10 services, unavailable one last, billing filter, price filter', str_contains($si, '10 services found') && cards($si, 'ldg-svc') === 10 && strpos($si, 'Statutory audit') > strpos($si, 'Virtual CFO') && str_contains($si, 'name="a[frequency]"') && str_contains($si, 'name="max"'));
$q2 = static fn (string $s): int => cards($spub->req('GET', 'services?' . $s, [], false)['body'], 'ldg-svc');
check('Filters: monthly → 4; up to ₹2,000 → 3; startups → 4', $q2('a%5Bfrequency%5D=Monthly') === 4 && $q2('max=2000') === 3 && $q2('c=startups') === 4, $q2('a%5Bfrequency%5D=Monthly') . '/' . $q2('max=2000') . '/' . $q2('c=startups'));
check('Service without a fee says "Fees on request"', str_contains($si, 'Fees on request'));
$sd = $spub->req('GET', 'services/itr-filing', [], false)['body'];
check('Service page: "Ideal for:" label, fee with note, included and documents lists', str_contains($sd, 'Ideal for:</span> Salaried people') && str_contains($sd, '₹1,499') && str_contains($sd, 'per return') && str_contains($sd, 'What’s included') && str_contains($sd, 'Documents you’ll need'));
check('Call-back form: "You are" choice and no date field', str_contains($sd, 'name="extra[You are]"') && !preg_match('/<input type="date" name="extra\[/', $sd) && str_contains($sd, 'Request a call back'));
$f = formOn($spub, $shost, 'services/itr-filing', 'enquiry');
sleep(3);
$r = $spub->req('POST', $f['action'], ['_t' => $f['t'], '_back' => $f['back'], 'name' => "Dev $sfx", 'phone' => '+91 99090 11111', 'extra' => ['Service' => 'Income tax return (ITR) filing', 'You are' => 'An individual']], false);
check('Call-back request accepted', str_contains($r['location'], 'status=sent') && str_contains($spub->req('GET', $r['location'], [], false)['body'], 'One of our chartered accountants will call you'));
$row = $pdo->query("SELECT extra_json FROM {$sv}contact_submissions WHERE name = " . $pdo->quote("Dev $sfx"))->fetch();
check('Stored with the service and client type', $row && json_decode($row['extra_json'], true) === ['Service' => 'Income tax return (ITR) filing', 'You are' => 'An individual'], json_encode($row));
$pr = $spub->req('GET', 'pricing', [], false)['body'];
check('Pricing page: 3 packages, the middle one highlighted', substr_count($pr, 'class="ldg-card ldg-plan') === 3 && str_contains($pr, 'ldg-plan is-featured') && str_contains($pr, '₹9,999'));
$f = formOn($spub, $shost, '', 'appointment');
sleep(3);
$date = date('Y-m-d', strtotime('+2 days'));
$r = $spub->req('POST', $f['action'], ['_t' => $f['t'], '_back' => '', 'name' => "Asha $sfx", 'phone' => '+91 99090 22222', 'extra' => ['Service' => 'GST registration or returns', 'Preferred time' => 'Morning (10 am – 1 pm)', 'Preferred date' => $date]], false);
$row = $pdo->query("SELECT form_key, extra_json FROM {$sv}contact_submissions WHERE name = " . $pdo->quote("Asha $sfx"))->fetch();
check('Free consultation booking stored as an appointment', str_contains($r['location'], 'status=sent') && $row && $row['form_key'] === 'appointment' && (json_decode($row['extra_json'], true)['Preferred date'] ?? '') === $date);
check('"services" is reserved on the services website', isset($so->req('POST', 'api/pages', ['title' => 'Services', 'slug' => 'services', 'page_type' => 'generic'])['json']['errors']['slug']));
check('Header dropdown lists the service groups', str_contains($sh, 'class="dropdown-menu ldg-dropdown"') && str_contains($sh, 'services?c=startups'));

echo "\n== Earlier listing templates still show locations as places ==\n";
$re = (int) $pdo->query("SELECT w.id FROM wb_websites w JOIN wb_templates t ON t.id = w.template_id WHERE t.code = 'realestate_template_01' AND w.status <> 'deleted' ORDER BY w.id DESC LIMIT 1")->fetchColumn();
if ($re) {
    $folder = basename((string) $pdo->query("SELECT folder_path FROM wb_websites WHERE id = $re")->fetchColumn());
    $rc = new Client((string) preg_replace('#site_[^/]+/$#', $folder . '/', $siteUrl));
    $rc->basic = $qa;
    $rp = $rc->req('GET', 'properties/sky-terrace-3-bhk', [], false)['body'];
    check('Real estate: locality keeps the map-pin icon (location filter on)', str_contains($rp, '<i class="bi bi-geo-alt" aria-hidden="true"></i>Baner, Pune') || str_contains($rp, 'bi-geo-alt'));
}

printf("\n%d passed, %d failed (%.1fs)\n", $pass, $fail, microtime(true) - $started);
exit($fail ? 1 : 0);
