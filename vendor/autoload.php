<?php
// Bundled third-party libraries (no Composer needed on cPanel). Shipped with the builder and with every generated website.
//  - ezyang/htmlpurifier v4.18.0 (LGPL-2.1): rich-text sanitising
//  - phpmailer/phpmailer v6.9.3 (LGPL-2.1): email (SMTP or PHP mail())
if (!defined('HTMLPURIFIER_PREFIX')) {
    define('HTMLPURIFIER_PREFIX', __DIR__ . '/ezyang/htmlpurifier/library');
}
require_once HTMLPURIFIER_PREFIX . '/HTMLPurifier/Bootstrap.php';
require_once HTMLPURIFIER_PREFIX . '/HTMLPurifier.autoload.php';

spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'PHPMailer\\PHPMailer\\', 20) === 0) {
        $file = __DIR__ . '/phpmailer/phpmailer/src/' . substr($class, 20) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});
