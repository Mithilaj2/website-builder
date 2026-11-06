<div class="card">
  <div class="card-header d-flex flex-wrap align-items-center gap-2">
    <form class="filters d-flex flex-wrap gap-2" id="catFilters" role="search">
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Search categories" aria-label="Search categories">
      <select name="status" class="form-select form-select-sm" aria-label="Status">
        <option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option>
      </select>
    </form>
    <?php if (can('categories.manage')): ?>
      <small class="text-muted ms-2 d-none d-md-inline"><i class="bi bi-grip-vertical"></i> Drag rows to reorder</small>
      <button class="btn btn-primary btn-sm ms-auto" id="btnAddCategory"><i class="bi bi-plus-lg me-1"></i>Add category</button>
    <?php endif; ?>
  </div>
  <div id="catTable"></div>
</div>

<?php if (can('categories.manage')): ?>
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
    <form data-ajax data-reload-table="categories" novalidate>
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <div class="alert alert-danger form-alert d-none py-2 small"></div>
        <div class="row g-3">
          <div class="col-md-7"><label class="form-label">Name *</label><input class="form-control" name="name" maxlength="120" required></div>
          <div class="col-md-5"><label class="form-label">Slug</label><input class="form-control" name="slug" maxlength="60" placeholder="auto from name">
            <div class="form-text">Folder name under <code>templates/</code>.</div></div>
          <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3" maxlength="2000"></textarea></div>
          <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
          <div class="col-md-3"><label class="form-label">Sort order</label><input class="form-control" type="number" name="sort_order" min="0"></div>
          <div class="col-md-5"><label class="form-label">Image</label><input class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp" data-preview="#catImgPreview">
            <div class="form-text">JPG, PNG or WEBP, up to 5 MB.</div></div>
          <div class="col-md-6"><img id="catImgPreview" class="img-preview" alt="Category image preview"></div>
          <div class="col-md-6 remove-image-wrap d-none"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_image" value="1" id="removeCatImg"><label class="form-check-label" for="removeCatImg">Remove current image</label></div></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save category</button></div>
    </form>
  </div></div>
</div>
<?php endif; ?>
