<section <?= $view->attrs($section, 'pcr-courses pcr-courses-list') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="pcr-course-list">
      <?php foreach ((array) ($data['items'] ?? []) as $item): $img = $view->img($item['image'] ?? ''); ?>
        <article class="pcr-course-row<?= $img !== '' ? ' has-image' : '' ?>">
          <?php if ($img !== ''): ?><div class="pcr-course-thumb"><img src="<?= $img ?>" alt="<?= $view->alt($item['image'] ?? '', (string) ($item['title'] ?? '')) ?>" loading="lazy"></div><?php endif; ?>
          <div class="pcr-course-main">
            <?php if (($item['level'] ?? '') !== ''): ?><span class="pcr-tag"><?= $view->e($item['level']) ?></span><?php endif; ?>
            <h3 class="pcr-card-title"><?= $view->e($item['title'] ?? '') ?></h3>
            <?php if (($item['description'] ?? '') !== ''): ?><p><?= $view->text($item['description']) ?></p><?php endif; ?>
          </div>
          <div class="pcr-course-side">
            <?= $view->partial('course-meta', ['item' => $item]) ?>
            <?php if ($view->hasLink($item['link'] ?? [])): ?><a class="btn pcr-btn pcr-btn-ghost" href="<?= $view->href($item['link']) ?>"<?= $view->linkAttrs($item['link']) ?>><?= $view->e($item['link']['text']) ?></a><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <?php if ($view->hasLink($data['button'] ?? [])): ?><div class="pcr-actions justify-content-center"><a class="btn pcr-btn pcr-btn-primary" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a></div><?php endif; ?>
  </div>
</section>
