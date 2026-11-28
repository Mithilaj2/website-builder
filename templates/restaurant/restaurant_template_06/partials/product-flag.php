<?php if (!$inStock): ?><span class="ltw-flag is-dark">Sold out</span>
<?php elseif (($p['badge'] ?? '') !== ''): ?><span class="ltw-flag"><?= $view->e($p['badge']) ?></span>
<?php elseif ((int) ($p['discount_pct'] ?? 0) > 0): ?><span class="ltw-flag is-sale">−<?= (int) $p['discount_pct'] ?>%</span><?php endif; ?>
