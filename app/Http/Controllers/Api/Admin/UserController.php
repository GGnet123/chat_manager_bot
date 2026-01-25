<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * List all users (super admin only).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $user = $request->user();
        $query = User::with('businesses');

        // Super admins see all users
        // Admin managers see only users in their businesses
        if (!$user->isSuperAdmin()) {
            $businessIds = $user->adminManagedBusinesses()->pluck('businesses.id');
            $query->whereHas('businesses', fn ($q) => $q->whereIn('business_id', $businessIds));
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('business_id')) {
            $query->whereHas('businesses', fn ($q) => $q->where('business_id', $request->business_id));
        }

        $users = $query->paginate($request->query('per_page', 15));

        return UserResource::collection($users);
    }

    /**
     * Create a new user (super admin only can create all roles).
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $authUser = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', Rule::in(['super_admin', 'admin_manager', 'manager'])],
            'business_id' => 'nullable|exists:businesses,id',
            'is_active' => 'boolean',
            'business_ids' => 'nullable|array',
            'business_ids.*' => 'exists:businesses,id',
        ]);

        // Only super admins can create super_admin or admin_manager roles
        if (!$authUser->isSuperAdmin() && in_array($validated['role'], ['super_admin', 'admin_manager'])) {
            abort(403, 'Only super admins can create admin users.');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'business_id' => $validated['business_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Attach to businesses if provided
        if (!empty($validated['business_ids'])) {
            foreach ($validated['business_ids'] as $businessId) {
                $user->businesses()->attach($businessId, ['role' => $validated['role']]);
            }
        } elseif (!empty($validated['business_id'])) {
            $user->businesses()->attach($validated['business_id'], ['role' => $validated['role']]);
        }

        return response()->json(new UserResource($user->load('businesses')), 201);
    }

    /**
     * Show a specific user.
     */
    public function show(Request $request, User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user->load('businesses'));
    }

    /**
     * Update a user.
     */
    public function update(Request $request, User $targetUser): UserResource
    {
        $this->authorize('update', $targetUser);

        $authUser = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($targetUser->id),
            ],
            'password' => ['sometimes', 'confirmed', Password::defaults()],
            'role' => ['sometimes', 'string', Rule::in(['super_admin', 'admin_manager', 'manager'])],
            'business_id' => 'nullable|exists:businesses,id',
            'is_active' => 'sometimes|boolean',
        ]);

        // Only super admins can change to/from super_admin or admin_manager
        if (!$authUser->isSuperAdmin()) {
            if (isset($validated['role']) && in_array($validated['role'], ['super_admin', 'admin_manager'])) {
                abort(403, 'Only super admins can assign admin roles.');
            }
            if (in_array($targetUser->role, ['super_admin', 'admin_manager'])) {
                abort(403, 'You cannot modify admin users.');
            }
        }

        // Cannot demote yourself if you're the last super admin
        if ($authUser->id === $targetUser->id
            && isset($validated['role'])
            && $validated['role'] !== 'super_admin'
            && $targetUser->role === 'super_admin'
        ) {
            $superAdminCount = User::where('role', 'super_admin')->count();
            if ($superAdminCount <= 1) {
                abort(403, 'Cannot demote the last super admin.');
            }
        }

        $updateData = array_intersect_key($validated, array_flip(['name', 'email', 'role', 'business_id', 'is_active']));
        if (isset($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $targetUser->update($updateData);

        return new UserResource($targetUser->fresh()->load('businesses'));
    }

    /**
     * Delete a user.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json(null, 204);
    }
}
