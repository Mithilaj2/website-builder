<section <?= $view->attrs($section, 'crl-logos') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><p class="crl-logos-title"><?= $view->e($data['heading']) ?></p><?php endif; ?>
    <ul class="crl-logo-row">
      <?php foreach ((array) ($data['items'] ?? []) as $l): $src = $view->img($l['image'] ?? ''); $name = (string) ($l['name'] ?? ''); ?>
        <li><?php if (preg_match('#^https?://#', (string) ($l['url'] ?? ''))): ?><a href="<?= $view->e($l['url']) ?>" target="_blank" rel="noopener"><?php endif; ?>
          <?php if ($src !== ''): ?><img src="<?= $src ?>" alt="<?= $view->e($name) ?>" loading="lazy"><?php else: ?><span><?= $view->e($name) ?></span><?php endif; ?>
          <?php if (preg_match('#^https?://#', (string) ($l['url'] ?? ''))): ?></a><?php endif; ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
