<div class="ccm-event-meta">
  <?php if ($t): ?><span><i class="bi bi-calendar3"></i><?= date('l', $t) ?></span><?php endif; ?>
  <?php if (($ev['time'] ?? '') !== ''): ?><span><i class="bi bi-clock"></i><?= $view->e($ev['time']) ?></span><?php endif; ?>
  <?php if (($ev['location'] ?? '') !== ''): ?><span><i class="bi bi-geo-alt"></i><?= $view->e($ev['location']) ?></span><?php endif; ?>
</div>
