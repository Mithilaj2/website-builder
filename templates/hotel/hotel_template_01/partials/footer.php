<?php
$menu = $view->menu('header');
$legal = $view->menu('footer');
$social = $view->social();
$about = (string) $view->site('footer_about', $view->site('tagline'));
$lcfg = $view->listingConfig();
$rooms = $lcfg ? $view->listings(['limit' => 6]) : [];
$light = ($view->theme['footer_style'] ?? '') === 'light';
$telPhone = 'tel:' . preg_replace('/[^0-9+]/', '', (string) $view->site('phone'));
?>
<footer class="lvh-footer<?= $light ? ' is-light' : '' ?>">
  <div class="container">
    <div class="lvh-footer-top">
      <?= $view->partial('brand') ?>
      <?php if ($about !== ''): ?><p class="lvh-footer-about"><?= $view->e($about) ?></p><?php endif; ?>
      <?php if ($social): ?><div class="lvh-footer-social"><?php foreach ($social as $s): ?><a href="<?= $view->e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= $view->e(ucfirst($s['network'])) ?>"><?= $view->icon($s['icon']) ?></a><?php endforeach; ?></div><?php endif; ?>
    </div>
    <div class="row g-4 g-lg-5">
      <div class="col-md-4">
        <h2 class="lvh-footer-title">Reach us</h2>
        <ul class="lvh-footer-contact">
          <?php if ($view->site('address') !== ''): ?><li><i class="bi bi-geo-alt"></i><span><?= $view->text($view->site('address')) ?></span></li><?php endif; ?>
          <?php if ($view->site('phone') !== ''): ?><li><i class="bi bi-telephone"></i><a href="<?= $view->e($telPhone) ?>"><?= $view->e($view->site('phone')) ?></a></li><?php endif; ?>
          <?php if ($view->site('email') !== ''): ?><li><i class="bi bi-envelope"></i><a href="mailto:<?= $view->e($view->site('email')) ?>"><?= $view->e($view->site('email')) ?></a></li><?php endif; ?>
          <?php if ($view->site('hours') !== ''): ?><li><i class="bi bi-clock"></i><span><?= $view->e($view->site('hours')) ?></span></li><?php endif; ?>
        </ul>
      </div>
      <?php if ($rooms): ?>
      <div class="col-6 col-md-4">
        <h2 class="lvh-footer-title"><?= $view->e($lcfg['plural']) ?></h2>
        <ul class="lvh-footer-links">
          <?php foreach ($rooms as $r): ?><li><a href="<?= $view->e($view->listingUrl($r)) ?>"><?= $view->e($r['title']) ?></a></li><?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
      <div class="col-6 col-md-4">
        <h2 class="lvh-footer-title">The hotel</h2>
        <ul class="lvh-footer-links">
          <?php foreach ($menu as $item): ?><li><a href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?>><?= $view->e($item['title']) ?></a></li><?php endforeach; ?>
          <?php foreach ($legal as $item): ?><li><a href="<?= $view->e($item['url']) ?>"><?= $view->e($item['title']) ?></a></li><?php endforeach; ?>
        </ul>
      </div>
    </div>
    <div class="lvh-footer-bottom">
      <span>© <?= $view->year() ?> <?= $view->e($view->site('name', 'Hotel')) ?>. <?= $view->e($view->site('footer_text', 'All rights reserved.')) ?></span>
      <span>Rates are per room per night and exclude taxes unless stated.</span>
    </div>
  </div>
</footer>
