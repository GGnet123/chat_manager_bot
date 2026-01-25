<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManagerNotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $businessId = $request->query('business_id', $user->business_id);

        $query = ManagerNotificationPreference::where('user_id', $user->id);

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $preferences = $query->get();

        return response()->json($preferences);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'in_app' => 'boolean',
            'whatsapp_groups' => 'nullable|array',
            'whatsapp_groups.*' => 'string',
            'telegram_groups' => 'nullable|array',
            'telegram_groups.*' => 'string',
            'action_types' => 'nullable|array',
            'action_types.*' => 'string',
        ]);

        $preference = ManagerNotificationPreference::updateOrCreate(
            [
                'user_id' => $user->id,
                'business_id' => $validated['business_id'],
            ],
            [
                'in_app' => $validated['in_app'] ?? true,
                'whatsapp_groups' => $validated['whatsapp_groups'] ?? [],
                'telegram_groups' => $validated['telegram_groups'] ?? [],
                'action_types' => $validated['action_types'] ?? [],
            ]
        );

        return response()->json($preference);
    }
}
