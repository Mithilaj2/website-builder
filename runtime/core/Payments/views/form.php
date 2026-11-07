<?php
/** @var array $settings @var array $values @var array $errors @var callable $e @var callable $inr */
$p = $settings['page'];
$err = static fn (string $k): string => isset($errors[$k]) ? '<div class="wbpay-error">' . $e($errors[$k]) . '</div>' : '';
$inv = static fn (string $k): string => isset($errors[$k]) ? ' is-invalid' : '';
$v = static fn (string $k): string => $e($values[$k] ?? '');
$opts = $p['purposes'];
$sel = (string) ($values['purpose_option'] ?? '');
if ($sel === '' && $opts && !$p['custom_purpose']) {
    $sel = '0';
}
?>
<div class="wbpay-card">
  <h1 class="wbpay-title"><?= $e($p['title']) ?></h1>
  <?php if ($settings['mode'] === 'test'): ?><div class="wbpay-alert wbpay-alert-warn" role="note"><strong>Test mode:</strong> no real money will be charged.</div><?php endif; ?>
  <?php if ($p['intro'] !== ''): ?><p class="wbpay-intro"><?= nl2br($e($p['intro'])) ?></p><?php endif; ?>
  <?php if ($alert !== ''): ?><div class="wbpay-alert wbpay-alert-error" role="alert"><?= $e($alert) ?></div><?php endif; ?>
  <form method="post" action="<?= $e($action) ?>" class="wbpay-form" novalidate data-wbpay-form>
    <input type="hidden" name="_t" value="<?= $e($token) ?>">
    <div class="wbpay-hp" aria-hidden="true"><label>Leave this empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <?php if ($opts): ?>
      <div class="wbpay-field">
        <label for="wbpay-option">Payment for</label>
        <select id="wbpay-option" name="purpose_option" class="wbpay-input<?= $inv('purpose_option') ?>" data-wbpay-option>
          <?php if ($p['custom_purpose'] || count($opts) > 1): ?><option value="">Choose…</option><?php endif; ?>
          <?php foreach ($opts as $i => $o): ?>
            <option value="<?= $i ?>" data-amount="<?= (int) $o['amount'] ?>" <?= $sel === (string) $i ? 'selected' : '' ?>><?= $e($o['label']) ?><?= $o['amount'] > 0 ? ' — ' . $e($inr((int) $o['amount'])) : '' ?></option>
          <?php endforeach; ?>
          <?php if ($p['custom_purpose']): ?><option value="other" <?= $sel === 'other' ? 'selected' : '' ?>>Something else</option><?php endif; ?>
        </select>
        <?= $err('purpose_option') ?>
      </div>
    <?php endif; ?>
    <?php if ($p['custom_purpose']): ?>
      <div class="wbpay-field" data-wbpay-custom>
        <label for="wbpay-purpose"><?= $opts ? 'Describe the payment' : 'Payment for' ?></label>
        <input id="wbpay-purpose" name="purpose" class="wbpay-input<?= $inv('purpose') ?>" maxlength="190" value="<?= $v('purpose') ?>" placeholder="e.g. Invoice 1042, advance for booking">
        <?= $err('purpose') ?>
      </div>
    <?php endif; ?>
    <div class="wbpay-field" data-wbpay-amount>
      <label for="wbpay-amount">Amount (₹)</label>
      <div class="wbpay-amount"><span aria-hidden="true">₹</span><input id="wbpay-amount" name="amount" class="wbpay-input<?= $inv('amount') ?>" inputmode="decimal" autocomplete="off" value="<?= $v('amount') ?>" placeholder="0.00"></div>
      <div class="wbpay-hint">Between <?= $e($inr($p['min_amount'] * 100)) ?> and <?= $e($inr($p['max_amount'] * 100)) ?>.</div>
      <?= $err('amount') ?>
    </div>
    <div class="wbpay-row">
      <div class="wbpay-field">
        <label for="wbpay-name">Your name</label>
        <input id="wbpay-name" name="name" class="wbpay-input<?= $inv('name') ?>" maxlength="120" autocomplete="name" value="<?= $v('name') ?>" required>
        <?= $err('name') ?>
      </div>
      <div class="wbpay-field">
        <label for="wbpay-phone">Mobile number</label>
        <input id="wbpay-phone" name="phone" class="wbpay-input<?= $inv('phone') ?>" maxlength="20" autocomplete="tel" inputmode="tel" value="<?= $v('phone') ?>" required>
        <?= $err('phone') ?>
      </div>
    </div>
    <div class="wbpay-field">
      <label for="wbpay-email">Email (for your receipt)</label>
      <input id="wbpay-email" type="email" name="email" class="wbpay-input<?= $inv('email') ?>" maxlength="190" autocomplete="email" value="<?= $v('email') ?>" required>
      <?= $err('email') ?>
    </div>
    <div class="wbpay-field">
      <label for="wbpay-note">Note <span class="wbpay-optional">(optional)</span></label>
      <textarea id="wbpay-note" name="note" class="wbpay-input" rows="2" maxlength="500"><?= $v('note') ?></textarea>
    </div>
    <button type="submit" class="wbpay-btn">Continue to payment</button>
    <p class="wbpay-secure"><svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M8 1a3 3 0 0 0-3 3v2H4a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1h-1V4a3 3 0 0 0-3-3Zm-2 5V4a2 2 0 1 1 4 0v2H6Z"/></svg>
      Secure payment by <?= $e($gatewayName) ?>. Your card or bank details are entered on <?= $e($gatewayName) ?>’s page and never reach this website.</p>
  </form>
</div>
