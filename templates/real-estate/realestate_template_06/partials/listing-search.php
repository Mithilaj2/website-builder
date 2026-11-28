<?php
/* Search panel for listings (properties, packages…). Shown in the hero only when the template has listings. */
$lcfg = $view->listingConfig();
if (!$lcfg) {
    return;
}
$types = $view->listingCategories();
$locs = $view->listingLocations();
$for = $lcfg['attributes']['listing_for'] ?? null;
$sid = (int) ($sid ?? 0);
$budgets = $lcfg['price_format'] === 'lakh' && $lcfg['price_label'] !== 'Rent'
    ? [2500000 => 'Up to ₹25 Lakh', 5000000 => 'Up to ₹50 Lakh', 10000000 => 'Up to ₹1 Cr', 20000000 => 'Up to ₹2 Cr', 50000000 => 'Up to ₹5 Cr']
    : [15000 => 'Up to ₹15,000', 25000 => 'Up to ₹25,000', 40000 => 'Up to ₹40,000', 60000 => 'Up to ₹60,000', 100000 => 'Up to ₹1 Lakh'];
?>
<form class="hhv-lsearch" method="get" action="<?= $view->e($view->listingsUrl()) ?>" role="search" aria-label="Search <?= $view->e(strtolower($lcfg['plural'])) ?>">
  <?php if ($for && count($for['options']) > 1): ?>
    <fieldset class="hhv-lsearch-tabs"><legend class="visually-hidden"><?= $view->e($for['label']) ?></legend>
      <?php foreach ($for['options'] as $i => $o): ?>
        <input type="radio" class="btn-check" name="a[listing_for]" id="ls-<?= $sid ?>-<?= $i ?>" value="<?= $view->e($o) ?>"<?= $i === 0 ? ' checked' : '' ?>><label for="ls-<?= $sid ?>-<?= $i ?>"><?= $view->e($o === 'Sale' ? 'Buy' : $o) ?></label>
      <?php endforeach; ?>
    </fieldset>
  <?php endif; ?>
  <div class="hhv-lsearch-row">
    <label class="hhv-lsearch-f"><span><?= $view->e($lcfg['location_label']) ?></span>
      <?php if (count($locs) > 1): ?><select name="location"><option value="">Any <?= $view->e(strtolower($lcfg['location_label'])) ?></option><?php foreach ($locs as $l): ?><option><?= $view->e($l) ?></option><?php endforeach; ?></select>
      <?php else: ?><input type="search" name="q" placeholder="Name, area or landmark" maxlength="60"><?php endif; ?>
    </label>
    <?php if ($types): ?><label class="hhv-lsearch-f"><span><?= $view->e($lcfg['category_label']) ?></span><select name="c"><option value="">Any</option><?php foreach ($types as $t): ?><option value="<?= $view->e($t['slug']) ?>"><?= $view->e($t['name']) ?></option><?php endforeach; ?></select></label><?php endif; ?>
    <?php if ($lcfg['price_format'] !== 'none'): ?><label class="hhv-lsearch-f"><span>Budget</span><select name="max"><option value="">Any budget</option><?php foreach ($budgets as $v => $l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?></select></label><?php endif; ?>
    <button type="submit" class="btn hhv-btn hhv-btn-primary hhv-lsearch-go"><i class="bi bi-search" aria-hidden="true"></i>Search</button>
  </div>
</form>
