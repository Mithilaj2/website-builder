<?php
$details = array_filter([
    ['icon' => 'bi-geo-alt', 'label' => 'Visit us', 'value' => $view->text($view->site('address')), 'href' => ''],
    ['icon' => 'bi-telephone', 'label' => 'Call us', 'value' => $view->e($view->site('phone')), 'href' => 'tel:' . preg_replace('/[^0-9+]/', '', (string) $view->site('phone'))],
    ['icon' => 'bi-envelope', 'label' => 'Email us', 'value' => $view->e($view->site('email')), 'href' => 'mailto:' . $view->site('email')],
    ['icon' => 'bi-clock', 'label' => 'Working hours', 'value' => $view->e($view->site('hours')), 'href' => ''],
], static fn ($d) => $d['value'] !== '');
$map = (string) ($data['map_url'] ?? '');
?>
<section <?= $view->attrs($section, 'mrd-contact') ?>>
  <div class="container">
    <div class="row g-5">
      <?php if (!empty($data['show_details'])): ?>
      <div class="<?= !empty($data['show_form']) ? 'col-lg-5' : 'col-12' ?>">
        <?= $view->partial('section-head', ['data' => $data, 'align' => 'start']) ?>
        <ul class="mrd-contact-list">
          <?php foreach ($details as $d): ?>
            <li><span class="mrd-icon mrd-icon-soft"><i class="bi <?= $d['icon'] ?>"></i></span>
              <span><small><?= $d['label'] ?></small><?php if ($d['href'] !== ''): ?><a href="<?= $view->e($d['href']) ?>"><?= $d['value'] ?></a><?php else: ?><span><?= $d['value'] ?></span><?php endif; ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php if ($map !== '' && preg_match('#^https://#', $map)): ?><a class="btn mrd-btn mrd-btn-ghost" href="<?= $view->e($map) ?>" target="_blank" rel="noopener"><i class="bi bi-map"></i> View on map</a><?php endif; ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($data['show_form'])): ?>
      <div class="<?= !empty($data['show_details']) ? 'col-lg-7' : 'col-lg-8 mx-auto' ?>">
        <div class="mrd-card mrd-form-card">
          <h3 class="mrd-card-title mb-4"><?= $view->e($data['form_heading'] ?? 'Send us a message') ?></h3>
          <form class="mrd-form" method="post" action="<?= $view->formAction('contact') ?>" novalidate>
            <?= $view->formGuard() ?>
            <fieldset<?= $view->formsEnabled() ? '' : ' disabled' ?>>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label" for="cf-name-<?= (int) $section['id'] ?>">Full name *</label><input class="form-control" id="cf-name-<?= (int) $section['id'] ?>" name="name" required maxlength="120" autocomplete="name"></div>
              <div class="col-md-6"><label class="form-label" for="cf-email-<?= (int) $section['id'] ?>">Email *</label><input class="form-control" type="email" id="cf-email-<?= (int) $section['id'] ?>" name="email" required maxlength="190" autocomplete="email"></div>
              <div class="col-md-6"><label class="form-label" for="cf-phone-<?= (int) $section['id'] ?>">Phone</label><input class="form-control" type="tel" id="cf-phone-<?= (int) $section['id'] ?>" name="phone" maxlength="20" autocomplete="tel"></div>
              <div class="col-md-6"><label class="form-label" for="cf-subject-<?= (int) $section['id'] ?>">Subject</label><input class="form-control" id="cf-subject-<?= (int) $section['id'] ?>" name="subject" maxlength="190"></div>
              <div class="col-12"><label class="form-label" for="cf-msg-<?= (int) $section['id'] ?>">Message *</label><textarea class="form-control" id="cf-msg-<?= (int) $section['id'] ?>" name="message" rows="5" required maxlength="5000"></textarea></div>
              <div class="col-12"><button class="btn mrd-btn mrd-btn-primary" type="submit">Send message<i class="bi bi-send"></i></button></div>
            </div>
            </fieldset>
            <?php if (!$view->formsEnabled()): ?><p class="mrd-form-note"><i class="bi bi-info-circle"></i> Preview: enquiries are accepted once the website is published.</p><?php endif; ?>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>
