<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GptConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'model',
        'max_tokens',
        'temperature',
        'system_prompt',
        'available_actions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_tokens' => 'integer',
            'temperature' => 'float',
            'available_actions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function activate(): void
    {
        // Deactivate all other configurations for this business
        self::where('business_id', $this->business_id)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);

        $this->update(['is_active' => true]);
    }
}
