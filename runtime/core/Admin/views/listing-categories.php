<?php /** @var array $cfg */ ?>
<div class="d-flex flex-wrap gap-2 mb-3">
  <a class="btn btn-sm btn-light" href="<?= e($admin->url('listings')) ?>"><i class="bi bi-arrow-left"></i> <?= e($cfg['plural']) ?></a>
  <button class="btn btn-primary btn-sm ms-auto" id="lcatAdd"><i class="bi bi-plus-lg me-1"></i>Add</button>
</div>
<div class="card"><div id="lcatTable"></div></div>
<div class="modal fade" id="lcatModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
  <form data-ajax data-reload-table="lcats" id="lcatForm" novalidate>
    <div class="modal-header"><h5 class="modal-title"><?= e($cfg['category_label']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">
      <div class="alert alert-danger form-alert d-none py-2 small"></div>
      <div class="mb-3"><label class="form-label">Name *</label><input class="form-control" name="name" maxlength="80" required></div>
      <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2" maxlength="400"></textarea></div>
      <div class="mb-3"><label class="form-label d-block">Image</label><div class="d-flex align-items-center gap-2"><img class="cat-thumb" id="lcatThumb" alt=""><input type="hidden" name="image"><button type="button" class="btn btn-sm btn-outline-primary" id="lcatPick">Choose image</button><button type="button" class="btn btn-sm btn-light" id="lcatClear">Remove</button></div></div>
      <div class="row g-2">
        <div class="col-7"><label class="form-label">Visibility</label><select class="form-select" name="status"><option value="active">Shown</option><option value="hidden">Hidden</option></select></div>
        <div class="col-5"><label class="form-label">Sort order</label><input class="form-control" type="number" name="sort_order" value="0"></div>
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
  </form>
</div></div></div>
<?php include __DIR__ . '/shop-media-modal.php'; ?>
