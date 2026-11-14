<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchLetterheadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $branch = $this->resource;

        return [
            'name' => $branch?->name,
            'image' => $branch?->image,
            'address' => $branch?->location?->full_address,
            'contact_number' => $branch?->contact_number,
            'email' => $branch?->email,
            'tin' => data_get($branch?->settings, 'tin'),
            'agency_name' => $branch?->agencies?->name,
        ];
    }

    public static function for($branch): array
    {
        $branch?->loadMissing('location', 'agencies');

        return (new static($branch))->resolve();
    }
}
