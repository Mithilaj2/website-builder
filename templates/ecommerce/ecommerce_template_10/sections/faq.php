<section <?= $view->attrs($section, 'ppl-faq ppl-faq-center') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="ppl-narrow"><?= $view->partial('accordion', ['items' => $data['items'] ?? [], 'sid' => $section['id']]) ?></div>
  </div>
</section>
