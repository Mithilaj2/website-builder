<?php if (!$mailReady): ?>
  <div class="alert alert-warning d-flex gap-2 align-items-start"><i class="bi bi-envelope-exclamation fs-5"></i><div>No email address is set for enquiry notifications. Enquiries are still saved here.
  <?php if (($user['role'] ?? '') === 'owner'): ?><a href="<?= e($admin->url('settings')) ?>">Set up email</a><?php endif; ?></div></div>
<?php endif; ?>
<div class="row g-3 mb-4">
  <?php foreach ([['New enquiries', $stats['new'], 'envelope-open', 'cyan', 'enquiries'], ['Active pages', $stats['pages'], 'files', '', 'content'], ['Visible sections', $stats['sections'], 'layout-text-sidebar', 'green', 'content'], ['Images', $stats['media'], 'images', 'amber', 'media']] as [$label, $value, $icon, $cls, $link]): ?>
    <div class="col-6 col-xl-3"><a class="card h-100 text-reset text-decoration-none" href="<?= e($admin->url($link)) ?>"><div class="stat-card"><div class="icon <?= $cls ?>"><i class="bi bi-<?= $icon ?>"></i></div><div><div class="value"><?= (int) $value ?></div><div class="label"><?= e($label) ?></div></div></div></a></div>
  <?php endforeach; ?>
</div>
<div class="row g-3">
  <div class="col-xl-8">
    <div class="card"><div class="card-header d-flex align-items-center">Latest enquiries<a class="btn btn-sm btn-light ms-auto" href="<?= e($admin->url('enquiries')) ?>">All enquiries</a></div>
      <?php if (!$recent): ?><div class="empty-state"><i class="bi bi-inbox"></i>No enquiries yet. Messages sent through your website's contact form appear here.</div><?php else: ?>
      <div class="table-responsive"><table class="table mb-0"><thead><tr><th>From</th><th>Subject</th><th>Status</th><th>Received</th></tr></thead><tbody>
        <?php foreach ($recent as $r): ?><tr><td><div class="fw-semibold"><?= e($r['name']) ?></div><small class="text-muted"><?= e($r['email']) ?></small></td><td><?= e($r['subject'] ?: '—') ?></td><td><span class="badge text-bg-<?= $r['status'] === 'new' ? 'danger' : ($r['status'] === 'replied' ? 'success' : 'secondary') ?>"><?= e(ucfirst($r['status'])) ?></span></td><td class="small"><?= e(date('d M Y, H:i', strtotime($r['created_at']))) ?></td></tr><?php endforeach; ?>
      </tbody></table></div><?php endif; ?>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card"><div class="card-header">Quick actions</div><div class="card-body d-grid gap-2">
      <a class="quick-action" href="<?= e($admin->url('content')) ?>"><i class="bi bi-pencil-square"></i>Edit pages &amp; sections<i class="bi bi-chevron-right ms-auto small text-muted"></i></a>
      <a class="quick-action" href="<?= e($admin->url('media')) ?>"><i class="bi bi-cloud-upload"></i>Upload images<i class="bi bi-chevron-right ms-auto small text-muted"></i></a>
      <a class="quick-action" href="<?= e($admin->url('appearance')) ?>"><i class="bi bi-palette"></i>Colours, logo &amp; contact details<i class="bi bi-chevron-right ms-auto small text-muted"></i></a>
      <a class="quick-action" href="<?= e($base) ?>/" target="_blank" rel="noopener"><i class="bi bi-globe2"></i>View website<i class="bi bi-box-arrow-up-right ms-auto small text-muted"></i></a>
    </div></div>
  </div>
</div>
