<?php
$menu = $view->menu('header');
$legal = $view->menu('footer');
$social = $view->social();
$logo = $view->logo();
$about = (string) $view->site('footer_about', $view->site('tagline'));
$warm = ($view->theme['footer_style'] ?? '') === 'warm';
$telPhone = 'tel:' . preg_replace('/[^0-9+]/', '', (string) $view->site('phone'));
$hours = array_values(array_filter(array_map('trim', preg_split('/\R|;/', (string) $view->site('hours')) ?: [])));
?>
<footer class="sfr-footer<?= $warm ? ' is-warm' : '' ?>">
  <div class="container">
    <div class="sfr-footer-top">
      <a class="sfr-brand sfr-brand-footer" href="<?= $view->e($view->url('')) ?>"><?php if ($logo !== ''): ?><img src="<?= $logo ?>" alt="<?= $view->e($view->site('name')) ?>"><?php else: ?><span class="sfr-brand-name"><?= $view->e($view->site('name', 'Restaurant')) ?></span><?php endif; ?></a>
      <?php if ($about !== ''): ?><p class="sfr-footer-about"><?= $view->e($about) ?></p><?php endif; ?>
    </div>
    <div class="row g-4 g-lg-5">
      <div class="col-md-4">
        <h2 class="sfr-footer-title">Find us</h2>
        <ul class="sfr-footer-contact">
          <?php if ($view->site('address') !== ''): ?><li><i class="bi bi-geo-alt"></i><span><?= $view->text($view->site('address')) ?></span></li><?php endif; ?>
          <?php if ($view->site('phone') !== ''): ?><li><i class="bi bi-telephone"></i><a href="<?= $view->e($telPhone) ?>"><?= $view->e($view->site('phone')) ?></a></li><?php endif; ?>
          <?php if ($view->site('email') !== ''): ?><li><i class="bi bi-envelope"></i><a href="mailto:<?= $view->e($view->site('email')) ?>"><?= $view->e($view->site('email')) ?></a></li><?php endif; ?>
        </ul>
      </div>
      <div class="col-md-4">
        <h2 class="sfr-footer-title">Opening hours</h2>
        <?php if ($hours): ?><ul class="sfr-footer-hours"><?php foreach ($hours as $h): ?><li><?= $view->e($h) ?></li><?php endforeach; ?></ul><?php else: ?><p>Call us for today’s timings.</p><?php endif; ?>
      </div>
      <div class="col-md-4">
        <h2 class="sfr-footer-title">Explore</h2>
        <ul class="sfr-footer-links">
          <?php foreach ($menu as $item): ?><li><a href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?>><?= $view->e($item['title']) ?></a></li><?php endforeach; ?>
          <?php foreach ($legal as $item): ?><li><a href="<?= $view->e($item['url']) ?>"><?= $view->e($item['title']) ?></a></li><?php endforeach; ?>
        </ul>
        <?php if ($social): ?><div class="sfr-footer-social"><?php foreach ($social as $s): ?><a href="<?= $view->e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= $view->e(ucfirst($s['network'])) ?>"><?= $view->icon($s['icon']) ?></a><?php endforeach; ?></div><?php endif; ?>
      </div>
    </div>
    <div class="sfr-footer-bottom">
      <span>© <?= $view->year() ?> <?= $view->e($view->site('name', 'Restaurant')) ?>. <?= $view->e($view->site('footer_text', 'All rights reserved.')) ?></span>
    </div>
  </div>
</footer>
