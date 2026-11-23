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
$diet = ['veg' => 'Vegetarian', 'nonveg' => 'Non-vegetarian', 'egg' => 'Contains egg', 'vegan' => 'Vegan'];
$spice = ['1' => 'Mild', '2' => 'Medium spicy', '3' => 'Hot'];
$n = 0;
?>
<section <?= $view->attrs($section, 'sfr-menu') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <?php if (!$courses): ?>
      <p class="sfr-muted text-center">The menu will appear here once dishes are added.</p>
    <?php else: ?>
    <div class="sfr-menu-wrap"<?= $tabs ? ' data-sfr-menu' : '' ?>>
      <?php if ($tabs): ?>
        <div class="sfr-menu-tabs" role="tablist" aria-label="Courses">
          <?php foreach (array_keys($courses) as $i => $c): ?><button type="button" role="tab" id="m<?= $sid ?>-t<?= $i ?>" aria-controls="m<?= $sid ?>-p<?= $i ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"><?= $view->e($c) ?></button><?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php foreach ($courses as $c => $dishes): $i = $n++; ?>
        <div class="sfr-menu-course"<?= $tabs ? ' role="tabpanel" id="m' . $sid . '-p' . $i . '" aria-labelledby="m' . $sid . '-t' . $i . '"' : '' ?>>
          <?php if (count($courses) > 1): ?><h3 class="sfr-menu-course-title"><?= $view->e($c) ?></h3><?php endif; ?>
          <ul class="sfr-dishes">
            <?php foreach ($dishes as $d): $img = $photos ? $view->img($d['image'] ?? '') : ''; $dt = (string) ($d['diet'] ?? ''); $sp = (string) ($d['spice'] ?? '0'); ?>
              <li class="sfr-dish<?= $img !== '' ? ' has-img' : '' ?>">
                <?php if ($img !== ''): ?><img class="sfr-dish-img" src="<?= $img ?>" alt="<?= $view->alt($d['image'] ?? '', (string) ($d['name'] ?? '')) ?>" loading="lazy"><?php endif; ?>
                <div class="sfr-dish-body">
                  <div class="sfr-dish-line">
                    <h4 class="sfr-dish-name">
                      <?php if (isset($diet[$dt])): ?><span class="sfr-diet is-<?= $dt ?>" title="<?= $diet[$dt] ?>"><span class="visually-hidden"><?= $diet[$dt] ?></span></span><?php endif; ?>
                      <?= $view->e($d['name'] ?? '') ?>
                    </h4>
                    <span class="sfr-dish-dots" aria-hidden="true"></span>
                    <?php if (($d['price'] ?? '') !== ''): ?><span class="sfr-dish-price"><?= $view->e($d['price']) ?></span><?php endif; ?>
                  </div>
                  <?php if (($d['description'] ?? '') !== ''): ?><p class="sfr-dish-desc"><?= $view->e($d['description']) ?></p><?php endif; ?>
                  <?php if (isset($spice[$sp]) || ($d['tag'] ?? '') !== ''): ?>
                    <div class="sfr-dish-meta">
                      <?php if (isset($spice[$sp])): ?><span class="sfr-spice" title="<?= $spice[$sp] ?>"><?= str_repeat('<i class="bi bi-fire" aria-hidden="true"></i>', (int) $sp) ?><span class="visually-hidden"><?= $spice[$sp] ?></span></span><?php endif; ?>
                      <?php if (($d['tag'] ?? '') !== ''): ?><span class="sfr-dish-tag"><?= $view->e($d['tag']) ?></span><?php endif; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="sfr-menu-foot">
      <ul class="sfr-menu-legend" aria-label="Key">
        <li><span class="sfr-diet is-veg" aria-hidden="true"></span>Vegetarian</li>
        <li><span class="sfr-diet is-nonveg" aria-hidden="true"></span>Non-vegetarian</li>
        <li><i class="bi bi-fire" aria-hidden="true"></i>Spice level</li>
      </ul>
      <?php if (($data['note'] ?? '') !== ''): ?><p class="sfr-menu-note"><?= $view->e($data['note']) ?></p><?php endif; ?>
      <?php if ($view->hasLink($data['button'] ?? [])): ?><a class="btn sfr-btn sfr-btn-outline" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
