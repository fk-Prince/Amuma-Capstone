<?php

namespace App\Service;

use App\Http\Resources\BookingResource;
use App\Models\Bed;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\InvoiceAccommodation;
use App\Models\Patient;
use App\Models\PatientAdmission;
use App\Models\Room;
use App\Models\RoomTransfer;
use App\Models\User;
use App\Repository\BookingRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PatientAdmissionRepository;
use App\Utils\AdmissionHelper;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

class PatientAdmissionService
{
    public function __construct(
        private PatientAdmissionRepository $patientAdmissionRepository,
        private BedService $bedService,
        private InvoiceRepository $invoiceRepository,
        private PatientService $patientService,
        private BookingRepository $bookingRepository,
        private BranchContractService $branchContractService,
        private RefundService $refundService,
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
            'bed_id'             => $bed->bed_id,
            'patient_id'         => $payload['patient_id'],
            'status'             => PatientAdmission::STATUS_WAITING,
            'note'               => $reserved['note'] ?? null,
            'admitted_at'        => $admissionAt,
            'end_date'           => AdmissionHelper::calculateEndDate($admissionAt, $reserved['billing_cycle']),
        ]);

        if (!$admission) {
            throw new Exception('Unable to create patient admission.', 500);
        }

        $bed->update([
            'status' => Bed::STATUS_RESERVED,
        ]);

        return $admission;
    }

    public function action(array $payload)
    {
        return match ($payload['action']) {
            'admit' => $this->admitAdmission($payload),
            'new_admission' => $this->newAdmission($payload),
            'extend' => $this->extendAdmission($payload),
            'change_room' => $this->changeRoom($payload),
            'discharge' => $this->dischargeAdmission($payload),
            'cancel' => $this->cancelAdmission($payload),
            'branch_contract' => $this->branchContractService->roomContract($payload),
            default => throw new Exception('Invalid admission action.'),
        };
    }
    /*
        FOR NEW ADMISSION RECORD
    */
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

            if ($waiting) {
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
            $endDate =  AdmissionHelper::calculateEndDate($admittedAt, $contract['billing_cycle']);
            $admission = $this->patientAdmissionRepository->create([
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
                'branch_id'      => $payload['branch_id'],
                'total'          => $contract['price'],
                'original_total' => $contract['price'],
                'status'         => Invoice::STATUS_PENDING,
            ]);

            InvoiceAccommodation::create([
                'invoice_id'           => $invoice->invoice_id,
                'patient_admission_id' => $admission->patient_admission_id,
                'branch_contract_id'   => $contract['branch_contract_id'],
                'price'                => $contract['price'],
                'start_date'           => $admittedAt,
                'end_date'             => $endDate,
            ]);

            return [
                'message' => 'Patient admitted successfully.',
                'data'    => $this->patientService->showPatient($payload['p_uuid']),
            ];
        });
    }

    /*
        FOR ADMITTING PATIENT 
    */
    public function admitAdmission(array $payload)
    {
        try {
            return DB::transaction(function () use ($payload) {
                $admission = $this->patientAdmissionRepository->findByFields([
                    ['patient_admission_id', '=', $payload['admission_id']]
                ]);

                if (!$admission) {
                    throw new Exception('Admission not found.', 404);
                }


                $admission->load('invoiceAdmission');

                $initialFacility = $admission->invoiceAdmission()
                    ->latest('invoice_accommodation_id')
                    ->first();

                $admittedAt = isset($payload['admitted_at'])
                    ? Carbon::parse($payload['admitted_at'])
                    : now();

                $endDate = AdmissionHelper::calculateEndDate($admittedAt, $initialFacility->branchContract->billing_cycle);

                $admission->update([
                    'status'      => PatientAdmission::STATUS_ADMITTED,
                    'admitted_at' => $admittedAt,
                    'end_date'    => $endDate,
                ]);

                if ($admission->bed) {
                    $admission->bed->update([
                        'status' => Bed::STATUS_OCCUPIED,
                    ]);
                }

                if (!$initialFacility) {
                    throw new Exception('No invoice facility record found to activate for this admission.', 400);
                }

                $initialFacility->update([
                    'start_date' => $admittedAt,
                    'end_date'   => $endDate,
                ]);

                return [
                    'message' => 'Patient admitted successfully.',
                    'data'    => $this->patientService->showPatient($payload['p_uuid']),
                ];
            });
        } catch (Throwable $e) {
            throw new Exception(
                $e->getMessage() ?: 'Failed to admit patient.',
                $e->getCode() ?: 500
            );
        }
    }

    /*
        FOR DISCHARGE ADMISSION
    */
    public function dischargeAdmission(array $payload)
    {
        $admission = $this->patientAdmissionRepository->findByFields([
            ['patient_admission_id', '=', $payload['admission_id']]
        ]);

        if (!$admission) {
            throw new Exception('Admission not found.', 404);
        }

        return DB::transaction(function () use ($admission, $payload) {
            $currentInvoiceAccommodation = $admission->currentInvoiceAccommodation()
                ->with('branchContract')
                ->first();

            $currentInvoiceId = $currentInvoiceAccommodation?->invoice_id;

            $dischargedAt = now();

            $admission->update([
                'end_date' => $dischargedAt,
                'status' => PatientAdmission::STATUS_DISCHARGED,
                'note' => ($payload['note'] ?? '') ?: null,
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
                $invoices = Invoice::with([
                    'payments.refunds',
                    'invoiceAccommodation.branchContract',
                ])
                    ->whereIn('invoice_id', $invoiceIds)
                    ->get();

                foreach ($invoices as $invoice) {
                    if (
                        $currentInvoiceId !== null &&
                        $invoice->invoice_id === $currentInvoiceId &&
                        $currentInvoiceAccommodation
                    ) {
                        $this->refundService->createRefundCurrentInvoice(
                            $invoice,
                            $admission,
                            $currentInvoiceAccommodation
                        );

                        continue;
                    }

                    $this->refundService->createRefundFutureInvoice(
                        $invoice,
                        $payload
                    );

                    $invoice->refresh();

                    if ($invoice->net_paid_amount <= 0) {
                        $invoice->update([
                            'status' => Invoice::STATUS_VOID,
                        ]);
                    }
                }
            }

            return response()->json([
                'message' => 'Admission discharged successfully.',
                'data' => $this->patientService->showPatient(
                    $payload['p_uuid']
                ),
            ]);
        });
    }

    /*
        FOR CANCEL ADMISSION
    */
    public function cancelAdmission(array $payload)
    {
        $admission = $this->patientAdmissionRepository->findByFields([
            ['patient_admission_id', '=', $payload['admission_id']]
        ]);

        if (!$admission) {
            throw new Exception('Admission not found.', 404);
        }

        DB::transaction(function () use ($admission, $payload) {
            $admission->update([
                'status' => PatientAdmission::STATUS_CANCELLED,
                'note' => ($payload['note'] ?? '') ?: null,
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

            if ($invoiceIds->isEmpty()) {
                return;
            }

            $invoices = Invoice::with([
                'payments.refunds',
            ])
                ->whereIn('invoice_id', $invoiceIds)
                ->get();

            foreach ($invoices as $invoice) {
                $refundableAmount = $this->refundService->getRefundableAmount($invoice);

                if ($refundableAmount > 0) {
                    $this->refundService->createRefundsForInvoice(
                        $invoice,
                        $refundableAmount,
                        'Invoice refunded due to admission cancellation.'
                    );
                }

                $invoice->refresh();

                if ($invoice->net_paid_amount <= 0) {
                    $invoice->update([
                        'status' => Invoice::STATUS_VOID,
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Admission cancelled successfully.',
            'data' => $this->patientService->showPatient($payload['uuid']),
        ]);
    }

    /*
        FOR EXTENSION ENDDATE ADMISSION
    */
    public function extendAdmission(array $payload)
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

            $today = Carbon::today();

            $currentInvoiceAccommodation = $admission
                ->invoiceAdmission()
                ->orderByDesc('end_date')
                ->first();

            $invoiceEndDate = $currentInvoiceAccommodation->end_date
                ? Carbon::parse($currentInvoiceAccommodation->end_date)
                : null;

            $startDate = $invoiceEndDate
                ? $invoiceEndDate->copy()
                : $today->copy();

            $endDate = $startDate->copy()->addMonths(AdmissionHelper::billingCycle($contract['billing_cycle']));

            $admission->update([
                'end_date' => $endDate->format('Y-m-d'),
            ]);

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
                        'status'  => Bed::STATUS_OCCUPIED,
                        'room_id' => $payload['room_id'] ?? $newBed->room_id,
                    ]);

                    $admission->update([
                        'bed_id' => $newBedId,
                    ]);
                }
            }

            $invoice = Invoice::create([
                'branch_id'      => $payload['branch_id'],
                'total'          => $contract['price'],
                'original_total' => $contract['price'],
            ]);

            InvoiceAccommodation::create([
                'invoice_id'           => $invoice->invoice_id,
                'patient_admission_id' => $admission->patient_admission_id,
                'branch_contract_id'   => $contract['branch_contract_id'],
                'price'                => $contract['price'],
                'start_date'           => $startDate->format('Y-m-d'),
                'end_date'             => $endDate->format('Y-m-d'),
            ]);

            return [
                'message' => 'Admission extended successfully.',
                'data' => $this->patientService->showPatient($payload['p_uuid']),
            ];
        });
    }

    /*
        FOR CHANGE ROOM
    */
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
                'from_room_id'         => $oldRoomId,
                'from_bed_id'          => $oldBedId,
                'to_room_id'           => $newRoom->room_id,
                'to_bed_id'            => $newBed->bed_id,
                'reason'               => $payload['reason'] ?? null,
            ]);

            return [
                'message' => $isSameBed
                    ? 'Bed reassignment recorded.'
                    : 'Room and bed updated successfully.',
                'data' => $this->patientService->showPatient($payload['p_uuid']),
            ];
        });
    }


    /*
      WHEN THE ADMISSION IS ONLINE COMPLETE ADMISSION BOOKINGI IS APPROVED OR WALKING ADMISSION
    */
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
                        'status'       => Booking::STATUS_APPROVED,
                        'booking_data' => $bookingData,
                    ]);

                    return [
                        'message' => 'Pre-admission created successfully.',
                        'data' => $booking->fresh(),
                    ];
                }

                if ($type === Booking::TYPE_COMPLETEADMISSION) {
                    $booking = Booking::with(['patientsBooking.admissions.bed.room'])
                        ->where('reference_id', $referenceId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $patient = $booking->patientsBooking->first();

                    if (!$patient) {
                        throw new Exception('Patient not found', 404);
                    }

                    $admission = $patient->admissions->first();

                    if (!$admission) {
                        throw new Exception('Patient admission not found', 404);
                    }

                    $admissionAt = Carbon::parse($payload['reserved']['admitted_at']);

                    $endDate =  AdmissionHelper::calculateEndDate($admissionAt, $payload['reserved']['billing_cycle']);

                    $admission->update([
                        'status'      => PatientAdmission::STATUS_ADMITTED,
                        'admitted_at' => $admissionAt,
                        'end_date'    => $endDate,
                    ]);

                    $admission->bed?->update([
                        'status' => Bed::STATUS_OCCUPIED,
                    ]);

                    $booking->update([
                        'status' => Booking::STATUS_COMPLETED,
                    ]);

                    return [
                        'message' => 'Patient admitted successfully.',
                        'data' => $admission->fresh(['bed.room', 'patient']),
                    ];
                }
            }

            $data = $this->patientService->createFacilityPatient($payload);

            $patient = $data['patient'];
            $payload['patient_id'] = $patient['patient_id'];

            $admission = $this->registerPatientBed($payload);

            $startDate = Carbon::parse(
                $admission['admitted_at'] ?? $payload['reserved']['admitted_at']
            );

            $endDate = Carbon::parse(
                $admission['end_date']
                    ?? AdmissionHelper::calculateEndDate($startDate, $payload['reserved']['billing_cycle'])
            );

            $invoice = $this->invoiceRepository->create([
                'total'     => $payload['payment']['total_amount'],
                'branch_id' => $payload['branch_id'],
                'status'    => Invoice::STATUS_PENDING,
                'original_total' => $payload['payment']['total_amount'],
            ]);

            $invoice->invoiceAccommodation()->create([
                'patient_admission_id' => $admission['patient_admission_id'],
                'branch_contract_id'   => $payload['reserved']['contract_id'],
                'price'                => $payload['payment']['total_amount'],
                'start_date'           => $startDate->format('Y-m-d'),
                'end_date'             => $endDate->format('Y-m-d'),
            ]);

            return [
                'message' => 'Walk-in admission created successfully.',
            ];
        });
    }

    /*
      WHEN THE ADMISSION IS ONLINE PRE ADMISSION BOOKING IS APPROVED OR WALKING ADMISSION
    */
    public function preAdmission(array $payload)
    {
        $data = $this->patientService->createFacilityPatient($payload);
        $patient = $data['patient'];
        $payload['patient_id'] = $patient['patient_id'];

        $admission = $this->registerPatientBed($payload);

        $invoice = $this->invoiceRepository->create([
            'total'     => $payload['payment']['total_amount'],
            'branch_id' => $payload['branch_id'],
            'status'    => Invoice::STATUS_PENDING,
        ]);

        $invoice->invoiceAccommodation()->create([
            'price'                 => $payload['payment']['total_amount'],
            'patient_admission_id'  => $admission['patient_admission_id'],
            'branch_contract_id'    => $payload['reserved']['contract_id'],
            'start_date'            => $admission['admitted_at'],
            'end_date'              => $admission['end_date'],
        ]);

        return [
            'patient'   => $data,
            'invoice'   => $invoice,
            'admission' => $admission,
        ];
    }




    public function list(array $payload)
    {
        if ($payload['type'] === 'booking-admission') {
            $bookings = $this->bookingRepository
                ->paginate($payload['branch_id'], $payload);

            return BookingResource::collection($bookings);
        }

        if ($payload['type'] === 'room_transfers') {
            return RoomTransfer::with(['fromRoom', 'toRoom', 'fromBed', 'toBed'])
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
