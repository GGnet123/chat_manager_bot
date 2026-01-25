<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'client_id' => $this->client_id,
            'conversation_id' => $this->conversation_id,
            'action' => $this->action->value,
            'action_label' => $this->action->label(),
            'details' => $this->details,
            'client_name' => $this->client_name,
            'client_phone' => $this->client_phone,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'priority' => $this->priority,
            'assigned_to' => $this->assigned_to,
            'processed_at' => $this->processed_at,
            'notes' => $this->notes,
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
            'assigned_user' => $this->whenLoaded('assignedUser', fn () => [
                'id' => $this->assignedUser->id,
                'name' => $this->assignedUser->name,
            ]),
            'conversation' => $this->whenLoaded('conversation', fn () =>
                new ConversationResource($this->conversation)
            ),
        ];
    }
}
