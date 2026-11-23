<?= $view->partial('section-head', ['data' => ['eyebrow' => $data['eyebrow'] ?? '', 'heading' => $data['heading'] ?? ''], 'align' => 'start']) ?>
<div class="chi-rich"><?= $view->rich($data['description'] ?? '') ?></div>
<?php if (!empty($data['points'])): ?><ul class="chi-checks"><?php foreach ((array) $data['points'] as $pt): if (($pt['text'] ?? '') === '') { continue; } ?><li><i class="bi bi-check-lg"></i><span><?= $view->e($pt['text']) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
<?php if ($view->hasLink($data['button'] ?? [])): ?><div class="chi-actions"><a class="btn chi-btn chi-btn-primary" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a></div><?php endif; ?>
