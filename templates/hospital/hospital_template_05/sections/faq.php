<section <?= $view->attrs($section, 'lhc-faq lhc-faq-center') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="lhc-narrow"><?= $view->partial('accordion', ['items' => $data['items'] ?? [], 'sid' => $section['id']]) ?></div>
  </div>
</section>
