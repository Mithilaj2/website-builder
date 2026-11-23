<section <?= $view->attrs($section, 'chi-faq chi-faq-center') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="chi-narrow"><?= $view->partial('accordion', ['items' => $data['items'] ?? [], 'sid' => $section['id']]) ?></div>
  </div>
</section>
