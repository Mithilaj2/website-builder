<section <?= $view->attrs($section, 'crl-stats') ?>>
  <div class="container">
    <div class="crl-stats-band">
      <svg class="crl-stats-pulse" viewBox="0 0 1200 80" preserveAspectRatio="none" aria-hidden="true"><path d="M0 40h430l18-30 22 60 20-48 14 18h696" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="crl-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="row g-0 justify-content-center position-relative">
        <?php foreach ((array) ($data['items'] ?? []) as $item): ?>
          <div class="col-6 col-lg-3">
            <div class="crl-stat">
              <?php if (($item['icon'] ?? '') !== ''): ?><span class="crl-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?>
              <strong><span data-crl-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong>
              <span><?= $view->e($item['label'] ?? '') ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
