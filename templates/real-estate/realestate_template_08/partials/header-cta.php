<?php $cta = (array) $view->setting('site', 'header_button', []); ?>
<?php if ($view->hasLink($cta)): ?><a class="btn gft-btn gft-btn-primary gft-header-cta" href="<?= $view->href($cta) ?>"<?= $view->linkAttrs($cta) ?>><?= $view->e($cta['text']) ?></a><?php endif; ?>
