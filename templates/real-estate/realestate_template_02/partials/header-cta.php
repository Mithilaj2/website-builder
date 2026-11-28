<?php $cta = (array) $view->setting('site', 'header_button', []); ?>
<?php if ($view->hasLink($cta)): ?><a class="btn cvl-btn cvl-btn-primary cvl-header-cta" href="<?= $view->href($cta) ?>"<?= $view->linkAttrs($cta) ?>><?= $view->e($cta['text']) ?></a><?php endif; ?>
