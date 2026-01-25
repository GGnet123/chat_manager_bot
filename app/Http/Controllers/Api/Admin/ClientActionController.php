<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ActionStatus;
use App\Events\ActionStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClientActionResource;
use App\Models\ClientAction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientActionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = ClientAction::with(['client', 'assignedUser', 'conversation']);

        // Filter by business
        if (!$user->isSuperAdmin()) {
            $businessIds = $user->businesses->pluck('id')->toArray();
            if ($user->business_id) {
                $businessIds[] = $user->business_id;
            }
            $query->whereIn('business_id', $businessIds);
        } elseif ($request->has('business_id')) {
            $query->where('business_id', $request->business_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by action type
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        // Filter by assigned user
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                    ->orWhere('client_phone', 'like', "%{$search}%");
            });
        }

        $actions = $query->latest()->paginate($request->query('per_page', 15));

        return ClientActionResource::collection($actions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'client_id' => 'required|exists:clients,id',
            'conversation_id' => 'nullable|exists:conversations,id',
            'action' => 'required|string',
            'details' => 'nullable|array',
            'client_name' => 'nullable|string|max:255',
            'client_phone' => 'nullable|string|max:50',
            'priority' => 'nullable|string|in:low,normal,high',
            'notes' => 'nullable|string',
        ]);

        $action = ClientAction::create($validated);

        return response()->json(new ClientActionResource($action->load(['client', 'conversation'])), 201);
    }

    public function show(ClientAction $action): ClientActionResource
    {
        return new ClientActionResource($action->load(['client', 'assignedUser', 'conversation.messages']));
    }

    public function update(Request $request, ClientAction $action): ClientActionResource
    {
        $validated = $request->validate([
            'details' => 'nullable|array',
            'client_name' => 'nullable|string|max:255',
            'client_phone' => 'nullable|string|max:50',
            'priority' => 'nullable|string|in:low,normal,high',
            'notes' => 'nullable|string',
        ]);

        $action->update($validated);

        return new ClientActionResource($action->load(['client', 'assignedUser']));
    }

    public function updateStatus(Request $request, ClientAction $action): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,processing,completed,failed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $oldStatus = $action->status;
        $newStatus = ActionStatus::from($validated['status']);

        $action->update([
            'status' => $newStatus,
            'notes' => $validated['notes'] ?? $action->notes,
            'processed_at' => $newStatus === ActionStatus::Completed ? now() : $action->processed_at,
        ]);

        event(new ActionStatusChanged($action, $oldStatus, $newStatus));

        return response()->json([
            'message' => 'Status updated successfully',
            'action' => new ClientActionResource($action),
        ]);
    }

    public function assign(Request $request, ClientAction $action): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($validated['user_id']);

        $action->update(['assigned_to' => $user->id]);

        return response()->json([
            'message' => 'Action assigned successfully',
            'action' => new ClientActionResource($action->load('assignedUser')),
        ]);
    }
}
