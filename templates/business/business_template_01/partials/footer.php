<?php
$menu = $view->menu('header');
$legal = $view->menu('footer');
$social = $view->social();
$logo = $view->logo();
$about = (string) $view->site('footer_about', $view->site('tagline'));
?>
<footer class="mrd-footer">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-4">
        <a class="mrd-brand mrd-brand-footer" href="<?= $view->e($view->url('')) ?>">
          <?php if ($logo !== ''): ?><img src="<?= $logo ?>" alt="<?= $view->e($view->site('name')) ?>"><?php else: ?>
            <span class="mrd-brand-mark" aria-hidden="true"><?= $view->e(mb_strtoupper(mb_substr((string) $view->site('name', 'M'), 0, 1))) ?></span>
            <span class="mrd-brand-text"><?= $view->e($view->site('name', 'Company')) ?></span><?php endif; ?>
        </a>
        <?php if ($about !== ''): ?><p class="mrd-footer-about"><?= $view->e($about) ?></p><?php endif; ?>
        <?php if ($social): ?>
          <div class="mrd-footer-social">
            <?php foreach ($social as $s): ?><a href="<?= $view->e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= $view->e(ucfirst($s['network'])) ?>"><?= $view->icon($s['icon']) ?></a><?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="col-6 col-lg-2">
        <h2 class="mrd-footer-title">Company</h2>
        <ul class="mrd-footer-links">
          <?php foreach ($menu as $item): ?><li><a href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?>><?= $view->e($item['title']) ?></a></li><?php endforeach; ?>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h2 class="mrd-footer-title">Information</h2>
        <ul class="mrd-footer-links">
          <?php foreach ($legal as $item): ?><li><a href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?>><?= $view->e($item['title']) ?></a></li><?php endforeach; ?>
        </ul>
      </div>
      <div class="col-lg-4">
        <h2 class="mrd-footer-title">Contact</h2>
        <ul class="mrd-footer-contact">
          <?php if ($view->site('address') !== ''): ?><li><i class="bi bi-geo-alt"></i><span><?= $view->text($view->site('address')) ?></span></li><?php endif; ?>
          <?php if ($view->site('phone') !== ''): ?><li><i class="bi bi-telephone"></i><a href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', (string) $view->site('phone'))) ?>"><?= $view->e($view->site('phone')) ?></a></li><?php endif; ?>
          <?php if ($view->site('email') !== ''): ?><li><i class="bi bi-envelope"></i><a href="mailto:<?= $view->e($view->site('email')) ?>"><?= $view->e($view->site('email')) ?></a></li><?php endif; ?>
          <?php if ($view->site('hours') !== ''): ?><li><i class="bi bi-clock"></i><span><?= $view->e($view->site('hours')) ?></span></li><?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="mrd-footer-bottom">
      <span>© <?= $view->year() ?> <?= $view->e($view->site('name', 'Company')) ?>. <?= $view->e($view->site('footer_text', 'All rights reserved.')) ?></span>
    </div>
  </div>
</footer>
