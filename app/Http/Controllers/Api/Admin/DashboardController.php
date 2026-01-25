<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ActionStatus;
use App\Http\Controllers\Controller;
use App\Models\ClientAction;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $businessId = $request->query('business_id', $user->business_id);

        // Get statistics for the dashboard
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        $actionsQuery = ClientAction::query();
        $conversationsQuery = Conversation::query();

        if ($businessId && !$user->isSuperAdmin()) {
            $actionsQuery->where('business_id', $businessId);
            $conversationsQuery->where('business_id', $businessId);
        }

        $pendingActions = (clone $actionsQuery)->where('status', ActionStatus::Pending)->count();
        $todayActions = (clone $actionsQuery)->whereDate('created_at', $today)->count();
        $weekActions = (clone $actionsQuery)->where('created_at', '>=', $thisWeek)->count();
        $monthActions = (clone $actionsQuery)->where('created_at', '>=', $thisMonth)->count();

        $activeConversations = (clone $conversationsQuery)->where('status', 'active')->count();
        $todayConversations = (clone $conversationsQuery)->whereDate('created_at', $today)->count();

        // Action breakdown by type
        $actionsByType = (clone $actionsQuery)
            ->select('action', DB::raw('count(*) as count'))
            ->where('created_at', '>=', $thisMonth)
            ->groupBy('action')
            ->pluck('count', 'action');

        // Action breakdown by status
        $actionsByStatus = (clone $actionsQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'pending_actions' => $pendingActions,
            'today_actions' => $todayActions,
            'week_actions' => $weekActions,
            'month_actions' => $monthActions,
            'active_conversations' => $activeConversations,
            'today_conversations' => $todayConversations,
            'actions_by_type' => $actionsByType,
            'actions_by_status' => $actionsByStatus,
        ]);
    }

    public function activity(Request $request): JsonResponse
    {
        $user = $request->user();
        $businessId = $request->query('business_id', $user->business_id);
        $days = $request->query('days', 7);

        $query = ClientAction::query()
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date');

        if ($businessId && !$user->isSuperAdmin()) {
            $query->where('business_id', $businessId);
        }

        $activity = $query->get();

        // Recent actions
        $recentActions = ClientAction::query()
            ->with(['client', 'assignedUser'])
            ->when($businessId && !$user->isSuperAdmin(), fn ($q) => $q->where('business_id', $businessId))
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($action) => [
                'id' => $action->id,
                'action' => $action->action->value,
                'status' => $action->status->value,
                'client_name' => $action->client_name,
                'created_at' => $action->created_at,
                'assigned_to' => $action->assignedUser?->name,
            ]);

        return response()->json([
            'activity' => $activity,
            'recent_actions' => $recentActions,
        ]);
    }
}
