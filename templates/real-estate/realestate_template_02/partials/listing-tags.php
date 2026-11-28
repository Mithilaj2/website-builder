<?php
$it = $item ?? [];
$cfg = $view->listingConfig();
$for = (string) ($for ?? '');
?>
<span class="cvl-listing-tags">
  <?php if (!empty($it['is_closed'])): ?><span class="cvl-tag is-dark"><?= $view->e($cfg['closed_label'] ?? 'Closed') ?></span>
  <?php else: ?>
    <?php if ($for !== ''): ?><span class="cvl-tag is-light">For <?= $view->e(strtolower($for)) ?></span><?php endif; ?>
    <?php if (($it['badge'] ?? '') !== ''): ?><span class="cvl-tag"><?= $view->e($it['badge']) ?></span><?php endif; ?>
  <?php endif; ?>
</span>
