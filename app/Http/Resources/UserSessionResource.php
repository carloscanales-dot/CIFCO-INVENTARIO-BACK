<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'avatar' => $this->user->avatar ? asset('storage/'.$this->user->avatar) : null,
            ],
            'ip_address' => $this->ip_address,
            'device_type' => $this->device_type,
            'browser' => $this->browser,
            'platform' => $this->platform,
            'location' => $this->location,
            'login_at' => $this->login_at?->format('Y-m-d H:i:s'),
            'logout_at' => $this->logout_at?->format('Y-m-d H:i:s'),
            'is_active' => $this->is_active,
            'duration' => $this->logout_at 
                ? $this->login_at->diffForHumans($this->logout_at, true)
                : $this->login_at->diffForHumans(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
