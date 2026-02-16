<?php

namespace App\Services\Billing\Gateways;

use App\Models\Plan;
use App\Models\Tenant;

interface PaymentGatewayInterface
{
    public function createSubscription(Tenant $tenant, Plan $plan): string;
    public function cancelSubscription(string $subscriptionId): bool;
    public function handleWebhook(array $payload): bool;
}
