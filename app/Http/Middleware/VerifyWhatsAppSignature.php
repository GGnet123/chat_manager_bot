<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWhatsAppSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip verification for GET requests (webhook verification)
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            return response()->json(['error' => 'Missing signature'], 401);
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, config('whatsapp.app_secret'));

        // The signature from WhatsApp is prefixed with "sha256="
        $providedSignature = str_replace('sha256=', '', $signature);

        if (!hash_equals($expectedSignature, $providedSignature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
