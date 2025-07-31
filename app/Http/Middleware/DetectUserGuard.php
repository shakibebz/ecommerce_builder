<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class DetectUserGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->bearerToken();

        if ($header) {
            $token = PersonalAccessToken::findToken($header);

            if ($token) {
                $modelType = $token->tokenable_type;

                // Dynamically set guard based on tokenable_type
                if ($modelType === \App\Models\StoreUserAdmin::class) {
                    auth()->shouldUse('store_admin');
                } elseif ($modelType === \App\Models\Tenant::class) {
                    auth()->shouldUse('tenants'); // or 'api' or whatever you're using
                }
            }
        }

        return $next($request);
    }
}
