<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'rvp-services rvp-services-rows') ?>>
  <div class="container">
    <div class="row g-4 g-lg-5">
      <div class="col-lg-4"><div class="rvp-sticky-head"><?= $view->partial('section-head', ['data' => $data, 'align' => 'start']) ?></div></div>
      <div class="col-lg-8">
        <ol class="rvp-rows">
          <?php foreach ($items as $i => $item): $img = $view->img($item['image'] ?? ''); ?>
            <li class="rvp-row-item<?= $img !== '' ? ' has-image' : '' ?>">
              <?php if ($img !== ''): ?><div class="rvp-row-img"><img src="<?= $img ?>" alt="<?= $view->alt($item['image'] ?? '', (string) ($item['title'] ?? '')) ?>" loading="lazy"></div>
              <?php else: ?><span class="rvp-row-num" aria-hidden="true"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span><?php endif; ?>
              <div class="rvp-row-body">
                <h3 class="rvp-card-title"><?php if (($item['icon'] ?? '') !== '' && $img === ''): ?><?= $view->icon($item['icon']) ?><?php endif; ?><?= $view->e($item['title'] ?? '') ?></h3>
                <?php if (($item['description'] ?? '') !== ''): ?><p><?= $view->text($item['description']) ?></p><?php endif; ?>
                <?php if ($view->hasLink($item['link'] ?? [])): ?><a class="rvp-link" href="<?= $view->href($item['link']) ?>"<?= $view->linkAttrs($item['link']) ?>><?= $view->e($item['link']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>
  </div>
</section>
