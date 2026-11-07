<?php
declare(strict_types=1);

namespace App\Modules\Websites;

/**
 * Payment gateways offered in wizard step 7. The fields listed are what the website's owner
 * enters in their own admin panel after deployment; the server-side integrations are built in Phase 5.
 */
final class PaymentGateways
{
    public static function all(): array
    {
        return [
            'razorpay' => ['name' => 'Razorpay', 'fields' => ['Key ID', 'Key Secret', 'Webhook Secret'], 'note' => 'Test keys are available immediately after signing up, before KYC.'],
            'phonepe' => ['name' => 'PhonePe', 'fields' => ['Merchant ID', 'Salt Key / API credentials', 'Environment'], 'note' => 'Requires a PhonePe business account; credentials are issued after onboarding.'],
            'payu' => ['name' => 'PayU', 'fields' => ['Merchant Key', 'Merchant Salt', 'Environment'], 'note' => ''],
            'cashfree' => ['name' => 'Cashfree', 'fields' => ['App ID', 'Secret Key', 'Environment'], 'note' => ''],
            'stripe' => ['name' => 'Stripe', 'fields' => ['Publishable Key', 'Secret Key', 'Webhook Secret'], 'note' => 'Stripe accounts in India are by invitation for some business types.'],
        ];
    }
}
