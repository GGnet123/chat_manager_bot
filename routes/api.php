<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Health check (no auth, no middleware)
Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'php' => PHP_VERSION,
        'laravel' => app()->version(),
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('api.health');

// Webhook routes (no authentication)
Route::prefix('webhook')->group(function () {
    // WhatsApp webhook verification and incoming messages
    Route::get('whatsapp', [\App\Http\Controllers\Webhook\WhatsAppWebhookController::class, 'verify'])
        ->name('webhook.whatsapp.verify');
    Route::post('whatsapp', [\App\Http\Controllers\Webhook\WhatsAppWebhookController::class, 'handle'])
        ->name('webhook.whatsapp.handle');

    // Telegram webhook
    Route::post('telegram/{token}', [\App\Http\Controllers\Webhook\TelegramWebhookController::class, 'handle'])
        ->name('webhook.telegram.handle');
});

// Admin API routes (Sanctum authentication)
Route::prefix('v1')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('login', [\App\Http\Controllers\Api\Admin\AuthController::class, 'login'])
            ->name('api.auth.login');
        Route::post('logout', [\App\Http\Controllers\Api\Admin\AuthController::class, 'logout'])
            ->middleware('auth:sanctum')
            ->name('api.auth.logout');
        Route::get('user', [\App\Http\Controllers\Api\Admin\AuthController::class, 'user'])
            ->middleware('auth:sanctum')
            ->name('api.auth.user');
    });

    // Protected admin routes
    Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
        // Dashboard
        Route::get('dashboard/stats', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'stats'])
            ->name('api.admin.dashboard.stats');
        Route::get('dashboard/activity', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'activity'])
            ->name('api.admin.dashboard.activity');

        // Businesses
        Route::apiResource('businesses', \App\Http\Controllers\Api\Admin\BusinessController::class)
            ->names('api.admin.businesses');

        // Business Users (manage users within a business)
        Route::apiResource('businesses.users', \App\Http\Controllers\Api\Admin\BusinessUserController::class)
            ->names('api.admin.business-users');

        // Users (global user management - super admin and admin managers)
        Route::apiResource('users', \App\Http\Controllers\Api\Admin\UserController::class)
            ->names('api.admin.users');

        // Client Actions
        Route::apiResource('actions', \App\Http\Controllers\Api\Admin\ClientActionController::class)
            ->names('api.admin.actions');
        Route::post('actions/{action}/status', [\App\Http\Controllers\Api\Admin\ClientActionController::class, 'updateStatus'])
            ->name('api.admin.actions.status');
        Route::post('actions/{action}/assign', [\App\Http\Controllers\Api\Admin\ClientActionController::class, 'assign'])
            ->name('api.admin.actions.assign');

        // Conversations
        Route::get('conversations', [\App\Http\Controllers\Api\Admin\ConversationController::class, 'index'])
            ->name('api.admin.conversations.index');
        Route::get('conversations/{conversation}', [\App\Http\Controllers\Api\Admin\ConversationController::class, 'show'])
            ->name('api.admin.conversations.show');
        Route::get('conversations/{conversation}/messages', [\App\Http\Controllers\Api\Admin\ConversationController::class, 'messages'])
            ->name('api.admin.conversations.messages');

        // GPT Configurations
        Route::apiResource('gpt-configs', \App\Http\Controllers\Api\Admin\GptConfigurationController::class)
            ->names('api.admin.gpt-configs');
        Route::post('gpt-configs/{gptConfig}/activate', [\App\Http\Controllers\Api\Admin\GptConfigurationController::class, 'activate'])
            ->name('api.admin.gpt-configs.activate');
        Route::post('gpt-configs/{gptConfig}/test', [\App\Http\Controllers\Api\Admin\GptConfigurationController::class, 'test'])
            ->name('api.admin.gpt-configs.test');

        // Prompts
        Route::apiResource('prompts', \App\Http\Controllers\Api\Admin\PromptController::class)
            ->names('api.admin.prompts');

        // Notification Channels
        Route::apiResource('notification-channels', \App\Http\Controllers\Api\Admin\NotificationChannelController::class)
            ->names('api.admin.notification-channels');

        // Manager Notification Preferences
        Route::get('notification-preferences', [\App\Http\Controllers\Api\Admin\NotificationPreferenceController::class, 'index'])
            ->name('api.admin.notification-preferences.index');
        Route::put('notification-preferences', [\App\Http\Controllers\Api\Admin\NotificationPreferenceController::class, 'update'])
            ->name('api.admin.notification-preferences.update');
    });
});
