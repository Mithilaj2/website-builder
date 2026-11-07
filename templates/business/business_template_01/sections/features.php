<?php $img = $view->img($data['image'] ?? ''); ?>
<section <?= $view->attrs($section, 'mrd-features') ?>>
  <div class="container">
    <div class="row g-4 g-lg-5 align-items-center">
      <div class="<?= $img !== '' ? 'col-lg-5' : 'col-12' ?>">
        <?= $view->partial('section-head', ['data' => $data, 'align' => $img !== '' ? 'start' : 'center']) ?>
        <?php if ($img !== ''): ?><div class="mrd-features-img"><img src="<?= $img ?>" alt="<?= $view->alt($data['image'] ?? '', '') ?>" loading="lazy"></div><?php endif; ?>
      </div>
      <div class="<?= $img !== '' ? 'col-lg-7' : 'col-12' ?>">
        <div class="row g-4">
          <?php foreach ((array) ($data['items'] ?? []) as $item): ?>
            <div class="<?= $img !== '' ? 'col-md-6' : 'col-md-6 col-lg-4' ?>">
              <div class="mrd-feature">
                <span class="mrd-icon mrd-icon-soft"><?= $view->icon($item['icon'] ?? 'bi-award') ?></span>
                <div><h3 class="mrd-card-title"><?= $view->e($item['title'] ?? '') ?></h3>
                <?php if (($item['description'] ?? '') !== ''): ?><p><?= $view->text($item['description']) ?></p><?php endif; ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>
