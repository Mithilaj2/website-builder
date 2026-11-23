<?php
/** @var ?array $item @var array $images @var array $cfg @var array $categories @var array $config @var string $publicUrl */
$it = $item ?? ['id' => 0, 'title' => '', 'slug' => '', 'location' => '', 'price' => null, 'price_note' => '', 'short_description' => '', 'description' => '', 'badge' => '', 'is_featured' => 0, 'status' => 'active', 'sort_order' => 0, 'category_id' => null, 'attrs' => []];
$rupees = static fn ($p): string => $p === null || $p === '' ? '' : (string) intdiv((int) $p, 100);
?>
<script type="application/json" id="listingConfig"><?= json_encode(['tinymce' => $config['urls']['tinymce'], 'images' => $images], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <a class="btn btn-sm btn-light" href="<?= e($admin->url('listings')) ?>"><i class="bi bi-arrow-left"></i> <?= e($cfg['plural']) ?></a>
  <?php if ($it['id']): ?><a class="btn btn-sm btn-light ms-auto" href="<?= e($publicUrl . '/' . rawurlencode((string) $it['slug'])) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i>View on website</a><?php endif; ?>
</div>
<form data-ajax action="<?= e($admin->url($it['id'] ? 'api/listings/' . $it['id'] : 'api/listings')) ?>" id="listingForm" novalidate>
  <div class="alert alert-danger form-alert d-none py-2 small"></div>
  <div class="row g-3">
    <div class="col-xl-8">
      <div class="card mb-3"><div class="card-body">
        <div class="mb-3"><label class="form-label">Title *</label><input class="form-control" name="title" maxlength="150" required value="<?= e($it['title']) ?>"></div>
        <div class="row g-3 mb-3">
          <div class="<?= $cfg['price_format'] === 'none' ? 'col-12' : 'col-md-6' ?>"><label class="form-label"><?= e($cfg['location_label']) ?></label><input class="form-control" name="location" maxlength="120" value="<?= e((string) $it['location']) ?>"></div>
          <?php if ($cfg['price_format'] !== 'none'): ?><div class="col-md-3"><label class="form-label"><?= e($cfg['price_label']) ?> (₹)</label><input class="form-control" name="price" inputmode="numeric" value="<?= e($rupees($it['price'])) ?>" placeholder="On request"></div>
          <div class="col-md-3"><label class="form-label">Price note</label><input class="form-control" name="price_note" maxlength="40" value="<?= e((string) $it['price_note']) ?>" list="priceNotes" placeholder="e.g. onwards"><datalist id="priceNotes"><?php foreach ($cfg['price_notes'] as $n): ?><option value="<?= e($n) ?>"><?php endforeach; ?></datalist></div><?php endif; ?>
        </div>
        <div class="mb-3"><label class="form-label">Summary</label><textarea class="form-control" name="short_description" rows="2" maxlength="400"><?= e((string) $it['short_description']) ?></textarea><div class="form-text">Shown at the top of the page and in search results.</div></div>
        <div><label class="form-label">Full description</label><textarea class="form-control" name="description" id="listingDesc" rows="8"><?= e((string) $it['description']) ?></textarea></div>
      </div></div>
      <?php if ($cfg['attributes']): ?>
      <div class="card mb-3"><div class="card-header">Details</div><div class="card-body"><div class="row g-3">
        <?php foreach ($cfg['attributes'] as $key => $a): $v = $it['attrs'][$key] ?? null; $name = 'attr[' . $key . ']'; ?>
          <?php if ($a['type'] === 'list'): ?>
            <div class="col-12"><label class="form-label"><?= e($a['label']) ?></label><textarea class="form-control" name="<?= e($name) ?>" rows="4"><?= e(implode("\n", (array) $v)) ?></textarea><div class="form-text">One per line.<?= $a['help'] !== '' ? ' ' . e($a['help']) : '' ?></div></div>
          <?php elseif ($a['type'] === 'toggle'): ?>
            <div class="col-md-4 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="<?= e($name) ?>" value="1" id="at-<?= e($key) ?>"<?= $v === true ? ' checked' : '' ?>><label class="form-check-label" for="at-<?= e($key) ?>"><?= e($a['label']) ?></label></div></div>
          <?php elseif ($a['type'] === 'select'): ?>
            <div class="col-md-4"><label class="form-label"><?= e($a['label']) ?></label><select class="form-select" name="<?= e($name) ?>"><option value="">—</option><?php foreach ($a['options'] as $o): ?><option<?= $v === $o ? ' selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?></select></div>
          <?php else: ?>
            <div class="col-md-4"><label class="form-label"><?= e($a['label']) ?><?= $a['suffix'] !== '' ? ' (' . e($a['suffix']) . ')' : '' ?></label><input class="form-control" name="<?= e($name) ?>" value="<?= e(is_scalar($v) ? (string) $v : '') ?>"<?= $a['type'] === 'number' ? ' inputmode="decimal"' : ' maxlength="120"' ?>><?php if ($a['help'] !== ''): ?><div class="form-text"><?= e($a['help']) ?></div><?php endif; ?></div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div></div></div>
      <?php endif; ?>
      <div class="card mb-3">
        <div class="card-header d-flex align-items-center">Photos<button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="listingAddImages"><i class="bi bi-images me-1"></i>Add photos</button></div>
        <div class="card-body"><div class="prod-images" id="listingImages"></div><div class="form-text">The first photo is the cover. Up to 12 photos; landscape (4:3) looks best.</div><div class="invalid-feedback d-block" data-err="image"></div></div>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="card mb-3 position-sticky" style="top:80px"><div class="card-body">
        <div class="mb-3"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active"<?= $it['status'] === 'active' ? ' selected' : '' ?>>Active (shown on the website)</option><option value="closed"<?= $it['status'] === 'closed' ? ' selected' : '' ?>><?= e($cfg['closed_label']) ?> (shown, marked <?= e(strtolower($cfg['closed_label'])) ?>)</option><option value="draft"<?= $it['status'] === 'draft' ? ' selected' : '' ?>>Draft (hidden)</option></select></div>
        <div class="mb-3"><label class="form-label"><?= e($cfg['category_label']) ?></label><select class="form-select" name="category_id"><option value="">None</option><?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"<?= (int) $it['category_id'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select><div class="form-text"><a href="<?= e($admin->url('listing-categories')) ?>">Manage the list</a></div></div>
        <div class="mb-3"><label class="form-label">Badge</label><input class="form-control" name="badge" maxlength="30" value="<?= e((string) $it['badge']) ?>" placeholder="e.g. New, Hot deal"></div>
        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_featured" value="1" id="lFeat"<?= (int) $it['is_featured'] ? ' checked' : '' ?>><label class="form-check-label" for="lFeat">Featured (shown on the homepage)</label></div>
        <div class="mb-3"><label class="form-label">Sort order</label><input class="form-control" type="number" name="sort_order" value="<?= (int) $it['sort_order'] ?>"></div>
        <button class="btn btn-primary w-100" type="submit"><?= $it['id'] ? 'Save' : 'Add ' . e(strtolower($cfg['singular'])) ?></button>
        <?php if ($it['id']): ?><button type="button" class="btn btn-link text-danger w-100 mt-2" data-post="<?= e($admin->url('api/listings/' . $it['id'] . '/delete')) ?>?back=1" data-confirm="Delete this <?= e(strtolower($cfg['singular'])) ?>?"><i class="bi bi-trash me-1"></i>Delete</button><?php endif; ?>
      </div></div>
    </div>
  </div>
</form>
<?php include __DIR__ . '/shop-media-modal.php'; ?>
