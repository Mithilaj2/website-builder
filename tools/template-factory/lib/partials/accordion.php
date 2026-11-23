<?php $id = '{{p}}Faq' . (int) $sid; ?>
<div class="accordion {{p}}-accordion" id="<?= $id ?>">
  <?php foreach ((array) ($items ?? []) as $i => $q): ?>
    <div class="accordion-item">
      <h3 class="accordion-header"><button class="accordion-button<?= $i ? ' collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $id ?>-<?= $i ?>" aria-expanded="<?= $i ? 'false' : 'true' ?>" aria-controls="<?= $id ?>-<?= $i ?>"><?= $view->e($q['question'] ?? '') ?></button></h3>
      <div id="<?= $id ?>-<?= $i ?>" class="accordion-collapse collapse<?= $i ? '' : ' show' ?>" data-bs-parent="#<?= $id ?>"><div class="accordion-body"><?= $view->text($q['answer'] ?? '') ?></div></div>
    </div>
  <?php endforeach; ?>
</div>
