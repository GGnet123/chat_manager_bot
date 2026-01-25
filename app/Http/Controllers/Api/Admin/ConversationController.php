<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConversationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = Conversation::with(['client', 'business']);

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

        // Filter by platform
        if ($request->has('platform')) {
            $query->where('platform', $request->platform);
        }

        // Filter by client
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Search by client name/phone
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('client', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('telegram_id', 'like', "%{$search}%");
            });
        }

        $conversations = $query->latest('last_message_at')
            ->paginate($request->query('per_page', 15));

        return ConversationResource::collection($conversations);
    }

    public function show(Conversation $conversation): ConversationResource
    {
        return new ConversationResource($conversation->load(['client', 'business', 'actions']));
    }

    public function messages(Conversation $conversation): JsonResponse
    {
        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(fn ($msg) => [
                'id' => $msg->id,
                'role' => $msg->role,
                'content' => $msg->content,
                'created_at' => $msg->created_at,
                'metadata' => $msg->metadata,
            ]);

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages' => $messages,
        ]);
    }
}
