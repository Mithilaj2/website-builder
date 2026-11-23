<section <?= $view->attrs($section, 'trm-logos') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><p class="trm-logos-title"><?= $view->e($data['heading']) ?></p><?php endif; ?>
    <ul class="trm-logo-row">
      <?php foreach ((array) ($data['items'] ?? []) as $l): $src = $view->img($l['image'] ?? ''); $name = (string) ($l['name'] ?? ''); $link = preg_match('#^https?://#', (string) ($l['url'] ?? '')); ?>
        <li><?php if ($link): ?><a href="<?= $view->e($l['url']) ?>" target="_blank" rel="noopener"><?php endif; ?><?php if ($src !== ''): ?><img src="<?= $src ?>" alt="<?= $view->e($name) ?>" loading="lazy"><?php else: ?><span><?= $view->e($name) ?></span><?php endif; ?><?php if ($link): ?></a><?php endif; ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
