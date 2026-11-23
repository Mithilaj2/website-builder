<?php
$menu = $view->menu('header');
$cta = (array) $view->setting('site', 'header_button', []);
$logo = $view->logo();
$lcfg = $view->listingConfig();
$slug = (string) ($view->page['slug'] ?? '');
$onWork = $lcfg && (int) ($view->page['id'] ?? 0) === 0 && ($slug === $lcfg['path'] || str_starts_with($slug, $lcfg['path'] . '/'));
$items = [];
foreach ($menu as $i => $item) {
    if ($i === 1 && $lcfg) {
        $items[] = ['title' => $lcfg['menu_label'], 'url' => $view->listingsUrl(), 'active' => $onWork, 'external' => false];
    }
    $items[] = $item;
}
if ($lcfg && count($menu) < 2) {
    $items[] = ['title' => $lcfg['menu_label'], 'url' => $view->listingsUrl(), 'active' => $onWork, 'external' => false];
}
?>
<header class="skl-header" data-skl-header>
  <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
    <div class="container">
      <a class="navbar-brand skl-brand" href="<?= $view->e($view->url('')) ?>">
        <?php if ($logo !== ''): ?><img src="<?= $logo ?>" alt="<?= $view->e($view->site('name')) ?>">
        <?php else: ?><span class="skl-brand-dot" aria-hidden="true"></span><span class="skl-brand-name"><?= $view->e($view->site('name', 'Studio')) ?></span><?php endif; ?>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sklNav" aria-controls="sklNav" aria-expanded="false" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
      <div class="collapse navbar-collapse" id="sklNav">
        <ul class="navbar-nav ms-auto align-items-lg-center">
          <?php foreach ($items as $item): ?>
            <li class="nav-item"><a class="nav-link<?= $item['active'] ? ' active' : '' ?>" href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?><?= $item['active'] ? ' aria-current="page"' : '' ?>><?= $view->e($item['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($view->hasLink($cta)): ?><a class="btn skl-btn skl-btn-lime skl-header-cta" href="<?= $view->href($cta) ?>"<?= $view->linkAttrs($cta) ?>><?= $view->e($cta['text']) ?><i class="bi bi-arrow-up-right"></i></a><?php endif; ?>
      </div>
    </div>
  </nav>
</header>
