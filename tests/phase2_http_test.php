<?php
declare(strict_types=1);

/**
 * Phase 2 end-to-end HTTP test: template engine, website workspace, media, theme, previews, isolation.
 *
 *   php tests/phase2_http_test.php http://127.0.0.1:8080 info@channelsoftech.com 'Password123'
 *
 * Needs business_template_01 present in templates/business/. Registers and activates it if necessary.
 * Creates two test websites and deletes them (and its test client/user) at the end.
 */

require __DIR__ . '/support.php';

[$_, $base, $email, $password] = $argv + [null, 'http://127.0.0.1:8080', 'info@channelsoftech.com', ''];
$base = rtrim((string) $base, '/');
$sfx = substr(bin2hex(random_bytes(3)), 0, 6);
$pass = $fail = 0;
$tmp = sys_get_temp_dir();

function img(string $path, int $w = 900, int $h = 600, array $rgb = [200, 80, 40]): string
{
    $im = imagecreatetruecolor($w, $h);
    imagefill($im, 0, 0, imagecolorallocate($im, ...$rgb));
    str_ends_with($path, '.png') ? imagepng($im, $path) : imagejpeg($im, $path, 85);
    return $path;
}

$a = new Client($base);
check('Login', ($a->login($email, $password)['json']['ok'] ?? false) === true);

echo "\n== Template engine ==\n";
$tpl = null;
foreach ($a->req('GET', 'templates/list', ['q' => 'business_template_01'])['json']['rows'] ?? [] as $row) {
    if ($row['code'] === 'business_template_01') {
        $tpl = $row;
    }
}
if (!$tpl) {
    $r = $a->req('POST', 'templates/register', ['category' => 'business', 'folder' => 'business_template_01']);
    check('Register bundled Business Template 01', ($r['json']['ok'] ?? false) === true, $r['body']);
    preg_match('#templates/(\d+)#', (string) ($r['json']['redirect'] ?? ''), $m);
    $tid = (int) ($m[1] ?? 0);
} else {
    $tid = (int) $tpl['id'];
    check('Business Template 01 already registered', $tid > 0);
}
$r = $a->req('POST', "templates/$tid/validate");
check('Business Template 01 passes validation + security scan', ($r['json']['report']['ok'] ?? false) === true, $r['body']);
if (($a->req('GET', "templates/$tid/json")['json']['data']['status'] ?? '') !== 'active') {
    check('Activate template', ($a->req('POST', "templates/$tid/status")['json']['ok'] ?? false) === true);
}
foreach (['' => 'home', 'about' => 'About', 'services' => 'Services', 'gallery' => 'Gallery', 'contact' => 'Contact', 'privacy-policy' => 'Privacy'] as $slug => $label) {
    $r = $a->req('GET', "templates/$tid/preview", $slug !== '' ? ['page' => $slug] : [], false);
    check("Template live preview renders: $label", $r['code'] === 200 && str_contains($r['body'], 'mrd-header') && str_contains($r['body'], '</html>'));
}
$r = $a->req('GET', "templates/$tid/preview", ['page' => 'does-not-exist'], false);
check('Unknown page renders the template 404 (status 404)', $r['code'] === 404 && str_contains($r['body'], 'mrd-404'));
$home = $a->req('GET', "templates/$tid/preview", [], false)['body'];
preg_match('#href="([^"]+/preview-assets/template/' . $tid . '/css/style\.css[^"]*)"#', $home, $m);
check('Template CSS served through preview assets', isset($m[1]) && $a->req('GET', html_entity_decode($m[1]), [], false)['code'] === 200);
preg_match('#href="([^"]+/preview-assets/runtime/lib/bootstrap/css/bootstrap\.min\.css)"#', $home, $m);
check('Runtime library (Bootstrap) served', isset($m[1]) && $a->req('GET', $m[1], [], false)['code'] === 200);
preg_match("#url\\('([^']+/preview-assets/runtime/fonts/manrope/700\\.woff2)'\\)#", $home, $m);
check('Bundled web font served (no external CDN)', isset($m[1]) && $a->req('GET', $m[1], [], false)['code'] === 200);
check('Preview HTML loads nothing from external hosts', !preg_match('#(src|href)="https?://(?!127\.0\.0\.1|localhost)[^"]*\.(css|js|woff2?)#', $home));
check('Path traversal in asset URL blocked', $a->req('GET', "preview-assets/template/$tid/../manifest.json", [], false)['code'] !== 200
    && $a->req('GET', "preview-assets/template/$tid/..%2F..%2F..%2Fconfig%2Fconfig.php", [], false)['code'] !== 200);
check('Template PHP files are not served as assets', $a->req('GET', "preview-assets/template/$tid/../sections/hero.php", [], false)['code'] === 404);

echo "\n== Create website ==\n";
$client = $a->req('POST', 'clients', ['business_name' => "P2 Client $sfx", 'email' => "p2$sfx@example.com", 'phone' => '+91 98470 12345', 'address' => "12 Test Road\nKochi", 'status' => 'active'])['json']['id'] ?? 0;
$cat = 0;
foreach ($a->req('GET', 'categories/list', ['per_page' => 50])['json']['rows'] as $c) {
    if ($c['slug'] === 'business') {
        $cat = (int) $c['id'];
    }
}
$r = $a->req('GET', 'websites/templates', ['category' => $cat]);
check('Templates offered for the Business category', in_array($tid, array_map('intval', array_column($r['json']['templates'] ?? [], 'id')), true));
$r = $a->req('POST', 'websites', ['client_id' => $client, 'name' => '', 'category_id' => $cat, 'template_id' => 999999]);
check('Validation: name and template required', isset($r['json']['errors']['name'], $r['json']['errors']['template_id']));
$r = $a->req('POST', 'websites', ['client_id' => $client, 'name' => "Alpha Industries $sfx", 'category_id' => $cat, 'template_id' => $tid, 'domain' => 'alpha.example', 'description' => 'Precision engineering']);
check('Create website A', ($r['json']['ok'] ?? false) === true, $r['body']);
preg_match('#websites/(\d+)#', (string) ($r['json']['redirect'] ?? ''), $m);
$wa = (int) ($m[1] ?? 0);
$r = $a->req('POST', 'websites', ['client_id' => $client, 'name' => "Beta Traders $sfx", 'category_id' => $cat, 'template_id' => $tid]);
check('Create website B', ($r['json']['ok'] ?? false) === true, $r['body']);
preg_match('#websites/(\d+)#', (string) ($r['json']['redirect'] ?? ''), $m);
$wb = (int) ($m[1] ?? 0);
check('Workspace page renders', $a->req('GET', "websites/$wa", [], false)['code'] === 200);
$pages = $a->req('GET', "websites/$wa/pages")['json']['pages'] ?? [];
check('Website seeded with the template\'s 7 default pages', count($pages) === 7);
$homeA = $a->req('GET', "websites/$wa/preview", [], false);
check('Website preview renders', $homeA['code'] === 200 && str_contains($homeA['body'], "Alpha Industries $sfx"));
check('Client contact details used on the site', str_contains($homeA['body'], '+91 98470 12345') && str_contains($homeA['body'], "p2$sfx@example.com"));
$list = $a->req('GET', 'websites/list', ['q' => $sfx])['json'];
check('Websites list shows both, each with its own code', ($list['total'] ?? 0) === 2 && count(array_unique(array_column($list['rows'], 'site_code'))) === 2);

echo "\n== Pages ==\n";
$r = $a->req('POST', "websites/$wa/pages", ['title' => 'Admin', 'slug' => 'admin', 'page_type' => 'generic']);
check('Reserved URL "admin" rejected', isset($r['json']['errors']['slug']));
$r = $a->req('POST', "websites/$wa/pages", ['title' => 'Dup', 'slug' => 'about', 'page_type' => 'generic']);
check('Duplicate URL rejected', isset($r['json']['errors']['slug']));
$r = $a->req('POST', "websites/$wa/pages", ['title' => 'Bad', 'slug' => 'Bad Slug!', 'page_type' => 'generic']);
check('Invalid URL rejected', isset($r['json']['errors']['slug']));
$r = $a->req('POST', "websites/$wa/pages", ['title' => 'Our Projects', 'slug' => '', 'page_type' => 'generic', 'show_in_menu' => '1', 'seo_title' => 'Projects | Alpha', 'seo_description' => 'Recent engineering projects.']);
check('Add page (slug from title)', ($r['json']['ok'] ?? false) === true, $r['body']);
$newPage = (int) ($r['json']['id'] ?? 0);
$secs = $a->req('GET', "websites/$wa/pages/$newPage/sections")['json']['sections'] ?? [];
check('New page starts with its layout\'s default sections', count($secs) === 3 && $secs[0]['section_type'] === 'page_banner');
$r = $a->req('GET', "websites/$wa/preview", ['page' => 'our-projects'], false);
check('New page renders at /our-projects with SEO title', $r['code'] === 200 && str_contains($r['body'], '<title>Projects | Alpha</title>') && str_contains($r['body'], 'Recent engineering projects.'));
check('New page appears in the menu', str_contains($homeA = $a->req('GET', "websites/$wa/preview", [], false)['body'], '?page=our-projects'));
$r = $a->req('POST', "websites/$wa/pages/$newPage", ['title' => 'Projects', 'slug' => 'projects', 'page_type' => 'generic', 'show_in_menu' => '0']);
check('Rename page, change URL, hide from menu', ($r['json']['ok'] ?? false) === true, $r['body']);
check('Hidden page removed from menu but still reachable', !str_contains($a->req('GET', "websites/$wa/preview", [], false)['body'], '?page=projects') && $a->req('GET', "websites/$wa/preview", ['page' => 'projects'], false)['code'] === 200);
$homeId = 0;
foreach ($pages as $p) {
    if ((int) $p['is_home'] === 1) {
        $homeId = (int) $p['id'];
    }
}
check('Homepage cannot be deleted', ($a->req('POST', "websites/$wa/pages/$homeId/delete")['json']['ok'] ?? true) === false);
check('Homepage cannot be deactivated', ($a->req('POST', "websites/$wa/pages/$homeId/toggle")['json']['ok'] ?? true) === false);
check('Set another page as homepage', ($a->req('POST', "websites/$wa/pages/$newPage/home")['json']['ok'] ?? false) === true);
check('New homepage renders at the site root', str_contains($a->req('GET', "websites/$wa/preview", [], false)['body'], 'mrd-banner'));
$a->req('POST', "websites/$wa/pages/$homeId/home");
check('Deactivate a page → 404', ($a->req('POST', "websites/$wa/pages/$newPage/toggle")['json']['ok'] ?? false) === true && $a->req('GET', "websites/$wa/preview", ['page' => 'projects'], false)['code'] === 404);
$ids = array_column($a->req('GET', "websites/$wa/pages")['json']['pages'], 'id');
check('Reorder pages', ($a->req('POST', "websites/$wa/pages/reorder", ['ids' => array_reverse($ids)])['json']['ok'] ?? false) === true
    && (int) $a->req('GET', "websites/$wa/pages")['json']['pages'][0]['id'] === (int) end($ids));
check('Delete page (sections removed with it)', ($a->req('POST', "websites/$wa/pages/$newPage/delete")['json']['ok'] ?? false) === true);

echo "\n== Sections ==\n";
$secs = $a->req('GET', "websites/$wa/pages/$homeId/sections")['json']['sections'];
$hero = $secs[0];
check('Homepage sections in template order (hero first)', $hero['section_type'] === 'hero' && count($secs) === 9);
$r = $a->req('GET', "websites/$wa/sections/{$hero['id']}");
check('Section editor receives data + schema with typed fields', ($r['json']['schema']['type'] ?? '') === 'hero' && count($r['json']['schema']['fields']) >= 6);
$data = $r['json']['section']['data'];
$data['heading'] = "New headline $sfx <script>alert(1)</script>";
$data['primary_button'] = ['text' => 'Talk to us', 'url' => 'javascript:alert(1)'];
$r = $a->req('POST', "websites/$wa/sections/{$hero['id']}", ['data' => json_encode($data), 'style' => '{}']);
check('Unsafe link (javascript:) rejected', isset($r['json']['errors']['primary_button']), $r['body']);
$data['primary_button'] = ['text' => 'Talk to us', 'url' => 'page:contact'];
$data['image'] = 'uploads/pages/not-mine.jpg';
$r = $a->req('POST', "websites/$wa/sections/{$hero['id']}", ['data' => json_encode($data), 'style' => '{}']);
check('Image not in this site\'s media library rejected', isset($r['json']['errors']['image']), $r['body']);
$data['image'] = 'theme:../../../config/config.php';
$r = $a->req('POST', "websites/$wa/sections/{$hero['id']}", ['data' => json_encode($data), 'style' => '{}']);
check('Theme image path traversal rejected', isset($r['json']['errors']['image']));
$data['image'] = 'theme:images/hero.svg';
$data['highlights'] = array_fill(0, 6, ['icon' => 'bi-check', 'text' => 'x']);
$r = $a->req('POST', "websites/$wa/sections/{$hero['id']}", ['data' => json_encode($data), 'style' => '{}']);
check('Repeater max items enforced', isset($r['json']['errors']['highlights']));
$data['highlights'] = [['icon' => 'bi-star', 'text' => "Highlight $sfx"]];
$r = $a->req('POST', "websites/$wa/sections/{$hero['id']}", ['data' => json_encode($data), 'style' => json_encode(['bg_color' => '#112233', 'padding' => 'lg', 'anchor' => 'top-hero', 'overlay' => 40])]);
check('Save hero section', ($r['json']['ok'] ?? false) === true, $r['body']);
$page = $a->req('GET', "websites/$wa/preview", [], false)['body'];
check('Saved content rendered; HTML tags stripped from text', str_contains($page, "New headline $sfx alert(1)") && !str_contains($page, '<script>alert(1)'));
check('Repeater item rendered', str_contains($page, "Highlight $sfx"));
check('Style options rendered (anchor, colour, spacing)', str_contains($page, 'id="top-hero"') && str_contains($page, 'background-color:#112233') && str_contains($page, 'wb-pad-lg'));

$about = $secs[2];
$ad = $a->req('GET', "websites/$wa/sections/{$about['id']}")['json']['section']['data'];
$ad['description'] = '<p>Rich <strong>text</strong> <a href="https://example.com" onclick="evil()">link</a><img src=x onerror=alert(1)><script>bad()</script></p>';
$a->req('POST', "websites/$wa/sections/{$about['id']}", ['data' => json_encode($ad), 'style' => '{}']);
$saved = $a->req('GET', "websites/$wa/sections/{$about['id']}")['json']['section']['data']['description'] ?? '';
check('Rich text sanitised (keeps formatting, drops script/onerror/onclick)', str_contains($saved, '<strong>text</strong>') && !str_contains($saved, 'script') && !str_contains($saved, 'onerror') && !str_contains($saved, 'onclick'), $saved);

$r = $a->req('POST', "websites/$wa/pages/$homeId/sections", ['section_type' => 'not_a_type']);
check('Unsupported section type rejected', ($r['json']['ok'] ?? true) === false);
$r = $a->req('POST', "websites/$wa/pages/$homeId/sections", ['section_type' => 'faq', 'after' => $hero['id']]);
check('Add FAQ section after the hero', ($r['json']['ok'] ?? false) === true);
$faqId = (int) ($r['json']['id'] ?? 0);
$secs = $a->req('GET', "websites/$wa/pages/$homeId/sections")['json']['sections'];
check('Inserted at the right position', (int) $secs[1]['id'] === $faqId);
check('Duplicate section', ($a->req('POST', "websites/$wa/sections/$faqId/duplicate")['json']['ok'] ?? false) === true);
check('Disable section', ($a->req('POST', "websites/$wa/sections/{$hero['id']}/toggle")['json']['ok'] ?? false) === true);
check('Disabled section not rendered', !str_contains($a->req('GET', "websites/$wa/preview", [], false)['body'], "New headline $sfx"));
$a->req('POST', "websites/$wa/sections/{$hero['id']}/toggle");
$secs = $a->req('GET', "websites/$wa/pages/$homeId/sections")['json']['sections'];
$order = array_column($secs, 'id');
$moved = $order;
[$moved[0], $moved[1]] = [$moved[1], $moved[0]];
$a->req('POST', "websites/$wa/pages/$homeId/sections/reorder", ['ids' => $moved]);
$page = $a->req('GET', "websites/$wa/preview", [], false)['body'];
check('Drag-and-drop order respected in output', strpos($page, 'mrd-faq') < strpos($page, 'mrd-hero'));
$a->req('POST', "websites/$wa/pages/$homeId/sections/reorder", ['ids' => $order]);
check('Delete section', ($a->req('POST', "websites/$wa/sections/$faqId/delete")['json']['ok'] ?? false) === true);

echo "\n== Media library ==\n";
$r = $a->req('POST', "websites/$wa/media", ['folder' => 'banners', 'files[0]' => new CURLFile(img("$tmp/b1.jpg"), 'image/jpeg', 'factory-floor.jpg'), 'files[1]' => new CURLFile(img("$tmp/b2.png", 400, 400, [20, 120, 60]), 'image/png', 'team.png')]);
check('Upload 2 images', count($r['json']['items'] ?? []) === 2, $r['body']);
$up = $r['json']['items'][0] ?? ['path' => '', 'url' => '', 'id' => 0];
check('Stored in website A\'s own folder', str_starts_with($up['path'], 'uploads/banners/') && $a->req('GET', $up['url'], [], false)['code'] === 200);
file_put_contents("$tmp/evil.svg", '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(2)</script><a href="javascript:alert(3)"><rect width="10" height="10" fill="red"/></a><image href="https://evil.example/x.png"/><rect width="5" height="5" style="fill:url(https://evil.example/a)"/></svg>');
$r = $a->req('POST', "websites/$wa/media", ['folder' => 'general', 'files[0]' => new CURLFile("$tmp/evil.svg", 'image/svg+xml', 'logo.svg')]);
$svgItem = $r['json']['items'][0] ?? null;
$svg = $svgItem ? $a->req('GET', $svgItem['url'], [], false)['body'] : '';
check('SVG uploaded and sanitised (no script, handlers, javascript: or external refs)', $svgItem !== null && str_contains($svg, '<rect') && !preg_match('/script|onload|javascript|evil\.example/i', $svg), $svg);
file_put_contents("$tmp/shell.jpg", "\xFF\xD8\xFF<?php system(\$_GET['c']); ?>");
$r = $a->req('POST', "websites/$wa/media", ['files[0]' => new CURLFile("$tmp/shell.jpg", 'image/jpeg', 'photo.jpg')]);
check('Fake JPEG with PHP rejected', ($r['json']['ok'] ?? true) === false);
file_put_contents("$tmp/x.php", '<?php echo 1;');
$r = $a->req('POST', "websites/$wa/media", ['files[0]' => new CURLFile("$tmp/x.php", 'image/jpeg', 'x.php')]);
check('.php upload rejected', ($r['json']['ok'] ?? true) === false);
$ml = $a->req('GET', "websites/$wa/media", ['folder' => 'banners'])['json'];
check('Folder filter', ($ml['total'] ?? 0) === 2);
check('Alt text auto-filled from file name', in_array('factory floor', array_column($ml['rows'], 'alt_text'), true));
check('Edit alt text', ($a->req('POST', "websites/$wa/media/{$up['id']}", ['alt_text' => "Factory floor $sfx", 'folder' => 'banners'])['json']['ok'] ?? false) === true);
$data['image'] = $up['path'];
$a->req('POST', "websites/$wa/sections/{$hero['id']}", ['data' => json_encode($data), 'style' => '{}']);
$page = $a->req('GET', "websites/$wa/preview", [], false)['body'];
check('Uploaded image + its alt text used on the page', str_contains($page, substr($up['path'], 8)) && str_contains($page, "Factory floor $sfx"));
$r = $a->req('POST', "websites/$wa/media/{$up['id']}/replace", ['file' => new CURLFile(img("$tmp/b3.jpg", 1000, 700, [10, 10, 200]), 'image/jpeg', 'new.jpg')]);
check('Replace image keeps the same path', ($r['json']['ok'] ?? false) === true && str_contains($a->req('GET', "websites/$wa/preview", [], false)['body'], substr($up['path'], 8)));
$r = $a->req('POST', "websites/$wa/media/{$up['id']}/delete");
check('Deleting an image in use asks for confirmation (lists where)', ($r['json']['in_use'] ?? false) === true && str_contains((string) ($r['json']['message'] ?? ''), 'Hero'), $r['body']);
check('Forced delete removes it and clears references', ($a->req('POST', "websites/$wa/media/{$up['id']}/delete", ['force' => '1'])['json']['ok'] ?? false) === true
    && !str_contains($a->req('GET', "websites/$wa/preview", [], false)['body'], substr($up['path'], 8)));

echo "\n== Isolation between websites ==\n";
$bMedia = $a->req('POST', "websites/$wb/media", ['folder' => 'gallery', 'files[0]' => new CURLFile(img("$tmp/bb.jpg"), 'image/jpeg', 'beta.jpg')])['json']['items'][0] ?? ['path' => '', 'url' => ''];
check('Website A cannot serve website B\'s file', $a->req('GET', "websites/$wa/media/" . substr($bMedia['path'], 8), [], false)['code'] === 404);
$data['image'] = $bMedia['path'];
$r = $a->req('POST', "websites/$wa/sections/{$hero['id']}", ['data' => json_encode($data), 'style' => '{}']);
check('Website A cannot use website B\'s image', isset($r['json']['errors']['image']));
check('Website B\'s media library does not list A\'s images', ($a->req('GET', "websites/$wb/media")['json']['total'] ?? 0) === 1);
$bHome = $a->req('GET', "websites/$wb/preview", [], false)['body'];
check('Website B content unaffected by A\'s edits', !str_contains($bHome, "New headline $sfx") && str_contains($bHome, "Beta Traders $sfx"));
$bPages = $a->req('GET', "websites/$wb/pages")['json']['pages'];
check('Website A cannot edit B\'s page through its own URL', $a->req('POST', "websites/$wa/pages/" . $bPages[0]['id'], ['title' => 'Hijack', 'page_type' => 'generic'])['code'] !== 200
    || ($a->req('GET', "websites/$wb/pages")['json']['pages'][0]['title'] ?? '') !== 'Hijack');

echo "\n== Theme & site details ==\n";
$r = $a->req('POST', "websites/$wa/settings/theme", ['primary' => 'red', 'secondary' => '#E8A33D', 'bg' => '#FFFFFF', 'text' => '#111111', 'heading_font' => 'comic', 'body_font' => 'inter', 'button_style' => 'solid', 'radius' => '8px', 'header_style' => 'sticky-light', 'footer_style' => 'dark']);
check('Invalid colour and font rejected', isset($r['json']['errors']['primary'], $r['json']['errors']['heading_font']));
$r = $a->req('POST', "websites/$wa/settings/theme", ['primary' => '#7A1F3D', 'secondary' => '#2BB673', 'bg' => '#FFFFFF', 'text' => '#111111', 'heading_font' => 'playfair-display', 'body_font' => 'lora', 'button_style' => 'pill', 'radius' => '14px', 'header_style' => 'minimal', 'footer_style' => 'light', 'logo' => $svgItem['path'] ?? '', 'favicon' => '']);
check('Save theme', ($r['json']['ok'] ?? false) === true, $r['body']);
$page = $a->req('GET', "websites/$wa/preview", [], false)['body'];
check('Theme colours applied as CSS variables', str_contains($page, '--wb-primary:#7A1F3D') && str_contains($page, '--wb-secondary:#2BB673'));
check('Selected fonts loaded from bundled files', str_contains($page, "font-family:'Playfair Display'") && str_contains($page, 'lora/400.woff2'));
check('Button/header/footer style classes applied', str_contains($page, 'wb-btn-pill') && str_contains($page, 'wb-header-minimal') && str_contains($page, 'wb-footer-light'));
check('Logo from media library shown', str_contains($page, substr((string) ($svgItem['path'] ?? 'x'), 8)));
$r = $a->req('POST', "websites/$wa/settings/details", ['name' => "Alpha Engineering $sfx", 'tagline' => 'Precision parts', 'email' => 'bad-email', 'phone' => '+91 484 222 3333']);
check('Site details validation', isset($r['json']['errors']['email']));
$r = $a->req('POST', "websites/$wa/settings/details", ['name' => "Alpha Engineering $sfx", 'tagline' => 'Precision parts', 'email' => 'Sales@Alpha.example', 'phone' => '+91 484 222 3333', 'address' => 'Plot 7, KINFRA Park', 'hours' => 'Mon–Fri 9–6', 'header_button_text' => 'Get a quote', 'header_button_url' => 'page:contact', 'social_linkedin' => 'https://linkedin.com/company/alpha', 'social_facebook' => 'http://insecure.example', 'seo_description' => 'Alpha makes precision parts.']);
check('Insecure (http) social link rejected', isset($r['json']['errors']['social_facebook']));
$r = $a->req('POST', "websites/$wa/settings/details", ['name' => "Alpha Engineering $sfx", 'tagline' => 'Precision parts', 'email' => 'Sales@Alpha.example', 'phone' => '+91 484 222 3333', 'address' => 'Plot 7, KINFRA Park', 'hours' => 'Mon–Fri 9–6', 'header_button_text' => 'Get a quote', 'header_button_url' => 'page:contact', 'social_linkedin' => 'https://linkedin.com/company/alpha', 'seo_description' => 'Alpha makes precision parts.', 'whatsapp' => '+91 98470 00000']);
check('Save site details', ($r['json']['ok'] ?? false) === true, $r['body']);
$page = $a->req('GET', "websites/$wa/preview", [], false)['body'];
check('Details shown in header/footer (name, email lowercased, header button, social, WhatsApp)', str_contains($page, "Alpha Engineering $sfx") && str_contains($page, 'sales@alpha.example') && str_contains($page, 'Get a quote') && str_contains($page, 'linkedin.com/company/alpha') && str_contains($page, 'wa.me/919847000000'));
check('Default meta description used', str_contains($page, 'content="Alpha makes precision parts."'));

echo "\n== Permissions ==\n";
$vEmail = "p2viewer$sfx@example.com";
$a->req('POST', 'users', ['name' => 'P2 Viewer', 'email' => $vEmail, 'role' => 'viewer', 'status' => 'active', 'password' => 'ViewerPass123', 'password_confirmation' => 'ViewerPass123']);
$v = new Client($base);
$v->login($vEmail, 'ViewerPass123');
check('Viewer can open the workspace and preview', $v->req('GET', "websites/$wa", [], false)['code'] === 200 && $v->req('GET', "websites/$wa/preview", [], false)['code'] === 200);
check('Viewer cannot edit sections (403)', $v->req('POST', "websites/$wa/sections/{$hero['id']}", ['data' => '{}', 'style' => '{}'])['code'] === 403);
check('Viewer cannot upload media (403)', $v->req('POST', "websites/$wa/media", ['files[0]' => new CURLFile("$tmp/b1.jpg", 'image/jpeg', 'a.jpg')])['code'] === 403);
check('Viewer cannot create websites (403)', $v->req('POST', 'websites', ['name' => 'x'])['code'] === 403);
$guest = new Client($base);
check('Guests cannot see previews or media', $guest->req('GET', "websites/$wa/preview", [], false)['code'] === 302 && $guest->req('GET', $svgItem['url'] ?? "websites/$wa/media/x.jpg", [], false)['code'] === 302);

echo "\n== Delete websites ==\n";
check('Delete requires typing the website code', ($a->req('POST', "websites/$wa/delete", ['confirm' => 'nope'])['json']['ok'] ?? true) === false);
foreach ([$wa, $wb] as $wid) {
    $code = str_pad((string) $wid, 6, '0', STR_PAD_LEFT);
    $r = $a->req('POST', "websites/$wid/delete", ['confirm' => $code]);
    check("Delete website #$code (backup taken)", ($r['json']['ok'] ?? false) === true, $r['body']);
    check("  · its preview is gone", $a->req('GET', "websites/$wid/preview", [], false)['code'] === 404);
}
foreach ($a->req('GET', 'users/list', ['q' => $vEmail])['json']['rows'] as $u) {
    $a->req('POST', "users/{$u['id']}/delete");
}
check('Delete test client', ($a->req('POST', "clients/$client/delete")['json']['ok'] ?? false) === true);

echo "\nResult: $pass passed, $fail failed\n";
exit($fail ? 1 : 0);
