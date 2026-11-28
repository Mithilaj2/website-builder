<?php
$it = $item ?? [];
$cfg = $view->listingConfig();
$for = (string) ($for ?? '');
?>
<span class="bwr-listing-tags">
  <?php if (!empty($it['is_closed'])): ?><span class="bwr-tag is-dark"><?= $view->e($cfg['closed_label'] ?? 'Closed') ?></span>
  <?php else: ?>
    <?php if ($for !== ''): ?><span class="bwr-tag is-light">For <?= $view->e(strtolower($for)) ?></span><?php endif; ?>
    <?php if (($it['badge'] ?? '') !== ''): ?><span class="bwr-tag"><?= $view->e($it['badge']) ?></span><?php endif; ?>
  <?php endif; ?>
</span>
