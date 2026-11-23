<?php
/** @var \SiteRuntime\View $view @var array $item @var array $similar @var array $config */
$cfg = $config;
$images = array_values(array_filter(array_merge([(string) ($item['image'] ?? '')], $item['gallery'])));
$st = $view->formStatus('enquiry');
$en = $cfg['enquiry'];
$dateKey = $en['date_label'] !== '' ? (stripos($en['date_label'], 'date') === false ? $en['date_label'] . ' (date)' : $en['date_label']) : '';
?>
<section class="wblist wblist-detail">
  <div class="wblist-wrap">
    <nav class="wblist-crumbs" aria-label="Breadcrumb"><a href="<?= $view->e($view->url('')) ?>">Home</a><span>/</span><a href="<?= $view->e($view->listingsUrl()) ?>"><?= $view->e($cfg['plural']) ?></a><?php if ($item['category_slug']): ?><span>/</span><a href="<?= $view->e($view->listingsUrl(['c' => $item['category_slug']])) ?>"><?= $view->e($item['category_name']) ?></a><?php endif; ?></nav>
    <div class="wblist-head">
      <div>
        <?php if ($item['is_closed']): ?><span class="wblist-flag is-dark is-static"><?= $view->e($cfg['closed_label']) ?></span><?php elseif (($item['badge'] ?? '') !== ''): ?><span class="wblist-flag is-static"><?= $view->e($item['badge']) ?></span><?php endif; ?>
        <h1 class="wblist-title"><?= $view->e($item['title']) ?></h1>
        <?php if (($item['location'] ?? '') !== ''): ?><p class="wblist-loc"><i class="bi bi-geo-alt" aria-hidden="true"></i><?= $view->e($item['location']) ?></p><?php endif; ?>
      </div>
      <div class="wblist-head-price"><small><?= $view->e($cfg['price_label']) ?></small><strong><?= $view->e($item['price_text'] !== '' ? $item['price_text'] : 'On request') ?></strong><?php if (($item['price_note'] ?? '') !== '' && $item['price_text'] !== ''): ?><span><?= $view->e($item['price_note']) ?></span><?php endif; ?></div>
    </div>
    <?php if ($images): ?>
      <div class="wblist-gallery<?= count($images) > 4 ? ' has-many' : (count($images) > 1 ? ' has-' . count($images) : '') ?>" data-wblist-gallery>
        <?php foreach (array_slice($images, 0, 5) as $i => $ref): ?>
          <button type="button" class="wblist-gallery-item" data-wblist-open="<?= $i ?>" aria-label="View image <?= $i + 1 ?> of <?= count($images) ?>"><img src="<?= $view->img($ref) ?>" alt="<?= $i === 0 ? $view->e($item['title']) : '' ?>"<?= $i ? ' loading="lazy"' : '' ?>><?php if ($i === 4 && count($images) > 5): ?><span>+<?= count($images) - 5 ?></span><?php endif; ?></button>
        <?php endforeach; ?>
        <template data-wblist-images><?php foreach ($images as $ref): ?><img src="<?= $view->img($ref) ?>" alt=""><?php endforeach; ?></template>
      </div>
    <?php endif; ?>
    <div class="wblist-cols">
      <div class="wblist-main">
        <?php if ($item['facts']): ?>
          <ul class="wblist-specs"><?php foreach ($item['facts'] as $fct): ?><li><?php if ($fct['icon']): ?><i class="bi <?= $view->e($fct['icon']) ?>" aria-hidden="true"></i><?php endif; ?><span><small><?= $view->e($fct['label']) ?></small><strong><?= $view->e($fct['value']) ?></strong></span></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <?php if (($item['short_description'] ?? '') !== ''): ?><p class="wblist-lead"><?= $view->text($item['short_description']) ?></p><?php endif; ?>
        <?php if (trim(strip_tags((string) ($item['description'] ?? ''))) !== ''): ?><div class="wblist-prose"><?= $view->rich($item['description']) ?></div><?php endif; ?>
        <?php foreach ($item['lists'] as $list): ?>
          <div class="wblist-listblock"><h2><?= $view->e($list['label']) ?></h2><ul class="wblist-chips"><?php foreach ($list['items'] as $li): ?><li><i class="bi <?= $view->e($list['icon'] ?: 'bi-check2') ?>" aria-hidden="true"></i><?= $view->e($li) ?></li><?php endforeach; ?></ul></div>
        <?php endforeach; ?>
      </div>
      <aside class="wblist-side">
        <div class="wblist-box" id="enquiry-form">
          <h2><?= $view->e($en['heading']) ?></h2>
          <?php if ($st): ?><div class="wblist-alert is-<?= $view->e($st['status']) ?>" role="<?= $st['status'] === 'sent' ? 'status' : 'alert' ?>"><?= $view->e($st['status'] === 'sent' ? $en['success'] : $st['message']) ?></div><?php endif; ?>
          <?php if ($item['is_closed']): ?><p class="wblist-muted">This <?= $view->e(strtolower($cfg['singular'])) ?> is no longer available, but we may have similar ones. Send us your requirements below.</p><?php endif; ?>
          <form class="wblist-form" method="post" action="<?= $view->formAction('enquiry') ?>">
            <?= $view->formGuard() ?>
            <input type="hidden" name="extra[<?= $view->e($cfg['singular']) ?>]" value="<?= $view->e(mb_substr($item['title'], 0, 120)) ?>">
            <fieldset<?= $view->formsEnabled() ? '' : ' disabled' ?>>
              <label>Name *<input name="name" required maxlength="120" autocomplete="name"></label>
              <label>Mobile number *<input type="tel" name="phone" required maxlength="20" autocomplete="tel"></label>
              <label>Email<input type="email" name="email" maxlength="190" autocomplete="email"></label>
              <?php if ($en['select_label'] !== '' && $en['select_options']): ?><label><?= $view->e($en['select_label']) ?><select name="extra[<?= $view->e($en['select_label']) ?>]"><option value="">Select…</option><?php foreach ($en['select_options'] as $o): ?><option><?= $view->e($o) ?></option><?php endforeach; ?></select></label><?php endif; ?>
              <?php if ($dateKey !== ''): ?><label><?= $view->e($en['date_label']) ?><input type="date" name="extra[<?= $view->e($dateKey) ?>]" min="<?= date('Y-m-d') ?>"></label><?php endif; ?>
              <label><?= $view->e($en['message_label']) ?><textarea name="message" rows="3" maxlength="5000"></textarea></label>
              <button type="submit" class="wblist-btn wblist-btn-block"><?= $view->e($en['button']) ?></button>
            </fieldset>
            <?php if (!$view->formsEnabled()): ?><p class="wblist-muted small">Preview: enquiries are accepted once the website is published.</p><?php endif; ?>
          </form>
          <?php if ((string) $view->site('phone') !== ''): ?><a class="wblist-call" href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', (string) $view->site('phone'))) ?>"><i class="bi bi-telephone" aria-hidden="true"></i> <?= $view->e($view->site('phone')) ?></a><?php endif; ?>
          <?php if ((string) $view->site('whatsapp') !== ''): ?><a class="wblist-call is-wa" href="https://wa.me/<?= $view->e(preg_replace('/\D/', '', (string) $view->site('whatsapp'))) ?>?text=<?= $view->e(rawurlencode('Hi, I am interested in ' . $item['title'])) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp" aria-hidden="true"></i> WhatsApp</a><?php endif; ?>
        </div>
      </aside>
    </div>
    <?php if ($similar): ?>
      <div class="wblist-similar"><h2>Similar <?= $view->e(strtolower($cfg['plural'])) ?></h2><div class="wblist-grid"><?php foreach ($similar as $s): ?><?= $view->capture(__DIR__ . '/card.php', ['item' => $s]) ?><?php endforeach; ?></div></div>
    <?php endif; ?>
  </div>
  <div class="wblist-lightbox" data-wblist-lightbox hidden role="dialog" aria-modal="true" aria-label="Photos">
    <button type="button" class="wblist-lb-close" data-wblist-close aria-label="Close">×</button>
    <button type="button" class="wblist-lb-nav is-prev" data-wblist-step="-1" aria-label="Previous">‹</button>
    <img alt="" data-wblist-lb-img>
    <button type="button" class="wblist-lb-nav is-next" data-wblist-step="1" aria-label="Next">›</button>
    <span class="wblist-lb-count" data-wblist-lb-count></span>
  </div>
</section>
