<?php $cta = (array) $view->setting('site', 'header_button', []); ?>
<?php if ($view->hasLink($cta)): ?><a class="btn hhv-btn hhv-btn-primary hhv-header-cta" href="<?= $view->href($cta) ?>"<?= $view->linkAttrs($cta) ?>><?= $view->e($cta['text']) ?></a><?php endif; ?>
