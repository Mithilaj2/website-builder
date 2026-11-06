<section <?= $view->attrs($section, 'mrd-testimonials') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4 justify-content-center">
      <?php foreach ((array) ($data['items'] ?? []) as $t): $photo = $view->img($t['photo'] ?? ''); $rating = (int) ($t['rating'] ?? 0); ?>
        <div class="col-md-6 col-lg-4">
          <figure class="mrd-card mrd-quote">
            <i class="bi bi-quote mrd-quote-mark" aria-hidden="true"></i>
            <?php if ($rating > 0): ?><div class="mrd-stars" aria-label="<?= $rating ?> out of 5 stars"><?= str_repeat('<i class="bi bi-star-fill"></i>', min(5, $rating)) ?></div><?php endif; ?>
            <blockquote><p><?= $view->text($t['quote'] ?? '') ?></p></blockquote>
            <figcaption>
              <?php if ($photo !== ''): ?><img src="<?= $photo ?>" alt="" loading="lazy"><?php else: ?><span class="mrd-avatar" aria-hidden="true"><?= $view->e(mb_strtoupper(mb_substr((string) ($t['name'] ?? '?'), 0, 1))) ?></span><?php endif; ?>
              <span><strong><?= $view->e($t['name'] ?? '') ?></strong><small><?= $view->e($t['role'] ?? '') ?></small></span>
            </figcaption>
          </figure>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
