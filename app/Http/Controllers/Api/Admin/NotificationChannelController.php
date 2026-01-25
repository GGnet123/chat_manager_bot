<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationChannelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $businessId = $request->query('business_id', $user->business_id);

        $query = NotificationChannel::query();

        if ($businessId) {
            $query->where('business_id', $businessId);
        } elseif (!$user->isSuperAdmin()) {
            $businessIds = $user->businesses->pluck('id')->toArray();
            if ($user->business_id) {
                $businessIds[] = $user->business_id;
            }
            $query->whereIn('business_id', $businessIds);
        }

        if ($request->has('platform')) {
            $query->where('platform', $request->platform);
        }

        $channels = $query->get();

        return response()->json($channels);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'platform' => 'required|string|in:whatsapp,telegram',
            'chat_id' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $channel = NotificationChannel::create($validated);

        return response()->json($channel, 201);
    }

    public function show(NotificationChannel $notificationChannel): JsonResponse
    {
        return response()->json($notificationChannel);
    }

    public function update(Request $request, NotificationChannel $notificationChannel): JsonResponse
    {
        $validated = $request->validate([
            'platform' => 'sometimes|string|in:whatsapp,telegram',
            'chat_id' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:255',
            'is_active' => 'boolean',
        ]);

        $notificationChannel->update($validated);

        return response()->json($notificationChannel);
    }

    public function destroy(NotificationChannel $notificationChannel): JsonResponse
    {
        $notificationChannel->delete();

        return response()->json(null, 204);
    }
}
