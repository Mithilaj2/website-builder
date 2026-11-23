<section <?= $view->attrs($section, 'apx-courses apx-courses-list') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="apx-course-list">
      <?php foreach ((array) ($data['items'] ?? []) as $item): $img = $view->img($item['image'] ?? ''); ?>
        <article class="apx-course-row<?= $img !== '' ? ' has-image' : '' ?>">
          <?php if ($img !== ''): ?><div class="apx-course-thumb"><img src="<?= $img ?>" alt="<?= $view->alt($item['image'] ?? '', (string) ($item['title'] ?? '')) ?>" loading="lazy"></div><?php endif; ?>
          <div class="apx-course-main">
            <?php if (($item['level'] ?? '') !== ''): ?><span class="apx-tag"><?= $view->e($item['level']) ?></span><?php endif; ?>
            <h3 class="apx-card-title"><?= $view->e($item['title'] ?? '') ?></h3>
            <?php if (($item['description'] ?? '') !== ''): ?><p><?= $view->text($item['description']) ?></p><?php endif; ?>
          </div>
          <div class="apx-course-side">
            <?= $view->partial('course-meta', ['item' => $item]) ?>
            <?php if ($view->hasLink($item['link'] ?? [])): ?><a class="btn apx-btn apx-btn-ghost" href="<?= $view->href($item['link']) ?>"<?= $view->linkAttrs($item['link']) ?>><?= $view->e($item['link']['text']) ?></a><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <?php if ($view->hasLink($data['button'] ?? [])): ?><div class="apx-actions justify-content-center"><a class="btn apx-btn apx-btn-primary" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a></div><?php endif; ?>
  </div>
</section>
