<?php

namespace App\Contracts;

use App\Models\Company;
use App\Models\Subscription;
use Illuminate\Http\Request;

interface PaymentGatewayContract
{
    public function startCheckout(Company $company, string $planKey, string $method): CheckoutSession;

    public function chargeRecurring(Subscription $subscription, int $amountCents): ChargeResult;

    public function verifyWebhook(Request $request): WebhookEvent;
}
