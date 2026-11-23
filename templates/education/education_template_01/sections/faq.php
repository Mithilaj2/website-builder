<?php $id = 'lycFaq' . (int) $section['id']; ?>
<section <?= $view->attrs($section, 'lyc-faq') ?>>
  <div class="container">
    <div class="row g-4 g-lg-5">
      <div class="col-lg-4"><?= $view->partial('section-head', ['data' => $data, 'align' => 'start']) ?></div>
      <div class="col-lg-8">
        <div class="accordion lyc-accordion" id="<?= $id ?>">
          <?php foreach ((array) ($data['items'] ?? []) as $i => $q): ?>
            <div class="accordion-item">
              <h3 class="accordion-header"><button class="accordion-button<?= $i ? ' collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $id ?>-<?= $i ?>" aria-expanded="<?= $i ? 'false' : 'true' ?>" aria-controls="<?= $id ?>-<?= $i ?>"><?= $view->e($q['question'] ?? '') ?></button></h3>
              <div id="<?= $id ?>-<?= $i ?>" class="accordion-collapse collapse<?= $i ? '' : ' show' ?>" data-bs-parent="#<?= $id ?>"><div class="accordion-body"><?= $view->text($q['answer'] ?? '') ?></div></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>
