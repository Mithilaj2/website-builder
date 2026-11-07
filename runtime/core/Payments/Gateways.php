<?php
declare(strict_types=1);

namespace SiteRuntime\Payments;

/** The gateways a website can use (the builder's wizard reads the same list). */
final class Gateways
{
    /** @var array<string, class-string<Gateway>> */
    public const ALL = ['razorpay' => Razorpay::class, 'phonepe' => PhonePe::class, 'payu' => PayU::class, 'cashfree' => Cashfree::class, 'stripe' => Stripe::class];

    /** @return array<string, array{name:string, fields:list<array>, note:string, webhook_help:string}> */
    public static function definitions(): array
    {
        $out = [];
        foreach (self::ALL as $id => $class) {
            $out[$id] = ['name' => $class::name(), 'fields' => $class::fields(), 'note' => $class::note(), 'webhook_help' => $class::webhookHelp()];
        }
        return $out;
    }

    public static function make(string $id, array $creds, string $mode, ?Http $http = null, ?string $emulator = null): Gateway
    {
        $class = self::ALL[$id] ?? null;
        if ($class === null) {
            throw new GatewayError('Unknown payment gateway.');
        }
        return new $class($creds, $mode === 'live' ? 'live' : 'test', $http ?? new Http(), $mode === 'live' ? null : $emulator);
    }
}
