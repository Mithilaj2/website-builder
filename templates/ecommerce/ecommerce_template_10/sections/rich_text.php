<section <?= $view->attrs($section, 'ppl-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'ppl-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="ppl-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="ppl-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
