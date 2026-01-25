<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'whatsapp_phone_id' => $this->whatsapp_phone_id,
            'has_whatsapp' => !empty($this->whatsapp_phone_id) && !empty($this->whatsapp_access_token),
            'has_telegram' => !empty($this->telegram_bot_token),
            'settings' => $this->settings,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'users' => $this->whenLoaded('users', fn () =>
                $this->users->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->pivot->role,
                ])
            ),
            'gpt_configurations' => $this->whenLoaded('gptConfigurations'),
            'active_gpt_config' => $this->when(
                $this->relationLoaded('gptConfigurations'),
                fn () => $this->gptConfigurations->firstWhere('is_active', true)
            ),
        ];
    }
}
