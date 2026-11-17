<?php
$bg = $view->img(($data['background_image'] ?? '') ?: ($data['image'] ?? ''));
$highlights = (array) ($data['highlights'] ?? []);
$lcfg = $view->listingConfig();
$types = $lcfg ? $view->listingCategories() : [];
$locs = $lcfg ? $view->listingLocations() : [];
$hasFor = $lcfg && isset($lcfg['attributes']['listing_for']);
$sid = (int) $section['id'];
?>
<section <?= $view->attrs($section, 'kst-hero') ?>>
  <?php if ($bg !== ''): ?><div class="kst-hero-bg" style="background-image:url('<?= $bg ?>')" role="img" aria-label="<?= $view->alt(($data['background_image'] ?? '') ?: ($data['image'] ?? ''), (string) ($data['heading'] ?? '')) ?>"></div><?php endif; ?>
  <div class="container position-relative">
    <div class="kst-hero-copy">
      <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="kst-eyebrow"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
      <h1 class="kst-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
      <?php if (($data['subheading'] ?? '') !== ''): ?><p class="kst-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
      <?php if (!$lcfg): ?><?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'kst-btn-light']) ?><?php endif; ?>
    </div>
    <?php if ($lcfg): ?>
      <form class="kst-search" method="get" action="<?= $view->e($view->listingsUrl()) ?>" role="search" aria-label="Search <?= $view->e(strtolower($lcfg['plural'])) ?>">
        <?php if ($hasFor): ?>
          <fieldset class="kst-search-tabs"><legend class="visually-hidden">Buy or rent</legend>
            <?php foreach ($lcfg['attributes']['listing_for']['options'] as $i => $o): ?>
              <input type="radio" class="btn-check" name="a[listing_for]" id="hs-for-<?= $sid ?>-<?= $i ?>" value="<?= $view->e($o) ?>"<?= $i === 0 ? ' checked' : '' ?>><label for="hs-for-<?= $sid ?>-<?= $i ?>"><?= $view->e($o === 'Sale' ? 'Buy' : ($o === 'Rent' ? 'Rent' : $o)) ?></label>
            <?php endforeach; ?>
          </fieldset>
        <?php endif; ?>
        <div class="kst-search-row">
          <label class="kst-search-f is-wide"><span><?= $view->e($lcfg['location_label']) ?></span>
            <?php if (count($locs) > 1): ?><select name="location"><option value="">All <?= $view->e(strtolower($lcfg['location_label'])) ?>s</option><?php foreach ($locs as $l): ?><option><?= $view->e($l) ?></option><?php endforeach; ?></select>
            <?php else: ?><input type="search" name="q" placeholder="Project, area or landmark" maxlength="60"><?php endif; ?>
          </label>
          <?php if ($types): ?><label class="kst-search-f"><span><?= $view->e($lcfg['category_label']) ?></span><select name="c"><option value="">Any type</option><?php foreach ($types as $t): ?><option value="<?= $view->e($t['slug']) ?>"><?= $view->e($t['name']) ?></option><?php endforeach; ?></select></label><?php endif; ?>
          <label class="kst-search-f"><span>Budget</span><select name="max"><option value="">Any budget</option><?php foreach ([2500000 => 'Up to ₹25 Lakh', 5000000 => 'Up to ₹50 Lakh', 10000000 => 'Up to ₹1 Cr', 20000000 => 'Up to ₹2 Cr', 50000000 => 'Up to ₹5 Cr'] as $v => $l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?></select></label>
          <button type="submit" class="btn kst-btn kst-btn-primary kst-search-go"><i class="bi bi-search"></i>Search</button>
        </div>
      </form>
    <?php endif; ?>
    <?php if ($highlights): ?>
      <ul class="kst-hero-points"><?php foreach ($highlights as $h): ?><li><?= $view->icon(($h['icon'] ?? '') ?: 'bi-check2') ?><span><?= $view->e($h['text'] ?? '') ?></span></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
</section>
