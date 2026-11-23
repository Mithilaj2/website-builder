<?php
$menu = $view->menu('header');
$legal = $view->menu('footer');
$social = $view->social();
$email = (string) $view->site('email');
$light = ($view->theme['footer_style'] ?? '') === 'light';
$lcfg = $view->listingConfig();
?>
<footer class="skl-footer<?= $light ? ' is-light' : '' ?>">
  <div class="container">
    <p class="skl-footer-kicker"><?= $view->e($view->site('footer_about', 'Have a project in mind?')) ?></p>
    <?php if ($email !== ''): ?><a class="skl-footer-mail" href="mailto:<?= $view->e($email) ?>"><?= $view->e($email) ?><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a><?php endif; ?>
    <div class="skl-footer-grid">
      <div>
        <h2 class="skl-footer-title">Studio</h2>
        <?php if ($view->site('address') !== ''): ?><p><?= $view->text($view->site('address')) ?></p><?php endif; ?>
        <?php if ($view->site('phone') !== ''): ?><p><a href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', (string) $view->site('phone'))) ?>"><?= $view->e($view->site('phone')) ?></a></p><?php endif; ?>
      </div>
      <div>
        <h2 class="skl-footer-title">Menu</h2>
        <ul class="skl-footer-links">
          <?php foreach ($menu as $item): ?><li><a href="<?= $view->e($item['url']) ?>"><?= $view->e($item['title']) ?></a></li><?php endforeach; ?>
          <?php if ($lcfg): ?><li><a href="<?= $view->e($view->listingsUrl()) ?>"><?= $view->e($lcfg['menu_label']) ?></a></li><?php endif; ?>
        </ul>
      </div>
      <div>
        <h2 class="skl-footer-title">Follow</h2>
        <ul class="skl-footer-links">
          <?php foreach ($social as $s): ?><li><a href="<?= $view->e($s['url']) ?>" target="_blank" rel="noopener"><?= $view->e(ucfirst($s['network'])) ?></a></li><?php endforeach; ?>
        </ul>
      </div>
    </div>
    <div class="skl-footer-bottom">
      <span>© <?= $view->year() ?> <?= $view->e($view->site('name', 'Studio')) ?>. <?= $view->e($view->site('footer_text', 'All rights reserved.')) ?></span>
      <span><?php foreach ($legal as $item): ?><a href="<?= $view->e($item['url']) ?>"><?= $view->e($item['title']) ?></a> <?php endforeach; ?></span>
    </div>
  </div>
</footer>
