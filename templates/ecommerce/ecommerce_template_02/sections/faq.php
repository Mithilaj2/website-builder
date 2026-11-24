<section <?= $view->attrs($section, 'kth-faq kth-faq-center') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="kth-narrow"><?= $view->partial('accordion', ['items' => $data['items'] ?? [], 'sid' => $section['id']]) ?></div>
  </div>
</section>
