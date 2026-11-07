<?php
declare(strict_types=1);

namespace SiteRuntime\Shop;

/**
 * Prices a cart: current product prices and stock, coupon, GST (prices including or excluding tax),
 * shipping and the cash-on-delivery fee. All amounts in paise. Nothing is trusted from the browser
 * except product ids, option ids and quantities.
 *
 * GST: the discount is shared across lines in proportion to their value, and tax is worked out on each
 * line after its share of the discount. Shipping and the COD fee are charged without GST.
 */
final class Pricing
{
    public function __construct(private ShopService $shop)
    {
    }

    /**
     * @param list<array{0:int,1:int,2:int}> $items [productId, variantId, qty]
     * @return array{lines:list<array>, items:list<array{0:int,1:int,2:int}>, notices:list<string>, subtotal:int, discount:int,
     *               coupon:?array, coupon_error:string, shipping:int, cod_fee:int, tax:int, total:int, prices_include_tax:bool,
     *               inter_state:bool, count:int, min_order_ok:bool, cod_available:bool, free_shipping_gap:int}
     */
    public function quote(array $items, string $couponCode = '', string $method = 'online', string $state = ''): array
    {
        $s = $this->shop->settings();
        $db = $this->shop->db();
        $ids = array_values(array_unique(array_map(static fn ($l) => (int) $l[0], $items)));
        $products = [];
        if ($ids) {
            foreach ($db->fetchAll('SELECT * FROM ' . $db->t('products') . " WHERE status = 'active' AND id IN (" . implode(',', $ids) . ')') as $p) {
                $products[(int) $p['id']] = $p + ['variants' => []];
            }
            foreach ($db->fetchAll('SELECT * FROM ' . $db->t('product_variants') . ' WHERE product_id IN (' . implode(',', $ids) . ') ORDER BY sort_order, id') as $v) {
                if (isset($products[(int) $v['product_id']])) {
                    $products[(int) $v['product_id']]['variants'][(int) $v['id']] = $v;
                }
            }
        }
        $lines = [];
        $kept = [];
        $notices = [];
        $used = [];
        foreach ($items as [$pid, $vid, $qty]) {
            $p = $products[$pid] ?? null;
            if (!$p) {
                $notices[] = 'An item in your cart is no longer available and was removed.';
                continue;
            }
            $v = null;
            if ($p['variants']) {
                $v = $p['variants'][$vid] ?? null;
                if (!$v) {
                    $notices[] = $p['name'] . ': choose an option again.';
                    continue;
                }
            } elseif ($vid !== 0) {
                $vid = 0;
            }
            $stockKey = $pid . ':' . $vid;
            if ((int) $p['track_stock']) {
                $available = (int) ($v ? $v['stock'] : $p['stock']) - ($used[$stockKey] ?? 0);
                if ($available <= 0) {
                    $notices[] = $p['name'] . ($v ? ' (' . $v['label'] . ')' : '') . ' is out of stock and was removed.';
                    continue;
                }
                if ($qty > $available) {
                    $notices[] = 'Only ' . $available . ' left of ' . $p['name'] . ($v ? ' (' . $v['label'] . ')' : '') . '; the quantity was reduced.';
                    $qty = $available;
                }
            }
            $used[$stockKey] = ($used[$stockKey] ?? 0) + $qty;
            $unit = (int) ($v && $v['price'] !== null ? $v['price'] : $p['price']);
            $kept[] = [$pid, $vid, $qty];
            $lines[] = [
                'product_id' => $pid, 'variant_id' => $vid, 'name' => (string) $p['name'], 'slug' => (string) $p['slug'], 'image' => (string) ($p['image'] ?? ''),
                'variant_label' => $v ? (string) $v['label'] : '', 'option_name' => (string) ($p['option_name'] ?? ''),
                'sku' => (string) (($v['sku'] ?? '') ?: ($p['sku'] ?? '')), 'hsn' => (string) ($p['hsn'] ?? ''),
                'unit_price' => $unit, 'qty' => $qty, 'gross' => $unit * $qty, 'gst_rate' => (string) $p['gst_rate'],
                'max_qty' => (int) $p['track_stock'] ? min(Cart::MAX_QTY, (int) ($v ? $v['stock'] : $p['stock'])) : Cart::MAX_QTY,
            ];
        }
        $subtotal = array_sum(array_column($lines, 'gross'));

        // Coupon
        $coupon = null;
        $couponError = '';
        $discount = 0;
        $freeShip = false;
        if ($couponCode !== '' && $lines) {
            $c = $this->shop->couponByCode($couponCode);
            if (!$c) {
                $couponError = 'The code ' . $couponCode . ' is not valid or has expired.';
            } elseif ($subtotal < (int) $c['min_order']) {
                $couponError = 'The code ' . $c['code'] . ' needs an order of at least ₹' . ShopService::money((int) $c['min_order']) . '.';
            } else {
                $coupon = $c;
                if ($c['type'] === 'percent') {
                    $discount = intdiv($subtotal * (int) $c['value'], 100);
                    if ($c['max_discount'] !== null) {
                        $discount = min($discount, (int) $c['max_discount']);
                    }
                } elseif ($c['type'] === 'fixed') {
                    $discount = min((int) $c['value'], $subtotal);
                } else {
                    $freeShip = true;
                }
            }
        }

        // Share the discount across lines, then work out GST per line.
        $inclusive = (bool) $s['prices_include_tax'];
        $left = $discount;
        $tax = 0;
        $itemsTotal = 0;
        foreach ($lines as $i => &$l) {
            $share = $i === array_key_last($lines) ? $left : ($subtotal > 0 ? intdiv($discount * $l['gross'], $subtotal) : 0);
            $share = min($share, $left, $l['gross']);
            $left -= $share;
            $net = $l['gross'] - $share;
            $bp = (int) round(((float) $l['gst_rate']) * 100); // basis points: 18% = 1800
            if ($inclusive) {
                $lineTax = (int) round($net * $bp / (10000 + $bp));
                $l['taxable'] = $net - $lineTax;
                $l['line_total'] = $net;
            } else {
                $lineTax = (int) round($net * $bp / 10000);
                $l['taxable'] = $net;
                $l['line_total'] = $net + $lineTax;
            }
            $l['discount'] = $share;
            $l['tax'] = $lineTax;
            $tax += $lineTax;
            $itemsTotal += $l['line_total'];
        }
        unset($l);

        $afterDiscount = $subtotal - $discount;
        $freeAbove = (int) $s['free_shipping_above'];
        $shipping = (!$lines || $freeShip || ($freeAbove > 0 && $afterDiscount >= $freeAbove)) ? 0 : (int) $s['shipping_flat'];
        $codAvailable = (bool) $s['cod_enabled'] && $itemsTotal + $shipping + (int) $s['cod_fee'] <= (int) $s['cod_max'];
        $codFee = $method === 'cod' ? (int) $s['cod_fee'] : 0;
        return [
            'lines' => $lines, 'items' => $kept, 'notices' => array_values(array_unique($notices)),
            'subtotal' => $subtotal, 'discount' => $discount, 'coupon' => $coupon, 'coupon_error' => $couponError,
            'shipping' => $shipping, 'cod_fee' => $codFee, 'tax' => $tax, 'total' => $itemsTotal + $shipping + $codFee,
            'prices_include_tax' => $inclusive, 'inter_state' => $state !== '' && $state !== $s['store_state'],
            'count' => array_sum(array_column($lines, 'qty')), 'min_order_ok' => $afterDiscount >= (int) $s['min_order'],
            'cod_available' => $codAvailable, 'free_shipping_gap' => $freeAbove > 0 && $shipping > 0 ? max(0, $freeAbove - $afterDiscount) : 0,
        ];
    }
}
