<?php $photo = $view->img($d['photo'] ?? ''); $name = (string) ($d['name'] ?? ''); ?>
<article class="aur-doctor">
  <div class="aur-doctor-photo">
    <?php if ($photo !== ''): ?><img src="<?= $photo ?>" alt="<?= $view->e($name) ?>" loading="lazy"><?php else: ?><span class="aur-doctor-initial"><?= $view->e(mb_strtoupper(mb_substr(preg_replace('/^Dr\.?\s*/i', '', $name) ?: '?', 0, 1))) ?></span><?php endif; ?>
    <?php if (($d['specialty'] ?? '') !== ''): ?><span class="aur-tag"><?= $view->e($d['specialty']) ?></span><?php endif; ?>
  </div>
  <div class="aur-doctor-body">
    <h3 class="aur-card-title"><?= $view->e($name) ?></h3>
    <?php if (($d['qualification'] ?? '') !== ''): ?><p class="aur-doctor-qual"><?= $view->e($d['qualification']) ?></p><?php endif; ?>
    <ul class="aur-doctor-meta">
      <?php if (($d['experience'] ?? '') !== ''): ?><li><i class="bi bi-award"></i><?= $view->e($d['experience']) ?> experience</li><?php endif; ?>
      <?php if (($d['timings'] ?? '') !== ''): ?><li><i class="bi bi-clock"></i><?= $view->e($d['timings']) ?></li><?php endif; ?>
    </ul>
    <?php if ($view->hasLink($d['link'] ?? [])): ?><a class="btn aur-btn aur-btn-ghost aur-doctor-btn" href="<?= $view->href($d['link']) ?>"<?= $view->linkAttrs($d['link']) ?>><i class="bi bi-calendar2-plus"></i><?= $view->e($d['link']['text']) ?></a><?php endif; ?>
  </div>
</article>
