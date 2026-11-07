<?php
/**
 * Site details form (shared: builder wizard step 6 and website admin → Appearance).
 * Needs: $detailsAction, $editable; helpers e() and csrf_field().
 */
?>
<form id="detailsForm" data-ajax action="<?= e($detailsAction) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="alert alert-danger form-alert d-none py-2 small"></div>
      <div class="row g-3">
        <div class="col-xl-6">
          <div class="card mb-3"><div class="card-header">Business &amp; contact</div><div class="card-body"><div class="row g-3">
            <div class="col-md-6"><label class="form-label">Name shown on the website *</label><input class="form-control" name="name" maxlength="120" required></div>
            <div class="col-md-6"><label class="form-label">Tagline</label><input class="form-control" name="tagline" maxlength="150"></div>
            <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" maxlength="190"></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" maxlength="20"></div>
            <div class="col-md-6"><label class="form-label">WhatsApp number</label><input class="form-control" name="whatsapp" maxlength="20" placeholder="+91…"></div>
            <div class="col-md-6"><label class="form-label">Working hours</label><input class="form-control" name="hours" maxlength="120" placeholder="Mon–Sat: 9 am – 6 pm"></div>
            <div class="col-md-6"><label class="form-label">Emergency / helpline number</label><input class="form-control" name="emergency_phone" maxlength="20" placeholder="Shown by templates that have an emergency strip"></div>
            <div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="2" maxlength="400"></textarea></div>
          </div></div></div>
          <div class="card mb-3"><div class="card-header">Highlight badge</div><div class="card-body"><div class="row g-3">
            <div class="col-md-5"><label class="form-label">Badge title</label><input class="form-control" name="rating" maxlength="40" placeholder="e.g. NABH accredited"></div>
            <div class="col-md-7"><label class="form-label">Badge text</label><input class="form-control" name="rating_label" maxlength="90" placeholder="Leave the title empty to hide the badge"></div>
          </div></div></div>
          <div class="card mb-3"><div class="card-header">Header button</div><div class="card-body"><div class="row g-3">
            <div class="col-md-5"><label class="form-label">Text</label><input class="form-control" name="header_button_text" maxlength="40" placeholder="Leave empty to hide"></div>
            <div class="col-md-7"><label class="form-label">Link</label><input class="form-control" name="header_button_url" maxlength="500" list="pageLinkList" placeholder="page:contact"></div>
          </div></div></div>
        </div>
        <div class="col-xl-6">
          <div class="card mb-3"><div class="card-header">Footer</div><div class="card-body"><div class="row g-3">
            <div class="col-12"><label class="form-label">About text</label><textarea class="form-control" name="footer_about" rows="2" maxlength="400"></textarea></div>
            <div class="col-12"><label class="form-label">Copyright text</label><input class="form-control" name="footer_text" maxlength="200" placeholder="All rights reserved."></div>
          </div></div></div>
          <div class="card mb-3"><div class="card-header">Social media</div><div class="card-body"><div class="row g-3">
            <?php foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'twitter' => 'X (Twitter)', 'youtube' => 'YouTube'] as $k => $l): ?>
              <div class="col-md-6"><label class="form-label"><i class="bi bi-<?= $k === 'twitter' ? 'twitter-x' : e($k) ?> me-1"></i><?= e($l) ?></label><input class="form-control" name="social_<?= e($k) ?>" maxlength="200" placeholder="https://"></div>
            <?php endforeach; ?>
          </div></div></div>
          <div class="card mb-3"><div class="card-header">Default SEO</div><div class="card-body"><div class="row g-3">
            <div class="col-12"><label class="form-label">Default meta description</label><textarea class="form-control" name="seo_description" rows="2" maxlength="320"></textarea><div class="form-text">Used on pages without their own SEO description.</div></div>
            <div class="col-12"><label class="form-label">Default keywords</label><input class="form-control" name="seo_keywords" maxlength="255"></div>
          </div></div></div>
          <?php if ($editable): ?><button class="btn btn-primary" type="submit">Save site details</button><?php endif; ?>
        </div>
      </div>
    </form>
