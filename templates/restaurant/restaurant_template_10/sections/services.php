<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'pdd-services pdd-services-rows') ?>>
  <div class="container">
    <div class="row g-4 g-lg-5">
      <div class="col-lg-4"><div class="pdd-sticky-head"><?= $view->partial('section-head', ['data' => $data, 'align' => 'start']) ?></div></div>
      <div class="col-lg-8">
        <ol class="pdd-rows">
          <?php foreach ($items as $i => $item): $img = $view->img($item['image'] ?? ''); ?>
            <li class="pdd-row-item<?= $img !== '' ? ' has-image' : '' ?>">
              <?php if ($img !== ''): ?><div class="pdd-row-img"><img src="<?= $img ?>" alt="<?= $view->alt($item['image'] ?? '', (string) ($item['title'] ?? '')) ?>" loading="lazy"></div>
              <?php else: ?><span class="pdd-row-num" aria-hidden="true"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span><?php endif; ?>
              <div class="pdd-row-body">
                <h3 class="pdd-card-title"><?php if (($item['icon'] ?? '') !== '' && $img === ''): ?><?= $view->icon($item['icon']) ?><?php endif; ?><?= $view->e($item['title'] ?? '') ?></h3>
                <?php if (($item['description'] ?? '') !== ''): ?><p><?= $view->text($item['description']) ?></p><?php endif; ?>
                <?php if ($view->hasLink($item['link'] ?? [])): ?><a class="pdd-link" href="<?= $view->href($item['link']) ?>"<?= $view->linkAttrs($item['link']) ?>><?= $view->e($item['link']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>
  </div>
</section>
