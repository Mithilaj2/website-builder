<?php
$bg = $view->img(($data['background_image'] ?? '') ?: ($data['image'] ?? ''));
$highlights = (array) ($data['highlights'] ?? []);
$lcfg = $view->listingConfig();
$types = $lcfg ? $view->listingCategories() : [];
$views = $lcfg['attributes']['view']['options'] ?? [];
$sid = (int) $section['id'];
?>
<section <?= $view->attrs($section, 'lvh-hero') ?>>
  <?php if ($bg !== ''): ?><div class="lvh-hero-bg" style="background-image:url('<?= $bg ?>')" role="img" aria-label="<?= $view->alt(($data['background_image'] ?? '') ?: ($data['image'] ?? ''), (string) ($data['heading'] ?? '')) ?>"></div><?php endif; ?>
  <div class="container position-relative">
    <div class="lvh-hero-copy">
      <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="lvh-eyebrow"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
      <h1 class="lvh-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
      <?php if (($data['subheading'] ?? '') !== ''): ?><p class="lvh-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
      <?php if (!$lcfg): ?><?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'lvh-btn-light']) ?><?php endif; ?>
    </div>
  </div>
  <?php if ($lcfg): ?>
    <div class="container position-relative">
      <form class="lvh-finder" method="get" action="<?= $view->e($view->listingsUrl()) ?>" role="search" aria-label="Find a <?= $view->e(strtolower($lcfg['singular'])) ?>">
        <?php if ($types): ?><label class="lvh-finder-f"><span><?= $view->e($lcfg['category_label']) ?></span><select name="c"><option value="">All <?= $view->e(strtolower($lcfg['plural'])) ?></option><?php foreach ($types as $t): ?><option value="<?= $view->e($t['slug']) ?>"><?= $view->e($t['name']) ?></option><?php endforeach; ?></select></label><?php endif; ?>
        <?php if (isset($lcfg['attributes']['guests'])): ?><label class="lvh-finder-f"><span>Guests</span><select name="a[guests]"><option value="">Any</option><?php foreach ([1, 2, 3, 4, 6] as $g): ?><option value="<?= $g ?>"><?= $g ?><?= $g > 1 ? '+ guests' : ' guest' ?></option><?php endforeach; ?></select></label><?php endif; ?>
        <?php if ($views): ?><label class="lvh-finder-f"><span>View</span><select name="a[view]"><option value="">Any view</option><?php foreach ($views as $v): ?><option><?= $view->e($v) ?></option><?php endforeach; ?></select></label><?php endif; ?>
        <button type="submit" class="btn lvh-btn lvh-btn-primary lvh-finder-go">See <?= $view->e(strtolower($lcfg['plural'])) ?> &amp; rates</button>
      </form>
      <?php if ($highlights): ?><ul class="lvh-hero-points"><?php foreach ($highlights as $h): ?><li><?= $view->icon(($h['icon'] ?? '') ?: 'bi-dot') ?><span><?= $view->e($h['text'] ?? '') ?></span></li><?php endforeach; ?></ul><?php endif; ?>
    </div>
  <?php endif; ?>
</section>
