<?php

namespace App\Models;

use App\Enums\Platform;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'phone',
        'telegram_id',
        'name',
        'platform',
        'metadata',
        'first_contact_at',
        'last_contact_at',
    ];

    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'metadata' => 'array',
            'first_contact_at' => 'datetime',
            'last_contact_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ClientAction::class);
    }

    public function activeConversation(): ?Conversation
    {
        return $this->conversations()
            ->where('status', 'active')
            ->latest('last_message_at')
            ->first();
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? $this->phone ?? $this->telegram_id ?? 'Unknown';
    }
}
