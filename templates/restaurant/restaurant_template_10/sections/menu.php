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
<section <?= $view->attrs($section, 'pdd-menu pdd-menu-board') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <?php if (!$courses): ?>
      <p class="pdd-lead text-center">The menu will appear here once dishes are added.</p>
    <?php else: ?>
    <div class="pdd-menu-wrap"<?= $tabs ? ' data-pdd-menu' : '' ?>>
      <?php if ($tabs): ?>
        <div class="pdd-menu-tabs" role="tablist" aria-label="Courses">
          <?php foreach (array_keys($courses) as $i => $c): ?><button type="button" role="tab" id="m<?= $sid ?>-t<?= $i ?>" aria-controls="m<?= $sid ?>-p<?= $i ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"><?= $view->e($c) ?></button><?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php foreach ($courses as $c => $dishes): $i = $n++; ?>
        <div class="pdd-menu-course"<?= $tabs ? ' role="tabpanel" id="m' . $sid . '-p' . $i . '" aria-labelledby="m' . $sid . '-t' . $i . '"' : '' ?>>
          <?php if (count($courses) > 1): ?><h3 class="pdd-menu-course-title"><?= $view->e($c) ?></h3><?php endif; ?>
          <ul class="pdd-dishes">
            <?php foreach ($dishes as $d): $img = $photos ? $view->img($d['image'] ?? '') : ''; ?>
              <li class="pdd-dish<?= $img !== '' ? ' has-img' : '' ?>">
                <?php if ($img !== ''): ?><img class="pdd-dish-img" src="<?= $img ?>" alt="<?= $view->alt($d['image'] ?? '', (string) ($d['name'] ?? '')) ?>" loading="lazy"><?php endif; ?>
                <div class="pdd-dish-body">
                  <div class="pdd-dish-line">
                    <h4 class="pdd-dish-name"><?= $view->partial('dish-marks', ['dish' => $d, 'part' => 'diet']) ?><?= $view->e($d['name'] ?? '') ?></h4>
                    <span class="pdd-dish-dots" aria-hidden="true"></span>
                    <?php if (($d['price'] ?? '') !== ''): ?><span class="pdd-dish-price"><?= $view->e($d['price']) ?></span><?php endif; ?>
                  </div>
                  <?php if (($d['description'] ?? '') !== ''): ?><p class="pdd-dish-desc"><?= $view->e($d['description']) ?></p><?php endif; ?>
                  <?= $view->partial('dish-marks', ['dish' => $d]) ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
    <?= $view->partial('menu-foot', ['data' => $data]) ?>
    <?php endif; ?>
  </div>
</section>
