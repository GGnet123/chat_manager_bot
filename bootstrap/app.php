<?php

use App\Http\Middleware\VerifyTelegramSignature;
use App\Http\Middleware\VerifyWhatsAppSignature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '', // Remove /api prefix since we use api.aibotchat.xyz subdomain
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'verify.whatsapp' => VerifyWhatsAppSignature::class,
            'verify.telegram' => VerifyTelegramSignature::class,
        ]);

        // Enable CORS for API routes
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
