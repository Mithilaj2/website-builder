<?php $cta = (array) $view->setting('site', 'header_button', []); ?>
<?php if ($view->hasLink($cta)): ?><a class="btn rvp-btn rvp-btn-primary rvp-header-cta" href="<?= $view->href($cta) ?>"<?= $view->linkAttrs($cta) ?>><?= $view->e($cta['text']) ?></a><?php endif; ?>
