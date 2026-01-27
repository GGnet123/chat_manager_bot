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
        'state',
        'summary',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'context' => 'array',
            'state' => 'array',
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * Get default state for new conversations.
     */
    public static function defaultState(): array
    {
        return [
            'intent' => 'unknown',      // greeting, menu, reservation, order, inquiry, complaint, other
            'stage' => 'initial',        // initial, gathering_info, confirming, completed, transferred
            'awaiting' => null,          // What we're waiting for: name, phone, date, time, confirmation, etc.
            'flags' => [],               // Additional flags: needs_human, urgent, vip, etc.
        ];
    }

    /**
     * Get current conversation state or default.
     */
    public function getState(): array
    {
        return $this->state ?? self::defaultState();
    }

    /**
     * Update conversation state.
     */
    public function updateState(array $updates): void
    {
        $currentState = $this->getState();
        $this->update(['state' => array_merge($currentState, $updates)]);
    }

    /**
     * Get specific state value.
     */
    public function getStateValue(string $key, mixed $default = null): mixed
    {
        return $this->getState()[$key] ?? $default;
    }

    /**
     * Update conversation summary.
     */
    public function updateSummary(string $summary): void
    {
        $this->update(['summary' => $summary]);
    }

    /**
     * Update context with client/action information.
     */
    public function updateContext(array $updates): void
    {
        $currentContext = $this->context ?? [];
        $this->update(['context' => array_merge($currentContext, $updates)]);
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
            ->reorder()                       // Clear existing orderBy from relationship
            ->orderBy('created_at', 'desc')   // Get newest first for limiting
            ->take($limit)
            ->get()
            ->reverse()                       // Reverse to chronological order (oldest first)
            ->map(fn ($message) => [
                'datetime' => $message->created_at,
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->values()
            ->toArray();
    }
}
