<?php $photo = $view->img($m['photo'] ?? ''); ?>
<article class="kkc-member">
  <div class="kkc-member-photo"><?php if ($photo !== ''): ?><img src="<?= $photo ?>" alt="<?= $view->e($m['name'] ?? '') ?>" loading="lazy"><?php else: ?><span><?= $view->e(mb_strtoupper(mb_substr((string) ($m['name'] ?? '?'), 0, 1))) ?></span><?php endif; ?>
    <?php if (preg_match('#^https://#', (string) ($m['linkedin'] ?? ''))): ?><a class="kkc-member-link" href="<?= $view->e($m['linkedin']) ?>" target="_blank" rel="noopener" aria-label="<?= $view->e(($m['name'] ?? '') . ' on LinkedIn') ?>"><i class="bi bi-linkedin"></i></a><?php endif; ?></div>
  <div class="kkc-member-body">
    <h3 class="kkc-card-title"><?= $view->e($m['name'] ?? '') ?></h3>
    <?php if (($m['role'] ?? '') !== ''): ?><span class="kkc-member-role"><?= $view->e($m['role']) ?></span><?php endif; ?>
    <?php if (($m['bio'] ?? '') !== ''): ?><p><?= $view->text($m['bio']) ?></p><?php endif; ?>
  </div>
</article>
