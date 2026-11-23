<?php $logo = $view->logo(); ?>
<a class="navbar-brand lvh-brand" href="<?= $view->e($view->url('')) ?>">
  <?php if ($logo !== ''): ?><img src="<?= $logo ?>" alt="<?= $view->e($view->site('name')) ?>">
  <?php else: ?><span class="lvh-mark" aria-hidden="true"><svg viewBox="0 0 40 48"><path d="M4 46V20Q4 4 20 2Q36 4 36 20v26" fill="none" stroke="currentColor" stroke-width="2"/><path d="M11 46V24q0-10 9-12 9 2 9 12v22" fill="none" stroke="var(--wb-secondary)" stroke-width="2"/></svg></span><span class="lvh-brand-text"><span class="lvh-brand-name"><?= $view->e($view->site('name', 'Hotel')) ?></span><?php if ($view->site('tagline') !== ''): ?><small><?= $view->e($view->site('tagline')) ?></small><?php endif; ?></span><?php endif; ?>
</a>
