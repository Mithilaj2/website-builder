<section <?= $view->attrs($section, 'lvh-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'lvh-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="lvh-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="lvh-prose"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
