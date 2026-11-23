<?php
$menu = $view->menu('header');
$phone = (string) $view->site('phone');
$email = (string) $view->site('email');
$social = $view->social();
$cta = (array) $view->setting('site', 'header_button', []);
$logo = $view->logo();
$lcfg = $view->listingConfig();
$types = $lcfg ? $view->listingCategories() : [];
$slug = (string) ($view->page['slug'] ?? '');
$onListings = $lcfg && (int) ($view->page['id'] ?? 0) === 0 && ($slug === $lcfg['path'] || str_starts_with($slug, $lcfg['path'] . '/'));
?>
<?php if (($view->theme['header_style'] ?? '') !== 'minimal' && ($phone !== '' || $email !== '' || $social)): ?>
<div class="ldg-topbar">
  <div class="container d-flex flex-wrap align-items-center gap-3">
    <?php if ($phone !== ''): ?><a href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><i class="bi bi-telephone"></i><?= $view->e($phone) ?></a><?php endif; ?>
    <?php if ($email !== ''): ?><a href="mailto:<?= $view->e($email) ?>" class="d-none d-sm-inline-flex"><i class="bi bi-envelope"></i><?= $view->e($email) ?></a><?php endif; ?>
    <?php if ($view->site('hours') !== ''): ?><span class="d-none d-lg-inline-flex"><i class="bi bi-clock"></i><?= $view->e($view->site('hours')) ?></span><?php endif; ?>
    <?php if ($view->site('tagline') !== ''): ?><span class="ldg-topbar-note d-none d-xl-inline-flex ms-auto"><i class="bi bi-patch-check-fill"></i><?= $view->e($view->site('tagline')) ?></span><?php endif; ?>
    <?php if ($social): ?>
      <span class="ldg-topbar-social"><?php foreach ($social as $s): ?><a href="<?= $view->e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= $view->e(ucfirst($s['network'])) ?>"><?= $view->icon($s['icon']) ?></a><?php endforeach; ?></span>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<header class="ldg-header" data-ldg-header>
  <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
    <div class="container">
      <a class="navbar-brand ldg-brand" href="<?= $view->e($view->url('')) ?>">
        <?php if ($logo !== ''): ?><img src="<?= $logo ?>" alt="<?= $view->e($view->site('name')) ?>">
        <?php else: ?><span class="ldg-mark" aria-hidden="true"><svg viewBox="0 0 40 40"><rect x="2" y="2" width="36" height="36" rx="10" fill="currentColor"/><path d="M11 27V13M11 27h8M22 27l4-8 3 4 4-9" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><circle cx="33" cy="14" r="3" fill="var(--wb-secondary)"/></svg></span><span class="ldg-brand-name"><?= $view->e($view->site('name', 'Advisors')) ?></span><?php endif; ?>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#ldgNav" aria-controls="ldgNav" aria-expanded="false" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
      <div class="collapse navbar-collapse" id="ldgNav">
        <ul class="navbar-nav ms-auto align-items-lg-center">
          <?php foreach ($menu as $i => $item): ?>
            <li class="nav-item"><a class="nav-link<?= $item['active'] ? ' active' : '' ?>" href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?><?= $item['active'] ? ' aria-current="page"' : '' ?>><?= $view->e($item['title']) ?></a></li>
            <?php if ($i === 0 && $lcfg): ?>
              <li class="nav-item ldg-nav-drop">
                <a class="nav-link<?= $onListings ? ' active' : '' ?>" href="<?= $view->e($view->listingsUrl()) ?>"<?= $onListings ? ' aria-current="page"' : '' ?>><?= $view->e($lcfg['plural']) ?></a>
                <?php if ($types): ?>
                  <button class="ldg-drop-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= $view->e($lcfg['category_label']) ?>"><i class="bi bi-chevron-down"></i></button>
                  <ul class="dropdown-menu ldg-dropdown">
                    <?php foreach ($types as $t): ?><li><a class="dropdown-item" href="<?= $view->e($view->listingsUrl(['c' => $t['slug']])) ?>"><?= $view->e($t['name']) ?></a></li><?php endforeach; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= $view->e($view->listingsUrl()) ?>">All <?= $view->e(strtolower($lcfg['plural'])) ?></a></li>
                  </ul>
                <?php endif; ?>
              </li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
        <?php if ($view->hasLink($cta)): ?><a class="btn ldg-btn ldg-btn-primary ldg-header-cta" href="<?= $view->href($cta) ?>"<?= $view->linkAttrs($cta) ?>><?= $view->e($cta['text']) ?></a><?php endif; ?>
      </div>
    </div>
  </nav>
</header>
