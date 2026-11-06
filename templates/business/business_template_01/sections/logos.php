<?php
$items = array_filter((array) ($data['items'] ?? []), static fn ($i) => ($i['image'] ?? '') !== '');
if (!$items) { return; }
?>
<section <?= $view->attrs($section, 'mrd-logos') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><p class="mrd-logos-heading"><?= $view->e($data['heading']) ?></p><?php endif; ?>
    <div class="mrd-logos-row">
      <?php foreach ($items as $l): $img = '<img src="' . $view->img($l['image']) . '" alt="' . $view->e($l['name'] ?? '') . '" loading="lazy">'; $u = (string) ($l['url'] ?? ''); ?>
        <?php if ($u !== '' && preg_match('#^https?://#', $u)): ?><a href="<?= $view->e($u) ?>" target="_blank" rel="noopener"><?= $img ?></a><?php else: ?><span><?= $img ?></span><?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
