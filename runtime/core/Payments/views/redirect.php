<?php
/** @var array $action @var array $tx @var callable $e @var callable $inr */
?>
<div class="wbpay-card wbpay-center">
  <h1 class="wbpay-title">Complete your payment</h1>
  <p class="wbpay-amount-big"><?= $e($inr((int) $tx['amount'])) ?></p>
  <p class="wbpay-muted"><?= $e($tx['purpose']) ?> · Ref. <?= $e($tx['reference']) ?></p>
  <?php if ($action['type'] === 'form'): ?>
    <form method="post" action="<?= $e($action['url']) ?>" data-wbpay-autosubmit>
      <?php foreach ($action['fields'] as $k => $val): ?><input type="hidden" name="<?= $e($k) ?>" value="<?= $e($val) ?>">
      <?php endforeach; ?>
      <p class="wbpay-muted">Taking you to <?= $e($gatewayName) ?>…</p>
      <button type="submit" class="wbpay-btn">Continue to <?= $e($gatewayName) ?></button>
    </form>
  <?php else: ?>
    <div id="wbPay" data-gateway="<?= $e($action['gateway']) ?>" data-options="<?= $e(json_encode($action['options'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>" data-cancel="<?= $e($cancel) ?>"></div>
    <p class="wbpay-muted" data-wbpay-wait>Opening <?= $e($gatewayName) ?>…</p>
    <button type="button" class="wbpay-btn" data-wbpay-open>Pay <?= $e($inr((int) $tx['amount'])) ?> now</button>
    <script src="<?= $e($action['script']) ?>"></script>
  <?php endif; ?>
  <p class="wbpay-cancel"><a href="<?= $e($cancel) ?>">Cancel and go back</a></p>
</div>
