<section <?= $view->attrs($section, 'hcr-faq hcr-faq-center') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="hcr-narrow"><?= $view->partial('accordion', ['items' => $data['items'] ?? [], 'sid' => $section['id']]) ?></div>
  </div>
</section>
