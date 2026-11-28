<section <?= $view->attrs($section, 'nvk-team nvk-team-round') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4 justify-content-center">
      <?php foreach ((array) ($data['members'] ?? []) as $m): ?><div class="col-sm-6 col-lg-3"><?= $view->partial('member', ['m' => $m]) ?></div><?php endforeach; ?>
    </div>
  </div>
</section>
