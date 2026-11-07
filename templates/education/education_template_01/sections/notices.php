<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'lyc-notices') ?>>
  <div class="container">
    <div class="lyc-board">
      <div class="lyc-board-label">
        <i class="bi bi-pin-angle-fill"></i>
        <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="lyc-eyebrow"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
        <h2 class="lyc-board-title"><?= $view->e($data['heading'] ?? '') ?></h2>
        <?php if (($data['description'] ?? '') !== ''): ?><p><?= $view->text($data['description']) ?></p><?php endif; ?>
        <?php if ($view->hasLink($data['button'] ?? [])): ?><a class="lyc-link" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
      </div>
      <ul class="lyc-board-list">
        <?php foreach ($items as $n): $t = strtotime((string) ($n['date'] ?? '')); ?>
          <li>
            <?php if ($t): ?><time datetime="<?= $view->e(date('Y-m-d', $t)) ?>"><b><?= date('d', $t) ?></b><?= date('M', $t) ?></time><?php else: ?><time><i class="bi bi-megaphone"></i></time><?php endif; ?>
            <div>
              <?php if ($view->hasLink($n['link'] ?? [])): ?><a href="<?= $view->href($n['link']) ?>"<?= $view->linkAttrs($n['link']) ?>><?= $view->e($n['title'] ?? '') ?></a><?php else: ?><span><?= $view->e($n['title'] ?? '') ?></span><?php endif; ?>
              <?php if (!empty($n['is_new'])): ?><em class="lyc-new">New</em><?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>
