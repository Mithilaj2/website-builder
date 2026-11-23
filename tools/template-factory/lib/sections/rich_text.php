<section <?= $view->attrs($section, '{{p}}-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : '{{p}}-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="{{p}}-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="{{p}}-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
