<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        $t = Session::get('_csrf');
        if (!is_string($t) || strlen($t) !== 64) {
            $t = bin2hex(random_bytes(32));
            Session::set('_csrf', $t);
        }
        return $t;
    }

    /** Accepts the token from the X-CSRF-Token header (AJAX) or the _token form field. */
    public static function verify(): bool
    {
        $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_token'] ?? '');
        $real = Session::get('_csrf');
        return is_string($sent) && is_string($real) && $real !== '' && hash_equals($real, $sent);
    }
}
