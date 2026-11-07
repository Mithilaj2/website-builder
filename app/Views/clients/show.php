<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <a href="<?= e(url('clients')) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>All clients</a>
  <?= status_badge($client['status']) ?>
  <?php if (can('clients.manage')): ?>
    <div class="ms-auto d-flex gap-2">
      <button class="btn btn-outline-primary btn-sm" data-edit-client="<?= (int) $client['id'] ?>"><i class="bi bi-pencil me-1"></i>Edit</button>
      <button class="btn btn-outline-secondary btn-sm" data-post="<?= e(url('clients/' . $client['id'] . '/status')) ?>" data-reload-page="1">
        <?= $client['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
      <?php if (!$websites): ?>
        <button class="btn btn-outline-danger btn-sm" data-post="<?= e(url('clients/' . $client['id'] . '/delete?from=show')) ?>" data-confirm="Delete this client permanently?" data-confirm-text="Delete"><i class="bi bi-trash me-1"></i>Delete</button>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">Client details</div>
      <div class="card-body">
        <dl class="mb-0">
          <dt class="small text-muted">Business name</dt><dd><?= e($client['business_name']) ?></dd>
          <dt class="small text-muted">Contact person</dt><dd><?= e($client['contact_person'] ?: '—') ?></dd>
          <dt class="small text-muted">Email</dt><dd><?= $client['email'] ? '<a href="mailto:' . e($client['email']) . '">' . e($client['email']) . '</a>' : '—' ?></dd>
          <dt class="small text-muted">Phone</dt><dd><?= e($client['phone'] ?: '—') ?></dd>
          <dt class="small text-muted">Address</dt><dd style="white-space:pre-line"><?= e($client['address'] ?: '—') ?></dd>
          <dt class="small text-muted">Notes</dt><dd style="white-space:pre-line"><?= e($client['notes'] ?: '—') ?></dd>
          <dt class="small text-muted">Added</dt><dd class="mb-0"><?= e(format_date($client['created_at'], 'd M Y, H:i')) ?></dd>
        </dl>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header">Websites (<?= count($websites) ?>)</div>
      <?php if (!$websites): ?>
        <div class="empty-state"><i class="bi bi-globe2"></i>No websites for this client yet. The creation wizard arrives in Phase 3.</div>
      <?php else: ?>
        <div class="table-responsive"><table class="table mb-0">
          <thead><tr><th>Website</th><th>Category / Template</th><th>Folder &amp; tables</th><th>Status</th><th>Created</th></tr></thead>
          <tbody>
          <?php foreach ($websites as $w): ?>
            <tr>
              <td><div class="fw-semibold"><?= e($w['name']) ?></div><small class="text-muted"><?= e($w['domain'] ?: '') ?></small></td>
              <td><?= e($w['category']) ?><br><small class="text-muted"><?= e($w['template'] ?: 'Not selected') ?></small></td>
              <td><small><code><?= e($w['folder_path'] ?: '—') ?></code><br>prefix <code><?= e($w['table_prefix'] ?: '—') ?></code></small></td>
              <td><?= status_badge($w['status']) ?></td>
              <td><?= e(format_date($w['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
    </div>
    <div class="card">
      <div class="card-header">Recent activity</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($activity as $a): ?>
          <li class="list-group-item small"><span class="text-muted"><?= e(format_date($a['created_at'], 'd M Y H:i')) ?></span> · <?= e($a['user'] ?: 'System') ?> — <?= e($a['action']) ?></li>
        <?php endforeach; ?>
        <?php if (!$activity): ?><li class="list-group-item small text-muted">No activity recorded.</li><?php endif; ?>
      </ul>
    </div>
  </div>
</div>
<?php if (can('clients.manage')) { include __DIR__ . '/_form.php'; } ?>
