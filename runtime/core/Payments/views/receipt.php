<?php
/** @var array $tx @var array $settings @var callable $e @var callable $inr */
$st = $tx['status'];
?>
<div class="wbpay-card wbpay-receipt wbpay-<?= $e($st) ?>">
  <?php if ($st === 'paid'): ?>
    <div class="wbpay-icon wbpay-icon-ok" aria-hidden="true">✓</div>
    <h1 class="wbpay-title">Payment successful</h1>
    <p class="wbpay-lead"><?= $e($settings['page']['success_message']) ?></p>
  <?php elseif ($st === 'pending' || $st === 'created'): ?>
    <meta http-equiv="refresh" content="6;url=<?= $e($self) ?>">
    <div class="wbpay-icon wbpay-icon-wait" aria-hidden="true">…</div>
    <h1 class="wbpay-title">Confirming your payment</h1>
    <p class="wbpay-lead">We are waiting for confirmation from <?= $e($gatewayName) ?>. This page updates by itself. If money was deducted, it will be confirmed shortly — please do not pay again.</p>
  <?php else: ?>
    <div class="wbpay-icon wbpay-icon-fail" aria-hidden="true">!</div>
    <h1 class="wbpay-title"><?= $st === 'cancelled' ? 'Payment cancelled' : 'Payment not completed' ?></h1>
    <p class="wbpay-lead"><?= $st === 'cancelled' ? 'You cancelled the payment. Nothing was charged.' : 'The payment did not go through. If money was deducted, your bank will return it automatically.' ?></p>
  <?php endif; ?>
  <dl class="wbpay-details">
    <dt>Amount</dt><dd><?= $e($inr((int) $tx['amount'])) ?></dd>
    <dt>For</dt><dd><?= $e($tx['purpose']) ?></dd>
    <dt>Reference</dt><dd><?= $e($tx['reference']) ?></dd>
    <?php if ($st === 'paid' && $tx['gateway_payment_id']): ?><dt>Payment ID</dt><dd><?= $e($tx['gateway_payment_id']) ?></dd><?php endif; ?>
    <dt>Name</dt><dd><?= $e($tx['customer_name']) ?></dd>
    <dt><?= $st === 'paid' ? 'Paid on' : 'Date' ?></dt><dd><?= $e(date('d M Y, h:i A', strtotime((string) ($tx['paid_at'] ?: $tx['created_at'])))) ?></dd>
  </dl>
  <?php if ($tx['mode'] === 'test'): ?><div class="wbpay-alert wbpay-alert-warn">Test mode: no real money was charged.</div><?php endif; ?>
  <?php if ($st === 'paid'): ?>
    <p class="wbpay-muted">A receipt has been emailed to <?= $e($tx['customer_email']) ?>. Keep the reference for your records.</p>
    <p class="wbpay-actions"><button type="button" class="wbpay-btn wbpay-btn-light" data-wbpay-print>Print receipt</button></p>
  <?php elseif ($st === 'failed' || $st === 'cancelled'): ?>
    <p class="wbpay-actions"><a class="wbpay-btn" href="<?= $e($retry) ?>">Try again</a></p>
  <?php endif; ?>
</div>
