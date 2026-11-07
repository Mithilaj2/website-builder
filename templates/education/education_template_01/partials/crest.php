<?php
/* School crest: a shield with the first letter of the website name (used when no logo is uploaded). */
$letter = mb_strtoupper(mb_substr((string) $view->site('name', 'L'), 0, 1));
?>
<span class="lyc-crest" aria-hidden="true"><svg viewBox="0 0 40 46" width="40" height="46"><path d="M20 1.5 37.5 7v15.5C37.5 34 30 41.5 20 44.5 10 41.5 2.5 34 2.5 22.5V7Z" fill="currentColor"/><path d="M20 5.5 34 10v12.5c0 9.2-5.9 15.5-14 18.2C11.9 38 6 31.7 6 22.5V10Z" fill="none" stroke="var(--wb-secondary)" stroke-width="1.4"/></svg><b><?= $view->e($letter) ?></b></span>
