<?php

// namespace App\Http\Resources;

// use App\Models\Booking;
// use Carbon\Carbon;
// use Illuminate\Http\Request;
// use Illuminate\Http\Resources\Json\JsonResource;
// use Illuminate\Support\Facades\Log;

// class BranchResource extends JsonResource
// {
//     public function toArray(Request $request): array
//     {
//         $settings = $this->settings ?? [];

//         $timezone = $settings['time_zone'] ?? 'Asia/Manila';
//         $status = $settings['is_open'] ?? 'AUTO';

//         $openingTime = $settings['opening'] ?? null;
//         $closingTime = $settings['closing'] ?? null;

//         $isOpen = match ($status) {
//             'OPEN' => true,
//             'CLOSED' => false,
//             default => $this->calculateAutoAvailability($timezone, $openingTime, $closingTime),
//         };

//         return [
//             'branch_id' => $this->branch_id,
//             'uuid' => $this->uuid,
//             'name' => $this->name,
//             'description' => $this->description,
//             'image' => $this->image,
//             'settings' => [
//                 'is_open' => $this->getBranchOpenStatus($settings, $timezone, $openingTime, $closingTime),
//                 'time_zone' => $timezone,
//                 'opening' => $openingTime,
//                 'closing' => $closingTime,
//                 'reserved_walkin_slots' => $settings['reserved_walkin_slots'] ?? 0,
//                 'enable_booking_pre_admission' => $settings['enable_booking_pre_admission'] ?? false,
//                 'enable_booking_complete_admission' => $settings['enable_booking_complete_admission'] ?? false,
//                 'minimum_adl_hours' => $settings['minimum_adl_hours'] ?? 8,
//                 'currency' => $settings['currency'] ?? 'PHP',
//             ],

//             'location' => $this->location,
//             'reviewCount' => $this->reviews->count(),
//             'averageRating' => $this->reviews->count() > 0
//                 ? round($this->reviews->avg(fn($r) => (float) $r->rate), 2)
//                 : 0.00,

//             'subscriptions' => $this->subscriptions->map(function ($subscription) {
//                 return [
//                     'plans' => [
//                         'status' => $subscription->status,
//                         'plan_code' => optional($subscription->plans)->plan_code,
//                         'name' => optional($subscription->plans)->name,
//                     ],
//                 ];
//             })->values()->all(),

//             'facility' => $this->contracts
//                 ->where('category', 'Facility')
//                 ->map(function ($contract) {

//                     $reservedBedIds = $this->bookings
//                         ->where('status', Booking::STATUS_APPROVED)
//                         ->map(function ($booking) {
//                             return data_get(
//                                 $booking->booking_data,
//                                 'reserved.bed.bed_id'
//                             );
//                         })
//                         ->filter()
//                         ->values();

//                     $roomsOfType = $this->rooms
//                         ->filter(function ($room) use ($contract) {
//                             return strcasecmp(
//                                 $room->room_type,
//                                 $contract->accommodation_type
//                             ) === 0;
//                         });
//                     if ($roomsOfType->isEmpty()) {
//                         return null;
//                     }

//                     $availableSlots = $this->rooms
//                         ->filter(function ($room) use ($contract) {

//                             return strcasecmp(
//                                 $room->room_type,
//                                 $contract->accommodation_type
//                             ) === 0;
//                         })
//                         // ->flatMap(function ($room) use ($reservedBedIds) {

//                         //     return $room->availableBeds
//                         //         ->filter(function ($bed) use ($reservedBedIds) {

//                         //             return !in_array($bed->bed_id, $reservedBedIds->toArray())
//                         //                 && !in_array(
//                         //                     strtolower($bed->status),
//                         //                     [
//                         //                         'maintenance',
//                         //                         'occupied'
//                         //                     ]
//                         //                 );
//                         //         });
//                         // })
//                         ->count();


//                     return [
//                         'available_slot' => $availableSlots,
//                         'accommodation_type' => $contract->accommodation_type,
//                         'billing_cycle' => $contract->billing_cycle,
//                         'price' => $contract->price,
//                         'description' => $contract->description,
//                     ];
//                 })
//                 ->filter(fn($item) => !is_null($item))
//                 ->values()
//                 ->all(),

//             'homecare' => [
//                 'adl_hourly_rate' => $this->contracts
//                     ->where('category', 'Homecare')
//                     ->where('accommodation_type', 'ADL')
//                     ->first()?->price,
//                 'adl_min_hour' => 8,
//                 'description' => $this->contracts
//                     ->where('category', 'Homecare')
//                     ->where('accommodation_type', 'ADL')
//                     ->first()?->description,
//             ],
//             'services' => $this->whenLoaded('services', function () {
//                 return $this->services
//                     ->whereIn('type', ['online', 'both'])
//                     ->map(function ($service) {
//                         return [
//                             'service_id' => $service->service_id,
//                             'service_uuid' => $service->service_uuid,
//                             'service_name' => $service->service_name,
//                             'price' => $service->price,
//                             'maximum_duration' => $service->maximum_duration,
//                             'is_available' => $service->is_available,
//                             'type' => $service->type,
//                             'category' => $service->category ? [
//                                 'category_id' => $service->category->category_id,
//                                 'category_name' => $service->category->category_name,
//                             ] : null,
//                         ];
//                     })
//                     ->values();
//             }),
//         ];
//     }

//     private function normalizeTime(string $time): string
//     {
//         if (str_contains($time, 'AM') || str_contains($time, 'PM')) {
//             return $time;
//         }

//         return Carbon::createFromFormat('H:i', $time)
//             ->format('h:i A');
//     }

//     private function calculateAutoAvailability(?string $timezone, ?string $openingTime, ?string $closingTime): bool
//     {
//         if (!$openingTime || !$closingTime) {
//             return false;
//         }

//         if ($openingTime === '00:00' && $closingTime === '00:00') {
//             return true;
//         }

//         try {
//             $now = Carbon::now($timezone);

//             $open = Carbon::createFromFormat(
//                 'h:i A',
//                 $this->normalizeTime($openingTime),
//                 $timezone
//             );

//             $close = Carbon::createFromFormat(
//                 'h:i A',
//                 $this->normalizeTime($closingTime),
//                 $timezone
//             );

//             if ($close->lessThanOrEqualTo($open)) {
//                 $close->addDay();
//             }

//             return $now->between($open, $close);
//         } catch (\Exception $e) {
//             return false;
//         }
//     }
// }

namespace App\Http\Resources;

use App\Models\Bed;
use App\Models\Booking;
use App\Models\BranchImage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $settings = $this->settings ?? [];

        $timezone = $settings['time_zone'] ?? 'Asia/Manila';
        $openingTime = $settings['opening'] ?? null;
        $closingTime = $settings['closing'] ?? null;

        $status = $settings['status']
            ?? (($settings['is_open'] ?? false) ? 'OPEN' : 'CLOSED');

        $isOpen = $this->getBranchOpenStatus(
            $status,
            $timezone,
            $openingTime,
            $closingTime
        );

        $reservedWalkinSlots = $settings['reserved_walkin_slots'] ?? 0;
        $remainingReserved = $reservedWalkinSlots;

        return [
            'branch_id' => $this->branch_id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image,

            'settings' => [
                'is_open' => $isOpen,
                'status' => $status,
                'time_zone' => $timezone,
                'opening' => $openingTime,
                'closing' => $closingTime,
                'reserved_walkin_slots' => $settings['reserved_walkin_slots'] ?? 0,
                'enable_booking_pre_admission' => $settings['enable_booking_pre_admission'] ?? false,
                'enable_booking_complete_admission' => $settings['enable_booking_complete_admission'] ?? false,
                'minimum_adl_hours' => $settings['minimum_adl_hours'] ?? 8,
                'termination_fee_percent' => $settings['termination_fee_percent'] ?? 0,
                'currency' => $settings['currency'] ?? 'PHP',
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
                ->map(function ($contract) use (&$remainingReserved) {

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

                    $availableBeds = $roomsOfType
                        ->flatMap(function ($room) {
                            return $room->beds;
                        })
                        ->filter(function ($bed) {
                            return $bed->status === Bed::STATUS_AVAILABLE;
                        })
                        ->count();

                    $deduction = min($availableBeds, $remainingReserved);
                    $remainingReserved -= $deduction;

                    $availableSlots = max(0, $availableBeds - $deduction);

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

            'images' => $this->whenLoaded('images', function () {
                return $this->images
                    ->whereIn('type', [BranchImage::IMAGE_BRANCH, BranchImage::IMAGE_COMMON_ROOM, BranchImage::IMAGE_VIP_ROOM])
                    ->map(function ($image) {
                        return [
                            'branch_image_id' => $image->branch_image_id,
                            'image_url' => $image->image_url,
                            'type' => $image->type,
                            'description' => $image->description,
                        ];
                    })
                    ->values();
            }),


            'homecare' => [
                'adl_hourly_rate' => $this->contracts
                    ->where('category', 'Homecare')
                    ->where('accommodation_type', 'ADL')
                    ->first()?->price,
                'adl_min_hour' => $settings['minimum_adl_hours'] ?? 8,
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

    private function getBranchOpenStatus(
        string $status,
        ?string $timezone,
        ?string $openingTime,
        ?string $closingTime
    ): bool {
        if ($status === 'CLOSED') {
            return false;
        }

        if (!$openingTime || !$closingTime) {
            return true;
        }

        return $this->isWithinBusinessHours($timezone, $openingTime, $closingTime);
    }

    private function normalizeTime(string $time): string
    {
        if (str_contains($time, 'AM') || str_contains($time, 'PM')) {
            return $time;
        }

        return Carbon::createFromFormat('H:i', $time)
            ->format('h:i A');
    }

    private function isWithinBusinessHours(
        ?string $timezone,
        string $openingTime,
        string $closingTime
    ): bool {
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
