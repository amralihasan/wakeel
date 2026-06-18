<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymobService
{
    private string $apiKey;

    private string $iframeId;

    private int $cardIntegrationId;

    public function __construct()
    {
        $this->apiKey = config('paymob.api_key') ?? '';
        $this->iframeId = config('paymob.iframe_id') ?? '';
        $this->cardIntegrationId = (int) config('paymob.integrations.card', 0);
    }

    /**
     * Authenticate and get token from Paymob.
     *
     * @throws Exception
     */
    public function getAuthToken(): string
    {
        $response = Http::post('https://accept.paymob.com/api/auth/tokens', [
            'api_key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            Log::error('Paymob Auth failed', ['response' => $response->body()]);
            throw new Exception('Failed to authenticate with Paymob.');
        }

        return $response->json('token');
    }

    /**
     * Register an order with Paymob.
     *
     * @throws Exception
     */
    public function registerOrder(string $authToken, float $amount, string $merchantOrderId): int
    {
        $amountCents = (int) round($amount * 100);

        $response = Http::post('https://accept.paymob.com/api/ecommerce/orders', [
            'auth_token' => $authToken,
            'delivery_needed' => false,
            'amount_cents' => $amountCents,
            'currency' => 'EGP',
            'merchant_order_id' => $merchantOrderId,
            'items' => [],
        ]);

        if ($response->failed()) {
            Log::error('Paymob Order Registration failed', ['response' => $response->body()]);
            throw new Exception('Failed to register order with Paymob.');
        }

        return $response->json('id');
    }

    /**
     * Get the payment key token for the iframe.
     *
     * @throws Exception
     */
    public function getPaymentKey(string $authToken, int $paymobOrderId, float $amount, array $billingData): string
    {
        $amountCents = (int) round($amount * 100);

        // Map and merge with default required fields to prevent Paymob validation errors
        $formattedBillingData = array_merge([
            'apartment' => 'NA',
            'email' => 'NA',
            'floor' => 'NA',
            'first_name' => 'NA',
            'street' => 'NA',
            'building' => 'NA',
            'phone_number' => 'NA',
            'shipping_method' => 'NA',
            'postal_code' => 'NA',
            'city' => 'NA',
            'country' => 'EG',
            'last_name' => 'NA',
            'state' => 'NA',
        ], $billingData);

        $response = Http::post('https://accept.paymob.com/api/acceptance/payment_keys', [
            'auth_token' => $authToken,
            'amount_cents' => $amountCents,
            'expiration' => 3600,
            'order_id' => $paymobOrderId,
            'billing_data' => $formattedBillingData,
            'currency' => 'EGP',
            'integration_id' => $this->cardIntegrationId,
        ]);

        if ($response->failed()) {
            Log::error('Paymob Payment Key generation failed', ['response' => $response->body()]);
            throw new Exception('Failed to generate payment key from Paymob.');
        }

        return $response->json('token');
    }

    /**
     * Generate the checkout iframe URL.
     */
    public function getCheckoutUrl(string $paymentToken): string
    {
        return "https://accept.paymob.com/api/acceptance/iframes/{$this->iframeId}?payment_token={$paymentToken}";
    }
}
