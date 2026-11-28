<?php
$it = $item ?? [];
$cfg = $view->listingConfig();
$for = (string) ($for ?? '');
?>
<span class="bhl-listing-tags">
  <?php if (!empty($it['is_closed'])): ?><span class="bhl-tag is-dark"><?= $view->e($cfg['closed_label'] ?? 'Closed') ?></span>
  <?php else: ?>
    <?php if ($for !== ''): ?><span class="bhl-tag is-light">For <?= $view->e(strtolower($for)) ?></span><?php endif; ?>
    <?php if (($it['badge'] ?? '') !== ''): ?><span class="bhl-tag"><?= $view->e($it['badge']) ?></span><?php endif; ?>
  <?php endif; ?>
</span>
