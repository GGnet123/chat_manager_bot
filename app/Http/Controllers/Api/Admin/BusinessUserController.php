<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class BusinessUserController extends Controller
{
    /**
     * List users for a specific business.
     */
    public function index(Request $request, Business $business): AnonymousResourceCollection
    {
        $this->authorizeBusinessAccess($request->user(), $business);

        $query = $business->users()->with('businesses');

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('role')) {
            $query->wherePivot('role', $request->role);
        }

        $users = $query->paginate($request->query('per_page', 15));

        return UserResource::collection($users);
    }

    /**
     * Add a user to a business (create new user or attach existing).
     */
    public function store(Request $request, Business $business): JsonResponse
    {
        $this->authorizeBusinessAccess($request->user(), $business);

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required_without:user_id|string|max:255',
            'email' => [
                'required_without:user_id',
                'email',
                'max:255',
                Rule::unique('users', 'email')->when($request->user_id, fn ($rule) => $rule->ignore($request->user_id)),
            ],
            'password' => ['required_without:user_id', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', Rule::in(['manager', 'admin_manager'])],
        ]);

        $authUser = $request->user();

        // Admin managers can only create regular managers
        if (!$authUser->isSuperAdmin() && $validated['role'] === 'admin_manager') {
            abort(403, 'Only super admins can assign admin manager role.');
        }

        if (!empty($validated['user_id'])) {
            // Attach existing user to business
            $user = User::findOrFail($validated['user_id']);

            // Check if user is already in the business
            if ($business->users()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'message' => 'User is already assigned to this business.',
                ], 422);
            }

            $business->users()->attach($user->id, ['role' => $validated['role']]);
        } else {
            // Create new user and attach to business
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'business_id' => $business->id,
                'is_active' => true,
            ]);

            $business->users()->attach($user->id, ['role' => $validated['role']]);
        }

        return response()->json(new UserResource($user->load('businesses')), 201);
    }

    /**
     * Show a specific user in the business context.
     */
    public function show(Request $request, Business $business, User $user): UserResource
    {
        $this->authorizeBusinessAccess($request->user(), $business);

        // Ensure the user belongs to the business
        if (!$business->users()->where('user_id', $user->id)->exists()) {
            abort(404, 'User not found in this business.');
        }

        return new UserResource($user->load('businesses'));
    }

    /**
     * Update user's role in the business.
     */
    public function update(Request $request, Business $business, User $user): UserResource
    {
        $this->authorizeBusinessAccess($request->user(), $business);

        // Ensure the user belongs to the business
        $pivotRecord = $business->users()->where('user_id', $user->id)->first();
        if (!$pivotRecord) {
            abort(404, 'User not found in this business.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['sometimes', 'confirmed', Password::defaults()],
            'role' => ['sometimes', 'string', Rule::in(['manager', 'admin_manager'])],
            'is_active' => 'sometimes|boolean',
        ]);

        $authUser = $request->user();

        // Admin managers cannot modify other admin managers or super admins
        if (!$authUser->isSuperAdmin()) {
            if ($user->isSuperAdmin() || $user->isAdminManager()) {
                abort(403, 'You cannot modify this user.');
            }

            // Admin managers cannot promote to admin_manager
            if (isset($validated['role']) && $validated['role'] === 'admin_manager') {
                abort(403, 'Only super admins can assign admin manager role.');
            }
        }

        // Update user model fields
        $userFields = array_intersect_key($validated, array_flip(['name', 'email', 'is_active']));
        if (isset($validated['password'])) {
            $userFields['password'] = Hash::make($validated['password']);
        }
        if (!empty($userFields)) {
            $user->update($userFields);
        }

        // Update pivot role if provided
        if (isset($validated['role'])) {
            $business->users()->updateExistingPivot($user->id, ['role' => $validated['role']]);
            // Also update the user's main role if they only belong to this business
            if ($user->businesses()->count() === 1) {
                $user->update(['role' => $validated['role']]);
            }
        }

        return new UserResource($user->fresh()->load('businesses'));
    }

    /**
     * Remove a user from the business.
     */
    public function destroy(Request $request, Business $business, User $user): JsonResponse
    {
        $this->authorizeBusinessAccess($request->user(), $business);

        // Ensure the user belongs to the business
        if (!$business->users()->where('user_id', $user->id)->exists()) {
            abort(404, 'User not found in this business.');
        }

        $authUser = $request->user();

        // Cannot remove yourself
        if ($authUser->id === $user->id) {
            abort(403, 'You cannot remove yourself from the business.');
        }

        // Admin managers cannot remove other admin managers or super admins
        if (!$authUser->isSuperAdmin()) {
            if ($user->isSuperAdmin() || $user->isAdminManager()) {
                abort(403, 'You cannot remove this user.');
            }
        }

        // Detach user from business
        $business->users()->detach($user->id);

        // If user's primary business_id matches, clear it
        if ($user->business_id === $business->id) {
            // Set to another business if they belong to one, or null
            $anotherBusiness = $user->businesses()->first();
            $user->update(['business_id' => $anotherBusiness?->id]);
        }

        return response()->json(null, 204);
    }

    /**
     * Authorize that the current user can manage users in this business.
     */
    private function authorizeBusinessAccess(User $authUser, Business $business): void
    {
        if ($authUser->isSuperAdmin()) {
            return;
        }

        // Check if user is an admin manager for this business
        $pivotRole = $authUser->businesses()
            ->where('business_id', $business->id)
            ->first()
            ?->pivot
            ?->role;

        if ($pivotRole !== 'admin_manager') {
            abort(403, 'You do not have permission to manage users in this business.');
        }
    }
}
