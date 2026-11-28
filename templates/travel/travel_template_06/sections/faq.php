<section <?= $view->attrs($section, 'jts-faq jts-faq-split') ?>>
  <div class="container">
    <div class="row g-4 g-lg-5">
      <div class="col-lg-4"><?= $view->partial('section-head', ['data' => $data, 'align' => 'start']) ?></div>
      <div class="col-lg-8"><?= $view->partial('accordion', ['items' => $data['items'] ?? [], 'sid' => $section['id']]) ?></div>
    </div>
  </div>
</section>
