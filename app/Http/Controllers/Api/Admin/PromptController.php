<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prompt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromptController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $businessId = $request->query('business_id', $user->business_id);

        $query = Prompt::query();

        if ($businessId) {
            $query->where('business_id', $businessId);
        } elseif (!$user->isSuperAdmin()) {
            $businessIds = $user->businesses->pluck('id')->toArray();
            if ($user->business_id) {
                $businessIds[] = $user->business_id;
            }
            $query->whereIn('business_id', $businessIds);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $prompts = $query->get();

        return response()->json($prompts);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'content' => 'required|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $prompt = Prompt::create($validated);

        return response()->json($prompt, 201);
    }

    public function show(Prompt $prompt): JsonResponse
    {
        return response()->json($prompt);
    }

    public function update(Request $request, Prompt $prompt): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|max:100',
            'content' => 'sometimes|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $prompt->update($validated);

        return response()->json($prompt);
    }

    public function destroy(Prompt $prompt): JsonResponse
    {
        $prompt->delete();

        return response()->json(null, 204);
    }
}
