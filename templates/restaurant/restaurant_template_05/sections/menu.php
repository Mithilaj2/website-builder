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
<section <?= $view->attrs($section, 'eao-menu eao-menu-board') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <?php if (!$courses): ?>
      <p class="eao-lead text-center">The menu will appear here once dishes are added.</p>
    <?php else: ?>
    <div class="eao-menu-wrap"<?= $tabs ? ' data-eao-menu' : '' ?>>
      <?php if ($tabs): ?>
        <div class="eao-menu-tabs" role="tablist" aria-label="Courses">
          <?php foreach (array_keys($courses) as $i => $c): ?><button type="button" role="tab" id="m<?= $sid ?>-t<?= $i ?>" aria-controls="m<?= $sid ?>-p<?= $i ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"><?= $view->e($c) ?></button><?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php foreach ($courses as $c => $dishes): $i = $n++; ?>
        <div class="eao-menu-course"<?= $tabs ? ' role="tabpanel" id="m' . $sid . '-p' . $i . '" aria-labelledby="m' . $sid . '-t' . $i . '"' : '' ?>>
          <?php if (count($courses) > 1): ?><h3 class="eao-menu-course-title"><?= $view->e($c) ?></h3><?php endif; ?>
          <ul class="eao-dishes">
            <?php foreach ($dishes as $d): $img = $photos ? $view->img($d['image'] ?? '') : ''; ?>
              <li class="eao-dish<?= $img !== '' ? ' has-img' : '' ?>">
                <?php if ($img !== ''): ?><img class="eao-dish-img" src="<?= $img ?>" alt="<?= $view->alt($d['image'] ?? '', (string) ($d['name'] ?? '')) ?>" loading="lazy"><?php endif; ?>
                <div class="eao-dish-body">
                  <div class="eao-dish-line">
                    <h4 class="eao-dish-name"><?= $view->partial('dish-marks', ['dish' => $d, 'part' => 'diet']) ?><?= $view->e($d['name'] ?? '') ?></h4>
                    <span class="eao-dish-dots" aria-hidden="true"></span>
                    <?php if (($d['price'] ?? '') !== ''): ?><span class="eao-dish-price"><?= $view->e($d['price']) ?></span><?php endif; ?>
                  </div>
                  <?php if (($d['description'] ?? '') !== ''): ?><p class="eao-dish-desc"><?= $view->e($d['description']) ?></p><?php endif; ?>
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
