<?= $view->partial('section-head', ['data' => ['eyebrow' => $data['eyebrow'] ?? '', 'heading' => $data['heading'] ?? ''], 'align' => 'start']) ?>
<div class="rvp-rich"><?= $view->rich($data['description'] ?? '') ?></div>
<?php if (!empty($data['points'])): ?><ul class="rvp-checks"><?php foreach ((array) $data['points'] as $pt): if (($pt['text'] ?? '') === '') { continue; } ?><li><i class="bi bi-check-lg"></i><span><?= $view->e($pt['text']) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
<?php if ($view->hasLink($data['button'] ?? [])): ?><div class="rvp-actions"><a class="btn rvp-btn rvp-btn-primary" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a></div><?php endif; ?>
