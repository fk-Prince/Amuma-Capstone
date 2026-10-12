<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InquiryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => trim("{$this->first_name} {$this->last_name}"),
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'city' => $this->city,
            'message' => $this->message,
            'status' => $this->status,
            'reply' => $this->reply,
            'replied_at' => $this->replied_at,
            'created_at' => $this->created_at,
        ];
    }
}
