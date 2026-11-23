<?php if (($item['duration'] ?? '') !== '' || ($item['fee'] ?? '') !== ''): ?>
<div class="pcr-course-meta">
  <?php if (($item['duration'] ?? '') !== ''): ?><span><i class="bi bi-hourglass-split"></i><?= $view->e($item['duration']) ?></span><?php endif; ?>
  <?php if (($item['fee'] ?? '') !== ''): ?><span><i class="bi bi-wallet2"></i><?= $view->e($item['fee']) ?></span><?php endif; ?>
</div>
<?php endif; ?>
