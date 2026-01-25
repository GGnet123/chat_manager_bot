<?php

namespace App\Models;

use App\Enums\Platform;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationChannel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'platform',
        'chat_id',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
