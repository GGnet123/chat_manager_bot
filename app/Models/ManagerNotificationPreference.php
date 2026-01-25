<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagerNotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'in_app',
        'whatsapp_groups',
        'telegram_groups',
        'action_types',
    ];

    protected function casts(): array
    {
        return [
            'in_app' => 'boolean',
            'whatsapp_groups' => 'array',
            'telegram_groups' => 'array',
            'action_types' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function shouldNotifyFor(string $actionType): bool
    {
        if (empty($this->action_types)) {
            return true; // Notify for all if no specific types are set
        }

        return in_array($actionType, $this->action_types);
    }
}
