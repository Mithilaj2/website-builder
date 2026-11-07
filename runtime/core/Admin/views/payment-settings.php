<?php
/** @var array $settings @var array $gateways @var array $webhooks */
$p = $settings['page'];
$purposeText = implode("\n", array_map(static fn ($o) => $o['label'] . ($o['amount'] > 0 ? ' | ' . rtrim(rtrim(number_format($o['amount'] / 100, 2, '.', ''), '0'), '.') : ''), $p['purposes']));
?>
<form data-ajax id="paySettingsForm" action="<?= e($admin->url('payments/settings')) ?>" novalidate autocomplete="off">
  <?= csrf_field() ?>
  <div class="alert alert-danger form-alert d-none py-2 small"></div>
  <div class="row g-3">
    <div class="col-xl-7">
      <div class="card mb-3"><div class="card-header">Gateway</div><div class="card-body">
        <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" role="switch" name="enabled" value="1" id="payOn" <?= $settings['enabled'] ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="payOn">Accept online payments on this website</label></div>
        <div class="row g-3 mb-2">
          <div class="col-md-7"><label class="form-label">Payment gateway</label>
            <select class="form-select" name="gateway" id="payGateway"><option value="">Choose…</option>
              <?php foreach ($gateways as $id => $g): ?><option value="<?= e($id) ?>" <?= $settings['gateway'] === $id ? 'selected' : '' ?>><?= e($g['name']) ?></option><?php endforeach; ?>
            </select></div>
          <div class="col-md-5"><label class="form-label">Mode</label>
            <div class="btn-group w-100" role="group" aria-label="Mode">
              <input type="radio" class="btn-check" name="mode" id="modeTest" value="test" <?= $settings['mode'] !== 'live' ? 'checked' : '' ?>><label class="btn btn-outline-warning" for="modeTest">Test</label>
              <input type="radio" class="btn-check" name="mode" id="modeLive" value="live" <?= $settings['mode'] === 'live' ? 'checked' : '' ?>><label class="btn btn-outline-success" for="modeLive">Live</label>
            </div></div>
        </div>
        <div class="small text-muted mb-1" id="modeHelp">Test mode uses the gateway’s sandbox: no real money moves. Switch to Live only after a successful test payment.</div>
        <?php if (!$https): ?><div class="alert alert-warning small py-2 mt-2 mb-0" id="httpsWarn"><i class="bi bi-shield-exclamation me-1"></i>This website is not on HTTPS. Turn on SSL (cPanel → SSL/TLS Status → Run AutoSSL) before taking live payments.</div><?php endif; ?>
      </div></div>

      <?php foreach ($gateways as $id => $g): ?>
      <div class="card mb-3 gw-keys" data-gateway="<?= e($id) ?>" hidden>
        <div class="card-header d-flex align-items-center"><?= e($g['name']) ?> keys</div>
        <div class="card-body">
          <?php if ($g['note']): ?><p class="small text-muted"><?= e($g['note']) ?></p><?php endif; ?>
          <?php foreach (['test' => 'Test keys', 'live' => 'Live keys'] as $mode => $label): ?>
            <fieldset class="mode-keys border rounded-3 p-3 mb-3" data-mode="<?= $mode ?>">
              <legend class="float-none w-auto px-2 fs-6 mb-0"><span class="badge text-bg-<?= $mode === 'live' ? 'success' : 'warning' ?>"><?= e($label) ?></span></legend>
              <div class="row g-2">
              <?php foreach ($g['fields'] as $f): $name = "keys[$mode][$id][{$f['key']}]"; $val = (string) ($settings['keys'][$mode][$id][$f['key']] ?? ''); ?>
                <div class="col-md-6">
                  <label class="form-label small mb-1"><?= e($f['label']) ?><?= $f['required'] ? '' : ' <span class="text-muted">(optional)</span>' ?></label>
                  <?php if ($f['secret']): ?>
                    <input class="form-control form-control-sm font-monospace" type="password" name="<?= e($name) ?>" autocomplete="new-password" placeholder="<?= $val === '__saved__' ? 'Saved — leave empty to keep' : '' ?>">
                  <?php else: ?>
                    <input class="form-control form-control-sm font-monospace" name="<?= e($name) ?>" value="<?= e($val) ?>" spellcheck="false">
                  <?php endif; ?>
                  <?php if (!empty($f['help'])): ?><div class="form-text"><?= e($f['help']) ?></div><?php endif; ?>
                </div>
              <?php endforeach; ?>
              </div>
              <button type="button" class="btn btn-outline-secondary btn-sm mt-2" data-test-connection data-gateway="<?= e($id) ?>" data-mode="<?= $mode ?>" data-busy-text="Checking…"><i class="bi bi-plug me-1"></i>Save and test <?= $mode ?> keys</button>
            </fieldset>
          <?php endforeach; ?>
          <div class="small"><div class="fw-semibold mb-1">Webhook address <span class="text-muted fw-normal">(recommended)</span></div>
            <div class="input-group input-group-sm mb-1"><input class="form-control font-monospace" readonly value="<?= e($webhooks[$id]) ?>" aria-label="Webhook address"><button class="btn btn-outline-secondary" type="button" data-copy="<?= e($webhooks[$id]) ?>">Copy</button></div>
            <div class="text-muted"><?= e($g['webhook_help']) ?> Webhooks confirm payments even if the customer closes the browser before returning to the website.</div></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="col-xl-5">
      <div class="card mb-3"><div class="card-header">“Pay online” page</div><div class="card-body">
        <p class="small">Address: <a href="<?= e($payUrl) ?>" target="_blank" rel="noopener"><?= e($payUrl) ?></a>. Link to it from buttons and menus with <code>page:pay</code>.</p>
        <div class="mb-3"><label class="form-label">Page title</label><input class="form-control" name="page[title]" value="<?= e($p['title']) ?>" maxlength="80"></div>
        <div class="mb-3"><label class="form-label">Introduction</label><textarea class="form-control" name="page[intro]" rows="2" maxlength="600"><?= e($p['intro']) ?></textarea></div>
        <div class="mb-2"><label class="form-label">Payment options <span class="text-muted small">(one per line; add “| amount” for a fixed price)</span></label>
          <textarea class="form-control font-monospace" name="page[purposes]" rows="5" placeholder="Consultation fee | 500&#10;Advance for booking | 2000&#10;Invoice payment"><?= e($purposeText) ?></textarea></div>
        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="page[custom_purpose]" value="1" id="customPurpose" <?= $p['custom_purpose'] ? 'checked' : '' ?>><label class="form-check-label" for="customPurpose">Let customers type what the payment is for and enter any amount</label></div>
        <div class="row g-2 mb-3">
          <div class="col-6"><label class="form-label">Minimum (₹)</label><input class="form-control" type="number" min="1" name="page[min_amount]" value="<?= (int) $p['min_amount'] ?>"></div>
          <div class="col-6"><label class="form-label">Maximum (₹)</label><input class="form-control" type="number" min="1" name="page[max_amount]" value="<?= (int) $p['max_amount'] ?>"></div>
        </div>
        <div class="mb-1"><label class="form-label">Message after a successful payment</label><input class="form-control" name="page[success_message]" value="<?= e($p['success_message']) ?>" maxlength="300"></div>
      </div></div>
      <div class="card"><div class="card-body">
        <button class="btn btn-primary w-100" type="submit">Save payment settings</button>
        <p class="small text-muted mt-2 mb-0">Keys are stored encrypted with this website’s own key and are never shown again. Customers enter card and bank details on the gateway’s page, never on this website. Receipts are emailed using Settings → Email.</p>
      </div></div>
    </div>
  </div>
</form>
