<?php
declare(strict_types=1);

/**
 * Phase 3 end-to-end test: wizard, generation, standalone website, regeneration, rollback, statuses.
 *
 *   php tests/phase3_http_test.php <builder-url> <email> <password> [qa-sites-url] [builder-root-on-disk]
 *
 * qa-sites-url: where generated websites are reachable (default: builder setting).
 * builder-root-on-disk: enables the failure/rollback test (it temporarily breaks a template file).
 */

require __DIR__ . '/support.php';

[$_, $base, $email, $password, $qaUrl, $root] = $argv + [null, 'http://127.0.0.1:8080', 'info@channelsoftech.com', '', '', ''];
$base = rtrim((string) $base, '/');
$sfx = substr(bin2hex(random_bytes(3)), 0, 6);
$pass = $fail = 0;
$tmp = sys_get_temp_dir();

function generate(Client $a, int $wid, ?int $runId = null): array
{
    if ($runId === null) {
        $r = $a->req('POST', "websites/$wid/generate");
        if (!($r['json']['ok'] ?? false)) {
            return ['status' => 'not-started', 'error' => $r['json']['message'] ?? $r['body'], 'code' => $r['code'], 'steps' => []];
        }
        $runId = $r['json']['state']['run_id'];
    }
    for ($i = 0; $i < 30; $i++) {
        $s = $a->req('POST', "websites/$wid/generations/$runId/step")['json']['state'] ?? ['status' => 'error', 'steps' => []];
        if ($s['status'] !== 'running') {
            return $s;
        }
    }
    return $s;
}

function qa(string $url, string $user, string $pw): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_USERPWD => "$user:$pw", CURLOPT_FOLLOWLOCATION => false]);
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $loc = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    return ['code' => $code, 'body' => $body, 'location' => $loc];
}

function qaPost(string $url, array $data, string $user, string $pw): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_USERPWD => "$user:$pw", CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data)]);
    curl_exec($ch);
    $r = ['code' => (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE), 'location' => (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL)];
    curl_close($ch);
    return $r;
}

$a = new Client($base);
check('Login', ($a->login($email, $password)['json']['ok'] ?? false) === true);

echo "\n== Settings: QA access ==\n";
$r = $a->req('GET', 'settings', [], false);
check('Settings page (Super Admin)', $r['code'] === 200 && preg_match('/name="qa_sites_url"/', $r['body']) === 1);
preg_match('/font-monospace" value="([A-Za-z0-9]+)"/', $r['body'], $m);
$qaPw = $m[1] ?? '';
check('QA password generated', strlen($qaPw) === 18);
if ($qaUrl !== '') {
    check('Save QA sites URL', ($a->req('POST', 'settings', ['qa_sites_url' => $qaUrl])['json']['ok'] ?? false) === true);
}
check('Invalid QA URL rejected', isset($a->req('POST', 'settings', ['qa_sites_url' => 'not a url'])['json']['errors']['qa_sites_url']));
if ($qaUrl !== '') {
    $a->req('POST', 'settings', ['qa_sites_url' => $qaUrl]);
}

echo "\n== Step 1–2: create website with a new client ==\n";
$tid = 0;
foreach ($a->req('GET', 'templates/list', ['q' => 'business_template_01'])['json']['rows'] ?? [] as $row) {
    $tid = $row['code'] === 'business_template_01' ? (int) $row['id'] : $tid;
}
if (!$tid) {
    $r = $a->req('POST', 'templates/register', ['category' => 'business', 'folder' => 'business_template_01']);
    preg_match('#templates/(\d+)#', (string) ($r['json']['redirect'] ?? ''), $m);
    $tid = (int) ($m[1] ?? 0);
    $a->req('POST', "templates/$tid/status");
}
check('Business Template 01 available', $tid > 0);
$cat = 0;
foreach ($a->req('GET', 'categories/list', ['per_page' => 50])['json']['rows'] as $c) {
    $cat = $c['slug'] === 'business' ? (int) $c['id'] : $cat;
}
$clientsBefore = (int) ($a->req('GET', 'clients/list')['json']['total'] ?? 0);
$r = $a->req('POST', 'websites', ['client_mode' => 'new', 'client_id' => '__new', 'new_business_name' => "Gamma Foods $sfx", 'name' => '', 'category_id' => $cat, 'template_id' => $tid]);
check('Validation error does not leave an orphan client', isset($r['json']['errors']['name']) && (int) ($a->req('GET', 'clients/list')['json']['total'] ?? 0) === $clientsBefore);
$r = $a->req('POST', 'websites', ['client_mode' => 'new', 'client_id' => '__new', 'new_business_name' => "Gamma Foods $sfx", 'new_contact_person' => 'Lakshmi', 'new_email' => "gamma$sfx@gamma.in", 'new_phone' => '+91 99470 22222', 'new_address' => 'MG Road, Kochi', 'name' => "Gamma Foods $sfx", 'category_id' => $cat, 'template_id' => $tid, 'domain' => 'gammafoods.in']);
check('Create website + new client in one step', ($r['json']['ok'] ?? false) === true, $r['body']);
preg_match('#websites/(\d+)#', (string) ($r['json']['redirect'] ?? ''), $m);
$wid = (int) ($m[1] ?? 0);
$code = str_pad((string) $wid, 6, '0', STR_PAD_LEFT);
$clientId = (int) ($a->req('GET', 'clients/list', ['q' => "Gamma Foods $sfx"])['json']['rows'][0]['id'] ?? 0);
check('Client was created', $clientId > 0);
check('Wizard opens at step 3 after creation', str_contains((string) ($r['json']['redirect'] ?? ''), 'step=3'));
foreach ([1, 2, 3, 4, 5, 6, 7, 8] as $n) {
    $page = $a->req('GET', "websites/$wid", ['step' => $n], false);
    if ($page['code'] !== 200) {
        break;
    }
}
check('All 8 wizard steps render', $page['code'] === 200 && substr_count($page['body'], 'class="tab-pane fade" id="step') === 8);

echo "\n== Step 7: payments ==\n";
$r = $a->req('POST', "websites/$wid/payments", ['enabled' => '1', 'gateway' => '', 'mode' => 'test']);
check('Enabling payment requires a gateway', isset($r['json']['errors']['gateway']));
check('Unknown gateway rejected', isset($a->req('POST', "websites/$wid/payments", ['enabled' => '1', 'gateway' => 'paypal', 'mode' => 'test'])['json']['errors']['gateway']));
check('Save Razorpay (test mode)', ($a->req('POST', "websites/$wid/payments", ['enabled' => '1', 'gateway' => 'razorpay', 'mode' => 'test'])['json']['ok'] ?? false) === true);

echo "\n== Step 8: checklist ==\n";
$r = $a->req('POST', "websites/$wid/wizard-step", ['step' => 8]);
check('Reaching review marks the website Configuration Pending', ($r['json']['status'] ?? '') === 'config_pending');
$cl = $a->req('GET', "websites/$wid/checklist")['json'];
check('Checklist has no errors for a fresh site', ($cl['errors'] ?? ['x']) === [], json_encode($cl['errors'] ?? null));
check('Checklist warns about demo images and missing logo', count(array_filter($cl['warnings'], fn ($w) => str_contains($w, 'demo image'))) === 1 && count(array_filter($cl['warnings'], fn ($w) => str_contains($w, 'logo'))) === 1);
check('Checklist reports the payment gateway', (bool) array_filter($cl['items'], fn ($i) => str_contains($i['label'], 'Razorpay')));
$pages = $a->req('GET', "websites/$wid/pages")['json']['pages'];
$about = array_values(array_filter($pages, fn ($p) => $p['slug'] === 'about'))[0];
$aboutSecs = $a->req('GET', "websites/$wid/pages/{$about['id']}/sections")['json']['sections'];
foreach ($aboutSecs as $s) {
    $a->req('POST', "websites/$wid/sections/{$s['id']}/toggle");
}
$cl = $a->req('GET', "websites/$wid/checklist")['json'];
check('A page with no visible sections is an error', (bool) array_filter($cl['errors'], fn ($e) => str_contains($e, 'About Us')));
$r = $a->req('POST', "websites/$wid/generate");
check('Generation refused while the checklist has errors', $r['code'] === 422 && isset($r['json']['checklist']));
foreach ($aboutSecs as $s) {
    $a->req('POST', "websites/$wid/sections/{$s['id']}/toggle");
}

echo "\n== Generate ==\n";
$up = $a->req('POST', "websites/$wid/media", ['folder' => 'logo', 'files[0]' => new CURLFile(makeImageFile("$tmp/logo$sfx.png"), 'image/png', 'gamma-logo.png')])['json']['items'][0] ?? ['path' => ''];
$st = generate($a, $wid);
check('Generation succeeds', ($st['status'] ?? '') === 'success', json_encode($st['error'] ?? $st));
$byKey = array_column($st['steps'] ?? [], 'status', 'step_key');
check('All 11 steps completed', count($byKey) === 11 && !array_diff(array_values($byKey), ['success', 'skipped']), json_encode($byKey));
check('Website checks ran over HTTP (not skipped)', ($byKey['smoke_test'] ?? '') === 'success', (string) (array_column($st['steps'], 'log', 'step_key')['smoke_test'] ?? ''));
$siteUrl = (string) ($st['site_url'] ?? '');
$w = $a->req('GET', 'websites/list', ['q' => $code])['json']['rows'][0] ?? [];
check('Status is Generated', ($w['status'] ?? '') === 'generated');
$hist = $a->req('GET', "websites/$wid/generations")['json'];
check('Generation history recorded', count($hist['runs'] ?? []) === 1 && $hist['runs'][0]['status'] === 'success');

echo "\n== The generated website (standalone) ==\n";
$qaPw = $hist['qa_password'] ?? $qaPw;
$home = qa($siteUrl, 'qa', $qaPw);
check('Homepage served by the generated site', $home['code'] === 200 && str_contains($home['body'], "Gamma Foods $sfx"));
check('Uses its own assets (theme/, assets/, uploads/) — nothing from the builder', !str_contains($home['body'], 'preview-assets') && !str_contains($home['body'], '/websites/') && str_contains($home['body'], 'theme/assets/css/style.css') && str_contains($home['body'], 'assets/lib/bootstrap'));
check('Client details from step 1 used', str_contains($home['body'], "gamma$sfx@gamma.in") && str_contains($home['body'], '+91 99470 22222'));
foreach (['about', 'services', 'gallery', 'contact', 'privacy-policy', 'terms-and-conditions'] as $slug) {
    $r = qa($siteUrl . $slug, 'qa', $qaPw);
    check("Clean URL /$slug → 200", $r['code'] === 200 && str_contains($r['body'], '</html>') && !preg_match('/Fatal error|Warning:/', $r['body']));
}
check('Unknown page → 404 with template design', qa($siteUrl . 'nope-' . $sfx, 'qa', $qaPw)['code'] === 404);
preg_match('#href="([^"]*theme/assets/css/style\.css)#', $home['body'], $m);
check('Template CSS loads', isset($m[1]) && qa((string) preg_replace('#^(https?://[^/]+).*$#', '$1', $siteUrl) . html_entity_decode($m[1]), 'qa', $qaPw)['code'] === 200);
foreach (['config/database.php', 'config/app.php', 'core/Site.php', 'theme/sections/hero.php', 'theme/manifest.json', 'storage/'] as $priv) {
    $r = qa($siteUrl . $priv, 'qa', $qaPw);
    check("Private path blocked: /$priv", in_array($r['code'], [403, 404], true) && !str_contains($r['body'], "'pass'"), (string) $r['code']);
}
$contact = qa($siteUrl . 'contact', 'qa', $qaPw)['body'];
preg_match('/name="_t" value="([^"]+)"/', $contact, $t);
preg_match('/<form class="mrd-form" method="post" action="([^"]+)"/', $contact, $act);
check('Contact form is live on the generated site', isset($t[1], $act[1]) && !str_contains($contact, 'fieldset disabled'));
$host = (string) preg_replace('#^(https?://[^/]+).*$#', '$1', $siteUrl);
$tooFast = qaPost($host . html_entity_decode($act[1] ?? ''), ['_t' => $t[1] ?? '', '_back' => 'contact', 'name' => 'Fast', 'email' => 'f@x.com', 'message' => 'hi'], 'qa', $qaPw);
check('Instant (bot-speed) submission rejected', str_contains($tooFast['location'], 'status=error'));
sleep(3);
$ok = qaPost($host . html_entity_decode($act[1] ?? ''), ['_t' => $t[1] ?? '', '_back' => 'contact', 'name' => 'Meera', 'email' => 'Meera@Example.com', 'message' => "Order enquiry $sfx"], 'qa', $qaPw);
check('Valid enquiry accepted (303 → thank-you message)', $ok['code'] === 303 && str_contains($ok['location'], 'status=sent'));
check('Thank-you message shown', str_contains(qa($ok['location'], 'qa', $qaPw)['body'], 'is-sent'));
check('Invalid email rejected', str_contains(qaPost($host . html_entity_decode($act[1] ?? ''), ['_t' => $t[1] ?? '', 'name' => 'X', 'email' => 'nope', 'message' => 'x'], 'qa', $qaPw)['location'], 'reason=invalid'));

echo "\n== Regenerate after changes ==\n";
$homeId = (int) array_values(array_filter($pages, fn ($p) => (int) $p['is_home'] === 1))[0]['id'];
$hero = $a->req('GET', "websites/$wid/pages/$homeId/sections")['json']['sections'][0];
$hd = $a->req('GET', "websites/$wid/sections/{$hero['id']}")['json']['section']['data'];
$hd['heading'] = "Fresh from Kerala $sfx";
$a->req('POST', "websites/$wid/sections/{$hero['id']}", ['data' => json_encode($hd), 'style' => '{}']);
check('Content changes are live immediately (same database)', str_contains(qa($siteUrl, 'qa', $qaPw)['body'], "Fresh from Kerala $sfx"));
$st2 = generate($a, $wid);
check('Regeneration succeeds', ($st2['status'] ?? '') === 'success', json_encode($st2['error'] ?? $st2));
check('Uploaded media kept across regeneration', qa($siteUrl . $up['path'], 'qa', $qaPw)['code'] === 200);
$hist = $a->req('GET', "websites/$wid/generations")['json']['runs'];
check('Second run recorded as "regenerate"', count($hist) === 2 && $hist[0]['type'] === 'regenerate');

if ($root !== '' && is_dir($root)) {
    echo "\n== Failure and automatic rollback ==\n";
    $view = rtrim($root, '/') . '/templates/business/business_template_01/sections/stats.php';
    $orig = (string) file_get_contents($view);
    file_put_contents($view, "<?php throw new RuntimeException('simulated template bug'); ?>\n" . $orig);
    $st3 = generate($a, $wid);
    check('Broken template makes generation fail at the website checks', ($st3['status'] ?? '') === 'failed' && str_contains((string) ($st3['error'] ?? ''), 'Run website checks'), json_encode(array_column($st3['steps'] ?? [], 'log', 'step_key')['smoke_test'] ?? '') . ' / copy_theme: ' . json_encode(array_column($st3['steps'] ?? [], 'log', 'step_key')['copy_theme'] ?? ''));
    sleep(3); // a separate PHP process (dev server) may serve its cached copy of the broken file for opcache.revalidate_freq seconds
    $live = qa($siteUrl, 'qa', $qaPw);
    check('Previous working version restored automatically (site still up)', $live['code'] === 200 && str_contains($live['body'], "Fresh from Kerala $sfx"));
    check('Website marked Generation Failed', ($a->req('GET', 'websites/list', ['q' => $code])['json']['rows'][0]['status'] ?? '') === 'failed');
    file_put_contents($view, $orig);
    $retry = $a->req('POST', "websites/$wid/generations/{$st3['run_id']}/retry");
    check('Retry from the failed step accepted', ($retry['json']['ok'] ?? false) === true);
    $st4 = generate($a, $wid, (int) $st3['run_id']);
    check('Retry succeeds after the fix', ($st4['status'] ?? '') === 'success', json_encode($st4['error'] ?? ''));
}

echo "\n== Concurrency & permissions ==\n";
$r = $a->req('POST', "websites/$wid/generate");
$runId = (int) ($r['json']['state']['run_id'] ?? 0);
$second = $a->req('POST', "websites/$wid/generate");
check('Second concurrent generation refused', $second['code'] === 409, $second['code'] . ' ' . $second['body'] . ' / first: ' . $r['body']);
generate($a, $wid, $runId);
$vEmail = "p3viewer$sfx@example.com";
$a->req('POST', 'users', ['name' => 'P3 Viewer', 'email' => $vEmail, 'role' => 'viewer', 'status' => 'active', 'password' => 'ViewerPass123', 'password_confirmation' => 'ViewerPass123']);
$v = new Client($base);
$v->login($vEmail, 'ViewerPass123');
check('Viewer can see the checklist', $v->req('GET', "websites/$wid/checklist")['code'] === 200);
check('Viewer cannot generate (403)', $v->req('POST', "websites/$wid/generate")['code'] === 403);
check('Viewer cannot open Settings (403)', $v->req('GET', 'settings', [], false)['code'] === 403);
$vh = $v->req('GET', "websites/$wid/generations")['json'] ?? [];
check('Generation history hides the QA password from viewers', array_key_exists('qa_password', $vh) && $vh['qa_password'] === null);

echo "\n== Statuses ==\n";
check('Deployed not allowed straight from Generated', ($a->req('POST', "websites/$wid/status?status=deployed")['json']['ok'] ?? true) === false);
check('Mark as Testing', ($a->req('POST', "websites/$wid/status?status=testing")['json']['ok'] ?? false) === true);
check('Ready for deployment', ($a->req('POST', "websites/$wid/status?status=ready")['json']['ok'] ?? false) === true);
check('Template change not allowed once ready', ($a->req('POST', "websites/$wid/template", ['template_id' => $tid, 'confirm' => 1])['json']['ok'] ?? true) === false);
check('Mark as Deployed', ($a->req('POST', "websites/$wid/status?status=deployed")['json']['ok'] ?? false) === true);
check('Deployed: content locked in the builder (409)', $a->req('POST', "websites/$wid/sections/{$hero['id']}", ['data' => json_encode($hd), 'style' => '{}'])['code'] === 409);
check('Deployed: generation refused', ($a->req('POST', "websites/$wid/generate")['json']['ok'] ?? true) === false);
check('Deployed: cannot be deleted', ($a->req('POST', "websites/$wid/delete", ['confirm' => $code])['json']['ok'] ?? true) === false);
check('Archive', ($a->req('POST', "websites/$wid/status?status=archived")['json']['ok'] ?? false) === true);
check('Restore from archive → Generated', ($a->req('POST', "websites/$wid/status?status=restore")['json']['ok'] ?? false) === true
    && ($a->req('GET', 'websites/list', ['q' => $code])['json']['rows'][0]['status'] ?? '') === 'generated');

echo "\n== Step 2: switch template ==\n";
$dupCode = "zz_p3_tpl_$sfx";
$r = $a->req('POST', "templates/$tid/duplicate", ['code' => $dupCode, 'name' => "P3 Copy $sfx"]);
preg_match('#templates/(\d+)$#', (string) ($r['json']['redirect'] ?? ''), $m);
$tid2 = (int) ($m[1] ?? 0);
$a->req('POST', "templates/$tid2/status");
$r = $a->req('POST', "websites/$wid/template", ['template_id' => $tid2]);
check('Template switch needs confirmation', ($r['json']['ok'] ?? true) === false);
$r = $a->req('POST', "websites/$wid/template", ['template_id' => $tid2, 'confirm' => 1]);
check('Switch template', ($r['json']['ok'] ?? false) === true, $r['body']);
$preview = $a->req('GET', "websites/$wid/preview", [], false)['body'];
check('Content reset from the new template', !str_contains($preview, "Fresh from Kerala $sfx") && str_contains($preview, 'Clear strategy.'));
check('Site details kept (name, contact)', str_contains($preview, "Gamma Foods $sfx") && str_contains($preview, "gamma$sfx@gamma.in"));
check('Media library kept', ($a->req('GET', "websites/$wid/media")['json']['total'] ?? 0) === 1);
check('Website now uses the new template', str_contains($a->req('GET', "websites/$wid", ['step' => 2], false)['body'], "P3 Copy $sfx"));

echo "\n== Cleanup ==\n";
$r = $a->req('POST', "websites/$wid/delete", ['confirm' => $code]);
check('Delete generated website (backup first)', ($r['json']['ok'] ?? false) === true, $r['body']);
check('Generated site is gone', in_array(qa($siteUrl, 'qa', $qaPw)['code'], [404, 403], true));
$a->req('POST', "templates/$tid2/delete");
$a->req('POST', "clients/$clientId/delete");
foreach ($a->req('GET', 'users/list', ['q' => $vEmail])['json']['rows'] as $u) {
    $a->req('POST', "users/{$u['id']}/delete");
}
echo "\nResult: $pass passed, $fail failed\n";
exit($fail ? 1 : 0);
