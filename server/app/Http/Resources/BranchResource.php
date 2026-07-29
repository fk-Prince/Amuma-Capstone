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
            'settings' => [
                'status' => $status,
                'is_open' => $isOpen,
                'timezone' => $timezone,
                'opening' => $openingTime,
                'closing' => $closingTime,
            ],
            'location' => $this->location,
            'reviewCount' => $this->reviews->count(),
            'averageRating' => $this->reviews->count() > 0
                ? round($this->reviews->avg(fn($r) => (float) $r->rate), 2)
                : 0.00,

            'subscriptions' => $this->subscriptions->map(function ($subscription) {
                return [
                    'plans' => [
                        'status' => $subscription->status,
                        'plan_code' => optional($subscription->plans)->plan_code,
                        'name' => optional($subscription->plans)->name,
                    ],
                ];
            })->values()->all(),
            'facility' => $this->contracts
                ->where('category', 'Facility')
                ->map(function ($contract) {
                    return [
                        'available_slot' => random_int(5, 10),
                        'accommodation_type' => $contract->accommodation_type,
                        'billing_cycle' => $contract->billing_cycle,
                        'price' => $contract->price,
                        'description' => $contract->description,
                    ];
                })
                ->values(),

            'homecare' => [
                'adl_hourly_rate' => $this->contracts
                    ->where('category', 'Homecare')
                    ->where('accommodation_type', 'ADL')
                    ->first()?->price,
                'adl_min_hour' => 8,
            ],
            // 'services' => $this->whenLoaded('services', function () {
            //     return $this->services->map(function ($service) {
            //         return [
            //             'service_id' => $service->service_id,
            //             'service_uuid' => $service->service_uuid,
            //             'service_name' => $service->service_name,
            //             'price' => $service->price,
            //             'maximum_duration' => $service->maximum_duration,
            //             'is_available' => $service->is_available,
            //             'type' => $service->type,
            //             'category' => $service->category ? [
            //                 'category_id' => $service->category->category_id,
            //                 'category_name' => $service->category->category_name,
            //             ] : null,
            //         ];
            //     });
            // }),
        ];
    }

    private function normalizeTime(string $time): string
    {
        if (str_contains($time, 'AM') || str_contains($time, 'PM')) {
            return $time;
        }

        return Carbon::createFromFormat('H:i', $time)
            ->format('h:i A');
    }

    private function calculateAutoAvailability(?string $timezone, ?string $openingTime, ?string $closingTime): bool
    {
        if (!$openingTime || !$closingTime) {
            return false;
        }

        if ($openingTime === '00:00' && $closingTime === '00:00') {
            return true;
        }

        try {
            $now = Carbon::now($timezone);

            $open = Carbon::createFromFormat(
                'h:i A',
                $this->normalizeTime($openingTime),
                $timezone
            );

            $close = Carbon::createFromFormat(
                'h:i A',
                $this->normalizeTime($closingTime),
                $timezone
            );

            if ($close->lessThanOrEqualTo($open)) {
                $close->addDay();
            }

            return $now->between($open, $close);
        } catch (\Exception $e) {
            return false;
        }
    }
}
