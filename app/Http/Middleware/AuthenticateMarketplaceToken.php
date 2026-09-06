<?php

namespace App\Http\Middleware;

use App\Models\MarketplaceCustomer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMarketplaceToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        if (! $plain) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $accessToken = PersonalAccessToken::findToken($plain);

        if (
            ! $accessToken
            || ! ($accessToken->tokenable instanceof MarketplaceCustomer)
            || ($accessToken->expires_at && $accessToken->expires_at->isPast())
        ) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $customer = $accessToken->tokenable->withAccessToken($accessToken);

        Auth::shouldUse('marketplace');
        Auth::guard('marketplace')->setUser($customer);
        $request->setUserResolver(static fn () => $customer);

        return $next($request);
    }
}
