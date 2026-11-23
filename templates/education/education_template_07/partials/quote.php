<?php $photo = $view->img($t['photo'] ?? ''); $r = (int) ($t['rating'] ?? 0); ?>
<figure class="rsp-card rsp-quote<?= !empty($big) ? ' is-big' : '' ?>">
  <span class="rsp-quote-mark" aria-hidden="true"><i class="bi bi-quote"></i></span>
  <?php if ($r > 0): ?><div class="rsp-stars" aria-label="<?= min(5, $r) ?> out of 5"><?= str_repeat('<i class="bi bi-star-fill"></i>', min(5, $r)) ?></div><?php endif; ?>
  <blockquote><p><?= $view->text($t['quote'] ?? '') ?></p></blockquote>
  <figcaption><?php if ($photo !== ''): ?><img src="<?= $photo ?>" alt="" loading="lazy"><?php else: ?><span class="rsp-avatar"><?= $view->e(mb_strtoupper(mb_substr((string) ($t['name'] ?? '?'), 0, 1))) ?></span><?php endif; ?>
    <span><strong><?= $view->e($t['name'] ?? '') ?></strong><?php if (($t['role'] ?? '') !== ''): ?><small><?= $view->e($t['role']) ?></small><?php endif; ?></span></figcaption>
</figure>
