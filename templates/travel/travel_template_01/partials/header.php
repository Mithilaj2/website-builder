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
<div class="hzt-topbar">
  <div class="container d-flex flex-wrap align-items-center gap-3">
    <?php if ($phone !== ''): ?><a href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><i class="bi bi-telephone"></i><?= $view->e($phone) ?></a><?php endif; ?>
    <?php if ($email !== ''): ?><a href="mailto:<?= $view->e($email) ?>" class="d-none d-sm-inline-flex"><i class="bi bi-envelope"></i><?= $view->e($email) ?></a><?php endif; ?>
    <?php if ($view->site('hours') !== ''): ?><span class="d-none d-lg-inline-flex"><i class="bi bi-clock"></i><?= $view->e($view->site('hours')) ?></span><?php endif; ?>
    <?php if ($social): ?>
      <span class="ms-auto hzt-topbar-social"><?php foreach ($social as $s): ?><a href="<?= $view->e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= $view->e(ucfirst($s['network'])) ?>"><?= $view->icon($s['icon']) ?></a><?php endforeach; ?></span>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<header class="hzt-header" data-hzt-header>
  <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
    <div class="container">
      <a class="navbar-brand hzt-brand" href="<?= $view->e($view->url('')) ?>">
        <?php if ($logo !== ''): ?><img src="<?= $logo ?>" alt="<?= $view->e($view->site('name')) ?>">
        <?php else: ?><span class="hzt-mark" aria-hidden="true"><svg viewBox="0 0 40 40"><circle cx="20" cy="22" r="8" fill="var(--wb-secondary)"/><path d="M2 30h36" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/><path d="M8 36h24" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/><path d="M4 26l8-10 6 6 6-9 12 13" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linejoin="round"/></svg></span><span class="hzt-brand-name"><?= $view->e($view->site('name', 'Travels')) ?></span><?php endif; ?>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#hztNav" aria-controls="hztNav" aria-expanded="false" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
      <div class="collapse navbar-collapse" id="hztNav">
        <ul class="navbar-nav ms-auto align-items-lg-center">
          <?php foreach ($menu as $i => $item): ?>
            <li class="nav-item"><a class="nav-link<?= $item['active'] ? ' active' : '' ?>" href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?><?= $item['active'] ? ' aria-current="page"' : '' ?>><?= $view->e($item['title']) ?></a></li>
            <?php if ($i === 0 && $lcfg): ?>
              <li class="nav-item hzt-nav-drop">
                <a class="nav-link<?= $onListings ? ' active' : '' ?>" href="<?= $view->e($view->listingsUrl()) ?>"<?= $onListings ? ' aria-current="page"' : '' ?>><?= $view->e($lcfg['plural']) ?></a>
                <?php if ($types): ?>
                  <button class="hzt-drop-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= $view->e($lcfg['category_label']) ?>"><i class="bi bi-chevron-down"></i></button>
                  <ul class="dropdown-menu hzt-dropdown">
                    <?php foreach ($types as $t): ?><li><a class="dropdown-item" href="<?= $view->e($view->listingsUrl(['c' => $t['slug']])) ?>"><?= $view->e($t['name']) ?></a></li><?php endforeach; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= $view->e($view->listingsUrl()) ?>">All <?= $view->e(strtolower($lcfg['plural'])) ?></a></li>
                  </ul>
                <?php endif; ?>
              </li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
        <?php if ($view->hasLink($cta)): ?><a class="btn hzt-btn hzt-btn-primary hzt-header-cta" href="<?= $view->href($cta) ?>"<?= $view->linkAttrs($cta) ?>><?= $view->e($cta['text']) ?></a><?php endif; ?>
      </div>
    </div>
  </nav>
</header>
