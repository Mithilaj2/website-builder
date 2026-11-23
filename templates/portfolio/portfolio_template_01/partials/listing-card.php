<?php
/* One project: image, title, client and the card facts (year, industry). */
$it = $item ?? [];
$img = $view->img($it['image'] ?? '');
$facts = array_values(array_filter((array) ($it['facts'] ?? []), static fn ($f) => $f['card']));
$url = $view->listingUrl($it);
?>
<article class="skl-work<?= !empty($it['is_closed']) ? ' is-closed' : '' ?>">
  <a class="skl-work-img" href="<?= $view->e($url) ?>" tabindex="-1" aria-hidden="true">
    <?php if ($img !== ''): ?><img src="<?= $img ?>" alt="" loading="lazy"><?php endif; ?>
    <span class="skl-work-go"><i class="bi bi-arrow-up-right"></i></span>
    <?php if (($it['badge'] ?? '') !== ''): ?><span class="skl-work-badge"><?= $view->e($it['badge']) ?></span><?php endif; ?>
  </a>
  <div class="skl-work-body">
    <h3 class="skl-work-title"><a href="<?= $view->e($url) ?>"><?= $view->e($it['title'] ?? '') ?></a></h3>
    <p class="skl-work-meta">
      <?php if (($it['category_name'] ?? '') !== ''): ?><span><?= $view->e($it['category_name']) ?></span><?php endif; ?>
      <?php foreach ($facts as $f): ?><span><?= $view->e($f['value']) ?></span><?php endforeach; ?>
    </p>
  </div>
</article>
