<?php /** @var array $config */ ?>
<script type="application/json" id="shopConfig"><?= json_encode(['api' => $config['urls']['api'], 'images' => []], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<div class="d-flex flex-wrap gap-2 mb-3">
  <a class="btn btn-sm btn-light" href="<?= e($admin->url('products')) ?>"><i class="bi bi-arrow-left"></i> Products</a>
  <button class="btn btn-primary btn-sm ms-auto" id="catAdd"><i class="bi bi-plus-lg me-1"></i>Add category</button>
</div>
<div class="card"><div id="catTable"></div></div>
<div class="modal fade" id="catModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
  <form data-ajax data-reload-table="categories" id="catForm" novalidate>
    <div class="modal-header"><h5 class="modal-title">Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">
      <div class="alert alert-danger form-alert d-none py-2 small"></div>
      <div class="mb-3"><label class="form-label">Name *</label><input class="form-control" name="name" maxlength="80" required></div>
      <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2" maxlength="400"></textarea></div>
      <div class="mb-3"><label class="form-label d-block">Image</label>
        <div class="d-flex align-items-center gap-2"><img class="cat-thumb" id="catThumb" alt=""><input type="hidden" name="image"><button type="button" class="btn btn-sm btn-outline-primary" id="catPick">Choose image</button><button type="button" class="btn btn-sm btn-light" id="catClear">Remove</button></div></div>
      <div class="row g-2">
        <div class="col-7"><label class="form-label">Visibility</label><select class="form-select" name="status"><option value="active">Shown in the shop</option><option value="hidden">Hidden</option></select></div>
        <div class="col-5"><label class="form-label">Sort order</label><input class="form-control" type="number" name="sort_order" value="0"></div>
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
  </form>
</div></div></div>
<?php include __DIR__ . '/shop-media-modal.php'; ?>
