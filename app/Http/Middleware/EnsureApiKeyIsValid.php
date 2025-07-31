<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiKeyIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $submittedApiKey = $request->header('X-API-KEY');
        $validApiKey = config('services.crawler.api_key');

        if (!$validApiKey) {
            // Failsafe: if the API key is not configured, deny all access.
            abort(500, 'API Key is not configured on the server.');
        }

        if ($submittedApiKey !== $validApiKey) {
            abort(401, 'Unauthorized: Invalid API Key.');
        }

        return $next($request);
    }
}
