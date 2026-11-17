<?php
$bg = $view->img(($data['background_image'] ?? '') ?: ($data['image'] ?? ''));
$highlights = (array) ($data['highlights'] ?? []);
$lcfg = $view->listingConfig();
$dests = $lcfg ? $view->listingCategories() : [];
$types = $lcfg['attributes']['trip_type']['options'] ?? [];
?>
<section <?= $view->attrs($section, 'hzt-hero') ?>>
  <?php if ($bg !== ''): ?><div class="hzt-hero-bg" style="background-image:url('<?= $bg ?>')" role="img" aria-label="<?= $view->alt(($data['background_image'] ?? '') ?: ($data['image'] ?? ''), (string) ($data['heading'] ?? '')) ?>"></div><?php endif; ?>
  <div class="container position-relative">
    <div class="hzt-hero-copy">
      <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="hzt-eyebrow"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
      <h1 class="hzt-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
      <?php if (($data['subheading'] ?? '') !== ''): ?><p class="hzt-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
      <?php if (!$lcfg): ?><?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'hzt-btn-light']) ?><?php endif; ?>
    </div>
    <?php if ($lcfg): ?>
      <form class="hzt-search" method="get" action="<?= $view->e($view->listingsUrl()) ?>" role="search" aria-label="Find a <?= $view->e(strtolower($lcfg['singular'])) ?>">
        <?php if ($dests): ?>
          <label class="hzt-search-f"><i class="bi bi-geo-alt" aria-hidden="true"></i><span><small>Where to?</small><select name="c"><option value="">Any destination</option><?php foreach ($dests as $d): ?><option value="<?= $view->e($d['slug']) ?>"><?= $view->e($d['name']) ?></option><?php endforeach; ?></select></span></label>
        <?php else: ?>
          <label class="hzt-search-f"><i class="bi bi-geo-alt" aria-hidden="true"></i><span><small>Where to?</small><input type="search" name="q" placeholder="Destination or package" maxlength="60"></span></label>
        <?php endif; ?>
        <?php if ($types): ?><label class="hzt-search-f"><i class="bi bi-compass" aria-hidden="true"></i><span><small>Trip type</small><select name="a[trip_type]"><option value="">Any trip</option><?php foreach ($types as $t): ?><option><?= $view->e($t) ?></option><?php endforeach; ?></select></span></label><?php endif; ?>
        <label class="hzt-search-f"><i class="bi bi-wallet2" aria-hidden="true"></i><span><small>Budget per person</small><select name="max"><option value="">Any budget</option><?php foreach ([15000 => 'Up to ₹15,000', 30000 => 'Up to ₹30,000', 50000 => 'Up to ₹50,000', 100000 => 'Up to ₹1 lakh', 200000 => 'Up to ₹2 lakh'] as $v => $l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?></select></span></label>
        <button type="submit" class="btn hzt-btn hzt-btn-primary hzt-search-go"><i class="bi bi-search"></i>Find trips</button>
      </form>
    <?php endif; ?>
    <?php if ($highlights): ?>
      <ul class="hzt-hero-points"><?php foreach ($highlights as $h): ?><li><?= $view->icon(($h['icon'] ?? '') ?: 'bi-check2') ?><span><?= $view->e($h['text'] ?? '') ?></span></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
</section>
