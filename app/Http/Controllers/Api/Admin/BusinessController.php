<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessResource;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class BusinessController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = Business::query();

        // Non-super admins can only see their assigned businesses
        if (!$user->isSuperAdmin()) {
            $query->whereHas('users', fn ($q) => $q->where('user_id', $user->id));
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $businesses = $query->paginate($request->query('per_page', 15));

        return BusinessResource::collection($businesses);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Business::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:businesses',
            'whatsapp_phone_id' => 'nullable|string|max:255',
            'whatsapp_access_token' => 'nullable|string',
            'telegram_bot_token' => 'nullable|string',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $business = Business::create($validated);

        return response()->json(new BusinessResource($business), 201);
    }

    public function show(Business $business): BusinessResource
    {
        $this->authorize('view', $business);

        return new BusinessResource($business->load(['users', 'gptConfigurations']));
    }

    public function update(Request $request, Business $business): BusinessResource
    {
        $this->authorize('update', $business);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:businesses,slug,' . $business->id,
            'whatsapp_phone_id' => 'nullable|string|max:255',
            'whatsapp_access_token' => 'nullable|string',
            'telegram_bot_token' => 'nullable|string',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $business->update($validated);

        return new BusinessResource($business);
    }

    public function destroy(Business $business): JsonResponse
    {
        $this->authorize('delete', $business);

        $business->delete();

        return response()->json(null, 204);
    }
}
