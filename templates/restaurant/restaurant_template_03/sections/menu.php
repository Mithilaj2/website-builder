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
<section <?= $view->attrs($section, 'atr-menu atr-menu-board') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <?php if (!$courses): ?>
      <p class="atr-lead text-center">The menu will appear here once dishes are added.</p>
    <?php else: ?>
    <div class="atr-menu-wrap"<?= $tabs ? ' data-atr-menu' : '' ?>>
      <?php if ($tabs): ?>
        <div class="atr-menu-tabs" role="tablist" aria-label="Courses">
          <?php foreach (array_keys($courses) as $i => $c): ?><button type="button" role="tab" id="m<?= $sid ?>-t<?= $i ?>" aria-controls="m<?= $sid ?>-p<?= $i ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"><?= $view->e($c) ?></button><?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php foreach ($courses as $c => $dishes): $i = $n++; ?>
        <div class="atr-menu-course"<?= $tabs ? ' role="tabpanel" id="m' . $sid . '-p' . $i . '" aria-labelledby="m' . $sid . '-t' . $i . '"' : '' ?>>
          <?php if (count($courses) > 1): ?><h3 class="atr-menu-course-title"><?= $view->e($c) ?></h3><?php endif; ?>
          <ul class="atr-dishes">
            <?php foreach ($dishes as $d): $img = $photos ? $view->img($d['image'] ?? '') : ''; ?>
              <li class="atr-dish<?= $img !== '' ? ' has-img' : '' ?>">
                <?php if ($img !== ''): ?><img class="atr-dish-img" src="<?= $img ?>" alt="<?= $view->alt($d['image'] ?? '', (string) ($d['name'] ?? '')) ?>" loading="lazy"><?php endif; ?>
                <div class="atr-dish-body">
                  <div class="atr-dish-line">
                    <h4 class="atr-dish-name"><?= $view->partial('dish-marks', ['dish' => $d, 'part' => 'diet']) ?><?= $view->e($d['name'] ?? '') ?></h4>
                    <span class="atr-dish-dots" aria-hidden="true"></span>
                    <?php if (($d['price'] ?? '') !== ''): ?><span class="atr-dish-price"><?= $view->e($d['price']) ?></span><?php endif; ?>
                  </div>
                  <?php if (($d['description'] ?? '') !== ''): ?><p class="atr-dish-desc"><?= $view->e($d['description']) ?></p><?php endif; ?>
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
