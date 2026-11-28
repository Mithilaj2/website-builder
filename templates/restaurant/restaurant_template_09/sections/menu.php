<?php
/* Food menu: dishes grouped by "course" in order of first appearance; tabs (with JS) or a full list. */
$courses = [];
foreach ((array) ($data['items'] ?? []) as $it) {
    $c = trim((string) ($it['course'] ?? '')) ?: 'Menu';
    $courses[$c][] = $it;
}
$tabs = ($data['layout'] ?? 'tabs') === 'tabs' && count($courses) > 1;
$photos = !empty($data['show_images']);
$sid = (int) $section['id'];
$n = 0;
?>
<section <?= $view->attrs($section, 'sks-menu sks-menu-cards') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <?php if (!$courses): ?>
      <p class="sks-lead text-center">The menu will appear here once dishes are added.</p>
    <?php else: ?>
    <div class="sks-menu-wrap"<?= $tabs ? ' data-sks-menu' : '' ?>>
      <?php if ($tabs): ?>
        <div class="sks-menu-tabs" role="tablist" aria-label="Courses">
          <?php foreach (array_keys($courses) as $i => $c): ?><button type="button" role="tab" id="m<?= $sid ?>-t<?= $i ?>" aria-controls="m<?= $sid ?>-p<?= $i ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"><?= $view->e($c) ?></button><?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php foreach ($courses as $c => $dishes): $i = $n++; ?>
        <div class="sks-menu-course"<?= $tabs ? ' role="tabpanel" id="m' . $sid . '-p' . $i . '" aria-labelledby="m' . $sid . '-t' . $i . '"' : '' ?>>
          <?php if (count($courses) > 1): ?><h3 class="sks-menu-course-title"><?= $view->e($c) ?></h3><?php endif; ?>
          <div class="row g-4">
            <?php foreach ($dishes as $d): $img = $photos ? $view->img($d['image'] ?? '') : ''; ?>
              <div class="col-sm-6 col-lg-4">
                <article class="sks-dish-card sks-card">
                  <?php if ($img !== ''): ?><div class="sks-dish-photo"><img src="<?= $img ?>" alt="<?= $view->alt($d['image'] ?? '', (string) ($d['name'] ?? '')) ?>" loading="lazy"><?php if (($d['price'] ?? '') !== ''): ?><span class="sks-dish-price"><?= $view->e($d['price']) ?></span><?php endif; ?></div><?php endif; ?>
                  <div class="sks-dish-body">
                    <h4 class="sks-dish-name"><?= $view->partial('dish-marks', ['dish' => $d, 'part' => 'diet']) ?><?= $view->e($d['name'] ?? '') ?></h4>
                    <?php if ($img === '' && ($d['price'] ?? '') !== ''): ?><span class="sks-dish-price is-inline"><?= $view->e($d['price']) ?></span><?php endif; ?>
                    <?php if (($d['description'] ?? '') !== ''): ?><p class="sks-dish-desc"><?= $view->e($d['description']) ?></p><?php endif; ?>
                    <?= $view->partial('dish-marks', ['dish' => $d]) ?>
                  </div>
                </article>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?= $view->partial('menu-foot', ['data' => $data]) ?>
    <?php endif; ?>
  </div>
</section>
