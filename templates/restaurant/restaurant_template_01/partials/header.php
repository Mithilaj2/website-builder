<?php
$menu = $view->menu('header');
$phone = (string) $view->site('phone');
$cta = (array) $view->setting('site', 'header_button', []);
$logo = $view->logo();
?>
<header class="sfr-header" data-sfr-header>
  <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
    <div class="container">
      <a class="navbar-brand sfr-brand" href="<?= $view->e($view->url('')) ?>">
        <?php if ($logo !== ''): ?><img src="<?= $logo ?>" alt="<?= $view->e($view->site('name')) ?>">
        <?php else: ?><span class="sfr-mark" aria-hidden="true"><svg viewBox="0 0 40 40"><circle cx="20" cy="20" r="17" fill="none" stroke="currentColor" stroke-width="2"/><path d="M20 8c4 5 4 10 0 14-4-4-4-9 0-14Z" fill="var(--wb-secondary)"/><path d="M11 26h18M14 31h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><span class="sfr-brand-name"><?= $view->e($view->site('name', 'Restaurant')) ?></span><?php endif; ?>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sfrNav" aria-controls="sfrNav" aria-expanded="false" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
      <div class="collapse navbar-collapse" id="sfrNav">
        <ul class="navbar-nav mx-auto align-items-lg-center">
          <?php foreach ($menu as $item): ?>
            <li class="nav-item"><a class="nav-link<?= $item['active'] ? ' active' : '' ?>" href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?><?= $item['active'] ? ' aria-current="page"' : '' ?>><?= $view->e($item['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <div class="sfr-header-end">
          <?php if ($phone !== ''): ?><a class="sfr-header-phone" href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><i class="bi bi-telephone"></i><span><?= $view->e($phone) ?></span></a><?php endif; ?>
          <?php if ($view->hasLink($cta)): ?><a class="btn sfr-btn sfr-btn-primary sfr-header-cta" href="<?= $view->href($cta) ?>"<?= $view->linkAttrs($cta) ?>><?= $view->e($cta['text']) ?></a><?php endif; ?>
        </div>
      </div>
    </div>
  </nav>
</header>
