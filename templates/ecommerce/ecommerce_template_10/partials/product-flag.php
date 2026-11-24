<?php if (!$inStock): ?><span class="ppl-flag is-dark">Sold out</span>
<?php elseif (($p['badge'] ?? '') !== ''): ?><span class="ppl-flag"><?= $view->e($p['badge']) ?></span>
<?php elseif ((int) ($p['discount_pct'] ?? 0) > 0): ?><span class="ppl-flag is-sale">−<?= (int) $p['discount_pct'] ?>%</span><?php endif; ?>
