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
            'admissions' => $this->whenLoaded('admissions', function () {
                return $this->admissions->map(function ($admission) {
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

                        // 'current_contract' => $admission->admissionContract ? [
                        //     'branch_contract_id' => $admission->admissionContract->branch_contract_id,
                        //     'category' => $admission->admissionContract->category,
                        //     'accommodation_type' => $admission->admissionContract->accommodation_type,
                        //     'billing_cycle' => $admission->admissionContract->billing_cycle,
                        //     'price' => $admission->admissionContract->price,
                        // ] : null,

                        'invoices' => $admission->invoiceAdmission->map(function ($invoiceFacility) {
                            return [
                                'invoice_facility_id' => $invoiceFacility->invoice_facility_id,
                                'invoice_id' => $invoiceFacility->invoice_id,
                                'invoice_code' => $invoiceFacility->invoice?->invoice_code,

                                'price' => $invoiceFacility->price,

                                'contract' => $invoiceFacility->branchContract ? [
                                    'branch_contract_id' => $invoiceFacility->branchContract->branch_contract_id,
                                    'category' => $invoiceFacility->branchContract->category,
                                    'accommodation_type' => $invoiceFacility->branchContract->accommodation_type,
                                    'billing_cycle' => $invoiceFacility->branchContract->billing_cycle,
                                    'price' => $invoiceFacility->branchContract->price,
                                ] : null,
                            ];
                        })->values(),
                    ];
                })->values();
            }),

            'latest_admission' => $this->whenLoaded('latestAdmission', function () {
                $admission = $this->latestAdmission;

                if (!$admission) {
                    return null;
                }

                return [
                    'patient_admission_id' => $admission->patient_admission_id,
                    'status' => $admission->status,
                    'admitted_at' => $admission->admitted_at,
                    'end_date' => $admission->end_date,

                    'bed' => [
                        'bed_id' => $admission->bed?->bed_id,
                        'bed_no' => $admission->bed?->bed_no ?? 'N/A',
                        'status' => $admission->bed?->status,
                    ],

                    'room' => [
                        'room_id' => $admission->bed?->room?->room_id,
                        'room_no' => $admission->bed?->room?->room_no ?? 'N/A',
                        'room_type' => $admission->bed?->room?->room_type,
                        'floor' => $admission->bed?->room?->floor,
                    ],

                    'current_contract' => $admission->admissionContract ? [
                        'branch_contract_id' => $admission->admissionContract->branch_contract_id,
                        'category' => $admission->admissionContract->category,
                        'accommodation_type' => $admission->admissionContract->accommodation_type,
                        'billing_cycle' => $admission->admissionContract->billing_cycle,
                        'price' => $admission->admissionContract->price,
                    ] : null,

                    'invoices' => $admission->invoiceAdmission->map(function ($invoiceFacility) {
                        return [
                            'invoice_facility_id' => $invoiceFacility->invoice_facility_id,
                            'invoice_id' => $invoiceFacility->invoice_id,
                            'invoice_code' => $invoiceFacility->invoice?->invoice_code,
                            'status' => $invoiceFacility->invoice?->status,
                            'price' => $invoiceFacility->price,

                            'contract' => $invoiceFacility->branchContract ? [
                                'branch_contract_id' => $invoiceFacility->branchContract->branch_contract_id,
                                'category' => $invoiceFacility->branchContract->category,
                                'accommodation_type' => $invoiceFacility->branchContract->accommodation_type,
                                'billing_cycle' => $invoiceFacility->branchContract->billing_cycle,
                                'price' => $invoiceFacility->branchContract->price,
                            ] : null,
                        ];
                    })->values(),
                ];
            }),
        ];
    }
}

// namespace App\Http\Resources;

// use Illuminate\Http\Request;
// use Illuminate\Http\Resources\Json\JsonResource;

// class PatientResource extends JsonResource
// {
//     public function toArray(Request $request): array
//     {
//         $admissions = $this->admissions;

//         $records = collect($this->medication ?? []);

//         return [
//             'patient_id' => $this->patient_id,
//             'uuid' => $this->uuid,

//             'full_name' => trim(
//                 "{$this->first_name} {$this->middle_name} {$this->last_name}"
//             ),

//             'first_name' => $this->first_name,
//             'middle_name' => $this->middle_name,
//             'last_name' => $this->last_name,

//             'gender' => $this->gender,
//             'date_of_birth' => $this->date_of_birth,
//             'age' => $this->date_of_birth?->age,

//             'blood_type' => $this->blood_type,
//             'height' => $this->height,
//             'weight' => $this->weight,

//             'phone_number' => $this->phone_number,
//             'citizenship' => $this->citizenship,

//             'location' => $this->whenLoaded('location', fn() => [
//                 'full_address' => $this->location?->full_address,
//             ]),
//             'initial_medication' => $this->initial_medication,
//             'medication' => $records
//                 ->whereIn('category', ['Medication', 'PRN'])
//                 ->values(),
//             'vital' => $records
//                 ->where('category', 'Vital Signs')
//                 ->values(),
//             'admissions' => $this->whenLoaded('admissions', function () {
//                 return $this->admissions->map(function ($admission) {
//                     return [
//                         'patient_admission_id' => $admission->patient_admission_id,
//                         'status' => $admission->status,
//                         'admitted_at' => $admission->admitted_at,
//                         'end_date' => $admission->end_date,

//                         'bed' => [
//                             'bed_id' => $admission->bed?->bed_id,
//                             'bed_no' => $admission->bed?->bed_no,
//                             'status' => $admission->bed?->status,
//                         ],

//                         'room' => [
//                             'room_id' => $admission->bed?->room?->room_id,
//                             'room_no' => $admission->bed?->room?->room_no,
//                             'room_type' => $admission->bed?->room?->room_type,
//                             'floor' => $admission->bed?->room?->floor,
//                         ],

//                         // 'current_contract' => $admission->admissionContract ? [
//                         //     'branch_contract_id' => $admission->admissionContract->branch_contract_id,
//                         //     'category' => $admission->admissionContract->category,
//                         //     'accommodation_type' => $admission->admissionContract->accommodation_type,
//                         //     'billing_cycle' => $admission->admissionContract->billing_cycle,
//                         //     'price' => $admission->admissionContract->price,
//                         // ] : null,

//                         'invoices' => $admission->invoiceAdmission->map(function ($invoice) {
//                             return [
//                                 'invoice_facility_id' => $invoice->invoice_facility_id,
//                                 'invoice_id' => $invoice->invoice_id,
//                                 'invoice_code' => $invoice->invoice_code,

//                                 'price' => $invoice->price,

//                                 'contract' => $invoice->branchContract ? [
//                                     'branch_contract_id' => $invoice->branchContract->branch_contract_id,
//                                     'category' => $invoice->branchContract->category,
//                                     'accommodation_type' => $invoice->branchContract->accommodation_type,
//                                     'billing_cycle' => $invoice->branchContract->billing_cycle,
//                                     'price' => $invoice->branchContract->price,
//                                 ] : null,
//                             ];
//                         })->values(),
//                     ];
//                 })->values();
//             }),

//             'latest_admission' => $this->whenLoaded('latestAdmission', function () {
//                 $admission = $this->latestAdmission;

//                 if (!$admission) {
//                     return null;
//                 }

//                 return [
//                     'patient_admission_id' => $admission->patient_admission_id,
//                     'status' => $admission->status,
//                     'admitted_at' => $admission->admitted_at,
//                     'end_date' => $admission->end_date,

//                     'bed' => [
//                         'bed_id' => $admission->bed?->bed_id,
//                         'bed_no' => $admission->bed?->bed_no ?? 'N/A',
//                         'status' => $admission->bed?->status,
//                     ],

//                     'room' => [
//                         'room_id' => $admission->bed?->room?->room_id,
//                         'room_no' => $admission->bed?->room?->room_no ?? 'N/A',
//                         'room_type' => $admission->bed?->room?->room_type,
//                         'floor' => $admission->bed?->room?->floor,
//                     ],

//                     'current_contract' => $admission->admissionContract ? [
//                         'branch_contract_id' => $admission->admissionContract->branch_contract_id,
//                         'category' => $admission->admissionContract->category,
//                         'accommodation_type' => $admission->admissionContract->accommodation_type,
//                         'billing_cycle' => $admission->admissionContract->billing_cycle,
//                         'price' => $admission->admissionContract->price,
//                     ] : null,

//                     'invoices' => $admission->invoiceAdmission->map(function ($invoice) {
//                         return [
//                             'invoice_facility_id' => $invoice->invoice_facility_id,
//                             'invoice_id' => $invoice->invoice_id,
//                             'invoice_code' => $invoice->invoice_code,
//                             'price' => $invoice->price,

//                             'contract' => $invoice->branchContract ? [
//                                 'branch_contract_id' => $invoice->branchContract->branch_contract_id,
//                                 'category' => $invoice->branchContract->category,
//                                 'accommodation_type' => $invoice->branchContract->accommodation_type,
//                                 'billing_cycle' => $invoice->branchContract->billing_cycle,
//                                 'price' => $invoice->branchContract->price,
//                             ] : null,
//                         ];
//                     })->values(),
//                 ];
//             }),
//         ];
//     }
// }
