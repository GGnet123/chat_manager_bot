<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    /**
     * Super admins can view all businesses.
     * Other users can only view businesses they belong to.
     */
    public function viewAny(User $user): bool
    {
        return true; // Filtering is done in controller
    }

    /**
     * Super admins can view any business.
     * Other users can only view businesses they belong to.
     */
    public function view(User $user, Business $business): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->businesses->contains($business->id);
    }

    /**
     * Only super admins can create businesses.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Only super admins can update businesses.
     */
    public function update(User $user, Business $business): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Only super admins can delete businesses.
     */
    public function delete(User $user, Business $business): bool
    {
        return $user->isSuperAdmin();
    }
}
