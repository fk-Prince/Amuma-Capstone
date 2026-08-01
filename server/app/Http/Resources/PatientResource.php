<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $admissions = $this->admissions;

        $records = collect($this->medication ?? []);

        return [
            'patient_id' => $this->patient_id,
            'uuid' => $this->uuid,

            'full_name' => trim(
                "{$this->first_name} {$this->middle_name} {$this->last_name}"
            ),

            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,

            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth,
            'age' => $this->date_of_birth?->age,

            'blood_type' => $this->blood_type,
            'height' => $this->height,
            'weight' => $this->weight,

            'phone_number' => $this->phone_number,
            'citizenship' => $this->citizenship,

            'location' => $this->whenLoaded('location', fn() => [
                'full_address' => $this->location?->full_address,
            ]),
            'initial_medication' => $this->initial_medication,
            'medication' => $records
                ->whereIn('category', ['Medication', 'PRN'])
                ->values(),
            'vital' => $records
                ->where('category', 'Vital Signs')
                ->values(),
            // 'admission' => collect([
            //     [
            //         'patient_admission_id' => 1,
            //         'status' => 'Admitted',
            //         'admitted_at' => now()->subDays(3),
            //         'end_date' => now()->subDays(5),

            //         'bed' => [
            //             'bed_id' => 1,
            //             'bed_no' => 'A-101',
            //             'status' => 'occupied',
            //         ],

            //         'room' => [
            //             'room_id' => 1,
            //             'room_no' => '101',
            //             'room_type' => 'Private',
            //             'floor' => '1',
            //         ],

            //         'contract' => [
            //             'category' => 'Standard',
            //             'accommodation_type' => 'Private Room',
            //             'billing_cycle' => 'Monthly',
            //             'price' => 2500,
            //         ],
            //     ],
            //     [
            //         'patient_admission_id' => 2,
            //         'status' => 'discharged',
            //         'admitted_at' => now()->subDays(30),
            //         'end_date' => now()->subDays(5),

            //         'bed' => [
            //             'bed_id' => 2,
            //             'bed_no' => 'B-202',
            //             'status' => 'available',
            //         ],

            //         'room' => [
            //             'room_id' => 2,
            //             'room_no' => '202',
            //             'room_type' => 'Semi Private',
            //             'floor' => '2',
            //         ],

            //         'contract' => [
            //             'category' => 'Premium',
            //             'accommodation_type' => 'Semi Private Room',
            //             'billing_cycle' => 'Monthly',
            //             'price' => 3500,
            //         ],
            //     ],
            // ]),
            'admissions' => $admissions->map(function ($admission) {
                return [
                    'patient_admission_id' => $admission->patient_admission_id,
                    'status' => $admission->status,
                    'admitted_at' => $admission->admitted_at,
                    'end_date' => $admission->end_date,

                    'bed' => [
                        'bed_id' => $admission->bed?->bed_id,
                        'bed_no' => $admission->bed?->bed_no,
                        'status' => $admission->bed?->status,
                    ],

                    'room' => [
                        'room_id' => $admission->bed?->room?->room_id,
                        'room_no' => $admission->bed?->room?->room_no,
                        'room_type' => $admission->bed?->room?->room_type,
                        'floor' => $admission->bed?->room?->floor,
                    ],

                    'invoices' => $admission->invoiceAdmission->map(function ($invoice) {
                        return [
                            'invoice_facility_id' => $invoice->invoice_facility_id,
                            'invoice_id' => $invoice->invoice_id,
                            'price' => $invoice->price,

                            'contract' => $invoice->branchContract ? [
                                'branch_contract_id' => $invoice->branchContract->branch_contract_id,
                                'category' => $invoice->branchContract->category,
                                'accommodation_type' => $invoice->branchContract->accommodation_type,
                                'billing_cycle' => $invoice->branchContract->billing_cycle,
                                'price' => $invoice->branchContract->price,
                            ] : null,
                        ];
                    })->values(),
                ];
            })->values(),
        ];
    }
}
