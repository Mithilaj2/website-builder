<?php
declare(strict_types=1);

namespace SiteRuntime\Shop;

/** GST tax invoice for an order, as a PDF. */
final class Invoice
{
    private const L = 40.0;
    private const R = 555.28;

    public static function pdf(array $order, array $shop, array $site): string
    {
        $pdf = new Pdf();
        $pdf->addPage();
        $m = static fn (int $p): string => 'Rs. ' . ShopService::money($p);
        $mm = static fn (int $p): string => \SiteRuntime\Payments\PaymentService::inr($p);
        $seller = trim((string) ($shop['legal_name'] ?: ($site['name'] ?? 'Shop')));
        $sellerAddr = trim((string) ($shop['invoice_address'] ?: ($site['address'] ?? '')));

        // Header band
        $pdf->box(0, 0, Pdf::W, 8, 30, 41, 59);
        $pdf->font('B', 16);
        $pdf->color(30, 41, 59);
        $pdf->text(self::L, 52, $seller);
        $pdf->font('', 9);
        $pdf->color(90, 100, 115);
        $y = 68;
        foreach ($pdf->wrap($sellerAddr, 280) as $line) {
            $pdf->text(self::L, $y, $line);
            $y += 12;
        }
        $contact = array_filter([(string) ($site['phone'] ?? ''), (string) ($site['email'] ?? '')]);
        if ($contact) {
            $pdf->text(self::L, $y, implode('  ·  ', $contact));
            $y += 12;
        }
        if ($shop['gstin'] !== '') {
            $pdf->font('B', 9);
            $pdf->color(30, 41, 59);
            $pdf->text(self::L, $y, 'GSTIN: ' . $shop['gstin']);
            $y += 12;
        }
        $pdf->font('B', 18);
        $pdf->color(30, 41, 59);
        $pdf->textRight(self::R, 52, $shop['gstin'] !== '' ? 'TAX INVOICE' : 'INVOICE');
        $pdf->font('', 9);
        $pdf->color(90, 100, 115);
        $meta = ['Invoice no.' => (string) $order['invoice_no'], 'Invoice date' => date('d M Y', strtotime((string) ($order['invoiced_at'] ?: $order['created_at']))),
            'Order no.' => (string) $order['order_no'], 'Order date' => date('d M Y', strtotime((string) $order['created_at']))];
        $my = 70;
        foreach ($meta as $k => $v) {
            $pdf->font('', 9);
            $pdf->textRight(self::R - 110, $my, $k);
            $pdf->font('B', 9);
            $pdf->textRight(self::R, $my, $v);
            $my += 13;
        }
        $y = max($y, $my) + 14;
        $pdf->line(self::L, $y, self::R, $y, 0.8, 210);
        $y += 20;

        // Billing / shipping
        $pdf->font('B', 8);
        $pdf->color(120, 130, 145);
        $pdf->text(self::L, $y, 'BILL & SHIP TO');
        $pdf->text(330, $y, 'PLACE OF SUPPLY');
        $y += 14;
        $pdf->font('B', 10);
        $pdf->color(30, 41, 59);
        $pdf->text(self::L, $y, (string) $order['customer_name']);
        $pdf->text(330, $y, (string) $order['state']);
        $pdf->font('', 9);
        $pdf->color(70, 80, 95);
        $by = $y + 13;
        foreach (array_merge($pdf->wrap((string) $order['address'], 260), [$order['city'] . ' - ' . $order['pincode'] . ', ' . $order['state'], $order['customer_phone'] . '  ·  ' . $order['customer_email']]) as $line) {
            $pdf->text(self::L, $by, $line);
            $by += 12;
        }
        if ($order['customer_gstin']) {
            $pdf->font('B', 9);
            $pdf->text(self::L, $by, 'GSTIN: ' . $order['customer_gstin']);
            $by += 12;
        }
        $pdf->font('', 9);
        $pdf->text(330, $y + 13, 'Payment: ' . ($order['payment_method'] === 'cod' ? 'Cash on delivery' : 'Online') . ' (' . ($order['payment_status'] === 'paid' ? 'paid' : 'due') . ')');
        $y = $by + 16;

        // Items table
        $cols = [['#', self::L + 4, 'l'], ['Item', self::L + 22, 'l'], ['Qty', 300, 'r'], ['Rate', 352, 'r'], ['Taxable', 418, 'r'], ['GST', 462, 'r'], ['Amount', self::R - 4, 'r']];
        $head = static function (Pdf $pdf, float $y) use ($cols): void {
            $pdf->box(self::L, $y - 12, self::R - self::L, 18, 241, 245, 249);
            $pdf->font('B', 8);
            $pdf->color(71, 85, 105);
            foreach ($cols as [$label, $x, $align]) {
                $align === 'r' ? $pdf->textRight($x, $y, $label) : $pdf->text($x, $y, $label);
            }
        };
        $head($pdf, $y);
        $y += 20;
        $rateGroups = [];
        foreach ($order['items'] as $i => $it) {
            $pdf->font('', 9);
            $name = $pdf->wrap($it['name'] . ($it['variant_label'] ? ' - ' . $it['variant_label'] : ''), 190);
            $sub = trim(($it['hsn'] ? 'HSN ' . $it['hsn'] : '') . ($it['sku'] ? '   SKU ' . $it['sku'] : '') . ((int) $it['discount'] > 0 ? '   Discount ' . $mm((int) $it['discount']) : ''));
            $h = count($name) * 11 + ($sub !== '' ? 11 : 0) + 8;
            if ($y + $h > Pdf::H - 200) {
                $pdf->addPage();
                $y = 60;
                $head($pdf, $y);
                $y += 20;
            }
            $pdf->color(30, 41, 59);
            $pdf->text(self::L + 4, $y, (string) ($i + 1));
            $ly = $y;
            foreach ($name as $line) {
                $pdf->text(self::L + 22, $ly, $line);
                $ly += 11;
            }
            if ($sub !== '') {
                $pdf->font('', 7.5);
                $pdf->color(120, 130, 145);
                $pdf->text(self::L + 22, $ly, $sub);
                $pdf->font('', 9);
                $pdf->color(30, 41, 59);
            }
            $rate = rtrim(rtrim(number_format((float) $it['gst_rate'], 2, '.', ''), '0'), '.');
            $pdf->textRight(300, $y, (string) $it['qty']);
            $pdf->textRight(352, $y, $mm((int) $it['unit_price']));
            $pdf->textRight(418, $y, $mm((int) $it['taxable']));
            $pdf->textRight(462, $y, $rate . '%');
            $pdf->textRight(self::R - 4, $y, $mm((int) $it['line_total']));
            $rateGroups[$rate] = ($rateGroups[$rate] ?? [0, 0]);
            $rateGroups[$rate][0] += (int) $it['taxable'];
            $rateGroups[$rate][1] += (int) $it['tax'];
            $y += $h;
            $pdf->line(self::L, $y - 9, self::R, $y - 9, 0.4, 226);
        }
        if ($y > Pdf::H - 230) {
            $pdf->addPage();
            $y = 60;
        }

        // Tax summary (left) and totals (right)
        $y += 8;
        $ty = $y;
        $pdf->font('B', 8);
        $pdf->color(120, 130, 145);
        $pdf->text(self::L, $ty, 'GST SUMMARY');
        $ty += 14;
        $pdf->font('', 8.5);
        $pdf->color(70, 80, 95);
        $inter = (int) $order['inter_state'] === 1;
        foreach ($rateGroups as $rate => [$taxable, $tax]) {
            if ((float) $rate == 0.0) {
                $pdf->text(self::L, $ty, 'Exempt / nil-rated: taxable ' . $mm($taxable));
            } elseif ($inter) {
                $pdf->text(self::L, $ty, "IGST $rate% on " . $mm($taxable) . ': ' . $mm($tax));
            } else {
                $half = intdiv($tax, 2);
                $pdf->text(self::L, $ty, 'CGST ' . ((float) $rate / 2) . '% ' . $mm($half) . '   SGST ' . ((float) $rate / 2) . '% ' . $mm($tax - $half) . '   (taxable ' . $mm($taxable) . ')');
            }
            $ty += 12;
        }
        $pdf->text(self::L, $ty + 2, (int) $order['prices_include_tax'] ? 'Prices include GST.' : 'GST added to the prices shown.');

        $rows = [['Items', (int) $order['subtotal']]];
        if ((int) $order['discount'] > 0) {
            $rows[] = ['Discount' . ($order['coupon_code'] ? ' (' . $order['coupon_code'] . ')' : ''), -(int) $order['discount']];
        }
        if (!(int) $order['prices_include_tax']) {
            $rows[] = ['GST', (int) $order['tax']];
        }
        $rows[] = ['Shipping', (int) $order['shipping']];
        if ((int) $order['cod_fee'] > 0) {
            $rows[] = ['Cash on delivery fee', (int) $order['cod_fee']];
        }
        $pdf->font('', 9);
        foreach ($rows as [$label, $amount]) {
            $pdf->color(70, 80, 95);
            $pdf->text(360, $y, $label);
            $pdf->textRight(self::R - 4, $y, ($amount < 0 ? '- ' : '') . $mm(abs($amount)));
            $y += 14;
        }
        $pdf->box(352, $y - 10, self::R - 352, 24, 30, 41, 59);
        $pdf->font('B', 11);
        $pdf->color(255, 255, 255);
        $pdf->text(360, $y + 6, 'Total');
        $pdf->textRight(self::R - 6, $y + 6, $m((int) $order['total']));
        $y = max($y + 30, $ty + 20);
        if ((int) $order['prices_include_tax']) {
            $pdf->font('', 8);
            $pdf->color(120, 130, 145);
            $pdf->textRight(self::R - 4, $y, 'Includes GST of ' . $m((int) $order['tax']));
            $y += 12;
        }
        $pdf->font('', 8);
        $pdf->color(140, 150, 160);
        $pdf->line(self::L, Pdf::H - 60, self::R, Pdf::H - 60, 0.5, 220);
        $pdf->text(self::L, Pdf::H - 44, 'This is a computer-generated invoice and does not need a signature.');
        $pdf->textRight(self::R, Pdf::H - 44, 'Thank you for shopping with ' . (string) ($site['name'] ?? $seller) . '.');
        return $pdf->output('Invoice ' . $order['invoice_no']);
    }
}
