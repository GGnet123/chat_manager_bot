<?php

namespace App\Models;

use App\Enums\ActionStatus;
use App\Enums\ActionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientAction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'client_id',
        'conversation_id',
        'action',
        'details',
        'client_name',
        'client_phone',
        'status',
        'priority',
        'assigned_to',
        'processed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'action' => ActionType::class,
            'status' => ActionStatus::class,
            'details' => 'array',
            'processed_at' => 'datetime',
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

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isPending(): bool
    {
        return $this->status === ActionStatus::Pending;
    }

    public function isCompleted(): bool
    {
        return $this->status === ActionStatus::Completed;
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => ActionStatus::Processing]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => ActionStatus::Completed,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => ActionStatus::Failed]);
    }
}
