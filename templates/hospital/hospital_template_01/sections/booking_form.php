<?php
$sid = (int) $section['id'];
$form = in_array($data['form_type'] ?? '', ['appointment', 'admission', 'booking', 'enquiry'], true) ? $data['form_type'] : 'enquiry';
$sel1 = trim((string) ($data['select1_label'] ?? ''));
$sel2 = trim((string) ($data['select2_label'] ?? ''));
$dateLabel = trim((string) ($data['date_label'] ?? ''));
$msgLabel = trim((string) ($data['message_label'] ?? ''));
$emailReq = !empty($data['email_required']);
$img = $view->img($data['image'] ?? '');
$st = $view->formStatus($form);
$dropdowns = [];
foreach ([[$sel1, $data['select1_options'] ?? ''], [$sel2, $data['select2_options'] ?? '']] as [$label, $raw]) {
    $list = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $raw) ?: [])));
    if ($label !== '' && $list) {
        $dropdowns[] = [$label, $list];
    }
}
?>
<section <?= $view->attrs($section, 'crl-booking') ?>>
  <div class="container">
    <div class="row g-4 g-lg-5 align-items-start">
      <div class="col-lg-5">
        <?= $view->partial('section-head', ['data' => $data, 'align' => 'start']) ?>
        <?php if ($img !== ''): ?><div class="crl-frame crl-booking-img"><img src="<?= $img ?>" alt="" loading="lazy"></div><?php endif; ?>
        <?php if (!empty($data['points'])): ?>
          <ul class="crl-points">
            <?php foreach ((array) $data['points'] as $p): ?>
              <li><span class="crl-icon crl-icon-accent"><?= $view->icon(($p['icon'] ?? '') ?: 'bi-check2') ?></span><span><strong><?= $view->e($p['title'] ?? '') ?></strong><?php if (($p['text'] ?? '') !== ''): ?><small><?= $view->e($p['text']) ?></small><?php endif; ?></span></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
      <div class="col-lg-7">
        <div class="crl-card crl-form-card">
          <h3 class="crl-card-title mb-4" id="<?= $form ?>-form"><?= $view->e($data['form_heading'] ?? 'Your details') ?></h3>
          <?php if ($st): ?>
            <div class="crl-form-alert is-<?= $view->e($st['status']) ?>" role="<?= $st['status'] === 'sent' ? 'status' : 'alert' ?>"><i class="bi <?= $st['status'] === 'sent' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' ?>"></i><?= $view->e($st['status'] === 'sent' && ($data['success_message'] ?? '') !== '' ? $data['success_message'] : $st['message']) ?></div>
          <?php endif; ?>
          <form class="crl-form" method="post" action="<?= $view->formAction($form) ?>">
            <?= $view->formGuard() ?>
            <?php if ($emailReq): ?><input type="hidden" name="_email_required" value="1"><?php endif; ?>
            <fieldset<?= $view->formsEnabled() ? '' : ' disabled' ?>>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label" for="bf-name-<?= $sid ?>">Full name *</label><input class="form-control" id="bf-name-<?= $sid ?>" name="name" required maxlength="120" autocomplete="name"></div>
              <div class="col-md-6"><label class="form-label" for="bf-phone-<?= $sid ?>">Mobile number<?= $emailReq ? '' : ' *' ?></label><input class="form-control" type="tel" id="bf-phone-<?= $sid ?>" name="phone" maxlength="20" autocomplete="tel"<?= $emailReq ? '' : ' required' ?>></div>
              <div class="col-12"><label class="form-label" for="bf-email-<?= $sid ?>">Email<?= $emailReq ? ' *' : '' ?></label><input class="form-control" type="email" id="bf-email-<?= $sid ?>" name="email" maxlength="190" autocomplete="email"<?= $emailReq ? ' required' : '' ?>></div>
              <?php foreach ($dropdowns as $k => [$label, $options]): ?>
                <div class="col-md-6"><label class="form-label" for="bf-s<?= $k ?>-<?= $sid ?>"><?= $view->e($label) ?></label>
                  <select class="form-select" id="bf-s<?= $k ?>-<?= $sid ?>" name="extra[<?= $view->e($label) ?>]"><option value="">Select…</option><?php foreach ($options as $o): ?><option><?= $view->e($o) ?></option><?php endforeach; ?></select></div>
              <?php endforeach; ?>
              <?php if ($dateLabel !== ''): ?>
                <div class="col-md-6"><label class="form-label" for="bf-date-<?= $sid ?>"><?= $view->e($dateLabel) ?></label><input class="form-control" type="date" id="bf-date-<?= $sid ?>" name="extra[<?= $view->e(stripos($dateLabel, 'date') === false ? $dateLabel . ' (date)' : $dateLabel) ?>]" min="<?= date('Y-m-d') ?>"></div>
              <?php endif; ?>
              <?php if ($msgLabel !== ''): ?>
                <div class="col-12"><label class="form-label" for="bf-msg-<?= $sid ?>"><?= $view->e($msgLabel) ?></label><textarea class="form-control" id="bf-msg-<?= $sid ?>" name="message" rows="3" maxlength="5000"></textarea></div>
              <?php endif; ?>
              <div class="col-12"><button class="btn crl-btn crl-btn-accent" type="submit"><?= $view->e(($data['button_text'] ?? '') ?: 'Send request') ?><i class="bi bi-arrow-right"></i></button></div>
            </div>
            </fieldset>
            <?php if (!$view->formsEnabled()): ?><p class="crl-form-note"><i class="bi bi-info-circle"></i> Preview: requests are accepted once the website is published.</p><?php endif; ?>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
