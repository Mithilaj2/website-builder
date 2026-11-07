<section <?= $view->attrs($section, 'trm-testimonials') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4 justify-content-center">
      <?php foreach ((array) ($data['items'] ?? []) as $t): $photo = $view->img($t['photo'] ?? ''); $r = (int) ($t['rating'] ?? 0); ?>
        <div class="col-md-6 col-lg-4">
          <figure class="trm-quote">
            <?php if ($r > 0): ?><div class="trm-stars" aria-label="<?= $r ?> out of 5"><?= str_repeat('<i class="bi bi-star-fill"></i>', min(5, $r)) ?></div><?php endif; ?>
            <blockquote><?= $view->text($t['quote'] ?? '') ?></blockquote>
            <figcaption><?php if ($photo !== ''): ?><img src="<?= $photo ?>" alt="" loading="lazy"><?php else: ?><span class="trm-avatar"><?= $view->e(mb_strtoupper(mb_substr((string) ($t['name'] ?? '?'), 0, 1))) ?></span><?php endif; ?>
              <span><strong><?= $view->e($t['name'] ?? '') ?></strong><?php if (($t['role'] ?? '') !== ''): ?><small><i class="bi bi-patch-check-fill"></i> <?= $view->e($t['role']) ?></small><?php endif; ?></span></figcaption>
          </figure>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
