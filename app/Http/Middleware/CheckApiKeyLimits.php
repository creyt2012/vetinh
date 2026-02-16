<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiKeyLimits
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-KEY');

        if (!$key) {
            return response()->json(['status' => 'error', 'message' => 'API Key required'], 401);
        }

        $apiKey = \App\Models\ApiKey::where('key', $key)->active()->first();

        if (!$apiKey) {
            return response()->json(['status' => 'error', 'message' => 'Invalid or inactive API Key'], 403);
        }

        // 1. Check Monthly Quota
        if ($apiKey->usage_count >= $apiKey->monthly_quota) {
            return response()->json(['status' => 'error', 'message' => 'Monthly quota exceeded'], 429);
        }

        // 2. Rate Limiting (RPM) using Cache
        $cacheKey = "api_limit:{$apiKey->key}";
        $requests = \Illuminate\Support\Facades\Cache::get($cacheKey, 0);

        if ($requests >= $apiKey->rate_limit) {
            return response()->json(['status' => 'error', 'message' => 'Rate limit exceeded (RPM)'], 429);
        }

        // Increment counts
        \Illuminate\Support\Facades\Cache::put($cacheKey, $requests + 1, now()->addMinute());
        $apiKey->increment('usage_count');
        $apiKey->update(['last_used_at' => now()]);

        return $next($request);
    }
}
