<?php
declare(strict_types=1);

namespace App\Modules\Websites;

use SiteRuntime\Payments\Gateways;

/**
 * Payment gateways offered in wizard step 7: the same list the website runtime implements.
 * The keys themselves are entered by the website's owner in the website admin (Payments → Settings).
 */
final class PaymentGateways
{
    public static function all(): array
    {
        $out = [];
        foreach (Gateways::definitions() as $id => $g) {
            $out[$id] = ['name' => $g['name'], 'fields' => array_column(array_filter($g['fields'], static fn ($f) => $f['required']), 'label'), 'note' => $g['note']];
        }
        return $out;
    }
}
