<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Super admins can view all users.
     * Admin managers can view users in their business.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdminManager();
    }

    /**
     * Super admins can view any user.
     * Admin managers can view users in their business.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdminManager()) {
            return $this->sharesBusiness($user, $model);
        }

        return $user->id === $model->id;
    }

    /**
     * Super admins can create any user.
     * Admin managers can create managers in their business.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdminManager();
    }

    /**
     * Super admins can update any user.
     * Admin managers can update managers in their business.
     */
    public function update(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdminManager()) {
            // Admin managers cannot modify super admins or other admin managers
            if ($model->isSuperAdmin() || $model->isAdminManager()) {
                return false;
            }

            return $this->sharesBusiness($user, $model);
        }

        return false;
    }

    /**
     * Super admins can delete any user (except themselves).
     * Admin managers can delete managers in their business.
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdminManager()) {
            // Admin managers cannot delete super admins or other admin managers
            if ($model->isSuperAdmin() || $model->isAdminManager()) {
                return false;
            }

            return $this->sharesBusiness($user, $model);
        }

        return false;
    }

    /**
     * Only super admins can manage business_user relationships globally.
     * Admin managers can manage users in their business.
     */
    public function manageBusinessUsers(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdminManager();
    }

    /**
     * Check if two users share at least one business.
     */
    private function sharesBusiness(User $user, User $model): bool
    {
        $userBusinessIds = $user->businesses->pluck('id')->toArray();
        $modelBusinessIds = $model->businesses->pluck('id')->toArray();

        return !empty(array_intersect($userBusinessIds, $modelBusinessIds));
    }
}
