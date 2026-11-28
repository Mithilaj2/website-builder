<?php if (!$inStock): ?><span class="cvl-flag is-dark">Sold out</span>
<?php elseif (($p['badge'] ?? '') !== ''): ?><span class="cvl-flag"><?= $view->e($p['badge']) ?></span>
<?php elseif ((int) ($p['discount_pct'] ?? 0) > 0): ?><span class="cvl-flag is-sale">−<?= (int) $p['discount_pct'] ?>%</span><?php endif; ?>
