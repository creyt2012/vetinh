<?php

namespace App\Services\Billing\Gateways;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class StripeGateway implements PaymentGatewayInterface
{
    public function createSubscription(Tenant $tenant, Plan $plan): string
    {
        // Enforce Enterprise Logic: Sync with Stripe API (Mocking the call for now)
        Log::info("Initiating Stripe Checkout for Tenant #{$tenant->id} on Plan: {$plan->name}");

        // This would typically involve \Stripe\Checkout\Session::create(...)
        $mockCheckoutId = "cs_test_" . bin2hex(random_bytes(16));

        $tenant->update([
            'billing_status' => 'PENDING',
            'last_gateway' => 'stripe'
        ]);

        return $mockCheckoutId;
    }

    public function cancelSubscription(string $subscriptionId): bool
    {
        Log::info("Terminating Stripe Subscription: {$subscriptionId}");

        // Logic to communicate with Stripe API
        return true;
    }

    public function handleWebhook(array $payload): bool
    {
        $event = $payload['type'] ?? 'unknown';
        Log::info("PROCESSING_STRIPE_WEBHOOK: {$event}");

        switch ($event) {
            case 'checkout.session.completed':
                // Update Tenant subscription status
                break;
            case 'customer.subscription.deleted':
                // Revoke access
                break;
        }

        return true;
    }
}
