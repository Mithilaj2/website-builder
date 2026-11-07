<?php
/**
 * Page modal, section editor, media picker and link/icon lists (shared: builder wizard and website admin).
 * Needs: $pageTypes, $editable; helpers e() and csrf_field().
 */
?>
<datalist id="pageLinkList"></datalist>
<datalist id="iconList"><?php foreach (['bi-briefcase','bi-graph-up-arrow','bi-people','bi-compass','bi-gear-wide-connected','bi-globe2','bi-cpu','bi-award','bi-shield-check','bi-clock-history','bi-check2-circle','bi-star','bi-heart-pulse','bi-hospital','bi-mortarboard','bi-book','bi-house-door','bi-building','bi-cart3','bi-truck','bi-airplane','bi-cup-hot','bi-camera','bi-telephone','bi-envelope','bi-geo-alt','bi-lightning-charge','bi-tools','bi-bullseye','bi-receipt','bi-person-workspace','bi-shield-lock','bi-chat-dots','bi-hand-thumbs-up','bi-trophy','bi-calendar-check','bi-wallet2','bi-bar-chart','bi-lightbulb','bi-leaf'] as $i): ?><option value="<?= e($i) ?>"><?php endforeach; ?></datalist>

<!-- Page modal -->
<div class="modal fade" id="pageModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
  <form data-ajax novalidate>
    <?= csrf_field() ?>
    <div class="modal-header"><h5 class="modal-title">Page</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">
      <div class="alert alert-danger form-alert d-none py-2 small"></div>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Page title *</label><input class="form-control" name="title" maxlength="150" required></div>
        <div class="col-md-6"><label class="form-label">URL</label><div class="input-group"><span class="input-group-text">/</span><input class="form-control" name="slug" maxlength="120" placeholder="auto from title"></div><div class="form-text slug-help"></div></div>
        <div class="col-md-6"><label class="form-label">Page layout</label><select class="form-select" name="page_type"><?php foreach ($pageTypes as $t): ?><option value="<?= e($t) ?>"><?= e(ucwords(str_replace('_', ' ', $t))) ?></option><?php endforeach; ?></select><div class="form-text type-help">New pages start with this layout's default sections.</div></div>
        <div class="col-md-6 d-flex flex-column justify-content-end gap-1">
          <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_in_menu" value="1" id="pgMenu"><label class="form-check-label" for="pgMenu">Show in main menu</label></div>
          <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_in_footer" value="1" id="pgFooter"><label class="form-check-label" for="pgFooter">Show in footer links</label></div>
        </div>
        <div class="col-12"><hr class="my-1"><div class="fw-semibold">Search engine (SEO)</div></div>
        <div class="col-12"><label class="form-label d-flex">SEO title <small class="ms-auto text-muted" data-count-for="seo_title"></small></label><input class="form-control" name="seo_title" maxlength="190" placeholder="Defaults to “Page title | Website name”"></div>
        <div class="col-12"><label class="form-label d-flex">Meta description <small class="ms-auto text-muted" data-count-for="seo_description"></small></label><textarea class="form-control" name="seo_description" rows="2" maxlength="320" placeholder="150–160 characters works best"></textarea></div>
        <div class="col-12"><label class="form-label">Keywords</label><input class="form-control" name="seo_keywords" maxlength="255" placeholder="comma, separated, keywords"></div>
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save page</button></div>
  </form>
</div></div></div>

<!-- Section editor -->
<div class="modal fade" id="sectionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"><div class="modal-dialog modal-fullscreen-lg-down" style="max-width:min(1500px,96vw)"><div class="modal-content">
  <div class="modal-header">
    <h5 class="modal-title"><i class="bi section-icon me-2"></i><span class="section-title">Edit section</span></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  </div>
  <div class="modal-body p-0">
    <div class="row g-0 section-editor">
      <div class="col-lg-5 border-end section-editor-form">
        <ul class="nav nav-pills px-3 pt-3 gap-1" role="tablist">
          <li class="nav-item"><button class="nav-link active py-1" data-bs-toggle="pill" data-bs-target="#secContent" type="button">Content</button></li>
          <li class="nav-item"><button class="nav-link py-1" data-bs-toggle="pill" data-bs-target="#secStyle" type="button">Style</button></li>
        </ul>
        <div class="tab-content p-3">
          <div class="alert alert-danger form-alert d-none py-2 small"></div>
          <div class="tab-pane fade show active" id="secContent"><div class="mb-3"><label class="form-label">Internal label (optional)</label><input class="form-control form-control-sm" id="secLabel" maxlength="120" placeholder="Only shown in the builder"></div><div id="secFields"></div></div>
          <div class="tab-pane fade" id="secStyle">
            <div class="row g-3">
              <div class="col-6"><label class="form-label">Background colour</label><div class="input-group input-group-sm"><input type="color" class="form-control form-control-color" data-style-color><input class="form-control" data-style="bg_color" placeholder="Template default"></div></div>
              <div class="col-6"><label class="form-label">Spacing</label><select class="form-select form-select-sm" data-style="padding"><option value="md">Normal</option><option value="sm">Compact</option><option value="lg">Spacious</option><option value="none">None</option></select></div>
              <div class="col-12"><label class="form-label">Background image</label><div class="wb-image-field" data-style-image="bg_image"></div></div>
              <div class="col-6"><label class="form-label">Dark overlay on image</label><input type="range" class="form-range" min="0" max="90" step="5" data-style="overlay"><div class="form-text"><span data-overlay-value>0</span>%</div></div>
              <div class="col-6"><label class="form-label">Text colour</label><select class="form-select form-select-sm" data-style="tone"><option value="auto">Template default</option><option value="light">Light text (for dark backgrounds)</option><option value="dark">Dark text</option></select></div>
              <div class="col-12"><label class="form-label">Anchor name (for #links)</label><input class="form-control form-control-sm" data-style="anchor" maxlength="40" placeholder="e.g. services"><div class="form-text">Link to this section from a button with “#services”.</div></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-7 section-editor-preview d-none d-lg-flex flex-column">
        <div class="px-3 py-2 border-bottom small text-muted d-flex align-items-center"><i class="bi bi-eye me-2"></i>Live preview (updates when you save)
          <div class="btn-group btn-group-sm ms-auto"><button class="btn btn-outline-secondary active" data-sec-device="100%"><i class="bi bi-display"></i></button><button class="btn btn-outline-secondary" data-sec-device="390px"><i class="bi bi-phone"></i></button></div></div>
        <div class="preview-stage flex-grow-1"><iframe id="sectionPreview" title="Section preview"></iframe></div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <?php if ($editable): ?>
      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
      <button type="button" class="btn btn-outline-primary" id="secSave" data-busy-text="Saving…">Save &amp; preview</button>
      <button type="button" class="btn btn-primary" id="secSaveClose" data-busy-text="Saving…">Save &amp; close</button>
    <?php else: ?>
      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
    <?php endif; ?>
  </div>
</div></div></div>

<!-- Media picker -->
<div class="modal fade" id="mediaPicker" tabindex="-1" aria-hidden="true" style="z-index:1065"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Choose an image</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body" id="mediaPickerBody"></div>
</div></div></div>
