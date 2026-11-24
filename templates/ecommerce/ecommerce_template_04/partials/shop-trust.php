<?php if (!$view->shopEnabled()) { return; } $free = (int) $view->setting('shop', 'free_shipping_above', 0); $cod = (bool) $view->setting('shop', 'cod_enabled', false); ?>
<ul class="gbo-trust">
  <li><i class="bi bi-truck"></i><span><strong><?= $free > 0 ? 'Free delivery over ' . $view->e($view->money($free)) : 'Fast delivery' ?></strong><small>Packed with care, tracked to your door</small></span></li>
  <li><i class="bi bi-shield-check"></i><span><strong>Secure payment</strong><small>UPI, cards, net banking<?= $cod ? ', cash on delivery' : '' ?></small></span></li>
  <li><i class="bi bi-arrow-repeat"></i><span><strong>Easy returns</strong><small><?= $view->e((string) $view->setting('shop', 'return_policy', 'Hassle-free returns on unused items.')) ?></small></span></li>
</ul>
