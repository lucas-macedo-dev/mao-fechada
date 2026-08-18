<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->uuid,
            'name'              => $this->name,
            'email'             => $this->email,
            'email_verified'    => $this->email_verified_at !== null,
            'locale'            => $this->locale,
            'profile_photo_url' => $this->profile_photo_url,
            'tutorial_progress' => $this->tutorial_progress,
        ];
    }
}
