<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'business_id' => $this->business_id,
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Pivot data when loaded through a business relationship
            'pivot_role' => $this->whenPivotLoaded('business_user', fn () => $this->pivot->role),

            // Relationships
            'businesses' => $this->whenLoaded('businesses', fn () =>
                $this->businesses->map(fn ($business) => [
                    'id' => $business->id,
                    'name' => $business->name,
                    'slug' => $business->slug,
                    'role' => $business->pivot->role,
                ])
            ),
            'business' => $this->whenLoaded('business', fn () => [
                'id' => $this->business->id,
                'name' => $this->business->name,
                'slug' => $this->business->slug,
            ]),
        ];
    }
}
