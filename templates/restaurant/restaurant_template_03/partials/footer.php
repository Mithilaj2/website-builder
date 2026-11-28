<?php
$menu = $view->menu('header');
$about = (string) $view->site('footer_about', $view->site('tagline'));
$phone = (string) $view->site('phone');
$email = (string) $view->site('email');
$address = (string) $view->site('address');
?>
<footer class="atr-footer atr-footer-band">
  <div class="container">
    <?php if ($phone !== '' || $email !== '' || $address !== ''): ?>
    <div class="atr-footer-cards">
      <?php if ($phone !== ''): ?><a class="atr-footer-card" href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><span class="atr-icon"><i class="bi bi-telephone"></i></span><span><small>Call us</small><strong><?= $view->e($phone) ?></strong></span></a><?php endif; ?>
      <?php if ($email !== ''): ?><a class="atr-footer-card" href="mailto:<?= $view->e($email) ?>"><span class="atr-icon"><i class="bi bi-envelope"></i></span><span><small>Write to us</small><strong><?= $view->e($email) ?></strong></span></a><?php endif; ?>
      <?php if ($address !== ''): ?><div class="atr-footer-card"><span class="atr-icon"><i class="bi bi-geo-alt"></i></span><span><small>Visit us</small><strong><?= $view->e(strtok($address, "\n")) ?></strong></span></div><?php endif; ?>
    </div>
    <?php endif; ?>
    <?= $view->partial('shop-trust') ?>
    <div class="row g-4 g-lg-5">
      <div class="col-lg-5">
        <?= $view->partial('brand', ['class' => 'atr-brand-footer']) ?>
        <?php if ($about !== ''): ?><p class="atr-footer-about"><?= $view->e($about) ?></p><?php endif; ?>
      </div>
      <div class="col-sm-6 col-lg-3 offset-lg-1">
        <h2 class="atr-footer-title">Company</h2>
        <ul class="atr-footer-links">
          <?php foreach ($menu as $item): ?><li><a href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?>><?= $view->e($item['title']) ?></a></li><?php endforeach; ?>
        </ul>
      </div>
      <div class="col-sm-6 col-lg-3">
        <h2 class="atr-footer-title">Follow</h2>
        <?php if ($view->site('hours') !== ''): ?><p class="atr-footer-about"><i class="bi bi-clock me-2"></i><?= $view->e($view->site('hours')) ?></p><?php endif; ?>
        <?= $view->partial('social') ?>
      </div>
    </div>
    <?= $view->partial('footer-bottom') ?>
  </div>
</footer>
