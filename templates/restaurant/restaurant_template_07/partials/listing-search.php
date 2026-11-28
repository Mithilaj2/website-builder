<?php
/* Search panel for listings (properties, packages…). Shown in the hero only when the template has listings. */
$lcfg = $view->listingConfig();
if (!$lcfg) {
    return;
}
$types = $view->listingCategories();
$locs = $view->listingLocations();
$for = $lcfg['attributes']['listing_for'] ?? null;
$pick = null;
if (!$for && !$lcfg['location_filter']) {
    foreach ($lcfg['attributes'] as $a) {
        if ($a['filter'] && $a['type'] === 'select') {
            $pick = $a;
            break;
        }
    }
}
$sid = (int) ($sid ?? 0);
if ($lcfg['price_label'] === 'Rent') {
    $budgets = [15000 => 'Up to ₹15,000', 25000 => 'Up to ₹25,000', 40000 => 'Up to ₹40,000', 60000 => 'Up to ₹60,000', 100000 => 'Up to ₹1 Lakh'];
} elseif ($lcfg['price_format'] === 'lakh') {
    $budgets = [2500000 => 'Up to ₹25 Lakh', 5000000 => 'Up to ₹50 Lakh', 10000000 => 'Up to ₹1 Cr', 20000000 => 'Up to ₹2 Cr', 50000000 => 'Up to ₹5 Cr'];
} else {
    $budgets = [15000 => 'Up to ₹15,000', 30000 => 'Up to ₹30,000', 50000 => 'Up to ₹50,000', 100000 => 'Up to ₹1 Lakh', 200000 => 'Up to ₹2 Lakh'];
}
?>
<form class="bnb-lsearch" method="get" action="<?= $view->e($view->listingsUrl()) ?>" role="search" aria-label="Search <?= $view->e(strtolower($lcfg['plural'])) ?>">
  <?php if ($for && count($for['options']) > 1): ?>
    <fieldset class="bnb-lsearch-tabs"><legend class="visually-hidden"><?= $view->e($for['label']) ?></legend>
      <?php foreach ($for['options'] as $i => $o): ?>
        <input type="radio" class="btn-check" name="a[listing_for]" id="ls-<?= $sid ?>-<?= $i ?>" value="<?= $view->e($o) ?>"<?= $i === 0 ? ' checked' : '' ?>><label for="ls-<?= $sid ?>-<?= $i ?>"><?= $view->e($o === 'Sale' ? 'Buy' : $o) ?></label>
      <?php endforeach; ?>
    </fieldset>
  <?php endif; ?>
  <div class="bnb-lsearch-row">
    <label class="bnb-lsearch-f"><span><?= $view->e($lcfg['location_filter'] ? $lcfg['location_label'] : 'Search') ?></span>
      <?php if ($lcfg['location_filter'] && count($locs) > 1): ?><select name="location"><option value="">Any <?= $view->e(strtolower($lcfg['location_label'])) ?></option><?php foreach ($locs as $l): ?><option><?= $view->e($l) ?></option><?php endforeach; ?></select>
      <?php else: ?><input type="search" name="q" placeholder="<?= $view->e($lcfg['location_filter'] ? 'Name, area or landmark' : 'Place or trip') ?>" maxlength="60"><?php endif; ?>
    </label>
    <?php if ($types): ?><label class="bnb-lsearch-f"><span><?= $view->e($lcfg['category_label']) ?></span><select name="c"><option value="">Any</option><?php foreach ($types as $t): ?><option value="<?= $view->e($t['slug']) ?>"><?= $view->e($t['name']) ?></option><?php endforeach; ?></select></label><?php endif; ?>
    <?php if ($pick): ?><label class="bnb-lsearch-f"><span><?= $view->e($pick['label']) ?></span><select name="a[<?= $view->e($pick['key']) ?>]"><option value="">Any</option><?php foreach ($pick['options'] as $o): ?><option><?= $view->e($o) ?></option><?php endforeach; ?></select></label><?php endif; ?>
    <?php if ($lcfg['price_format'] !== 'none'): ?><label class="bnb-lsearch-f"><span>Budget</span><select name="max"><option value="">Any budget</option><?php foreach ($budgets as $v => $l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?></select></label><?php endif; ?>
    <button type="submit" class="btn bnb-btn bnb-btn-primary bnb-lsearch-go"><i class="bi bi-search" aria-hidden="true"></i>Search</button>
  </div>
</form>
