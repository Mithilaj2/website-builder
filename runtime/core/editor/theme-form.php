<?php
/**
 * Theme customisation form (shared: builder wizard step 6 and website admin → Appearance).
 * Needs: $themeAction, $fontOptions, $headerStyles, $footerStyles, $editable; helpers e() and csrf_field().
 */
?>
<form id="themeForm" action="<?= e($themeAction) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-xl-7">
          <div class="card mb-3"><div class="card-header">Colours</div><div class="card-body"><div class="row g-3">
            <?php foreach (['primary' => 'Primary colour', 'secondary' => 'Accent colour', 'bg' => 'Page background', 'text' => 'Text colour'] as $k => $label): ?>
              <div class="col-sm-6"><label class="form-label"><?= e($label) ?></label>
                <div class="input-group"><input type="color" class="form-control form-control-color" data-color-for="<?= e($k) ?>" aria-label="<?= e($label) ?> picker"><input class="form-control" name="<?= e($k) ?>" maxlength="7" pattern="#[0-9A-Fa-f]{6}"></div></div>
            <?php endforeach; ?>
          </div></div></div>
          <div class="card mb-3"><div class="card-header">Typography &amp; shape</div><div class="card-body"><div class="row g-3">
            <div class="col-sm-6"><label class="form-label">Heading font</label><select class="form-select" name="heading_font"><?= $fontOptions ?></select></div>
            <div class="col-sm-6"><label class="form-label">Body font</label><select class="form-select" name="body_font"><?= $fontOptions ?></select></div>
            <div class="col-sm-6"><label class="form-label">Button style</label><select class="form-select" name="button_style"><?php foreach (\SiteRuntime\Theme::BUTTON_STYLES as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
            <div class="col-sm-6"><label class="form-label">Corner rounding</label><select class="form-select" name="radius"><?php foreach (\SiteRuntime\Theme::RADII as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
            <div class="col-sm-6"><label class="form-label">Header style</label><select class="form-select" name="header_style"><?php foreach ($headerStyles as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
            <div class="col-sm-6"><label class="form-label">Footer style</label><select class="form-select" name="footer_style"><?php foreach ($footerStyles as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
          </div></div></div>
        </div>
        <div class="col-xl-5">
          <div class="card mb-3"><div class="card-header">Logo &amp; favicon</div><div class="card-body">
            <div class="mb-3"><label class="form-label">Logo</label><div class="wb-image-field" data-name="logo"></div><div class="form-text">Shown in the header and footer. Without a logo, the website name is shown.</div></div>
            <div><label class="form-label">Favicon</label><div class="wb-image-field" data-name="favicon"></div><div class="form-text">Square image, at least 64×64 px (PNG recommended).</div></div>
          </div></div>
          <div class="card"><div class="card-body d-flex flex-wrap gap-2">
            <?php if ($editable): ?>
              <button type="submit" class="btn btn-primary">Save theme</button>
              <button type="button" class="btn btn-light" id="btnThemeDefaults">Reset to template defaults</button>
            <?php endif; ?>
            <div class="w-100 small text-muted mt-1">Changes appear in the Preview tab after saving.</div>
          </div></div>
        </div>
      </div>
    </form>
