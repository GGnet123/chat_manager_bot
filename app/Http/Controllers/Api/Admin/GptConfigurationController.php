<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GptConfiguration;
use App\Services\AI\ChatGptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GptConfigurationController extends Controller
{
    public function __construct(
        private ChatGptService $chatGptService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $businessId = $request->query('business_id', $user->business_id);

        $query = GptConfiguration::query();

        if ($businessId) {
            $query->where('business_id', $businessId);
        } elseif (!$user->isSuperAdmin()) {
            $businessIds = $user->businesses->pluck('id')->toArray();
            if ($user->business_id) {
                $businessIds[] = $user->business_id;
            }
            $query->whereIn('business_id', $businessIds);
        }

        $configs = $query->get();

        return response()->json($configs);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:100',
            'max_tokens' => 'required|integer|min:1|max:4096',
            'temperature' => 'required|numeric|min:0|max:2',
            'system_prompt' => 'nullable|string',
            'available_actions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $config = GptConfiguration::create($validated);

        // If this is set as active, deactivate others
        if ($config->is_active) {
            $config->activate();
        }

        return response()->json($config, 201);
    }

    public function show(GptConfiguration $gptConfig): JsonResponse
    {
        return response()->json($gptConfig);
    }

    public function update(Request $request, GptConfiguration $gptConfig): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'model' => 'sometimes|string|max:100',
            'max_tokens' => 'sometimes|integer|min:1|max:4096',
            'temperature' => 'sometimes|numeric|min:0|max:2',
            'system_prompt' => 'nullable|string',
            'available_actions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $gptConfig->update($validated);

        return response()->json($gptConfig);
    }

    public function destroy(GptConfiguration $gptConfig): JsonResponse
    {
        $gptConfig->delete();

        return response()->json(null, 204);
    }

    public function activate(GptConfiguration $gptConfig): JsonResponse
    {
        $gptConfig->activate();

        return response()->json([
            'message' => 'Configuration activated successfully',
            'config' => $gptConfig,
        ]);
    }

    public function test(Request $request, GptConfiguration $gptConfig): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $result = $this->chatGptService->complete(
            messages: [['role' => 'user', 'content' => $validated['message']]],
            config: $gptConfig,
        );

        return response()->json([
            'response' => $result->getDisplayContent(),
            'actions' => $result->actions,
            'tokens' => [
                'prompt' => $result->promptTokens,
                'completion' => $result->completionTokens,
                'total' => $result->getTotalTokens(),
            ],
        ]);
    }
}
