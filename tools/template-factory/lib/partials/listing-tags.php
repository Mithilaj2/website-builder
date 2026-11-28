<?php
$it = $item ?? [];
$cfg = $view->listingConfig();
$for = (string) ($for ?? '');
?>
<span class="{{p}}-listing-tags">
  <?php if (!empty($it['is_closed'])): ?><span class="{{p}}-tag is-dark"><?= $view->e($cfg['closed_label'] ?? 'Closed') ?></span>
  <?php else: ?>
    <?php if ($for !== ''): ?><span class="{{p}}-tag is-light">For <?= $view->e(strtolower($for)) ?></span><?php endif; ?>
    <?php if (($it['badge'] ?? '') !== ''): ?><span class="{{p}}-tag"><?= $view->e($it['badge']) ?></span><?php endif; ?>
  <?php endif; ?>
</span>
