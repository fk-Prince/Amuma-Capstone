<?php

namespace App\Http\Resources;

use App\Models\Bed;
use App\Models\Booking;
use App\Models\Branch;
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
            'is_verified' => $this->status === Branch::STATUS_VERIFIED,
            'cover_image' => $this->whenLoaded('images', function () {
                return $this->images
                    ->where('type', BranchImage::IMAGE_COVER)
                    ->sortByDesc('branch_image_id')
                    ->first()?->image_url;
            }),

            'settings' => [
                'is_open' => $isOpen,
                'status' => $status,
                'time_zone' => $timezone,
                'opening' => $openingTime,
                'closing' => $closingTime,
                'reserved_walkin_slots' => $settings['reserved_walkin_slots'] ?? 0,
                'enable_booking_pre_admission' => $settings['enable_booking_pre_admission'] ?? false,
                'enable_booking_complete_admission' => $settings['enable_booking_complete_admission'] ?? false,
                'requires_full_payment_on_admit' => $settings['requires_full_payment_on_admit'] ?? true,
                'complete_admission_booking_percent' => $settings['complete_admission_booking_percent'] ?? 100,
                'minimum_adl_hours' => $settings['minimum_adl_hours'] ?? 8,
                'currency' => $settings['currency'] ?? 'PHP',
                'tin' => $settings['tin'] ?? null,
            ],

            'location' => $this->location,

            'starting_price' => $this->startingPrice(),

            'diagnosis_case_prices' => $this->diagnosis_case_min_price !== null
                ? [
                    'min' => (float) $this->diagnosis_case_min_price,
                    'max' => (float) $this->diagnosis_case_max_price,
                ]
                : null,

            'reviewCount' => $this->reviews->count(),

            'averageRating' => $this->reviews->count() > 0
                ? round($this->reviews->avg(fn($r) => (float) $r->rate), 2)
                : 0.00,

            'subscriptions' => $this->subscriptions->map(function ($subscription) {
                $plan = $subscription->effectivePlan();

                return [
                    'plans' => [
                        'status' => $subscription->status,
                        'plan_code' => $plan?->plan_code,
                        'name' => $plan?->name,
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

    // Homecare branches start at their cheapest homecare service, in-house
    // branches at their cheapest facility plan, and a hybrid at whichever of
    // the two is lower. The hourly ADL rate is not a starting price.
    private function startingPrice(): ?array
    {
        $codes = $this->relationLoaded('subscriptions')
            ? $this->subscriptions->map(fn($subscription) => $subscription->plans?->plan_code)->filter()->all()
            : [];

        $candidates = [];

        if (array_intersect($codes, ['A', 'C']) && $this->homecare_service_min_price !== null) {
            $candidates[] = [
                'amount' => (float) $this->homecare_service_min_price,
                'cycle' => null,
            ];
        }

        if (array_intersect($codes, ['B', 'C'])) {
            $plan = $this->contracts
                ->where('category', 'Facility')
                ->sortBy('price')
                ->first();

            if ($plan) {
                $candidates[] = [
                    'amount' => (float) $plan->price,
                    'cycle' => $plan->billing_cycle,
                ];
            }
        }

        return collect($candidates)->sortBy('amount')->first();
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
