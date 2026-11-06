<div class="modal fade" id="clientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
    <form data-ajax data-reload-table="clients" novalidate>
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">Client</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <div class="alert alert-danger form-alert d-none py-2 small"></div>
        <div class="row g-3">
          <div class="col-md-7"><label class="form-label">Client / business name *</label><input class="form-control" name="business_name" maxlength="190" required></div>
          <div class="col-md-5"><label class="form-label">Contact person</label><input class="form-control" name="contact_person" maxlength="120"></div>
          <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" maxlength="190"></div>
          <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" maxlength="20" placeholder="+91 98765 43210"></div>
          <div class="col-12"><label class="form-label">Business address</label><textarea class="form-control" name="address" rows="2" maxlength="1000"></textarea></div>
          <div class="col-12"><label class="form-label">Internal notes</label><textarea class="form-control" name="notes" rows="3" maxlength="5000"></textarea></div>
          <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save client</button></div>
    </form>
  </div></div>
</div>
