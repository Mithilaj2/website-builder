<div class="card">
  <div class="card-header d-flex flex-wrap align-items-center gap-2">
    <form class="filters d-flex flex-wrap gap-2" id="enqFilters" role="search">
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Search name, email, phone, message" aria-label="Search enquiries">
      <select name="status" class="form-select form-select-sm" aria-label="Status"><option value="">All (except spam)</option><option value="new">New</option><option value="read">Read</option><option value="replied">Replied</option><option value="spam">Spam</option></select>
    </form>
    <a class="btn btn-outline-primary btn-sm ms-auto" href="<?= e($admin->url('api/enquiries/export')) ?>"><i class="bi bi-download me-1"></i>Export CSV</a>
  </div>
  <div id="enqTable"></div>
</div>
<div class="modal fade" id="enqModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Enquiry</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body"></div>
  <div class="modal-footer flex-wrap">
    <select class="form-select form-select-sm w-auto me-auto" id="enqStatus" aria-label="Status"><option value="new">New</option><option value="read">Read</option><option value="replied">Replied</option><option value="spam">Spam</option></select>
    <a class="btn btn-primary btn-sm" id="enqReply" href="#"><i class="bi bi-reply me-1"></i>Reply by email</a>
    <button class="btn btn-outline-danger btn-sm" id="enqDelete"><i class="bi bi-trash me-1"></i>Delete</button>
  </div>
</div></div></div>
