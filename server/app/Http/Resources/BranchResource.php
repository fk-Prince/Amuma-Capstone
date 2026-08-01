<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

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

                    $reservedBedIds = $this->bookings
                        ->where('status', Booking::STATUS_AWAITING)
                        ->map(function ($booking) {
                            return data_get(
                                $booking->booking_data,
                                'reserved.bed.bed_id'
                            );
                        })
                        ->filter()
                        ->values();

                    $roomsOfType = $this->rooms
                        ->filter(function ($room) use ($contract) {
                            return strcasecmp(
                                $room->room_type,
                                $contract->accommodation_type
                            ) === 0;
                        });
                    if ($roomsOfType->isEmpty()) {
                        return null;
                    }

                    $availableSlots = $this->rooms
                        ->filter(function ($room) use ($contract) {

                            return strcasecmp(
                                $room->room_type,
                                $contract->accommodation_type
                            ) === 0;
                        })
                        // ->flatMap(function ($room) use ($reservedBedIds) {

                        //     return $room->availableBeds
                        //         ->filter(function ($bed) use ($reservedBedIds) {

                        //             return !in_array($bed->bed_id, $reservedBedIds->toArray())
                        //                 && !in_array(
                        //                     strtolower($bed->status),
                        //                     [
                        //                         'maintenance',
                        //                         'occupied'
                        //                     ]
                        //                 );
                        //         });
                        // })
                        ->count();


                    return [
                        'available_slot' => $availableSlots,
                        'accommodation_type' => $contract->accommodation_type,
                        'billing_cycle' => $contract->billing_cycle,
                        'price' => $contract->price,
                        'description' => $contract->description,
                    ];
                })
                ->filter(fn($item) => !is_null($item))
                ->values()
                ->all(),

            'homecare' => [
                'adl_hourly_rate' => $this->contracts
                    ->where('category', 'Homecare')
                    ->where('accommodation_type', 'ADL')
                    ->first()?->price,
                'adl_min_hour' => 8,
                'description' => $this->contracts
                    ->where('category', 'Homecare')
                    ->where('accommodation_type', 'ADL')
                    ->first()?->description,
            ],
            'services' => $this->whenLoaded('services', function () {
                return $this->services
                    ->whereIn('type', ['online', 'both'])
                    ->map(function ($service) {
                        return [
                            'service_id' => $service->service_id,
                            'service_uuid' => $service->service_uuid,
                            'service_name' => $service->service_name,
                            'price' => $service->price,
                            'maximum_duration' => $service->maximum_duration,
                            'is_available' => $service->is_available,
                            'type' => $service->type,
                            'category' => $service->category ? [
                                'category_id' => $service->category->category_id,
                                'category_name' => $service->category->category_name,
                            ] : null,
                        ];
                    })
                    ->values();
            }),
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
