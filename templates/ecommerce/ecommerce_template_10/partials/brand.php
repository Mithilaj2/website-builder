<?php
$logo = $view->logo();
$cls = $class ?? '';
?>
<a class="ppl-brand<?= $cls !== '' ? ' ' . $view->e($cls) : '' ?>" href="<?= $view->e($view->url('')) ?>">
  <?php if ($logo !== ''): ?><img src="<?= $logo ?>" alt="<?= $view->e($view->site('name')) ?>"><?php else: ?>
    <span class="ppl-brand-mark" aria-hidden="true"><?= $view->e(mb_strtoupper(mb_substr((string) $view->site('name', 'C'), 0, 1))) ?></span>
    <span class="ppl-brand-text"><?= $view->e($view->site('name', 'Company')) ?></span><?php endif; ?>
</a>
