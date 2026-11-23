<?php
$rows = (array) ($data['rows'] ?? []);
$hlLabel = trim((string) ($data['highlight_label'] ?? ''));
$hlValue = trim((string) ($data['highlight_value'] ?? ''));
$emergency = (string) $view->site('phone');
?>
<section <?= $view->attrs($section, 'sfr-hours') ?>>
  <div class="container">
    <div class="sfr-hours-box">
      <div class="row g-4 g-lg-5 align-items-center">
        <div class="col-lg-5">
          <?= $view->partial('section-head', ['data' => $data, 'align' => 'start']) ?>
          <?php if ($hlLabel !== '' || $hlValue !== ''): ?>
            <div class="sfr-hours-highlight">
              <span class="sfr-pulse" aria-hidden="true"></span>
              <span><small><?= $view->e($hlLabel) ?></small><strong><?= $view->e($hlValue) ?></strong></span>
              <?php if ($emergency !== ''): ?><a class="btn sfr-btn sfr-btn-primary btn-sm ms-auto" href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $emergency)) ?>"><i class="bi bi-telephone"></i><?= $view->e($emergency) ?></a><?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="col-lg-7">
          <div class="sfr-timetable">
            <dl>
              <?php foreach ($rows as $r): ?>
                <div class="sfr-timetable-row"><dt><?= $view->e($r['label'] ?? '') ?></dt><dd><?= $view->e($r['hours'] ?? '') ?></dd></div>
              <?php endforeach; ?>
            </dl>
            <?php if (($data['note'] ?? '') !== ''): ?><p class="sfr-timetable-note"><i class="bi bi-info-circle"></i><span><?= $view->text($data['note']) ?></span></p><?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
