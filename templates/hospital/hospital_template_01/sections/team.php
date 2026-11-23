<section <?= $view->attrs($section, 'crl-team') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4 justify-content-center">
      <?php foreach ((array) ($data['members'] ?? []) as $m): $photo = $view->img($m['photo'] ?? ''); ?>
        <div class="col-sm-6 col-lg-3">
          <article class="crl-member">
            <div class="crl-member-photo"><?php if ($photo !== ''): ?><img src="<?= $photo ?>" alt="<?= $view->e($m['name'] ?? '') ?>" loading="lazy"><?php else: ?><span><?= $view->e(mb_strtoupper(mb_substr((string) ($m['name'] ?? '?'), 0, 1))) ?></span><?php endif; ?></div>
            <h3 class="crl-card-title"><?= $view->e($m['name'] ?? '') ?></h3>
            <?php if (($m['role'] ?? '') !== ''): ?><span class="crl-member-role"><?= $view->e($m['role']) ?></span><?php endif; ?>
            <?php if (($m['bio'] ?? '') !== ''): ?><p><?= $view->text($m['bio']) ?></p><?php endif; ?>
            <?php if (preg_match('#^https://#', (string) ($m['linkedin'] ?? ''))): ?><a class="crl-member-link" href="<?= $view->e($m['linkedin']) ?>" target="_blank" rel="noopener" aria-label="<?= $view->e(($m['name'] ?? '') . ' on LinkedIn') ?>"><i class="bi bi-linkedin"></i></a><?php endif; ?>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
