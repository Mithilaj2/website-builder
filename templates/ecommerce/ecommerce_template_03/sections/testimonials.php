<?php $items = array_values((array) ($data['items'] ?? [])); $first = $items[0] ?? null; $rest = array_slice($items, 1); ?>
<section <?= $view->attrs($section, 'vlt-testimonials vlt-testimonials-featured') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4">
      <?php if ($first): ?><div class="<?= $rest ? 'col-lg-7' : 'col-lg-8 mx-auto' ?>"><?= $view->partial('quote', ['t' => $first, 'big' => true]) ?></div><?php endif; ?>
      <?php if ($rest): ?><div class="col-lg-5"><div class="vlt-quote-stack"><?php foreach ($rest as $t): ?><?= $view->partial('quote', ['t' => $t]) ?><?php endforeach; ?></div></div><?php endif; ?>
    </div>
  </div>
</section>
