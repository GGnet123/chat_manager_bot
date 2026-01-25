<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'business_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function assignedActions(): HasMany
    {
        return $this->hasMany(ClientAction::class, 'assigned_to');
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(ManagerNotificationPreference::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin']);
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isAdminManager(): bool
    {
        return $this->role === 'admin_manager';
    }

    /**
     * Check if user is an admin manager for a specific business.
     */
    public function isAdminManagerFor(int $businessId): bool
    {
        return $this->businesses()
            ->where('business_id', $businessId)
            ->wherePivot('role', 'admin_manager')
            ->exists();
    }

    /**
     * Get businesses where the user is an admin manager.
     */
    public function adminManagedBusinesses(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Business::class)
            ->wherePivot('role', 'admin_manager')
            ->withPivot('role')
            ->withTimestamps();
    }
}
