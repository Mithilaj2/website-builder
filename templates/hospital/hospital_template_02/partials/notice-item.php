<?php $t = strtotime((string) ($n['date'] ?? '')); ?>
<li>
  <?php if ($t): ?><time datetime="<?= $view->e(date('Y-m-d', $t)) ?>"><b><?= date('d', $t) ?></b><?= date('M', $t) ?></time><?php else: ?><time><i class="bi bi-megaphone"></i></time><?php endif; ?>
  <div>
    <?php if ($view->hasLink($n['link'] ?? [])): ?><a href="<?= $view->href($n['link']) ?>"<?= $view->linkAttrs($n['link']) ?>><?= $view->e($n['title'] ?? '') ?></a><?php else: ?><span><?= $view->e($n['title'] ?? '') ?></span><?php endif; ?>
    <?php if (!empty($n['is_new'])): ?><em class="srh-new">New</em><?php endif; ?>
  </div>
</li>
