<?php
$menu = $view->menu('header');
$phone = (string) $view->site('phone');
$email = (string) $view->site('email');
$social = $view->social();
$cta = (array) $view->setting('site', 'header_button', []);
$logo = $view->logo();
?>
<?php if (($view->theme['header_style'] ?? '') !== 'minimal' && ($phone !== '' || $email !== '' || $social)): ?>
<div class="mrd-topbar">
  <div class="container d-flex flex-wrap align-items-center gap-3">
    <?php if ($phone !== ''): ?><a href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><i class="bi bi-telephone"></i><?= $view->e($phone) ?></a><?php endif; ?>
    <?php if ($email !== ''): ?><a href="mailto:<?= $view->e($email) ?>"><i class="bi bi-envelope"></i><?= $view->e($email) ?></a><?php endif; ?>
    <?php if ($view->site('hours') !== ''): ?><span class="d-none d-lg-inline-flex"><i class="bi bi-clock"></i><?= $view->e($view->site('hours')) ?></span><?php endif; ?>
    <?php if ($social): ?>
      <span class="ms-auto mrd-topbar-social">
        <?php foreach ($social as $s): ?><a href="<?= $view->e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= $view->e(ucfirst($s['network'])) ?>"><?= $view->icon($s['icon']) ?></a><?php endforeach; ?>
      </span>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<header class="mrd-header" data-mrd-header>
  <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
    <div class="container">
      <a class="navbar-brand mrd-brand" href="<?= $view->e($view->url('')) ?>">
        <?php if ($logo !== ''): ?>
          <img src="<?= $logo ?>" alt="<?= $view->e($view->site('name')) ?>">
        <?php else: ?>
          <span class="mrd-brand-mark" aria-hidden="true"><?= $view->e(mb_strtoupper(mb_substr((string) $view->site('name', 'M'), 0, 1))) ?></span>
          <span class="mrd-brand-text"><?= $view->e($view->site('name', 'Company')) ?></span>
        <?php endif; ?>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mrdNav" aria-controls="mrdNav" aria-expanded="false" aria-label="Toggle navigation">
        <i class="bi bi-list"></i>
      </button>
      <div class="collapse navbar-collapse" id="mrdNav">
        <ul class="navbar-nav ms-auto align-items-lg-center">
          <?php foreach ($menu as $item): ?>
            <li class="nav-item"><a class="nav-link<?= $item['active'] ? ' active' : '' ?>" href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?><?= $item['active'] ? ' aria-current="page"' : '' ?>><?= $view->e($item['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($view->hasLink($cta)): ?>
          <a class="btn mrd-btn mrd-btn-primary ms-lg-4 mt-3 mt-lg-0" href="<?= $view->href($cta) ?>"<?= $view->linkAttrs($cta) ?>><?= $view->e($cta['text']) ?></a>
        <?php endif; ?>
      </div>
    </div>
  </nav>
</header>
