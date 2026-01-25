<?php

namespace App\Models;

use App\Enums\Platform;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'client_id',
        'platform',
        'status',
        'context',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'context' => 'array',
            'last_message_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->orderBy('created_at');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ClientAction::class);
    }

    public function getMessagesForGpt(int $limit = 20): array
    {
        return $this->messages()
            ->latest()
            ->take($limit)
            ->get()
            ->reverse()
            ->map(fn ($message) => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->values()
            ->toArray();
    }
}
