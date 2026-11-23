<?php
$menu = $view->menu('header');
$phone = (string) $view->site('phone');
$emergency = (string) $view->site('emergency_phone');
$email = (string) $view->site('email');
$cta = (array) $view->setting('site', 'header_button', []);
$logo = $view->logo();
$tagline = (string) $view->site('tagline');
$telEmergency = 'tel:' . preg_replace('/[^0-9+]/', '', (string) $view->site('emergency_phone'));
$telPhone = 'tel:' . preg_replace('/[^0-9+]/', '', (string) $view->site('phone'));
?>
<?php if (($view->theme['header_style'] ?? '') !== 'minimal' && ($emergency !== '' || $phone !== '' || $email !== '')): ?>
<div class="crl-strip">
  <div class="container d-flex flex-wrap align-items-center gap-2 gap-md-4">
    <?php if ($emergency !== ''): ?>
      <a class="crl-strip-emergency" href="<?= $view->e($telEmergency) ?>"><span class="crl-pulse" aria-hidden="true"></span>Emergency 24×7<b><?= $view->e($emergency) ?></b></a>
    <?php endif; ?>
    <span class="crl-strip-info ms-md-auto">
      <?php if ($phone !== ''): ?><a href="<?= $view->e($telPhone) ?>" class="<?= $emergency !== '' ? 'd-none d-md-inline-flex' : '' ?>"><i class="bi bi-telephone"></i><?= $view->e($phone) ?></a><?php endif; ?>
      <?php if ($email !== ''): ?><a href="mailto:<?= $view->e($email) ?>" class="d-none d-lg-inline-flex"><i class="bi bi-envelope"></i><?= $view->e($email) ?></a><?php endif; ?>
      <?php if ($view->site('hours') !== ''): ?><span class="d-none d-xl-inline-flex"><i class="bi bi-clock"></i><?= $view->e($view->site('hours')) ?></span><?php endif; ?>
    </span>
  </div>
</div>
<?php endif; ?>
<header class="crl-header" data-crl-header>
  <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
    <div class="container">
      <a class="navbar-brand crl-brand" href="<?= $view->e($view->url('')) ?>">
        <?php if ($logo !== ''): ?>
          <img src="<?= $logo ?>" alt="<?= $view->e($view->site('name')) ?>">
        <?php else: ?>
          <?= $view->partial('mark') ?>
          <span class="crl-brand-text"><span class="crl-brand-name"><?= $view->e($view->site('name', 'Hospital')) ?></span>
            <?php if ($tagline !== ''): ?><small><?= $view->e($tagline) ?></small><?php endif; ?></span>
        <?php endif; ?>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#crlNav" aria-controls="crlNav" aria-expanded="false" aria-label="Toggle navigation">
        <i class="bi bi-list"></i>
      </button>
      <div class="collapse navbar-collapse" id="crlNav">
        <ul class="navbar-nav mx-auto align-items-lg-center">
          <?php foreach ($menu as $item): ?>
            <li class="nav-item"><a class="nav-link<?= $item['active'] ? ' active' : '' ?>" href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?><?= $item['active'] ? ' aria-current="page"' : '' ?>><?= $view->e($item['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($view->hasLink($cta)): ?>
          <a class="btn crl-btn crl-btn-accent mt-3 mt-lg-0" href="<?= $view->href($cta) ?>"<?= $view->linkAttrs($cta) ?>><i class="bi bi-calendar2-check"></i><?= $view->e($cta['text']) ?></a>
        <?php endif; ?>
      </div>
    </div>
  </nav>
</header>
