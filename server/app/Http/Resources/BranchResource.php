<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $settings = $this->settings ?? [];

        $timezone = $settings['time_zone'] ?? 'Asia/Manila';
        $status = $settings['availability_status'] ?? 'AUTO';

        $openingTime = $settings['opening'] ?? null;
        $closingTime = $settings['closing'] ?? null;

        $isOpen = match ($status) {
            'OPEN' => true,
            'CLOSED' => false,
            default => $this->calculateAutoAvailability($timezone, $openingTime, $closingTime),
        };

        return [
            'branch_id' => $this->branch_id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image,
            'availability' => [
                'status' => $status,
                'is_open' => $isOpen,
                'timezone' => $timezone,
                'opening_time' => $openingTime,
                'closing_time' => $closingTime,
            ],
            'location' => $this->locations,
            'reviews' => $this->whenLoaded('reviews'),
            'reviewCount' => $this->reviews->count(),
            'averageRating' => $this->reviews->count() > 0
                ? round($this->reviews->avg(fn($r) => (float) $r->rate), 2)
                : null,
            'subscriptions' => $this->subscriptions->map(function ($subscription) {
                return [
                    'status' => $subscription->status,
                    'plan_code' => optional($subscription->plan)->plan_code,
                    'plan_name' => optional($subscription->plan)->name,
                ];
            })->values()->all(),
        ];
    }

    private function normalizeTime(string $time): string
    {
        [$timePart, $meridiem] = explode(' ', $time);
        [$hour, $minute] = explode(':', $timePart);

        $hour = (int) $hour;

        if ($hour === 0) {
            $hour = 12;
        }

        return sprintf('%02d:%s %s', $hour, $minute, $meridiem);
    }

    private function calculateAutoAvailability(?string $timezone, ?string $openingTime, ?string $closingTime): bool
    {
        if (!$openingTime || !$closingTime) {
            return false;
        }

        try {
            $now = Carbon::now($timezone);
            $open = Carbon::createFromFormat('h:i A', $this->normalizeTime($openingTime), $timezone);
            $close = Carbon::createFromFormat('h:i A', $this->normalizeTime($closingTime), $timezone);

            if ($close->lessThanOrEqualTo($open)) {
                $close->addDay();
            }

            return $now->between($open, $close);
        } catch (\Exception $e) {
            return false;
        }
    }
}
