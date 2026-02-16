<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\Billing\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'gateway' => 'required|in:stripe,paypal',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $tenant = auth()->user()->tenant; // Assuming user belongs to tenant

        try {
            $gateway = $this->paymentManager->driver($request->gateway);
            $sessionId = $gateway->createSubscription($tenant, $plan);

            return response()->json([
                'status' => 'success',
                'session_id' => $sessionId,
                'gateway' => $request->gateway
            ]);
        } catch (\Exception $e) {
            Log::error("Checkout failed: " . $e->getMessage());
            return response()->json(['error' => 'Payment initiation failed'], 500);
        }
    }

    public function webhook(Request $request, string $gatewayName)
    {
        try {
            $gateway = $this->paymentManager->driver($gatewayName);
            $gateway->handleWebhook($request->all());
            return response()->json(['status' => 'received']);
        } catch (\Exception $e) {
            Log::error("Webhook failed for {$gatewayName}: " . $e->getMessage());
            return response()->json(['error' => 'Webhook processing failed'], 400);
        }
    }
}
