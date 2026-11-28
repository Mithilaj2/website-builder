<?php
$rows = (array) ($data['rows'] ?? []);
$hlLabel = trim((string) ($data['highlight_label'] ?? ''));
$hlValue = trim((string) ($data['highlight_value'] ?? ''));
$emergency = (string) $view->site('emergency_phone');
?>
<section <?= $view->attrs($section, 'atr-hours atr-hours-panel') ?>>
  <div class="container">
    <div class="atr-hours-box">
      <div class="row g-4 g-lg-5 align-items-center">
        <div class="col-lg-5">
          <?= $view->partial('section-head', ['data' => $data, 'align' => 'start']) ?>
          <?= $view->partial('hours-highlight', ['label' => $hlLabel, 'value' => $hlValue, 'emergency' => $emergency]) ?>
        </div>
        <div class="col-lg-7"><?= $view->partial('timetable', ['rows' => $rows, 'note' => (string) ($data['note'] ?? '')]) ?></div>
      </div>
    </div>
  </div>
</section>
