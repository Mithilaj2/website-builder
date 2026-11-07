<?php $img = $view->img($data['image'] ?? ''); ?>
<section <?= $view->attrs($section, 'crl-features') ?>>
  <div class="container">
    <div class="row g-4 g-lg-5 align-items-center">
      <div class="<?= $img !== '' ? 'col-lg-7' : 'col-12' ?>">
        <?= $view->partial('section-head', ['data' => $data, 'align' => $img !== '' ? 'start' : 'center']) ?>
        <div class="row g-4">
          <?php foreach ((array) ($data['items'] ?? []) as $item): ?>
            <div class="<?= $img !== '' ? 'col-md-6' : 'col-md-6 col-lg-4' ?>">
              <div class="crl-feature">
                <span class="crl-icon crl-icon-gold"><?= $view->icon(($item['icon'] ?? '') ?: 'bi-star') ?></span>
                <div><h3 class="crl-feature-title"><?= $view->e($item['title'] ?? '') ?></h3>
                <?php if (($item['description'] ?? '') !== ''): ?><p><?= $view->text($item['description']) ?></p><?php endif; ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php if ($img !== ''): ?>
        <div class="col-lg-5"><div class="crl-frame crl-features-img"><img src="<?= $img ?>" alt="<?= $view->alt($data['image'] ?? '', (string) ($data['heading'] ?? '')) ?>" loading="lazy"></div></div>
      <?php endif; ?>
    </div>
  </div>
</section>
