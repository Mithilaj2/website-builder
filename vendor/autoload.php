<?php
// Bundled third-party libraries (no Composer needed on cPanel).
// ezyang/htmlpurifier v4.18.0 (LGPL-2.1) — rich-text sanitising.
if (!defined('HTMLPURIFIER_PREFIX')) {
    define('HTMLPURIFIER_PREFIX', __DIR__ . '/ezyang/htmlpurifier/library');
}
require_once HTMLPURIFIER_PREFIX . '/HTMLPurifier/Bootstrap.php';
require_once HTMLPURIFIER_PREFIX . '/HTMLPurifier.autoload.php';
