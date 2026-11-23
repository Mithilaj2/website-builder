<?php
$menu = $view->menu('header');
$phone = (string) $view->site('phone');
$cta = (array) $view->setting('site', 'header_button', []);
$logo = $view->logo();
$lcfg = $view->listingConfig();
$slug = (string) ($view->page['slug'] ?? '');
$onRooms = $lcfg && (int) ($view->page['id'] ?? 0) === 0 && ($slug === $lcfg['path'] || str_starts_with($slug, $lcfg['path'] . '/'));
$inline = ($view->theme['header_style'] ?? '') === 'inline';
$items = [];
foreach ($menu as $i => $item) {
    $items[] = $item;
    if ($i === 0 && $lcfg) {
        $items[] = ['title' => $lcfg['plural'], 'url' => $view->listingsUrl(), 'active' => $onRooms, 'external' => false];
    }
}
?>
<header class="lvh-header<?= $inline ? ' is-inline' : '' ?>" data-lvh-header>
  <?php if (!$inline): ?>
  <div class="lvh-header-top d-none d-lg-block">
    <div class="container">
      <div class="lvh-header-grid">
        <div class="lvh-header-side"><?php if ($phone !== ''): ?><a href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><i class="bi bi-telephone"></i><?= $view->e($phone) ?></a><?php endif; ?></div>
        <?= $view->partial('brand') ?>
        <div class="lvh-header-side text-end"><?php if ($view->hasLink($cta)): ?><a class="btn lvh-btn lvh-btn-primary" href="<?= $view->href($cta) ?>"<?= $view->linkAttrs($cta) ?>><?= $view->e($cta['text']) ?></a><?php endif; ?></div>
      </div>
    </div>
  </div>
  <?php endif; ?>
  <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
    <div class="container">
      <div class="<?= $inline ? '' : 'd-lg-none' ?>"><?= $view->partial('brand') ?></div>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#lvhNav" aria-controls="lvhNav" aria-expanded="false" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
      <div class="collapse navbar-collapse" id="lvhNav">
        <ul class="navbar-nav <?= $inline ? 'ms-auto' : 'mx-auto' ?> align-items-lg-center">
          <?php foreach ($items as $item): ?>
            <li class="nav-item"><a class="nav-link<?= $item['active'] ? ' active' : '' ?>" href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?><?= $item['active'] ? ' aria-current="page"' : '' ?>><?= $view->e($item['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($view->hasLink($cta)): ?><a class="btn lvh-btn lvh-btn-primary lvh-header-cta<?= $inline ? '' : ' d-lg-none' ?>" href="<?= $view->href($cta) ?>"<?= $view->linkAttrs($cta) ?>><?= $view->e($cta['text']) ?></a><?php endif; ?>
      </div>
    </div>
  </nav>
</header>
