<?php $sid = 'faq' . (int) ($section['id'] ?? 0); ?>
<section <?= $view->attrs($section, 'mrd-faq') ?>>
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-4"><?= $view->partial('section-head', ['data' => $data, 'align' => 'start']) ?></div>
      <div class="col-lg-8">
        <div class="accordion mrd-accordion" id="<?= $sid ?>">
          <?php foreach ((array) ($data['items'] ?? []) as $i => $q): $id = $sid . '-' . $i; ?>
            <div class="accordion-item">
              <h3 class="accordion-header"><button class="accordion-button<?= $i ? ' collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $id ?>" aria-expanded="<?= $i ? 'false' : 'true' ?>" aria-controls="<?= $id ?>"><?= $view->e($q['question'] ?? '') ?></button></h3>
              <div id="<?= $id ?>" class="accordion-collapse collapse<?= $i ? '' : ' show' ?>" data-bs-parent="#<?= $sid ?>"><div class="accordion-body"><?= $view->text($q['answer'] ?? '') ?></div></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>
