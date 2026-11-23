<?php
$menu = $view->menu('header');
$legal = $view->menu('footer');
$social = $view->social();
$logo = $view->logo();
$about = (string) $view->site('footer_about', $view->site('tagline'));
$cta = (array) $view->setting('site', 'header_button', []);
$emergency = (string) $view->site('emergency_phone');
$light = ($view->theme['footer_style'] ?? '') === 'light';
$telEmergency = 'tel:' . preg_replace('/[^0-9+]/', '', (string) $view->site('emergency_phone'));
$telPhone = 'tel:' . preg_replace('/[^0-9+]/', '', (string) $view->site('phone'));
?>
<footer class="crl-footer<?= $light ? ' is-light' : '' ?>">
  <div class="container">
    <div class="row g-4 g-lg-5">
      <div class="col-lg-4">
        <a class="crl-brand crl-brand-footer" href="<?= $view->e($view->url('')) ?>">
          <?php if ($logo !== ''): ?><img src="<?= $logo ?>" alt="<?= $view->e($view->site('name')) ?>"><?php else: ?>
            <?= $view->partial('mark') ?><span class="crl-brand-text"><span class="crl-brand-name"><?= $view->e($view->site('name', 'Hospital')) ?></span></span><?php endif; ?>
        </a>
        <?php if ($about !== ''): ?><p class="crl-footer-about"><?= $view->e($about) ?></p><?php endif; ?>
        <?php if ($social): ?>
          <div class="crl-footer-social">
            <?php foreach ($social as $s): ?><a href="<?= $view->e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= $view->e(ucfirst($s['network'])) ?>"><?= $view->icon($s['icon']) ?></a><?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="col-sm-6 col-lg-2">
        <h2 class="crl-footer-title">Quick links</h2>
        <ul class="crl-footer-links">
          <?php foreach ($menu as $item): ?><li><a href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?>><?= $view->e($item['title']) ?></a></li><?php endforeach; ?>
        </ul>
      </div>
      <div class="col-sm-6 col-lg-3">
        <h2 class="crl-footer-title">Reach us</h2>
        <ul class="crl-footer-contact">
          <?php if ($view->site('address') !== ''): ?><li><i class="bi bi-geo-alt"></i><span><?= $view->text($view->site('address')) ?></span></li><?php endif; ?>
          <?php if ($view->site('phone') !== ''): ?><li><i class="bi bi-telephone"></i><a href="<?= $view->e($telPhone) ?>"><?= $view->e($view->site('phone')) ?></a></li><?php endif; ?>
          <?php if ($view->site('email') !== ''): ?><li><i class="bi bi-envelope"></i><a href="mailto:<?= $view->e($view->site('email')) ?>"><?= $view->e($view->site('email')) ?></a></li><?php endif; ?>
          <?php if ($view->site('hours') !== ''): ?><li><i class="bi bi-clock"></i><span><?= $view->e($view->site('hours')) ?></span></li><?php endif; ?>
        </ul>
      </div>
      <div class="col-lg-3">
        <div class="crl-footer-card">
          <?php if ($emergency !== ''): ?>
            <span class="crl-footer-card-label"><span class="crl-pulse" aria-hidden="true"></span>Emergency, 24×7</span>
            <a class="crl-footer-card-phone" href="<?= $view->e($telEmergency) ?>"><?= $view->e($emergency) ?></a>
            <p>Ambulance and casualty care, every day of the year.</p>
          <?php else: ?>
            <span class="crl-footer-card-label">Appointments</span>
            <p>Book a consultation with our specialists online.</p>
          <?php endif; ?>
          <?php if ($view->hasLink($cta)): ?><a class="btn crl-btn crl-btn-accent btn-sm" href="<?= $view->href($cta) ?>"<?= $view->linkAttrs($cta) ?>><?= $view->e($cta['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
        </div>
      </div>
    </div>
    <div class="crl-footer-bottom">
      <span>© <?= $view->year() ?> <?= $view->e($view->site('name', 'Hospital')) ?>. <?= $view->e($view->site('footer_text', 'All rights reserved.')) ?></span>
      <?php if ($legal): ?><span class="crl-footer-legal"><?php foreach ($legal as $item): ?><a href="<?= $view->e($item['url']) ?>"><?= $view->e($item['title']) ?></a><?php endforeach; ?></span><?php endif; ?>
    </div>
  </div>
</footer>
