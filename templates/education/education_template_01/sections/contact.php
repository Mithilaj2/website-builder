<?php
$details = array_filter([
    ['icon' => 'bi-geo-alt', 'label' => 'Campus address', 'value' => $view->text($view->site('address')), 'href' => ''],
    ['icon' => 'bi-telephone', 'label' => 'Call the office', 'value' => $view->e($view->site('phone')), 'href' => 'tel:' . preg_replace('/[^0-9+]/', '', (string) $view->site('phone'))],
    ['icon' => 'bi-envelope', 'label' => 'Email', 'value' => $view->e($view->site('email')), 'href' => 'mailto:' . $view->site('email')],
    ['icon' => 'bi-clock', 'label' => 'Office hours', 'value' => $view->e($view->site('hours')), 'href' => ''],
], static fn ($d) => $d['value'] !== '');
$map = (string) ($data['map_url'] ?? '');
$sid = (int) $section['id'];
?>
<section <?= $view->attrs($section, 'lyc-contact') ?>>
  <div class="container">
    <div class="row g-4 g-lg-5">
      <?php if (!empty($data['show_details'])): ?>
      <div class="<?= !empty($data['show_form']) ? 'col-lg-5' : 'col-12' ?>">
        <?= $view->partial('section-head', ['data' => $data, 'align' => 'start']) ?>
        <ul class="lyc-contact-list">
          <?php foreach ($details as $d): ?>
            <li><span class="lyc-icon"><i class="bi <?= $d['icon'] ?>"></i></span>
              <span><small><?= $d['label'] ?></small><?php if ($d['href'] !== ''): ?><a href="<?= $view->e($d['href']) ?>"><?= $d['value'] ?></a><?php else: ?><span><?= $d['value'] ?></span><?php endif; ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($map !== '' && preg_match('#^https://#', $map)): ?><a class="btn lyc-btn lyc-btn-outline" href="<?= $view->e($map) ?>" target="_blank" rel="noopener"><i class="bi bi-map"></i> Directions to campus</a><?php endif; ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($data['show_form'])): ?>
      <div class="<?= !empty($data['show_details']) ? 'col-lg-7' : 'col-lg-8 mx-auto' ?>">
        <div class="lyc-card lyc-form-card">
          <h3 class="lyc-card-title mb-4" id="contact-form"><?= $view->e($data['form_heading'] ?? 'Send us a message') ?></h3>
          <?php if ($st = $view->formStatus('contact')): ?>
            <div class="lyc-form-alert is-<?= $view->e($st['status']) ?>" role="<?= $st['status'] === 'sent' ? 'status' : 'alert' ?>"><i class="bi <?= $st['status'] === 'sent' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' ?>"></i><?= $view->e($st['message']) ?></div>
          <?php endif; ?>
          <form class="lyc-form" method="post" action="<?= $view->formAction('contact') ?>">
            <?= $view->formGuard() ?>
            <fieldset<?= $view->formsEnabled() ? '' : ' disabled' ?>>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label" for="cf-name-<?= $sid ?>">Full name *</label><input class="form-control" id="cf-name-<?= $sid ?>" name="name" required maxlength="120" autocomplete="name"></div>
              <div class="col-md-6"><label class="form-label" for="cf-email-<?= $sid ?>">Email *</label><input class="form-control" type="email" id="cf-email-<?= $sid ?>" name="email" required maxlength="190" autocomplete="email"></div>
              <div class="col-md-6"><label class="form-label" for="cf-phone-<?= $sid ?>">Phone</label><input class="form-control" type="tel" id="cf-phone-<?= $sid ?>" name="phone" maxlength="20" autocomplete="tel"></div>
              <div class="col-md-6"><label class="form-label" for="cf-subject-<?= $sid ?>">Subject</label><input class="form-control" id="cf-subject-<?= $sid ?>" name="subject" maxlength="190"></div>
              <div class="col-12"><label class="form-label" for="cf-msg-<?= $sid ?>">Message *</label><textarea class="form-control" id="cf-msg-<?= $sid ?>" name="message" rows="5" required maxlength="5000"></textarea></div>
              <div class="col-12"><button class="btn lyc-btn lyc-btn-primary" type="submit">Send message<i class="bi bi-send"></i></button></div>
            </div>
            </fieldset>
            <?php if (!$view->formsEnabled()): ?><p class="lyc-form-note"><i class="bi bi-info-circle"></i> Preview: messages are accepted once the website is published.</p><?php endif; ?>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>
