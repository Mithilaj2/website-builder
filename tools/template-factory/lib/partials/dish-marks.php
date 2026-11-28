<?php
/* Diet mark (inline, before the dish name) or spice + label row, for one dish. */
$d = $dish ?? [];
$dt = (string) ($d['diet'] ?? '');
$sp = (string) ($d['spice'] ?? '0');
$diet = ['veg' => 'Vegetarian', 'nonveg' => 'Non-vegetarian', 'egg' => 'Contains egg', 'vegan' => 'Vegan'];
$spice = ['1' => 'Mild', '2' => 'Medium spicy', '3' => 'Hot'];
?>
<?php if (($part ?? '') === 'diet'): ?>
  <?php if (isset($diet[$dt])): ?><span class="{{p}}-diet is-<?= $dt ?>" title="<?= $diet[$dt] ?>"><span class="visually-hidden"><?= $diet[$dt] ?></span></span><?php endif; ?>
<?php elseif (isset($spice[$sp]) || ($d['tag'] ?? '') !== ''): ?>
  <div class="{{p}}-dish-meta">
    <?php if (isset($spice[$sp])): ?><span class="{{p}}-spice" title="<?= $spice[$sp] ?>"><?= str_repeat('<i class="bi bi-fire" aria-hidden="true"></i>', (int) $sp) ?><span class="visually-hidden"><?= $spice[$sp] ?></span></span><?php endif; ?>
    <?php if (($d['tag'] ?? '') !== ''): ?><span class="{{p}}-dish-tag"><?= $view->e($d['tag']) ?></span><?php endif; ?>
  </div>
<?php endif; ?>
