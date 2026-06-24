<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards premium/white-label routes.
 *
 * In the MVP there is no payment flow — access is token-gated via the
 * SINGELKARTAN_PREMIUM_TOKEN env variable. Pass the token as a query
 * parameter (?token=…) or in the Authorization header (Bearer …).
 *
 * When SINGELKARTAN_PREMIUM_MODE is false (default) all requests pass
 * through so the route is accessible for local development/preview.
 */
class RequiresPremium
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('singelkartan.premium_mode')) {
            return $next($request);
        }

        $configuredToken = (string) config('singelkartan.premium_token');

        if ($configuredToken === '') {
            abort(Response::HTTP_SERVICE_UNAVAILABLE, 'Premium token not configured.');
        }

        $provided = $request->bearerToken() ?? $request->query('token', '');

        if (! hash_equals($configuredToken, (string) $provided)) {
            abort(Response::HTTP_UNAUTHORIZED, 'Valid premium token required.');
        }

        return $next($request);
    }
}
