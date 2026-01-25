<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'whatsapp_phone_id',
        'whatsapp_access_token',
        'telegram_bot_token',
        'telegram_webhook_id',
        'telegram_webhook_secret',
        'settings',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
            'whatsapp_access_token' => 'encrypted',
            'telegram_bot_token' => 'encrypted',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function managers(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function clientActions(): HasMany
    {
        return $this->hasMany(ClientAction::class);
    }

    public function gptConfigurations(): HasMany
    {
        return $this->hasMany(GptConfiguration::class);
    }

    public function activeGptConfiguration(): ?GptConfiguration
    {
        return $this->gptConfigurations()->where('is_active', true)->first();
    }

    public function prompts(): HasMany
    {
        return $this->hasMany(Prompt::class);
    }

    public function notificationChannels(): HasMany
    {
        return $this->hasMany(NotificationChannel::class);
    }
}
