<?php $smtp = (array) ($mail['smtp'] ?? []); ?>
<div class="row g-3">
  <div class="col-xl-7">
    <div class="card"><div class="card-header">Email</div><div class="card-body">
      <form data-ajax id="mailForm" action="<?= e($admin->url('settings/mail')) ?>" novalidate autocomplete="off">
        <?= csrf_field() ?>
        <div class="alert alert-danger form-alert d-none py-2 small"></div>
        <div class="mb-3"><label class="form-label">Send enquiry notifications to</label><input class="form-control" name="notify_to" value="<?= e($mail['notify_to'] ?? '') ?>" placeholder="<?= e($siteEmail ?: 'you@example.com') ?>">
          <div class="form-text">One or more addresses, separated by commas. Empty = the website email<?= $siteEmail ? ' (' . e($siteEmail) . ')' : '' ?>.</div></div>
        <div class="row g-3 mb-3">
          <div class="col-md-6"><label class="form-label">Sender name</label><input class="form-control" name="from_name" value="<?= e($mail['from_name'] ?? '') ?>" maxlength="100" placeholder="Website name"></div>
          <div class="col-md-6"><label class="form-label">Sender email</label><input class="form-control" type="email" name="from_email" value="<?= e($mail['from_email'] ?? '') ?>" placeholder="no-reply@yourdomain.com"><div class="form-text">Use an address on your own domain.</div></div>
        </div>
        <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="smtp_enabled" value="1" id="smtpOn" <?= !empty($smtp['enabled']) ? 'checked' : '' ?>><label class="form-check-label" for="smtpOn">Send through an SMTP server (recommended for reliable delivery)</label></div>
        <div id="smtpFields" class="<?= empty($smtp['enabled']) ? 'd-none' : '' ?>">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">SMTP server</label><input class="form-control" name="smtp_host" value="<?= e($smtp['host'] ?? '') ?>" placeholder="mail.yourdomain.com"></div>
            <div class="col-md-3"><label class="form-label">Port</label><input class="form-control" type="number" name="smtp_port" value="<?= e($smtp['port'] ?? 587) ?>"></div>
            <div class="col-md-3"><label class="form-label">Security</label><select class="form-select" name="smtp_encryption"><?php foreach (['tls' => 'TLS (587)', 'ssl' => 'SSL (465)', 'none' => 'None'] as $k => $l): ?><option value="<?= $k ?>" <?= ($smtp['encryption'] ?? 'tls') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label">Username</label><input class="form-control" name="smtp_username" value="<?= e($smtp['username'] ?? '') ?>" autocomplete="off"></div>
            <div class="col-md-6"><label class="form-label">Password</label><input class="form-control" type="password" name="smtp_password" autocomplete="new-password" placeholder="<?= !empty($smtp['password']) ? 'Saved — leave empty to keep' : '' ?>"><div class="form-text">Stored encrypted.</div></div>
          </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3"><button class="btn btn-primary" type="submit">Save email settings</button><button class="btn btn-outline-primary" type="button" data-post="<?= e($admin->url('settings/mail-test')) ?>" data-busy-text="Sending…">Send test email to me</button></div>
      </form>
    </div></div>
  </div>
  <div class="col-xl-5">
    <div class="card"><div class="card-header d-flex align-items-center">Administrators<button class="btn btn-primary btn-sm ms-auto" id="btnAddUser"><i class="bi bi-plus-lg"></i> Add</button></div><div id="userTable"></div>
      <div class="card-footer bg-white small text-muted"><strong>Owner</strong>: everything. <strong>Editor</strong>: pages, media, navigation, appearance and enquiries, but not settings or administrators.</div></div>
  </div>
</div>
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
  <form data-ajax data-reload-table="users" novalidate autocomplete="off">
    <?= csrf_field() ?>
    <div class="modal-header"><h5 class="modal-title">Administrator</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">
      <div class="alert alert-danger form-alert d-none py-2 small"></div>
      <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" maxlength="120" required></div>
      <div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" maxlength="190" required></div>
      <div class="row g-3 mb-3"><div class="col-6"><label class="form-label">Role</label><select class="form-select" name="role"><option value="editor">Editor</option><option value="owner">Owner</option></select></div>
        <div class="col-6"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div></div>
      <div class="mb-1"><label class="form-label">Password</label><input class="form-control" type="password" name="password" autocomplete="new-password"><div class="form-text pw-hint"></div></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
  </form>
</div></div></div>
