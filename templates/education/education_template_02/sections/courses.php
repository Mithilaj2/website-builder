<section <?= $view->attrs($section, 'lac-courses lac-courses-cards') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4 justify-content-center">
      <?php foreach ((array) ($data['items'] ?? []) as $item): $img = $view->img($item['image'] ?? ''); ?>
        <div class="col-md-6 col-lg-4">
          <article class="lac-card lac-course">
            <div class="lac-course-img">
              <?php if ($img !== ''): ?><img src="<?= $img ?>" alt="<?= $view->alt($item['image'] ?? '', (string) ($item['title'] ?? '')) ?>" loading="lazy"><?php else: ?><span class="lac-course-ph"><i class="bi bi-mortarboard"></i></span><?php endif; ?>
              <?php if (($item['level'] ?? '') !== ''): ?><span class="lac-tag"><?= $view->e($item['level']) ?></span><?php endif; ?>
            </div>
            <div class="lac-course-body">
              <h3 class="lac-card-title"><?= $view->e($item['title'] ?? '') ?></h3>
              <?php if (($item['description'] ?? '') !== ''): ?><p><?= $view->text($item['description']) ?></p><?php endif; ?>
              <?= $view->partial('course-meta', ['item' => $item]) ?>
              <?php if ($view->hasLink($item['link'] ?? [])): ?><a class="lac-link" href="<?= $view->href($item['link']) ?>"<?= $view->linkAttrs($item['link']) ?>><?= $view->e($item['link']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
            </div>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if ($view->hasLink($data['button'] ?? [])): ?><div class="lac-actions justify-content-center"><a class="btn lac-btn lac-btn-ghost" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a></div><?php endif; ?>
  </div>
</section>
