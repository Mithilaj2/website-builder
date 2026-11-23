<?php
declare(strict_types=1);

namespace SiteRuntime\Shop;

/**
 * The visitor's cart, kept in a signed cookie (no database rows, no PHP session):
 * a list of [product id, variant id, quantity] and an optional coupon code.
 * The signature stops visitors editing the cookie; prices are always looked up again.
 */
final class Cart
{
    public const MAX_LINES = 30;
    public const MAX_QTY = 20;

    private array $items = [];
    private string $coupon = '';

    public function __construct(private string $key, private string $cookieName, private string $path)
    {
        $raw = (string) ($_COOKIE[$cookieName] ?? '');
        if ($raw !== '' && preg_match('/^([A-Za-z0-9_-]{1,4000})\.([a-f0-9]{32})$/', $raw, $m)
            && hash_equals(substr(hash_hmac('sha256', 'cart|' . $m[1], $key), 0, 32), $m[2])) {
            $d = json_decode((string) base64_decode(strtr($m[1], '-_', '+/')), true);
            if (is_array($d)) {
                foreach (array_slice((array) ($d['i'] ?? []), 0, self::MAX_LINES) as $l) {
                    if (is_array($l) && count($l) === 3) {
                        $this->items[] = [max(0, (int) $l[0]), max(0, (int) $l[1]), max(1, min(self::MAX_QTY, (int) $l[2]))];
                    }
                }
                $this->coupon = preg_match('/^[A-Z0-9_-]{3,30}$/', (string) ($d['c'] ?? '')) ? (string) $d['c'] : '';
            }
        }
    }

    /** @return list<array{0:int,1:int,2:int}> [productId, variantId, qty] */
    public function items(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return array_sum(array_column($this->items, 2));
    }

    public function coupon(): string
    {
        return $this->coupon;
    }

    public function add(int $productId, int $variantId, int $qty): void
    {
        $qty = max(1, min(self::MAX_QTY, $qty));
        foreach ($this->items as &$l) {
            if ($l[0] === $productId && $l[1] === $variantId) {
                $l[2] = min(self::MAX_QTY, $l[2] + $qty);
                return;
            }
        }
        if (count($this->items) < self::MAX_LINES) {
            $this->items[] = [$productId, $variantId, $qty];
        }
    }

    /** Set the quantity of line $i (0 removes it). */
    public function set(int $i, int $qty): void
    {
        if (!isset($this->items[$i])) {
            return;
        }
        if ($qty <= 0) {
            array_splice($this->items, $i, 1);
            return;
        }
        $this->items[$i][2] = min(self::MAX_QTY, $qty);
    }

    /** Replace the lines (after pricing dropped or capped some). */
    public function replace(array $items): void
    {
        $this->items = array_values($items);
    }

    public function setCoupon(string $code): void
    {
        $this->coupon = $code;
    }

    public function clear(): void
    {
        $this->items = [];
        $this->coupon = '';
    }

    public function save(): void
    {
        if (headers_sent()) {
            return;
        }
        $opts = ['path' => $this->path ?: '/', 'httponly' => true, 'samesite' => 'Lax',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'];
        if (!$this->items && $this->coupon === '') {
            setcookie($this->cookieName, '', ['expires' => time() - 3600] + $opts);
            return;
        }
        $payload = rtrim(strtr(base64_encode((string) json_encode(['i' => $this->items, 'c' => $this->coupon])), '+/', '-_'), '=');
        setcookie($this->cookieName, $payload . '.' . substr(hash_hmac('sha256', 'cart|' . $payload, $this->key), 0, 32), ['expires' => time() + 30 * 86400] + $opts);
    }
}
