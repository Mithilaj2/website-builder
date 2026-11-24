<?php if (!$inStock): ?><span class="vlt-flag is-dark">Sold out</span>
<?php elseif (($p['badge'] ?? '') !== ''): ?><span class="vlt-flag"><?= $view->e($p['badge']) ?></span>
<?php elseif ((int) ($p['discount_pct'] ?? 0) > 0): ?><span class="vlt-flag is-sale">−<?= (int) $p['discount_pct'] ?>%</span><?php endif; ?>
