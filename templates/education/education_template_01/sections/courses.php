<section <?= $view->attrs($section, 'lyc-courses') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4">
      <?php foreach ((array) ($data['items'] ?? []) as $item): $img = $view->img($item['image'] ?? ''); ?>
        <div class="col-md-6 col-lg-4">
          <article class="lyc-card lyc-course">
            <div class="lyc-course-img">
              <?php if ($img !== ''): ?><img src="<?= $img ?>" alt="<?= $view->alt($item['image'] ?? '', (string) ($item['title'] ?? '')) ?>" loading="lazy"><?php else: ?><span class="lyc-course-ph"><i class="bi bi-mortarboard"></i></span><?php endif; ?>
              <?php if (($item['level'] ?? '') !== ''): ?><span class="lyc-tag"><?= $view->e($item['level']) ?></span><?php endif; ?>
            </div>
            <div class="lyc-course-body">
              <h3 class="lyc-card-title"><?= $view->e($item['title'] ?? '') ?></h3>
              <?php if (($item['description'] ?? '') !== ''): ?><p><?= $view->text($item['description']) ?></p><?php endif; ?>
              <div class="lyc-course-meta">
                <?php if (($item['duration'] ?? '') !== ''): ?><span><i class="bi bi-hourglass-split"></i><?= $view->e($item['duration']) ?></span><?php endif; ?>
                <?php if (($item['fee'] ?? '') !== ''): ?><span><i class="bi bi-wallet2"></i><?= $view->e($item['fee']) ?></span><?php endif; ?>
              </div>
              <?php if ($view->hasLink($item['link'] ?? [])): ?><a class="lyc-link stretched-link" href="<?= $view->href($item['link']) ?>"<?= $view->linkAttrs($item['link']) ?>><?= $view->e($item['link']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
            </div>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if ($view->hasLink($data['button'] ?? [])): ?><div class="text-center mt-5"><a class="btn lyc-btn lyc-btn-outline" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a></div><?php endif; ?>
  </div>
</section>
