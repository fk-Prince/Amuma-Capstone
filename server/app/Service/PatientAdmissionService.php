<?php

namespace App\Service;

use App\Http\Resources\BookingResource;
use App\Models\Bed;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\InvoiceFacility;
use App\Models\Patient;
use App\Models\PatientAdmission;
use App\Models\Room;
use App\Models\RoomTransfer;
use App\Models\User;
use App\Repository\BookingRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PatientAdmissionRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class PatientAdmissionService
{
    public function __construct(
        private PatientAdmissionRepository $patientAdmissionRepository,
        private BedService $bedService,
        private InvoiceRepository $invoiceRepository,
        private PatientService $patientService,
        private BookingRepository $bookingRepository,
        private BranchContractService $branchContractService
    ) {}

    public function registerPatientBed(array $payload)
    {
        if (!isset($payload['reserved'])) {
            throw new Exception('Please select accommodation type.', 400);
        }

        $reserved = $payload['reserved'];

        if (empty($reserved['bed']['bed_id'])) {
            throw new Exception('Please select an available bed or room.', 400);
        }

        $bed = $this->bedService->findAvailableBed($reserved['bed']['bed_id']);
        $admissionAt = Carbon::parse($reserved['admitted_at']);

        $admission = $this->patientAdmissionRepository->create([
            'branch_contract_id' => $reserved['contract_id'],
            'bed_id'             => $bed->bed_id,
            'patient_id'         => $payload['patient_id'],
            'status'             => PatientAdmission::STATUS_WAITING,
            'note'               => $reserved['note'] ?? null,
            'admitted_at'        => $admissionAt,
            'end_date'           => $this->calculateEndDate($admissionAt, $reserved['billing_cycle']),
        ]);

        if (!$admission) throw new Exception('Unable to create patient admission.', 500);

        $bed->update([
            'status' => Bed::STATUS_RESERVED,
        ]);

        return $admission;
    }

    public function calculateEndDate(Carbon $admissionDate, string $billingCycle)
    {
        return match (strtolower($billingCycle)) {
            'monthly' => $admissionDate->copy()->addMonth(),
            'yearly',
            'annual' => $admissionDate->copy()->addYear(),
            default => throw new Exception('Invalid billing cycle.', 422),
        };
    }

    public function action(array $payload)
    {
        return match ($payload['action']) {
            'cancel' => $this->cancelAdmission($payload),
            'admit' => $this->admitAdmission($payload),
            'discharge' => $this->dischargeAdmission($payload),
            'extend' => $this->extendDischarge($payload),
            'change_room' => $this->changeRoom($payload),
            'branch_contract' =>  $this->branchContractService->roomContract($payload['user'], $payload),
            'new_admission' => $this->newAdmission($payload),
            default => throw new Exception('Invalid admission action.'),
        };
    }
    public function newAdmission(array $payload)
    {
        if (empty($payload['room_id']) || empty($payload['bed_id']) || empty($payload['contract_id'])) {
            throw new Exception('Please select accommodation type, room and bed.', 400);
        }

        if (empty($payload['admitted_at'])) {
            throw new Exception('Please select an admission date.', 400);
        }

        return DB::transaction(function () use ($payload) {
            $patient = Patient::where('uuid', $payload['p_uuid'])->first();

            if (!$patient) {
                throw new Exception('Patient not found.', 404);
            }

            $existing = $this->patientAdmissionRepository->findByFields([
                ['patient_id', '=', $patient->patient_id],
                ['status', '=', PatientAdmission::STATUS_ADMITTED],
            ]);

            if ($existing) {
                throw new Exception('Patient already has an active admission.', 400);
            }

            $waiting = $this->patientAdmissionRepository->findByFields([
                ['patient_id', '=', $patient->patient_id],
                ['status', '=', PatientAdmission::STATUS_WAITING],
            ]);

            if (!$waiting) {
                throw new Exception('Patient already has an waiting admission.', 400);
            }


            $bed = Bed::query()
                ->where('bed_id', $payload['bed_id'])
                ->where('room_id', $payload['room_id'])
                ->lockForUpdate()
                ->first();

            if (!$bed) {
                throw new Exception('Selected bed not found.', 404);
            }

            if ($bed->status !== Bed::STATUS_AVAILABLE) {
                throw new Exception('The selected bed is no longer available.', 400);
            }

            $contract = $this->branchContractService->show($payload);

            if (!$contract) {
                throw new Exception('Contract not found.', 404);
            }

            $admittedAt = Carbon::parse($payload['admitted_at']);
            $endDate = $this->calculateEndDate($admittedAt, $contract['billing_cycle']);

            $admission = $this->patientAdmissionRepository->create([
                'branch_contract_id' => $contract['branch_contract_id'],
                'patient_id'         => $patient->patient_id,
                'bed_id'             => $bed->bed_id,
                'status'             => PatientAdmission::STATUS_WAITING,
                'admitted_at'        => $admittedAt,
                'end_date'           => $endDate,
            ]);

            if (!$admission) {
                throw new Exception('Unable to create patient admission.', 500);
            }

            $bed->update([
                'status' => Bed::STATUS_RESERVED,
            ]);

            $invoice = Invoice::create([
                'branch_id' => $payload['branch_id'],
                'total'     => $contract['price'],
                'status'    => Invoice::STATUS_PENDING,
            ]);

            InvoiceFacility::create([
                'invoice_id'            => $invoice->invoice_id,
                'patient_admission_id'  => $admission->patient_admission_id,
                'branch_contract_id'    => $contract['branch_contract_id'],
                'price'                 => $contract['price'],
            ]);

            return [
                'message' => 'Patient admitted successfully.',
                'data'    => $this->patientService->showPatient($payload['p_uuid']),
            ];
        });
    }

    public function extendDischarge(array $payload)
    {
        return DB::transaction(function () use ($payload) {

            $admission = $this->patientAdmissionRepository->findByFields([
                ['patient_admission_id', '=', $payload['admission_id']]
            ]);

            if (!$admission) {
                throw new Exception('Admission not found.');
            }

            $contract = $this->branchContractService->show($payload);

            if (!$contract) {
                throw new Exception('Contract is required.');
            }

            $date = Carbon::parse($admission->end_date ?? now());

            switch (strtolower($contract['billing_cycle'])) {
                case 'monthly':
                    $date->addMonth();
                    break;
                case 'quarterly':
                    $date->addMonths(3);
                    break;
                case 'semi annual':
                case 'semi-annually':
                case 'semiannual':
                    $date->addMonths(6);
                    break;

                case 'annual':
                case 'yearly':
                    $date->addYear();
                    break;

                default:
                    throw new Exception('Invalid billing cycle.');
            }

            $admission->update([
                'end_date' => $date->format('Y-m-d'),
            ]);

            if ($admission->branch_contract_id != $contract['branch_contract_id']) {
                $admission->update([
                    'branch_contract_id' => $contract['branch_contract_id']
                ]);
            }

            if (!empty($payload['bed_id'])) {
                $newBedId = $payload['bed_id'];
                $currentBedId = $admission->bed_id ?? null;

                if ($currentBedId != $newBedId) {
                    if ($currentBedId) {
                        $currentBed = Bed::find($currentBedId);

                        if ($currentBed && $currentBed->status === Bed::STATUS_OCCUPIED) {
                            $currentBed->update([
                                'status' => Bed::STATUS_AVAILABLE,
                            ]);
                        }
                    }

                    $newBed = Bed::find($newBedId);

                    if (!$newBed) {
                        throw new Exception('Bed not found.');
                    }

                    if ($newBed->status !== Bed::STATUS_AVAILABLE) {
                        throw new Exception('The selected bed is not available right now.');
                    }

                    $newBed->update([
                        'status' => Bed::STATUS_OCCUPIED,
                        'room_id' => $payload['room_id'] ?? $newBed->room_id,
                    ]);

                    $admission->update([
                        'bed_id' => $newBedId,
                    ]);
                }
            }

            $invoice = Invoice::create([
                'branch_id' => $payload['branch_id'],
                'total' => $contract['price'],
                'is_collected' => false,
            ]);

            InvoiceFacility::create([
                'invoice_id' => $invoice->invoice_id,
                'patient_admission_id' => $admission->patient_admission_id,
                'branch_contract_id' => $contract['branch_contract_id'],
                'price' => $contract['price'],
            ]);

            return [
                'message' => 'Admission extended successfully.',
                'data' => $this->patientService->showPatient($payload['p_uuid']),
            ];
        });
    }

    public function admitAdmission(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $admission = $this->patientAdmissionRepository->findByFields([
                ['patient_admission_id', '=', $payload['admission_id']]
            ]);


            $admission->load('admissionContract');

            if (!$admission->admissionContract) {
                throw new \Exception('Admission contract not found.');
            }

            $admittedAt = isset($payload['admitted_at'])
                ? Carbon::parse($payload['admitted_at'])
                : now();

            $endDate = $this->calculateEndDate(
                $admittedAt,
                $admission->admissionContract->billing_cycle
            );

            $admission->update([
                'status' => PatientAdmission::STATUS_ADMITTED,
                'admitted_at' => $admittedAt,
                'end_date' => $endDate,
            ]);

            if ($admission->bed) {
                $admission->bed->update([
                    'status' => Bed::STATUS_OCCUPIED,
                ]);
            }

            return [
                'message' => 'Patient admitted successfully.',
                'data' => $this->patientService->showPatient($payload['p_uuid'])
            ];
        });
    }

    public function changeRoom(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $admission = PatientAdmission::query()
                ->where('patient_admission_id', $payload['admission_id'])
                ->lockForUpdate()
                ->first();

            if (!$admission) {
                throw new Exception('Currently not admitted or has no record', 400);
            }

            if ($admission->status !== PatientAdmission::STATUS_ADMITTED) {
                throw new Exception('Only currently admitted patients can change rooms.', 400);
            }

            $newBed = Bed::query()
                ->where('bed_id', $payload['bed_id'])
                ->where('room_id', $payload['room_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $isSameBed = (int) $admission->bed_id === (int) $newBed->bed_id;

            if (!$isSameBed && $newBed->status !== Bed::STATUS_AVAILABLE) {
                throw new Exception('Selected bed is no longer available.', 400);
            }

            $newRoom = Room::query()
                ->where('room_id', $payload['room_id'])
                ->where('branch_id', $payload['branch_id'])
                ->firstOrFail();


            $oldBedId = $admission->bed_id;
            $oldBed = Bed::query()
                ->where('bed_id', $oldBedId)
                ->lockForUpdate()
                ->first();

            if (!$oldBed) {
                throw new Exception('Current bed not found.', 400);
            }
            $oldRoomId = $oldBed->room_id;

            if (!$isSameBed) {
                if ($oldBedId) {
                    Bed::query()
                        ->where('bed_id', $oldBedId)
                        ->update(['status' => Bed::STATUS_AVAILABLE]);
                }

                Bed::query()
                    ->where('bed_id', $newBed->bed_id)
                    ->update(['status' => Bed::STATUS_OCCUPIED]);
            }


            $admission->update([
                'room_id' => $newRoom->room_id,
                'bed_id'  => $newBed->bed_id,
            ]);

            RoomTransfer::create([
                'patient_admission_id' => $admission->patient_admission_id,
                'from_room_id'          => $oldRoomId,
                'from_bed_id'           => $oldBedId,
                'to_room_id'            => $newRoom->room_id,
                'to_bed_id'             => $newBed->bed_id,
                'reason'                => $payload['reason'] ?? null
            ]);

            return [
                'message' => $isSameBed
                    ? 'Bed reassignment recorded.'
                    : 'Room and bed updated successfully.',
                'data' => $this->patientService->showPatient($payload['p_uuid'])
            ];
        });
    }

    public function dischargeAdmission(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $admission = $this->patientAdmissionRepository->findByFields([
                ['patient_admission_id', '=', $payload['admission_id']]
            ]);

            $admission->load('bed');
            $dischargedAt = now();
            $admission->update([
                'status' => PatientAdmission::STATUS_DISCHARGED,
            ]);
            // 'end_date' => $dischargedAt,
            if ($admission->bed) {
                $admission->bed->update([
                    'status' => Bed::STATUS_AVAILABLE,
                ]);
            }

            return [
                'message' => 'Patient discharged successfully.',
                'data' => $this->patientService->showPatient($payload['p_uuid'])
            ];
        });
    }

    public function cancelAdmission(array $payload)
    {
        $admission = $this->patientAdmissionRepository->findByFields([
            ['patient_admission_id', '=', $payload['admission_id']]
        ]);

        if (!$admission) {
            throw new Exception('Admission not found.', 404);
        }

        DB::transaction(function () use ($admission) {
            $admission->update([
                'status' => PatientAdmission::STATUS_CANCELLED,
            ]);


            if ($admission->bed_id) {
                $admission->bed()->update([
                    'status' => Bed::STATUS_AVAILABLE,
                ]);
            }

            $invoiceIds = $admission->invoiceAdmission()
                ->pluck('invoice_id')
                ->unique()
                ->filter();

            if ($invoiceIds->isNotEmpty()) {
                Invoice::whereIn('invoice_id', $invoiceIds)
                    ->where('status', '!=', Invoice::STATUS_PAID)
                    ->update(['status' => Invoice::STATUS_VOID]);
            }
        });

        return response()->json([
            'message' => 'Admission cancelled successfully.',
            'data' => $this->patientService->showPatient($payload['uuid']),
        ]);
    }

    public function storeAdmission(User $user, array $payload)
    {
        $referenceId = $payload['reference_id'] ?? null;

        return DB::transaction(function () use ($referenceId, $payload, $user) {

            if ($referenceId) {
                $type = $payload['facility']['type'];

                if ($type === Booking::TYPE_PREADMISSION) {
                    $facility = $payload['facility'] ?? [];

                    if (isset($payload['reserved']['room']['beds'])) {
                        unset($payload['reserved']['room']['beds']);
                    }

                    $this->preAdmission($payload);

                    $bookingData = [
                        'patient'  => $payload['patient'],
                        'guardian' => $payload['guardian'],
                        'facility' => $facility,
                        'homecare' => $payload['homecare'] ?? [],
                        'reserved' => $payload['reserved'],
                        'payment'  => $payload['payment'],
                    ];

                    $booking = Booking::where('reference_id', $referenceId)
                        ->firstOrFail();

                    $booking->update([
                        'booking_type' => Booking::BOOKINGTYPE_WALKIN,
                        'status' => Booking::STATUS_APPROVED,
                        'booking_data' => $bookingData,
                    ]);

                    return [
                        'message' => 'Pre-admission created successfully.',
                        'data' => $booking->fresh(),
                    ];
                }


                if ($type === Booking::TYPE_COMPLETEADMISSION) {
                    $booking = Booking::with([
                        'patientsBooking.admissions.bed.room'
                    ])
                        ->where('reference_id', $referenceId)
                        ->lockForUpdate()
                        ->firstOrFail();


                    $patient = $booking->patientsBooking->first();

                    if (!$patient) {
                        throw new Exception("Patient not found", 404);
                    }


                    $admission = $patient->admissions->first();

                    if (!$admission) {
                        throw new Exception("Patient admission not found", 404);
                    }


                    $admissionAt = Carbon::parse(
                        $payload['reserved']['admitted_at']
                    );


                    $admission->update([
                        'status' => PatientAdmission::STATUS_ADMITTED,
                        'admitted_at' => $admissionAt,
                        'end_date' => $this->calculateEndDate(
                            $admissionAt,
                            $payload['reserved']['billing_cycle']
                        ),
                    ]);


                    $admission->bed?->update([
                        'status' => Bed::STATUS_OCCUPIED,
                    ]);


                    $booking->update([
                        'status' => Booking::STATUS_COMPLETED,
                    ]);


                    return [
                        'message' => 'Patient admitted successfully.',
                        'data' => $admission->fresh([
                            'bed.room',
                            'patient',
                        ]),
                    ];
                }
            }


            $data = $this->patientService->createFacilityPatient($payload);
            $patient = $data['patient'];
            $payload['patient_id'] = $patient['patient_id'];

            $admission = $this->registerPatientBed($payload);

            $invoice = $this->invoiceRepository->create([
                'total' => $payload['payment']['total_amount'],
                'branch_id' => $payload['branch_id'],
                'status' => Invoice::STATUS_PENDING,
            ]);

            $invoice->invoiceFacility()->create([
                'price' =>  $payload['payment']['total_amount'],
                'patient_admission_id' => $admission['patient_admission_id'],
                'branch_contract_id' => $payload['reserved']['contract_id']
            ]);

            // return [
            //     'patient'        => $patient,
            //     'invoice'        => $invoice,
            //     'admission'      => $admission,
            //     'patient_access' => $data['patientAccess']
            // ];

            return [
                'message' => 'Walk-in admission created successfully.',
            ];
        });
    }

    public function preAdmission(array $payload)
    {
        $data = $this->patientService->createFacilityPatient($payload);
        $patient = $data['patient'];
        $payload['patient_id'] = $patient['patient_id'];

        $admission = $this->registerPatientBed($payload);

        $invoice = $this->invoiceRepository->create([
            'total' => $payload['payment']['total_amount'],
            'branch_id' => $payload['branch_id'],
            'status' => Invoice::STATUS_PENDING,
        ]);


        $invoice->invoiceFacility()->create([
            'price' =>  $payload['payment']['total_amount'],
            'patient_admission_id' => $admission['patient_admission_id'],
            'branch_contract_id' => $payload['reserved']['contract_id']
        ]);

        return [
            'patient' => $data,
            'invoice' => $invoice,
            'admission' => $admission
        ];
    }

    public function list(array $payload)
    {
        if ($payload['type'] === 'booking-admission') {
            $bookings = $this->bookingRepository
                ->paginate($payload['branch_id'], $payload);
            return BookingResource::collection($bookings);
        } else if ($payload['type'] === 'room_transfers') {
            return  RoomTransfer::with(['fromRoom',  'toRoom', 'fromBed', 'toBed',])
                ->where('patient_admission_id', $payload['patient_admission_id'])
                ->latest()
                ->get();
        }
    }

    public function show(array $payload)
    {
        $booking = $this->bookingRepository->findByField([
            ['reference_id', '=', $payload['reference_id']],
            ['branch_id', '=', $payload['branch_id']],
        ]);

        if (!$booking) {
            throw new Exception('Booking does not exist.', 404);
        }

        if ($booking->status !== Booking::STATUS_PENDING) {
            throw new Exception(
                "Booking cannot be processed. Current status: {$booking->status}.",
                400
            );
        }

        $bookingData = $booking->booking_data;
        $facilityType = strtolower($bookingData['facility']['type'] ?? '');
        if ($facilityType !== 'pre-admission') {
            throw new Exception(
                "This booking cannot be processed because the booking type is '{$facilityType}'. It must be 'pre-admission'."
            );
        }

        return new BookingResource($booking);
    }
}
