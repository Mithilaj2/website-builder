<?php
$rows = (array) ($data['rows'] ?? []);
$hlLabel = trim((string) ($data['highlight_label'] ?? ''));
$hlValue = trim((string) ($data['highlight_value'] ?? ''));
$emergency = (string) $view->site('emergency_phone');
?>
<section <?= $view->attrs($section, 'ltw-hours ltw-hours-band') ?>>
  <div class="container">
    <div class="row g-4 g-lg-5">
      <div class="col-lg-4">
        <?= $view->partial('section-head', ['data' => $data, 'align' => 'start']) ?>
      </div>
      <div class="col-lg-8">
        <?= $view->partial('timetable', ['rows' => $rows, 'note' => (string) ($data['note'] ?? '')]) ?>
        <?= $view->partial('hours-highlight', ['label' => $hlLabel, 'value' => $hlValue, 'emergency' => $emergency]) ?>
      </div>
    </div>
  </div>
</section>
