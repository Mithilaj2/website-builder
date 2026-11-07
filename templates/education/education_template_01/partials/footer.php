<?php
$menu = $view->menu('header');
$legal = $view->menu('footer');
$social = $view->social();
$logo = $view->logo();
$about = (string) $view->site('footer_about', $view->site('tagline'));
$cta = (array) $view->setting('site', 'header_button', []);
$light = ($view->theme['footer_style'] ?? '') === 'light';
?>
<footer class="lyc-footer<?= $light ? ' is-light' : '' ?>">
  <div class="container">
    <div class="row g-4 g-lg-5">
      <div class="col-lg-4">
        <a class="lyc-brand lyc-brand-footer" href="<?= $view->e($view->url('')) ?>">
          <?php if ($logo !== ''): ?><img src="<?= $logo ?>" alt="<?= $view->e($view->site('name')) ?>"><?php else: ?>
            <?= $view->partial('crest') ?><span class="lyc-brand-text"><span class="lyc-brand-name"><?= $view->e($view->site('name', 'Academy')) ?></span></span><?php endif; ?>
        </a>
        <?php if ($about !== ''): ?><p class="lyc-footer-about"><?= $view->e($about) ?></p><?php endif; ?>
        <?php if ($social): ?>
          <div class="lyc-footer-social">
            <?php foreach ($social as $s): ?><a href="<?= $view->e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= $view->e(ucfirst($s['network'])) ?>"><?= $view->icon($s['icon']) ?></a><?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="col-sm-6 col-lg-2">
        <h2 class="lyc-footer-title">Explore</h2>
        <ul class="lyc-footer-links">
          <?php foreach ($menu as $item): ?><li><a href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?>><?= $view->e($item['title']) ?></a></li><?php endforeach; ?>
        </ul>
      </div>
      <div class="col-sm-6 col-lg-3">
        <h2 class="lyc-footer-title">Visit us</h2>
        <ul class="lyc-footer-contact">
          <?php if ($view->site('address') !== ''): ?><li><i class="bi bi-geo-alt"></i><span><?= $view->text($view->site('address')) ?></span></li><?php endif; ?>
          <?php if ($view->site('phone') !== ''): ?><li><i class="bi bi-telephone"></i><a href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', (string) $view->site('phone'))) ?>"><?= $view->e($view->site('phone')) ?></a></li><?php endif; ?>
          <?php if ($view->site('email') !== ''): ?><li><i class="bi bi-envelope"></i><a href="mailto:<?= $view->e($view->site('email')) ?>"><?= $view->e($view->site('email')) ?></a></li><?php endif; ?>
        </ul>
      </div>
      <div class="col-lg-3">
        <div class="lyc-footer-card">
          <h2 class="lyc-footer-title">Admissions</h2>
          <p><?= $view->e($view->site('hours') !== '' ? 'Office hours: ' . $view->site('hours') : 'Talk to our admissions team about the right programme for you.') ?></p>
          <?php if ($view->hasLink($cta)): ?><a class="btn lyc-btn lyc-btn-gold btn-sm" href="<?= $view->href($cta) ?>"<?= $view->linkAttrs($cta) ?>><?= $view->e($cta['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
        </div>
      </div>
    </div>
    <div class="lyc-footer-bottom">
      <span>© <?= $view->year() ?> <?= $view->e($view->site('name', 'Academy')) ?>. <?= $view->e($view->site('footer_text', 'All rights reserved.')) ?></span>
      <?php if ($legal): ?><span class="lyc-footer-legal"><?php foreach ($legal as $item): ?><a href="<?= $view->e($item['url']) ?>"><?= $view->e($item['title']) ?></a><?php endforeach; ?></span><?php endif; ?>
    </div>
  </div>
</footer>
