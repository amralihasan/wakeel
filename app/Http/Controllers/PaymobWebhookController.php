<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymobWebhookController extends Controller
{
    /**
     * Handle Paymob webhook callbacks.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('Paymob Webhook received', ['payload' => $payload]);

        $hmac = $request->query('hmac');
        if (! $hmac) {
            Log::warning('Paymob Webhook missing HMAC query parameter');

            return response()->json(['error' => 'Missing signature'], 400);
        }

        if (! $this->verifySignature($payload, $hmac)) {
            Log::warning('Paymob Webhook signature verification failed');

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $obj = $payload['obj'] ?? null;
        if (! $obj) {
            return response()->json(['status' => 'ignored']);
        }

        $success = $obj['success'] ?? false;
        $order = $obj['order'] ?? null;

        if ($success && $order) {
            $merchantOrderId = $order['merchant_order_id'] ?? '';

            // Extract company ID and plan name from: company_{id}_plan_{planName}_{timestamp}
            if (preg_match('/^company_(\d+)_plan_([a-zA-Z0-9_-]+)_/', $merchantOrderId, $matches)) {
                $companyId = (int) $matches[1];
                $planName = $matches[2];

                $company = Company::find($companyId);
                if ($company) {
                    $company->update([
                        'plan' => $planName,
                        'paymob_subscription_id' => (string) ($obj['id'] ?? ''),
                    ]);
                    $company->rolloverBillingCycle();

                    Log::info('Successfully processed Paymob payment for company', [
                        'company_id' => $companyId,
                        'plan' => $planName,
                        'transaction_id' => $obj['id'] ?? null,
                    ]);

                    return response()->json(['status' => 'success']);
                }

                Log::error('Company not found during Paymob webhook processing', ['company_id' => $companyId]);
            } else {
                Log::warning('Paymob merchant_order_id does not match expected pattern', ['merchant_order_id' => $merchantOrderId]);
            }
        }

        return response()->json(['status' => 'ignored']);
    }

    /**
     * Verify Paymob HMAC signature.
     */
    private function verifySignature(array $payload, string $hmac): bool
    {
        $obj = $payload['obj'] ?? null;
        if (! $obj) {
            return false;
        }

        $order = $obj['order'] ?? null;
        $sourceData = $obj['source_data'] ?? null;

        if (! $order || ! $sourceData) {
            return false;
        }

        // Concatenate parameters in alphabetical / specified order as required by Paymob Accept API
        $stringToHash =
            ($obj['amount_cents'] ?? '').
            ($obj['created_at'] ?? '').
            ($obj['currency'] ?? '').
            (($obj['error_occured'] ?? false) ? 'true' : 'false').
            (($obj['has_parent_transaction'] ?? false) ? 'true' : 'false').
            ($obj['id'] ?? '').
            ($obj['integration_id'] ?? '').
            (($obj['is_3d_secure'] ?? false) ? 'true' : 'false').
            (($obj['is_auth'] ?? false) ? 'true' : 'false').
            (($obj['is_capture'] ?? false) ? 'true' : 'false').
            (($obj['is_refunded'] ?? false) ? 'true' : 'false').
            (($obj['is_standalone_payment'] ?? false) ? 'true' : 'false').
            (($obj['is_voided'] ?? false) ? 'true' : 'false').
            ($order['id'] ?? '').
            ($obj['owner'] ?? '').
            (($obj['pending'] ?? false) ? 'true' : 'false').
            ($sourceData['pan'] ?? '').
            ($sourceData['sub_type'] ?? '').
            ($sourceData['type'] ?? '').
            (($obj['success'] ?? false) ? 'true' : 'false');

        $calculatedHmac = hash_hmac('sha512', $stringToHash, config('paymob.hmac_secret') ?? '');

        return hash_equals($calculatedHmac, $hmac);
    }
}
