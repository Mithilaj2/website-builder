<?php
$menu = $view->menu('header');
$legal = $view->menu('footer');
$social = $view->social();
$logo = $view->logo();
$about = (string) $view->site('footer_about', $view->site('tagline'));
$lcfg = $view->listingConfig();
$types = $lcfg ? $view->listingCategories() : [];
$light = ($view->theme['footer_style'] ?? '') === 'light';
$telPhone = 'tel:' . preg_replace('/[^0-9+]/', '', (string) $view->site('phone'));
?>
<footer class="ldg-footer<?= $light ? ' is-light' : '' ?>">
  <div class="container">
    <div class="row g-4 g-lg-5">
      <div class="col-lg-4">
        <a class="ldg-brand ldg-brand-footer" href="<?= $view->e($view->url('')) ?>"><?php if ($logo !== ''): ?><img src="<?= $logo ?>" alt="<?= $view->e($view->site('name')) ?>"><?php else: ?><span class="ldg-brand-name"><?= $view->e($view->site('name', 'Advisors')) ?></span><?php endif; ?></a>
        <?php if ($about !== ''): ?><p class="ldg-footer-about"><?= $view->e($about) ?></p><?php endif; ?>
        <?php if ($social): ?><div class="ldg-footer-social"><?php foreach ($social as $s): ?><a href="<?= $view->e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= $view->e(ucfirst($s['network'])) ?>"><?= $view->icon($s['icon']) ?></a><?php endforeach; ?></div><?php endif; ?>
      </div>
      <?php if ($lcfg): ?>
      <div class="col-6 col-lg-2">
        <h2 class="ldg-footer-title"><?= $view->e($lcfg['plural']) ?></h2>
        <ul class="ldg-footer-links">
          <?php foreach ($types as $t): ?><li><a href="<?= $view->e($view->listingsUrl(['c' => $t['slug']])) ?>"><?= $view->e($t['name']) ?></a></li><?php endforeach; ?>
          <li><a href="<?= $view->e($view->listingsUrl()) ?>">All <?= $view->e(strtolower($lcfg['plural'])) ?></a></li>
        </ul>
      </div>
      <?php endif; ?>
      <div class="col-6 col-lg-2">
        <h2 class="ldg-footer-title">Company</h2>
        <ul class="ldg-footer-links">
          <?php foreach ($menu as $item): ?><li><a href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?>><?= $view->e($item['title']) ?></a></li><?php endforeach; ?>
          <?php foreach ($legal as $item): ?><li><a href="<?= $view->e($item['url']) ?>"><?= $view->e($item['title']) ?></a></li><?php endforeach; ?>
        </ul>
      </div>
      <div class="col-lg-4">
        <h2 class="ldg-footer-title">Talk to us</h2>
        <ul class="ldg-footer-contact">
          <?php if ($view->site('address') !== ''): ?><li><i class="bi bi-geo-alt"></i><span><?= $view->text($view->site('address')) ?></span></li><?php endif; ?>
          <?php if ($view->site('phone') !== ''): ?><li><i class="bi bi-telephone"></i><a href="<?= $view->e($telPhone) ?>"><?= $view->e($view->site('phone')) ?></a></li><?php endif; ?>
          <?php if ($view->site('email') !== ''): ?><li><i class="bi bi-envelope"></i><a href="mailto:<?= $view->e($view->site('email')) ?>"><?= $view->e($view->site('email')) ?></a></li><?php endif; ?>
          <?php if ($view->site('hours') !== ''): ?><li><i class="bi bi-clock"></i><span><?= $view->e($view->site('hours')) ?></span></li><?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="ldg-footer-bottom">
      <span>© <?= $view->year() ?> <?= $view->e($view->site('name', 'Advisors')) ?>. <?= $view->e($view->site('footer_text', 'All rights reserved.')) ?></span>
      <span class="ldg-footer-note">Fees shown are starting prices and exclude government charges and GST.</span>
    </div>
  </div>
</footer>
