<?php
$menu = $view->menu('header');
$legal = $view->menu('footer');
$social = $view->social();
$logo = $view->logo();
$about = (string) $view->site('footer_about', $view->site('tagline'));
$shopOn = $view->shopEnabled();
$cats = $shopOn ? $view->productCategories() : [];
$light = ($view->theme['footer_style'] ?? '') === 'light';
$telPhone = 'tel:' . preg_replace('/[^0-9+]/', '', (string) $view->site('phone'));
?>
<footer class="trm-footer<?= $light ? ' is-light' : '' ?>">
  <?php if ($shopOn): ?>
  <div class="trm-perks">
    <div class="container">
      <ul>
        <li><i class="bi bi-truck"></i><span><strong>Fast delivery</strong><small>Across India in 3–7 days</small></span></li>
        <li><i class="bi bi-shield-check"></i><span><strong>Secure payment</strong><small>UPI, cards, net banking<?= $view->setting('shop', 'cod_enabled', false) ? ', COD' : '' ?></small></span></li>
        <li><i class="bi bi-arrow-repeat"></i><span><strong>Easy returns</strong><small>We are here to help</small></span></li>
        <li><i class="bi bi-receipt"></i><span><strong>GST invoice</strong><small>With every order</small></span></li>
      </ul>
    </div>
  </div>
  <?php endif; ?>
  <div class="container">
    <div class="row g-4 g-lg-5">
      <div class="col-lg-4">
        <a class="trm-brand trm-brand-footer" href="<?= $view->e($view->url('')) ?>"><?php if ($logo !== ''): ?><img src="<?= $logo ?>" alt="<?= $view->e($view->site('name')) ?>"><?php else: ?><span class="trm-brand-name"><?= $view->e($view->site('name', 'Store')) ?></span><?php endif; ?></a>
        <?php if ($about !== ''): ?><p class="trm-footer-about"><?= $view->e($about) ?></p><?php endif; ?>
        <?php if ($social): ?><div class="trm-footer-social"><?php foreach ($social as $s): ?><a href="<?= $view->e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= $view->e(ucfirst($s['network'])) ?>"><?= $view->icon($s['icon']) ?></a><?php endforeach; ?></div><?php endif; ?>
      </div>
      <?php if ($cats): ?>
      <div class="col-6 col-lg-2">
        <h2 class="trm-footer-title">Shop</h2>
        <ul class="trm-footer-links">
          <?php foreach ($cats as $c): ?><li><a href="<?= $view->e($view->categoryUrl($c)) ?>"><?= $view->e($c['name']) ?></a></li><?php endforeach; ?>
          <li><a href="<?= $view->e($view->shopUrl()) ?>">All products</a></li>
        </ul>
      </div>
      <?php endif; ?>
      <div class="col-6 col-lg-2">
        <h2 class="trm-footer-title">Information</h2>
        <ul class="trm-footer-links">
          <?php foreach ($menu as $item): ?><li><a href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?>><?= $view->e($item['title']) ?></a></li><?php endforeach; ?>
          <?php foreach ($legal as $item): ?><li><a href="<?= $view->e($item['url']) ?>"><?= $view->e($item['title']) ?></a></li><?php endforeach; ?>
        </ul>
      </div>
      <div class="col-lg-4">
        <h2 class="trm-footer-title">Customer care</h2>
        <ul class="trm-footer-contact">
          <?php if ($view->site('phone') !== ''): ?><li><i class="bi bi-telephone"></i><a href="<?= $view->e($telPhone) ?>"><?= $view->e($view->site('phone')) ?></a></li><?php endif; ?>
          <?php if ($view->site('email') !== ''): ?><li><i class="bi bi-envelope"></i><a href="mailto:<?= $view->e($view->site('email')) ?>"><?= $view->e($view->site('email')) ?></a></li><?php endif; ?>
          <?php if ($view->site('hours') !== ''): ?><li><i class="bi bi-clock"></i><span><?= $view->e($view->site('hours')) ?></span></li><?php endif; ?>
          <?php if ($view->site('address') !== ''): ?><li><i class="bi bi-geo-alt"></i><span><?= $view->text($view->site('address')) ?></span></li><?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="trm-footer-bottom">
      <span>© <?= $view->year() ?> <?= $view->e($view->site('name', 'Store')) ?>. <?= $view->e($view->site('footer_text', 'All rights reserved.')) ?></span>
      <span class="trm-pay-icons" aria-label="Payment methods"><i class="bi bi-phone" title="UPI"></i><i class="bi bi-credit-card" title="Cards"></i><i class="bi bi-bank" title="Net banking"></i><?php if ($view->setting('shop', 'cod_enabled', false)): ?><i class="bi bi-cash-coin" title="Cash on delivery"></i><?php endif; ?></span>
    </div>
  </div>
</footer>
