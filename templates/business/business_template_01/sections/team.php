<section <?= $view->attrs($section, 'mrd-team') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4 justify-content-center">
      <?php foreach ((array) ($data['members'] ?? []) as $m): $photo = $view->img($m['photo'] ?? ''); $li = (string) ($m['linkedin'] ?? ''); ?>
        <div class="col-sm-6 col-lg-3">
          <article class="mrd-member">
            <div class="mrd-member-photo">
              <?php if ($photo !== ''): ?><img src="<?= $photo ?>" alt="<?= $view->e($m['name'] ?? '') ?>" loading="lazy"><?php else: ?><span aria-hidden="true"><?= $view->e(mb_strtoupper(mb_substr((string) ($m['name'] ?? '?'), 0, 1))) ?></span><?php endif; ?>
              <?php if ($li !== '' && preg_match('#^https://#', $li)): ?><a class="mrd-member-social" href="<?= $view->e($li) ?>" target="_blank" rel="noopener" aria-label="LinkedIn profile of <?= $view->e($m['name'] ?? '') ?>"><i class="bi bi-linkedin"></i></a><?php endif; ?>
            </div>
            <h3 class="mrd-card-title"><?= $view->e($m['name'] ?? '') ?></h3>
            <span class="mrd-member-role"><?= $view->e($m['role'] ?? '') ?></span>
            <?php if (($m['bio'] ?? '') !== ''): ?><p><?= $view->text($m['bio']) ?></p><?php endif; ?>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
