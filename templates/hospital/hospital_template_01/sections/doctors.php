<section <?= $view->attrs($section, 'crl-doctors') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4 justify-content-center">
      <?php foreach ((array) ($data['items'] ?? []) as $d): $photo = $view->img($d['photo'] ?? ''); $name = (string) ($d['name'] ?? ''); ?>
        <div class="col-sm-6 col-lg-3">
          <article class="crl-doctor">
            <div class="crl-doctor-photo">
              <?php if ($photo !== ''): ?><img src="<?= $photo ?>" alt="<?= $view->e($name) ?>" loading="lazy"><?php else: ?><span class="crl-doctor-initial"><?= $view->e(mb_strtoupper(mb_substr(preg_replace('/^Dr\.?\s*/i', '', $name) ?: '?', 0, 1))) ?></span><?php endif; ?>
              <?php if (($d['specialty'] ?? '') !== ''): ?><span class="crl-chip"><?= $view->e($d['specialty']) ?></span><?php endif; ?>
            </div>
            <div class="crl-doctor-body">
              <h3 class="crl-card-title"><?= $view->e($name) ?></h3>
              <?php if (($d['qualification'] ?? '') !== ''): ?><p class="crl-doctor-qual"><?= $view->e($d['qualification']) ?></p><?php endif; ?>
              <ul class="crl-doctor-meta">
                <?php if (($d['experience'] ?? '') !== ''): ?><li><i class="bi bi-award"></i><?= $view->e($d['experience']) ?> experience</li><?php endif; ?>
                <?php if (($d['timings'] ?? '') !== ''): ?><li><i class="bi bi-clock"></i><?= $view->e($d['timings']) ?></li><?php endif; ?>
              </ul>
              <?php if ($view->hasLink($d['link'] ?? [])): ?><a class="btn crl-btn crl-btn-soft btn-sm w-100 justify-content-center" href="<?= $view->href($d['link']) ?>"<?= $view->linkAttrs($d['link']) ?>><i class="bi bi-calendar2-plus"></i><?= $view->e($d['link']['text']) ?></a><?php endif; ?>
            </div>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if ($view->hasLink($data['button'] ?? [])): ?><div class="text-center mt-5"><a class="btn crl-btn crl-btn-outline" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a></div><?php endif; ?>
  </div>
</section>
