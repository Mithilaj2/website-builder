<?php
$menu = $view->menu('header');
$phone = (string) $view->site('phone');
$email = (string) $view->site('email');
$social = $view->social();
$cta = (array) $view->setting('site', 'header_button', []);
$logo = $view->logo();
$tagline = (string) $view->site('tagline');
?>
<?php if (($view->theme['header_style'] ?? '') !== 'minimal' && ($phone !== '' || $email !== '' || $social)): ?>
<div class="lyc-topbar">
  <div class="container d-flex flex-wrap align-items-center gap-3">
    <?php if ($phone !== ''): ?><a href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><i class="bi bi-telephone"></i><?= $view->e($phone) ?></a><?php endif; ?>
    <?php if ($email !== ''): ?><a href="mailto:<?= $view->e($email) ?>" class="d-none d-sm-inline-flex"><i class="bi bi-envelope"></i><?= $view->e($email) ?></a><?php endif; ?>
    <?php if ($view->site('hours') !== ''): ?><span class="d-none d-lg-inline-flex"><i class="bi bi-clock"></i><?= $view->e($view->site('hours')) ?></span><?php endif; ?>
    <?php if ($social): ?>
      <span class="ms-auto lyc-topbar-social">
        <?php foreach ($social as $s): ?><a href="<?= $view->e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= $view->e(ucfirst($s['network'])) ?>"><?= $view->icon($s['icon']) ?></a><?php endforeach; ?>
      </span>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<header class="lyc-header" data-lyc-header>
  <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
    <div class="container">
      <a class="navbar-brand lyc-brand" href="<?= $view->e($view->url('')) ?>">
        <?php if ($logo !== ''): ?>
          <img src="<?= $logo ?>" alt="<?= $view->e($view->site('name')) ?>">
        <?php else: ?>
          <?= $view->partial('crest') ?>
          <span class="lyc-brand-text"><span class="lyc-brand-name"><?= $view->e($view->site('name', 'Academy')) ?></span>
            <?php if ($tagline !== ''): ?><small><?= $view->e($tagline) ?></small><?php endif; ?></span>
        <?php endif; ?>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#lycNav" aria-controls="lycNav" aria-expanded="false" aria-label="Toggle navigation">
        <i class="bi bi-list"></i>
      </button>
      <div class="collapse navbar-collapse" id="lycNav">
        <ul class="navbar-nav ms-auto align-items-lg-center">
          <?php foreach ($menu as $item): ?>
            <li class="nav-item"><a class="nav-link<?= $item['active'] ? ' active' : '' ?>" href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?><?= $item['active'] ? ' aria-current="page"' : '' ?>><?= $view->e($item['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($view->hasLink($cta)): ?>
          <a class="btn lyc-btn lyc-btn-gold ms-lg-3 mt-3 mt-lg-0" href="<?= $view->href($cta) ?>"<?= $view->linkAttrs($cta) ?>><?= $view->e($cta['text']) ?><i class="bi bi-arrow-up-right"></i></a>
        <?php endif; ?>
      </div>
    </div>
  </nav>
</header>
