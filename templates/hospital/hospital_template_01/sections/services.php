<?php $cols = ['2' => 'col-md-6', '3' => 'col-md-6 col-lg-4', '4' => 'col-sm-6 col-lg-3'][$data['columns'] ?? '4'] ?? 'col-sm-6 col-lg-3'; ?>
<section <?= $view->attrs($section, 'crl-services') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4">
      <?php foreach ((array) ($data['items'] ?? []) as $item): $img = $view->img($item['image'] ?? ''); $linked = $view->hasLink($item['link'] ?? []); ?>
        <div class="<?= $cols ?>">
          <article class="crl-dept<?= $img !== '' ? ' has-image' : '' ?>">
            <?php if ($img !== ''): ?><div class="crl-dept-img"><img src="<?= $img ?>" alt="<?= $view->alt($item['image'] ?? '', (string) ($item['title'] ?? '')) ?>" loading="lazy"></div><?php endif; ?>
            <span class="crl-icon"><?= $view->icon(($item['icon'] ?? '') ?: 'bi-heart-pulse') ?></span>
            <h3 class="crl-card-title"><?= $view->e($item['title'] ?? '') ?></h3>
            <?php if (($item['description'] ?? '') !== ''): ?><p><?= $view->text($item['description']) ?></p><?php endif; ?>
            <?php if ($linked): ?><a class="crl-link stretched-link" href="<?= $view->href($item['link']) ?>"<?= $view->linkAttrs($item['link']) ?>><?= $view->e($item['link']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
