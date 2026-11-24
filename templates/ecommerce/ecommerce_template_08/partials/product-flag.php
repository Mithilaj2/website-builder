<?php if (!$inStock): ?><span class="erc-flag is-dark">Sold out</span>
<?php elseif (($p['badge'] ?? '') !== ''): ?><span class="erc-flag"><?= $view->e($p['badge']) ?></span>
<?php elseif ((int) ($p['discount_pct'] ?? 0) > 0): ?><span class="erc-flag is-sale">−<?= (int) $p['discount_pct'] ?>%</span><?php endif; ?>
