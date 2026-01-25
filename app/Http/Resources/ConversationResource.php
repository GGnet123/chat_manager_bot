<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'client_id' => $this->client_id,
            'platform' => $this->platform->value,
            'platform_label' => $this->platform->label(),
            'status' => $this->status,
            'context' => $this->context,
            'last_message_at' => $this->last_message_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'phone' => $this->client->phone,
                'telegram_id' => $this->client->telegram_id,
                'platform' => $this->client->platform->value,
            ]),
            'business' => $this->whenLoaded('business', fn () => [
                'id' => $this->business->id,
                'name' => $this->business->name,
            ]),
            'actions' => $this->whenLoaded('actions', fn () =>
                ClientActionResource::collection($this->actions)
            ),
            'messages_count' => $this->whenCounted('messages'),
            'last_message' => $this->when(
                $this->relationLoaded('messages') && $this->messages->isNotEmpty(),
                fn () => [
                    'content' => $this->messages->last()->content,
                    'role' => $this->messages->last()->role,
                    'created_at' => $this->messages->last()->created_at,
                ]
            ),
        ];
    }
}
