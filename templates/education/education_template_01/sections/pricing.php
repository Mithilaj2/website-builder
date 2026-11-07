<section <?= $view->attrs($section, 'lyc-pricing') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4 justify-content-center align-items-stretch">
      <?php foreach ((array) ($data['plans'] ?? []) as $p): $feat = !empty($p['featured']); ?>
        <div class="col-md-6 col-lg-4">
          <div class="lyc-card lyc-plan<?= $feat ? ' is-featured' : '' ?>">
            <?php if ($feat): ?><span class="lyc-plan-flag">Most chosen</span><?php endif; ?>
            <h3 class="lyc-card-title"><?= $view->e($p['name'] ?? '') ?></h3>
            <div class="lyc-plan-price"><strong><?= $view->e($p['price'] ?? '') ?></strong><?php if (($p['period'] ?? '') !== ''): ?><span><?= $view->e($p['period']) ?></span><?php endif; ?></div>
            <ul class="lyc-ticks">
              <?php foreach (preg_split('/\R/', (string) ($p['features'] ?? '')) ?: [] as $f): if (trim($f) === '') { continue; } ?><li><i class="bi bi-check-lg"></i><?= $view->e(trim($f)) ?></li><?php endforeach; ?>
            </ul>
            <?php if ($view->hasLink($p['button'] ?? [])): ?><a class="btn lyc-btn <?= $feat ? 'lyc-btn-gold' : 'lyc-btn-outline' ?> w-100 justify-content-center mt-auto" href="<?= $view->href($p['button']) ?>"<?= $view->linkAttrs($p['button']) ?>><?= $view->e($p['button']['text']) ?></a><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
