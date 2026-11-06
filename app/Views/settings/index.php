<div class="row g-3">
  <div class="col-xl-8">
    <div class="card">
      <div class="card-header">QA access to generated websites</div>
      <div class="card-body">
        <p class="small text-muted">Every generated website is a real, standalone site in <code>generated_websites/</code>. On this server it is protected with a password, so only the team can open it for testing. The generator also uses this address for its automatic website checks.</p>
        <form data-ajax action="<?= e(url('settings')) ?>" novalidate>
          <?= csrf_field() ?>
          <div class="alert alert-danger form-alert d-none py-2 small"></div>
          <div class="mb-3">
            <label class="form-label">Generated websites URL</label>
            <input class="form-control" name="qa_sites_url" value="<?= e($qaUrl) ?>" placeholder="<?= e($qaDefault) ?>">
            <div class="form-text">Leave empty when the builder is installed inside <code>public_html</code> (default shown). If the builder's document root is <code>public/</code>, point a subdomain (e.g. <code>qa.builder.yourdomain.com</code>) at the <code>generated_websites</code> folder and enter its address here.</div>
          </div>
          <button class="btn btn-primary" type="submit">Save</button>
        </form>
        <hr>
        <div class="row g-3 align-items-end">
          <div class="col-sm-4"><label class="form-label">QA username</label><input class="form-control" value="<?= e($qaUser) ?>" readonly></div>
          <div class="col-sm-5"><label class="form-label">QA password</label><input class="form-control font-monospace" value="<?= e($qaPassword) ?>" readonly></div>
          <div class="col-sm-3"><button class="btn btn-outline-danger w-100" data-post="<?= e(url('settings/qa-password')) ?>" data-confirm="Generate a new QA password? The old one stops working immediately.">New password</button></div>
        </div>
      </div>
    </div>
  </div>
</div>
