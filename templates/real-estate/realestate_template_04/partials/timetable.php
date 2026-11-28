<div class="mrs-timetable">
  <dl>
    <?php foreach ($rows as $r): ?><div class="mrs-timetable-row"><dt><?= $view->e($r['label'] ?? '') ?></dt><dd><?= $view->e($r['hours'] ?? '') ?></dd></div><?php endforeach; ?>
  </dl>
  <?php if ($note !== ''): ?><p class="mrs-timetable-note"><i class="bi bi-info-circle"></i><span><?= $view->text($note) ?></span></p><?php endif; ?>
</div>
