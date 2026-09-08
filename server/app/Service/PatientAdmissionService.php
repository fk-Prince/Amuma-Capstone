<?php

namespace App\Service;

use App\Http\Resources\BookingResource;
use App\Models\AdmissionPeriod;
use App\Models\Bed;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\InvoiceAdjustment;
use App\Models\Patient;
use App\Models\PatientAdmission;
use App\Models\Room;
use App\Models\RoomTransfer;
use App\Models\User;
use App\Repository\BookingRepository;
use App\Repository\InvoiceRepository;
use App\Repository\PatientAdmissionRepository;
use App\Utils\AccommodationHelper;
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
        private AdmissionPeriodService $periods,
        private RoomTransferService $transfers,
        private InvoiceService $invoiceService,
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

        if (empty($reserved['contract_id'])) {
            throw new Exception('Please select accommodation type.', 400);
        }

        $bed = $this->bedService->findAvailableBed($reserved['bed']['bed_id']);
        $admissionAt = Carbon::parse($reserved['admitted_at']);
        $plannedEnd = AdmissionHelper::calculateEndDate($admissionAt->copy(), $reserved['billing_cycle']);

        $admission = $this->patientAdmissionRepository->create([
            'patient_id'         => $payload['patient_id'],
            'bed_id'             => $bed->bed_id,
            'status'             => PatientAdmission::STATUS_WAITING,
            'note'               => $reserved['note'] ?? null,
            'admitted_at'        => $admissionAt,
            'discharged_at'      => $plannedEnd,
        ]);

        if (!$admission) {
            throw new Exception('Unable to create patient admission.', 500);
        }

        $this->periods->open(
            $admission,
            (int) $reserved['contract_id'],
            AdmissionPeriod::REASON_ADMITTED,
            $admissionAt,
            $plannedEnd
        );

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
            $endDate =  AdmissionHelper::calculateEndDate($admittedAt->copy(), $contract['billing_cycle']);
            $admission = $this->patientAdmissionRepository->create([
                'patient_id'         => $patient->patient_id,
                'bed_id'             => $bed->bed_id,
                'status'             => PatientAdmission::STATUS_WAITING,
                'admitted_at'        => $admittedAt,
                'discharged_at'      => $endDate,
            ]);

            if (!$admission) {
                throw new Exception('Unable to create patient admission.', 500);
            }

            $bed->update([
                'status' => Bed::STATUS_RESERVED,
            ]);

            $period = $this->periods->open(
                $admission,
                (int) $contract['branch_contract_id'],
                AdmissionPeriod::REASON_ADMITTED,
                $admittedAt,
                $endDate
            );

            $invoice = Invoice::create([
                'branch_id'      => $payload['branch_id'],
                'total_amount'   => $contract['price'],
                'status'         => Invoice::STATUS_PENDING,
            ]);

            $invoice->invoiceAdmissionLines()->create([
                'admission_period_id' => $period->admission_period_id,
                'price'               => $contract['price'],
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

                $period = $this->periods->current($admission);

                if (!$period) {
                    throw new Exception('No accommodation record found to activate for this admission.', 400);
                }

                $period->loadMissing('branchContract');

                $admittedAt = isset($payload['admitted_at'])
                    ? Carbon::parse($payload['admitted_at'])
                    : now();

                $endDate = AdmissionHelper::calculateEndDate(
                    $admittedAt->copy(),
                    $period->branchContract->billing_cycle
                );

                $admission->update([
                    'status'        => PatientAdmission::STATUS_ADMITTED,
                    'admitted_at'   => $admittedAt,
                    'discharged_at' => $endDate,
                ]);

                $period->update([
                    'start_date' => $admittedAt,
                    'end_date'   => $endDate,
                    'status'     => AdmissionPeriod::STATUS_ACTIVE,
                ]);

                Bed::where('bed_id', $this->transfers->currentBedId($admission))->update([
                    'status' => Bed::STATUS_OCCUPIED,
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
            $currentPeriod = $admission->currentPeriod()
                ->with('branchContract', 'invoiceAdmissionLines')
                ->first();

            $currentInvoiceIds = $currentPeriod
                ? $currentPeriod->invoiceAdmissionLines->pluck('invoice_id')->unique()
                : collect();

            $dischargedAt = now();

            $admission->update([
                'discharged_at' => $dischargedAt,
                'status' => PatientAdmission::STATUS_DISCHARGED,
                'note' => ($payload['note'] ?? '') ?: null,
            ]);

            $bedIds = collect([$admission->bed_id])->filter();

            if ($bedIds->isNotEmpty()) {
                Bed::whereIn('bed_id', $bedIds)->update([
                    'status' => Bed::STATUS_AVAILABLE,
                ]);
            }

            $invoiceIds = $admission->invoiceAdmission()
                ->pluck('invoice_id')
                ->unique()
                ->filter();

            if ($invoiceIds->isNotEmpty()) {
                $invoices = Invoice::with([
                    'allocations.refundAllocations.refund',
                    'invoiceAdmissionLines.admissionPeriod.branchContract',
                ])
                    ->whereIn('invoice_id', $invoiceIds)
                    ->get();

                foreach ($invoices as $invoice) {
                    if ($currentPeriod && $currentInvoiceIds->contains($invoice->invoice_id)) {
                        $this->refundService->createRefundCurrentInvoice(
                            $invoice,
                            $admission,
                            $currentPeriod
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

            AccommodationHelper::deactivate($admission, $dischargedAt);

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

            $bedIds = collect([$admission->bed_id])->filter();

            if ($bedIds->isNotEmpty()) {
                Bed::whereIn('bed_id', $bedIds)->update([
                    'status' => Bed::STATUS_AVAILABLE,
                ]);
            }

            $invoiceIds = $admission->invoiceAdmission()
                ->pluck('invoice_id')
                ->unique()
                ->filter();

            AccommodationHelper::deactivate($admission);

            if ($invoiceIds->isEmpty()) {
                return;
            }

            $invoices = Invoice::with([
                'allocations.refundAllocations.refund',
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

            $period = $this->periods->current($admission);

            if (!$period) {
                throw new Exception('No accommodation record found for this admission.', 400);
            }

            $coverageEnd = $admission->periods()
                ->whereNotIn('status', AdmissionPeriod::CLOSED_STATUSES)
                ->max('end_date');

            $startDate = $coverageEnd
                ? Carbon::parse($coverageEnd)
                : Carbon::today();

            $endDate = $startDate->copy()->addMonths(AdmissionHelper::billingCycle($contract['billing_cycle']));

            $admission->update(['discharged_at' => $endDate]);

            $bedId = $this->transfers->currentBedId($admission);

            if (!empty($payload['bed_id']) && (int) $payload['bed_id'] !== (int) $bedId) {
                $newBed = Bed::find($payload['bed_id']);

                if (!$newBed) {
                    throw new Exception('Bed not found.');
                }

                if ($newBed->status !== Bed::STATUS_AVAILABLE) {
                    throw new Exception('The selected bed is not available right now.');
                }

                $currentBed = Bed::find($bedId);

                if ($currentBed && $currentBed->status === Bed::STATUS_OCCUPIED) {
                    $currentBed->update([
                        'status' => Bed::STATUS_AVAILABLE,
                    ]);
                }

                $newBed->update([
                    'status'  => Bed::STATUS_OCCUPIED,
                    'room_id' => $payload['room_id'] ?? $newBed->room_id,
                ]);

                $this->transfers->move(
                    $admission,
                    (int) $newBed->bed_id,
                    RoomTransfer::TYPE_ROOM_CHANGE
                );
            }

            $nextPeriod = $this->periods->open(
                $admission,
                (int) $contract['branch_contract_id'],
                AdmissionPeriod::REASON_EXTENDED,
                $startDate,
                $endDate
            );

            $invoice = Invoice::create([
                'branch_id'      => $payload['branch_id'],
                'total_amount'   => $contract['price'],
            ]);

            $invoice->invoiceAdmissionLines()->create([
                'admission_period_id' => $nextPeriod->admission_period_id,
                'price'               => $contract['price'],
            ]);

            // Extending at the counter is a sale: the money is taken in the
            // same transaction that opens the period, so the stay can never be
            // extended without being paid for.
            $receipt = null;

            if (!empty($payload['require_payment'])) {
                $cash = round((float) ($payload['cash'] ?? 0), 2);

                if ($cash < (float) $contract['price']) {
                    throw new Exception(
                        'Payment of ' . $contract['price']
                            . ' is required to extend this stay.',
                        422
                    );
                }

                $receipt = $this->invoiceService->collectForInvoices(
                    Invoice::whereKey($invoice->invoice_id)->get(),
                    [
                        'cash' => $cash,
                        'payment_method' => $payload['payment_method'] ?? 'CASH',
                        'payor_name' => $payload['payor_name'] ?? null,
                    ],
                    $payload['user'] ?? null,
                    'Stay extended and paid.'
                );
            }

            return [
                'message' => $receipt
                    ? 'Stay extended and paid.'
                    : 'Admission extended successfully.',
                'invoice_code' => $invoice->invoice_code,
                'change' => $receipt['change'] ?? 0,
                'receipt' => $receipt['receipt'] ?? null,
                'data' => ($payload['include_patient'] ?? true)
                    ? $this->patientService->showPatient($payload['p_uuid'])
                    : null,
            ];
        });
    }

    /*
        FOR CHANGE ROOM AND CHANGE OF ACCOMMODATION
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

            $period = $this->periods->current($admission);

            if (!$period) {
                throw new Exception('No accommodation record found for this admission.', 400);
            }

            $period->loadMissing('branchContract', 'invoiceAdmissionLines.invoice');

            $newBed = Bed::query()
                ->where('bed_id', $payload['bed_id'])
                ->where('room_id', $payload['room_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $currentBedId = $this->transfers->currentBedId($admission);
            $isSameBed = (int) $currentBedId === (int) $newBed->bed_id;

            if (!$isSameBed && $newBed->status !== Bed::STATUS_AVAILABLE) {
                throw new Exception('Selected bed is no longer available.', 400);
            }

            $newRoom = Room::query()
                ->where('room_id', $payload['room_id'])
                ->where('branch_id', $payload['branch_id'])
                ->firstOrFail();

            $newContract = $this->periods->resolveContract($period, $newRoom, $payload);

            if (!$isSameBed) {
                Bed::query()
                    ->where('bed_id', $currentBedId)
                    ->update(['status' => Bed::STATUS_AVAILABLE]);

                Bed::query()
                    ->where('bed_id', $newBed->bed_id)
                    ->update(['status' => Bed::STATUS_OCCUPIED]);
            }

            $isAccommodationChange = (int) $newContract->branch_contract_id
                !== (int) $period->branch_contract_id;


            if (!$isAccommodationChange) {
                if ($isSameBed) {
                    return [
                        'message' => 'Bed reassignment recorded.',
                        'data' => $this->patientService->showPatient($payload['p_uuid']),
                    ];
                }

                $this->transfers->move(
                    $admission,
                    (int) $newBed->bed_id,
                    RoomTransfer::TYPE_ROOM_CHANGE,
                    $payload['reason'] ?? null
                );

                return [
                    'message' => 'Room and bed updated successfully.',
                    'data' => $this->patientService->showPatient($payload['p_uuid']),
                ];
            }

            $futurePeriods = $this->periods->future($admission, $period);

            $consumption = AdmissionHelper::periodConsumption($period);

            $remainingDays = $consumption['remaining'];
            $totalDays = $consumption['total'];
            $consumedDays = $consumption['consumed'];


            if ($remainingDays <= 0) {
                throw new Exception(
                    'This billing period has already ended, so there are no days left to move to the new accommodation. Extend the stay first, then change the accommodation.',
                    422
                );
            }

            $oldPrice = (float) $period->invoiceAdmissionLines->sum('price')
                ?: (float) $period->branchContract->price;

            $oldRemaining = round(($oldPrice / $totalDays) * $remainingDays, 2);
            $oldConsumed = round($oldPrice - $oldRemaining, 2);

            $newRemaining = round(
                AdmissionHelper::dailyRate($newContract->billing_cycle, (float) $newContract->price)
                    * $remainingDays,
                2
            );

            $difference = round($newRemaining - $oldRemaining, 2);

            $target = $period->invoiceAdmissionLines
                ->map(fn($line) => $line->invoice)
                ->filter()
                ->sortByDesc('invoice_id')
                ->first();

            if ($target && abs($difference) > 0) {
                InvoiceAdjustment::create([
                    'invoice_id' => $target->invoice_id,
                    'type'       => 'correction',
                    'amount'     => $difference,
                    'reason'     => $difference > 0
                        ? 'Accommodation upgraded mid-period. Difference for the remaining days.'
                        : 'Accommodation downgraded mid-period. Credit for the remaining days.',
                ]);
            }


            $windowEnd = Carbon::parse($period->end_date);

            $this->periods->repriceConsumed($period, $oldPrice, $oldConsumed);

            $periodStart = Carbon::parse($period->start_date);

            $nextStart = $periodStart->copy()
                ->startOfDay()
                ->addDays($consumedDays);

            $period->update(['end_date' => $nextStart]);

            AccommodationHelper::supersede($period);

            $this->periods->carryForward($futurePeriods, $newContract);

            $next = $this->periods->open(
                $admission,
                (int) $newContract->branch_contract_id,
                AdmissionPeriod::REASON_ACCOMMODATION_CHANGE,
                $nextStart,
                $windowEnd,
                $period,
                $payload['reason'] ?? null
            );

            if ($target) {
                $target->invoiceAdmissionLines()->create([
                    'admission_period_id' => $next->admission_period_id,
                    'price'               => $newRemaining,
                ]);
            }

            $target?->refresh();
            $target?->syncStatus();

            if (!$target || $target->balance_due <= 0) {
                AccommodationHelper::settle($next);
            }

            if (!$isSameBed) {
                $this->transfers->move(
                    $admission,
                    (int) $newBed->bed_id,
                    RoomTransfer::TYPE_ACCOMMODATION_CHANGE,
                    $payload['reason'] ?? null
                );
            }

            return [
                'message' => 'Accommodation changed successfully.',
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

                    $endDate =  AdmissionHelper::calculateEndDate($admissionAt->copy(), $payload['reserved']['billing_cycle']);

                    $admission->update([
                        'status'        => PatientAdmission::STATUS_ADMITTED,
                        'admitted_at'   => $admissionAt,
                        'discharged_at' => $endDate,
                    ]);

                    $this->periods->current($admission)?->update([
                        'start_date' => $admissionAt,
                        'end_date'   => $endDate,
                        'status'     => AdmissionPeriod::STATUS_ACTIVE,
                    ]);

                    Bed::where('bed_id', $admission->bed_id)->update([
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

            $this->guardEmailIsFree($payload['guardian']['email'] ?? null);

            $data = $this->patientService->createFacilityPatient($payload);

            $patient = $data['patient'];
            $payload['patient_id'] = $patient['patient_id'];

            $admission = $this->registerPatientBed($payload);

            $invoice = $this->invoiceRepository->create([
                'total_amount' => $payload['payment']['total_amount'],
                'branch_id' => $payload['branch_id'],
                'status'    => Invoice::STATUS_PENDING,
            ]);

            $invoice->invoiceAdmissionLines()->create([
                'admission_period_id' => $this->periods->current($admission)->admission_period_id,
                'price'               => $payload['payment']['total_amount'],
            ]);

            return [
                'message' => 'Walk-in admission created successfully.',
                'data' => $this->admissionSlip(
                    $admission,
                    $invoice,
                    $data['credentials'] ?? []
                ),
            ];
        });
    }


    private function guardEmailIsFree(?string $email): void
    {
        $email = trim((string) $email);

        if ($email === '') {
            throw new Exception('A guardian email is required.', 422);
        }

        if (User::where('email', $email)->exists()) {
            throw new Exception(
                "The email {$email} already taken. Use a different email for this guardian.",
                422
            );
        }
    }

    private function admissionSlip(object $admission, object $invoice, array $credentials): array
    {
        $admission->load('patient', 'bed.room', 'currentPeriod.branchContract');

        $patient = $admission->patient;
        $bed = $admission->bed;
        $contract = $admission->currentPeriod?->branchContract;

        return [
            'patient' => [
                'patient_uuid' => $patient?->uuid,
                'full_name' => trim(
                    ($patient?->first_name ?? '') . ' ' . ($patient?->last_name ?? '')
                ),
                'date_of_birth' => $patient?->date_of_birth,
                'gender' => $patient?->gender,
                'phone_number' => $patient?->phone_number,
            ],

            'admission' => [
                'patient_admission_id' => $admission->patient_admission_id,
                'admitted_at' => $admission->admitted_at,
                'discharged_at' => $admission->discharged_at,
                'status' => $admission->status,
                'room' => $bed?->room?->room_no,
                'floor' => $bed?->room?->floor,
                'bed' => $bed?->bed_no,
                'accommodation_type' => $contract?->accommodation_type,
                'billing_cycle' => $contract?->billing_cycle,
            ],

            'invoice' => [
                'invoice_code' => $invoice->invoice_code,
                'total_amount' => (float) $invoice->total_amount,
            ],

            'portal' => $credentials,
        ];
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
            'total_amount' => $payload['payment']['total_amount'],
            'branch_id' => $payload['branch_id'],
            'status'    => Invoice::STATUS_PENDING,
        ]);

        $invoice->invoiceAdmissionLines()->create([
            'admission_period_id' => $this->periods->current($admission)->admission_period_id,
            'price'               => $payload['payment']['total_amount'],
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
            return $this->transfers->history((int) $payload['patient_admission_id']);
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
