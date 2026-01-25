<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTelegramSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');
        $expectedToken = config('telegram.webhook_secret');

        if (!$expectedToken) {
            // If no secret is configured, skip verification
            return $next($request);
        }

        if (!hash_equals($expectedToken, $token ?? '')) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        return $next($request);
    }
}
