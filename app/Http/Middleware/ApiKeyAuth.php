<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates /v1/* requests with a tenant API key passed as
 * `Authorization: Bearer <key>` or `X-Api-Key: <key>`.
 * On success, sets `api_key` and `tenant_id` on the request.
 */
class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = $request->bearerToken() ?: $request->header('X-Api-Key');

        if (! $secret) {
            return response()->json(['error' => 'missing_api_key'], 401);
        }

        $key = ApiKey::findActiveBySecret($secret);

        if (! $key) {
            return response()->json(['error' => 'invalid_api_key'], 401);
        }

        $key->forceFill(['last_used_at' => now()])->saveQuietly();

        $request->attributes->set('api_key', $key);
        $request->attributes->set('tenant_id', $key->tenant_id);

        return $next($request);
    }
}
