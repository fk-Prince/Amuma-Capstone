<?php

namespace App\Service;

use App\Http\Resources\BookingResource;
use App\Guard\GuardianEmailGuard;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        private NotificationService $notificationService,
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
            'cancel_period' => $this->cancelPeriod($payload),
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
            if (get_class($e) === Exception::class && $e->getMessage() === '') {
                throw new Exception('Failed to admit patient.', $e->getCode() ?: 500);
            }

            throw $e;
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

            if ($currentPeriod) {
                $force = !empty($payload['force']);

                $settled = $this->refundService->settleDischarge($admission, $currentPeriod, $force);

                if ($settled['offset'] > 0 && $settled['owed_invoices']->isNotEmpty() && $admission->patient) {
                    $this->invoiceService->applyCredit(
                        $admission->patient,
                        $settled['owed_invoices'],
                        round((float) $settled['owed_invoices']->sum('balance_due'), 2),
                        $payload['user'] ?? null,
                        $settled['offset']
                    );
                }

                if ($force) {
                    $this->writeOffOutstanding($admission, $payload['user'] ?? null);
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

    private function writeOffOutstanding(PatientAdmission $admission, ?User $user): void
    {
        $invoiceIds = $admission->invoiceAdmission()->pluck('invoice_id')
            ->merge($admission->additionalCharges()->pluck('invoice_id'))
            ->filter()
            ->unique();

        Invoice::with('allocations.refundAllocations', 'invoiceAdjustments')
            ->whereIn('invoice_id', $invoiceIds)
            ->whereNotIn('status', Invoice::CLOSED_STATUSES)
            ->get()
            ->filter(fn(Invoice $invoice) => $invoice->balance_due > 0)
            ->each(fn(Invoice $invoice) => $invoice->update([
                'status' => Invoice::STATUS_WRITTEN_OFF,
                'written_off_at' => now(),
                'written_off_by' => $user?->user_id,
                'write_off_reason' => 'Force discharged with an unpaid balance. Marked as bad debt.',
            ]));
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

            $this->settleCancelledInvoices(
                $admission,
                $payload['user']->user_id ?? null,
                isset($payload['keep_amount']) ? (float) $payload['keep_amount'] : null
            );

            AccommodationHelper::deactivate($admission);
        });

        $patient = $admission->patient;
        $note = trim($payload['note'] ?? '');

        if ($patient) {
            $this->notificationService->notifyPatientAccess(
                $patient,
                "{$patient->display_name}'s admission has been cancelled by the branch."
                    . ($note !== '' ? " Note: {$note}" : ''),
                'Admission Cancelled',
                Auth::user()
            );
        }

        return response()->json([
            'message' => 'Admission cancelled successfully.',
            'data' => $this->patientService->showPatient($payload['p_uuid'] ?? $payload['uuid']),
        ]);
    }


    private function settleCancelledInvoices(PatientAdmission $admission, ?int $userId, ?float $keepAmount = null): void
    {
        $currentPeriodId = $this->periods->current($admission)?->admission_period_id;

        $admission->invoiceAdmission()->with('invoice')->get()
            ->each(function ($line) use ($currentPeriodId, $userId, $keepAmount) {
                $invoice = $this->lockOpenInvoice($line->invoice);

                if (!$invoice) {
                    return;
                }

                if ($line->admission_period_id === $currentPeriodId) {
                    $this->keepReservationFee($invoice, $userId, $keepAmount);
                    return;
                }

                $this->invoiceService->closeAsVoid($invoice, 'Admission cancelled.', $userId);
            });

        $admission->additionalCharges()->with('invoice')->get()
            ->each(function ($charge) use ($userId) {
                $invoice = $this->lockOpenInvoice($charge->invoice);

                if ($invoice) {
                    $this->invoiceService->closeAsVoid($invoice, 'Admission cancelled. Charge voided.', $userId);
                }
            });
    }

    /*
        FOR CANCELLING AN UPCOMING BILLING PERIOD
    */
    public function cancelPeriod(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $admission = $this->patientAdmissionRepository->findByFields([
                ['patient_admission_id', '=', $payload['admission_id']]
            ]);

            if (!$admission) {
                throw new Exception('Admission not found.', 404);
            }

            if ($admission->status !== PatientAdmission::STATUS_ADMITTED) {
                throw new Exception('Billing periods can only be cancelled while the patient is admitted.', 422);
            }

            $current = $this->periods->current($admission);

            if (!$current) {
                throw new Exception('No accommodation record found for this admission.', 400);
            }

            $upcoming = $this->periods->future($admission, $current);

            $period = $upcoming->firstWhere('admission_period_id', (int) ($payload['period_id'] ?? 0));

            if (!$period) {
                throw new Exception('Only an upcoming billing period can be cancelled.', 422);
            }

            $userId = $payload['user']->user_id ?? null;

            $period->invoiceAdmissionLines->groupBy('invoice_id')->each(
                function ($lines, $invoiceId) use ($period, $userId) {
                    $invoice = Invoice::where('invoice_id', $invoiceId)->lockForUpdate()->first();

                    if (!$invoice || in_array($invoice->status, Invoice::CLOSED_STATUSES, true)) {
                        return;
                    }

                    $sharesInvoice = $invoice->invoiceAdmissionLines()
                        ->where('admission_period_id', '!=', $period->admission_period_id)
                        ->exists();

                    if ($sharesInvoice) {
                        InvoiceAdjustment::create([
                            'invoice_id' => $invoice->invoice_id,
                            'type' => InvoiceAdjustment::TYPE_CORRECTION,
                            'amount' => -round((float) $lines->sum('price'), 2),
                            'reason' => 'Billing period cancelled.',
                        ]);

                        $invoice->syncStatus();

                        return;
                    }

                    if ($invoice->status === Invoice::STATUS_PAID) {
                        InvoiceAdjustment::create([
                            'invoice_id' => $invoice->invoice_id,
                            'type' => InvoiceAdjustment::TYPE_CORRECTION,
                            'amount' => -round((float) $invoice->adjusted_total, 2),
                            'reason' => 'Billing period cancelled.',
                        ]);

                        $invoice->syncStatus();

                        return;
                    }

                    $this->invoiceService->closeAsVoid($invoice, 'Billing period cancelled.', $userId);
                }
            );

            $period->update(['status' => AdmissionPeriod::STATUS_CANCELLED]);

            // Later periods move up to start where the cancelled one began, so
            // the stay stays continuous.
            $cursor = Carbon::parse($period->start_date);

            $upcoming
                ->filter(fn($next) => (int) $next->admission_period_id !== (int) $period->admission_period_id
                    && Carbon::parse($next->start_date)->gte(Carbon::parse($period->end_date)))
                ->sortBy('start_date')
                ->each(function ($next) use (&$cursor) {
                    $end = AdmissionHelper::calculateEndDate(
                        $cursor->copy(),
                        $next->branchContract->billing_cycle
                    );

                    $next->update(['start_date' => $cursor->copy(), 'end_date' => $end]);

                    $cursor = $end->copy();
                });

            $coverageEnd = $admission->periods()
                ->whereNotIn('status', AdmissionPeriod::CLOSED_STATUSES)
                ->max('end_date');

            if ($coverageEnd) {
                $admission->update(['discharged_at' => $coverageEnd]);
            }

            return [
                'message' => 'Billing period cancelled.',
                'data' => $this->patientService->showPatient($payload['p_uuid']),
            ];
        });
    }

    private function lockOpenInvoice(?Invoice $invoice): ?Invoice
    {
        if (!$invoice) {
            return null;
        }

        $invoice = Invoice::where('invoice_id', $invoice->invoice_id)->lockForUpdate()->first();

        if (!$invoice || in_array($invoice->status, Invoice::CLOSED_STATUSES, true)) {
            return null;
        }

        return $invoice;
    }

    private function keepReservationFee(Invoice $invoice, ?int $userId, ?float $keepAmount = null): void
    {
        $collected = (float) $invoice->net_paid_amount;

        if ($keepAmount !== null && ($keepAmount < 0 || round($keepAmount, 2) > round($collected, 2))) {
            throw new Exception("The amount kept can't be more than the {$collected} that was paid.", 422);
        }

        $kept = $keepAmount === null ? $collected : round($keepAmount, 2);

        if ($collected <= 0 || $kept <= 0) {
            $this->invoiceService->closeAsVoid($invoice, 'Admission cancelled.', $userId);
            return;
        }

        $uncollected = round((float) $invoice->adjusted_total - $kept, 2);

        if ($uncollected > 0) {
            InvoiceAdjustment::create([
                'invoice_id' => $invoice->invoice_id,
                'type' => InvoiceAdjustment::TYPE_CORRECTION,
                'amount' => -$uncollected,
                'reason' => 'Reservation fee collected. Admission cancelled.',
            ]);
        }

        $invoice->syncStatus();
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

            $contract = $this->periods->sameAccommodation($period, $contract);

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

            $patient = $admission->patient;

            if ($patient) {
                $this->notificationService->notifyPatientAccess(
                    $patient,
                    "{$patient->display_name}'s stay has been extended until "
                        . $endDate->toFormattedDateString() . '.',
                    'Admission Extended',
                    Auth::user()
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

                $patient = $admission->patient;

                if ($patient) {
                    $this->notificationService->notifyPatientAccess(
                        $patient,
                        "{$patient->display_name} was moved to room { $newBed->bed_no}.",
                        'Bed Changed',
                        Auth::user()
                    );
                }

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
                ((float) $newContract->price / $totalDays) * $remainingDays,
                2
            );

            $windowEnd = Carbon::parse($period->end_date);

            $periodStart = Carbon::parse($period->start_date);

            $nextStart = $periodStart->copy()
                ->startOfDay()
                ->addDays($consumedDays);

            $period->update(['end_date' => $nextStart]);

            AccommodationHelper::supersede($period);

            $upgrades = [];

            $this->periods->carryForward($futurePeriods, $newContract, $upgrades);

            $next = $this->periods->open(
                $admission,
                (int) $newContract->branch_contract_id,
                AdmissionPeriod::REASON_ACCOMMODATION_CHANGE,
                $nextStart,
                $windowEnd,
                $period,
                $payload['reason'] ?? null
            );

            $this->periods->moveBilling(
                $period,
                $next,
                $oldPrice,
                $oldConsumed,
                $newRemaining,
                $newContract,
                $upgrades
            );

            $this->periods->issueUpgrades($upgrades);

            AccommodationHelper::settle($next);

            if (!$isSameBed) {
                $this->transfers->move(
                    $admission,
                    (int) $newBed->bed_id,
                    RoomTransfer::TYPE_ACCOMMODATION_CHANGE,
                    $payload['reason'] ?? null
                );
            }

            $patient = $admission->patient;

            if ($patient) {
                $this->notificationService->notifyPatientAccess(
                    $patient,
                    "{$patient->display_name}'s accommodation was changed to room {$newRoom->room_no}.",
                    'Accommodation Changed',
                    Auth::user()
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

        if (isset($payload['patient']) && is_array($payload['patient'])) {
            $payload['patient']['avatar'] = $this->patientService->storeAvatar(
                $payload['patient']['avatar'] ?? null
            );
        }

        return DB::transaction(function () use ($referenceId, $payload, $user) {
            if ($referenceId) {
                $type = $payload['facility']['type'];

                if ($type === Booking::TYPE_PREADMISSION) {
                    $facility = $payload['facility'] ?? [];

                    $existing = Booking::where('reference_id', $referenceId)
                        ->where('branch_id', $payload['branch_id'])
                        ->lockForUpdate()
                        ->first();

                    if ($existing?->isProcessed()) {
                        throw new Exception(
                            "Booking {$referenceId} has already been processed.",
                            409
                        );
                    }

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
                        ->where('branch_id', $payload['branch_id'])
                        ->firstOrFail();

                    $booking->update([
                        'booking_type' => Booking::BOOKINGTYPE_WALKIN,
                        'status'       => Booking::STATUS_APPROVED,
                        'booking_data' => $bookingData,
                    ]);

                    if ($booking->branch) {
                        $this->notificationService->notifyBookingDecision(
                            $booking->branch,
                            $booking->fresh(),
                            Booking::STATUS_APPROVED,
                            $user
                        );
                    }

                    return [
                        'message' => 'Pre-admission created successfully.',
                        'data' => $booking->fresh(),
                    ];
                }

                if ($type === Booking::TYPE_COMPLETEADMISSION) {
                    $booking = Booking::with(['patientsBooking.admissions.bed.room'])
                        ->where('reference_id', $referenceId)
                        ->where('branch_id', $payload['branch_id'])
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

                    // $booking->update([
                    //     'status' => Booking::STATUS_COMPLETED,
                    // ]);

                    return [
                        'message' => 'Patient admitted successfully.',
                        'data' => $admission->fresh(['bed.room', 'patient']),
                    ];
                }
            }

            $this->guardEmailProvided($payload['guardian']['email'] ?? null);

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


    private function guardEmailProvided(?string $email): void
    {
        if (trim((string) $email) === '') {
            throw new Exception('A guardian email is required.', 422);
        }

        GuardianEmailGuard::assertAllowed($email);
    }

    public function guardianEmailExists(?string $email): array
    {
        $email = trim((string) $email);

        GuardianEmailGuard::assertAllowed($email);

        $user = $email === ''
            ? null
            : User::with('client.location')
            ->whereRaw('LOWER(TRIM(email)) = ?', [Str::lower($email)])
            ->first();

        $client = $user?->client;

        return [
            'exists' => (bool) $user,
            'guardian' => $client ? [
                'first_name' => $client->first_name,
                'middle_name' => $client->middle_name,
                'last_name' => $client->last_name,
                'phone_number' => $client->phone_number,
                'email' => $user->email,
                'address' => $client->location?->full_address,
                'occupation' => $client->occupation,
            ] : null,
        ];
    }

    private function admissionSlip(object $admission, object $invoice, array $credentials): array
    {
        $admission->load('patient.branch', 'bed.room', 'currentPeriod.branchContract');

        $patient = $admission->patient;
        $bed = $admission->bed;
        $contract = $admission->currentPeriod?->branchContract;

        return [
            'branch' => [
                'name' => $patient?->branch?->name,
            ],

            'patient' => [
                'patient_uuid' => $patient?->uuid,
                'full_name' => $patient?->display_name ?? '',
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

        if (!in_array($booking->status, [Booking::STATUS_PENDING, Booking::STATUS_APPROVED], true)) {
            $message = match ($booking->status) {
                Booking::STATUS_REJECTED => 'This booking was rejected' . ($booking->reason ? ": {$booking->reason}" : '.') . ' It cannot be admitted.',
                Booking::STATUS_EXPIRED => 'This booking has expired and cannot be admitted.',
                Booking::STATUS_CANCELLED => 'This booking was cancelled and cannot be admitted.',
                default => "Booking cannot be processed. Current status: {$booking->status}.",
            };

            throw new Exception($message, 400);
        }

        $bookingData = $booking->booking_data;
        $facilityType = strtolower($bookingData['facility']['type'] ?? '');

        if ($facilityType !== 'pre-admission') {
            throw new Exception(
                'Only pre-admission can be loaded here.',
                422
            );
        }

        return new BookingResource($booking);
    }
}
