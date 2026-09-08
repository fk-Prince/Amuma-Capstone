<?php

namespace App\Repository;

use App\Http\Resources\PatientInvoiceSummaryResource;
use App\Models\Invoice;
use App\Models\InvoiceAdjustment;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\RefundAllocation;
use App\Utils\DischargeCalculator;
use App\Utils\InvoiceMoney;
use Carbon\Carbon;

class InvoiceRepository
{
    public function __construct(
        private RefundRepository $refundRepository
    ) {}

    public function create(array $payload)
    {
        return Invoice::create($payload);
    }

    public function findByField(array $conditions)
    {
        return Invoice::where($conditions)->first();
    }

    public function findManyByField(array $conditions)
    {
        return Invoice::where($conditions)->get();
    }

    public function getInvoices(array $payload)
    {
        return match ($payload['search_type']) {
            'patient' => $this->getPatientInvoiceSummary($payload),
            'invoice' => $this->getInvoiceSearch($payload),
            default => null,
        };
    }

    public function getInvoiceDetails(array $payload)
    {
        return Invoice::with([
            'branch',
            'invoiceServices.scheduleService.service',
            'invoiceAdmissionLines.admissionPeriod.patientAdmission.patient',
            'invoiceAdmissionLines.admissionPeriod.patientAdmission.bed.room',
            'invoiceAdmissionLines.admissionPeriod.branchContract',
            'allocations.refundAllocations.refund',
            'allocations.payment',
            'payments',
            'invoiceAdjustments',
        ])
            ->where('invoice_code', $payload['invoice_code'])
            ->where('branch_id', $payload['branch_id'])
            ->first();
    }

    public function getInvoiceSearch(array $payload)
    {
        $perPage = $payload['per_page'] ?? 10;
        $branchId = $payload['branch_id'];
        $search = $payload['search'];

        $query = Invoice::where('branch_id', $branchId)
            ->where('status', '!=', Invoice::STATUS_VOID)
            ->with([
                'allocations.refundAllocations.refund',
                'allocations.payment',
                'payments',
                'invoiceServices.scheduleService.schedule.patient',
                'invoiceServices.scheduleService.service',
                'invoiceAdmissionLines.admissionPeriod.patientAdmission.patient',
                'invoiceAdmissionLines.admissionPeriod.patientAdmission.bed.room',
                'invoiceAdmissionLines.admissionPeriod.branchContract',
            ]);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where(
                    'invoice_code',
                    'like',
                    "%{$search}%"
                );
            });
        }

        if (!empty($payload['date_from'])) {
            $query->whereDate(
                'created_at',
                '>=',
                $payload['date_from']
            );
        }

        if (!empty($payload['date_to'])) {
            $query->whereDate(
                'created_at',
                '<=',
                $payload['date_to']
            );
        }

        $invoices = $query->paginate($perPage);

        $invoices->getCollection()->transform(
            fn($invoice) => $this->formatInvoice($invoice)
        );

        return $invoices;
    }

    public function getPatientWithUuid(array $payload)
    {
        $branchId = $payload['branch_id'];
        $patientUuid = $payload['p_uuid'];

        $scoped = fn() => Invoice::where(function ($q) use ($patientUuid) {
            $q->whereHas(
                'invoiceServices.scheduleService.schedule.patient',
                fn($p) => $p->where('uuid', $patientUuid)
            )->orWhereHas(
                'invoiceAdmissionLines.admissionPeriod.patientAdmission.patient',
                fn($p) => $p->where('uuid', $patientUuid)
            );
        })
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with([
                'branch',
                'allocations.refundAllocations.refund',
                'allocations.payment',
                'payments',
                'invoiceServices.scheduleService.schedule.patient',
                'invoiceServices.scheduleService.service',
                'invoiceAdmissionLines.admissionPeriod.patientAdmission.patient',
                'invoiceAdmissionLines.admissionPeriod.patientAdmission.bed.room',
                'invoiceAdmissionLines.admissionPeriod.branchContract',
                'invoiceAdjustments',
            ]);

        $invoices = $scoped()
            ->where('status', '!=', Invoice::STATUS_VOID)
            ->get();

        $voided = $scoped()
            ->where('status', Invoice::STATUS_VOID)
            ->get();

        $patient = Patient::where('uuid', $patientUuid)->first();

        if (!$patient && $invoices->isEmpty() && $voided->isEmpty()) {
            return null;
        }

        // Passed explicitly so the header keeps its patient once every invoice
        // has been voided and there is nothing left to infer it from.
        $summary = $this->patientInvoice(
            $invoices,
            $voided,
            $this->requestedSections($payload),
            isset($payload['admission_id']) ? (int) $payload['admission_id'] : null,
            isset($payload['schedule_services_id'])
                ? (int) $payload['schedule_services_id']
                : null,
            $patient
        );

        return new PatientInvoiceSummaryResource($summary);
    }

    // Driven by the patients, not the invoices: someone with no billing record
    // yet still belongs in the branch's billing list, showing zeroes.
    public function getPatientInvoiceSummary(array $payload)
    {
        $search = trim((string) ($payload['search'] ?? ''));
        $branchId = $payload['branch_id'] ?? null;

        $patients = Patient::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($search !== '', function ($q) use ($search) {
                $term = '%' . strtolower($search) . '%';

                $q->where(function ($name) use ($term) {
                    $name->whereRaw('LOWER(first_name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$term])
                        ->orWhereRaw(
                            "LOWER(first_name || ' ' || last_name) LIKE ?",
                            [$term]
                        );
                });
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        if ($patients->isEmpty()) {
            return PatientInvoiceSummaryResource::collection(collect());
        }

        $invoices = Invoice::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', '!=', Invoice::STATUS_VOID)
            ->with([
                'branch',
                'allocations.refundAllocations.refund',
                'allocations.payment',
                'payments',
                'invoiceServices.scheduleService.schedule.patient',
                'invoiceServices.scheduleService.service',
                'invoiceAdmissionLines.admissionPeriod.patientAdmission.patient',
                'invoiceAdmissionLines.admissionPeriod.patientAdmission.bed.room',
                'invoiceAdmissionLines.admissionPeriod.branchContract',
            ])
            ->get();

        $grouped = $invoices->groupBy(function ($invoice) {
            $patient =
                $invoice->invoiceServices
                ->first()
                ?->scheduleService
                ?->schedule
                ?->patient
                ??
                $invoice->invoiceAdmissionLines
                ->first()
                ?->patientAdmission
                ?->patient;

            return $patient?->patient_id ?? 'unknown';
        });

        $summaries = $patients
            ->map(fn($patient) => $this->patientInvoice(
                $grouped->get($patient->patient_id, collect()),
                null,
                ['all'],
                null,
                null,
                $patient
            ))
            ->values();

        return PatientInvoiceSummaryResource::collection($summaries);
    }

    // The admissions and services panels are the expensive parts of this
    // payload and each is only read when its tab is open, so the caller says
    // which it needs. Anything not asked for is left out of the array rather
    // than sent empty, which lets the client tell "not loaded" from "none".
    private function requestedSections(array $payload): array
    {
        $sections = $payload['sections'] ?? null;

        if (is_string($sections)) {
            $sections = array_filter(explode(',', $sections));
        }

        return $sections ? array_map('trim', (array) $sections) : ['all'];
    }

    private function wants(array $sections, string $section): bool
    {
        return in_array('all', $sections, true)
            || in_array($section, $sections, true);
    }

    private function patientInvoice(
        mixed $patientInvoices,
        mixed $voidedInvoices = null,
        array $sections = ['all'],
        ?int $admissionId = null,
        ?int $scheduleServiceId = null,
        mixed $patient = null
    ) {
        $voidedInvoices = collect($voidedInvoices ?? []);

        $patientInvoices = $patientInvoices
            ->where('status', '!=', Invoice::STATUS_VOID)
            ->values();

        // Given by the caller when the list is built from patients, so someone
        // who has never been billed still gets a row.
        $patientModel = $patient ?? $patientInvoices
            ->map(
                fn($invoice) =>
                $invoice->invoiceServices
                    ->first()
                    ?->scheduleService
                    ?->schedule
                    ?->patient
                    ??
                    $invoice->invoiceAdmissionLines
                    ->first()
                    ?->patientAdmission
                    ?->patient
            )
            ->filter()
            ->first();

        // Billing figures cover what is actually owed, so they ignore voided
        // invoices. Money that moved is counted across every invoice, since a
        // payment or refund on a voided invoice still left the till.
        $settledInvoices = $patientInvoices->concat($voidedInvoices);

        // What the invoices actually ask for. The raw total ignores every credit
        // note, so a downgraded stay kept reporting the price before the credit.
        $overallTotal = (float) $patientInvoices->sum('adjusted_total');

        $activePaid = (float) $patientInvoices->sum(
            fn($invoice) => $invoice->net_paid_amount
        );

        $overallPaid = (float) $settledInvoices->sum(
            fn($invoice) => $invoice->amount_paid
        );

        $overallRefunded = (float) $settledInvoices->sum(
            fn($invoice) => $invoice->refunded_completed_amount
        );

        $overallRefundProcessing = (float) $settledInvoices->sum(
            fn($invoice) => $invoice->refunded_requested_amount
        );

        // Money paid that no invoice claims any more, wherever it sits. Voiding
        // is not the only way to get there — a downgrade credit leaves an
        // overpayment on a perfectly live invoice, and that is owed back too.
        $overallRefundable = round(
            (float) $settledInvoices->sum(
                fn($invoice) => InvoiceMoney::refundable($invoice)
            ),
            2
        );

        // Summed per invoice, not netted across them. Subtracting one total from
        // another let a credit on a settled invoice cancel out a debt on an
        // unpaid one, so a patient owing money read as fully paid. What is owed
        // and what is held as credit are two separate figures.
        $overallBalance = round(
            (float) $patientInvoices->sum('balance_due'),
            2
        );

        $totalRefundedAny =
            $overallRefunded +
            $overallRefundProcessing;

        $refundStatus = match (true) {
            $totalRefundedAny <= 0 => 'none',
            $overallPaid <= 0 => 'full refunded',
            default => 'partially refunded',
        };

        $formattedInvoices = $patientInvoices
            ->map(function ($invoice) {
                $detail = $this->formatInvoiceDetail($invoice);

                $detail['refundable_amount'] = InvoiceMoney::refundable($invoice);
                $detail['has_pending_refund'] = $this->refundRepository
                    ->pendingForInvoice($invoice)
                    ->isNotEmpty();

                return $detail;
            })
            ->values();

        $latestInvoice = $formattedInvoices
            ->sortByDesc('created_at')
            ->first();

        $wantsInvoices = $this->wants($sections, 'invoices');
        $wantsAdmissions = $this->wants($sections, 'admissions');
        $wantsServices = $this->wants($sections, 'services');

        $summary = [
            'patient' => $patientModel ? [
                'patient_id' => $patientModel->patient_id,
                'patient_uuid' => $patientModel->uuid,
                'full_name' => trim(
                    $patientModel->first_name . ' ' .
                        $patientModel->last_name
                ),
                'first_name' => $patientModel->first_name,
                'middle_name' => $patientModel->middle_name,
                'last_name' => $patientModel->last_name,
                'gender' => $patientModel->gender,
                'date_of_birth' => $patientModel->date_of_birth,
                'age' => $patientModel->age,
                'blood_type' => $patientModel->blood_type,
                'phone_number' => $patientModel->phone_number,
                'citizenship' => $patientModel->citizenship,
            ] : null,

            'total_amount' => $overallTotal,
            'total_paid' => $overallPaid,
            'total_refunded' => $overallRefunded,
            'total_refund_requested' => $overallRefundProcessing,
            'total_refundable' => $overallRefundable,
            'refund_status' => $refundStatus,
            'total_balance' => $overallBalance,

            'status' => match (true) {
                $overallBalance <= 0 && $overallPaid > 0 => 'Paid',
                $overallPaid > 0 => 'Partial',
                default => 'Pending',
            },

            'invoice_count' => $patientInvoices->count(),

            'latest_invoice' => $latestInvoice,
        ];

        if ($wantsInvoices) {
            $summary['invoices'] = $formattedInvoices
                ->sortByDesc('created_at')
                ->values();

            $summary['voided_invoices'] = $voidedInvoices
                ->map(function ($invoice) {
                    $detail = $this->formatInvoiceDetail($invoice);

                    $detail['status'] = 'Void';
                    $detail['void_reason'] = $invoice->void_reason;
                    $detail['voided_at'] = $invoice->voided_at?->toIso8601String();
                    $detail['voided_by'] = $this->voidedByName($invoice);
                    $detail['refundable_amount'] =
                        InvoiceMoney::refundable($invoice);
                    $detail['has_pending_refund'] =
                        $this->refundRepository->pendingForInvoice($invoice)->isNotEmpty();

                    return $detail;
                })
                ->sortByDesc('created_at')
                ->values();

            $summary['payments'] = $this->formatPayments($settledInvoices);
        }

        // Refund requests are reviewed from the page header, so they travel with
        // every response rather than waiting for the invoices section.
        $summary['refunds'] = $this->formatRefunds($settledInvoices);

        if ($wantsAdmissions) {
            $summary['admissions'] = $this->formatAdmissions($patientInvoices);

            // The admissions panel is the only reader of this, so it travels
            // with that section rather than being computed for every request.
            $summary['discharge_calculation'] =
                $this->getPatientDischargeCalculation($patientInvoices);
        }

        if ($wantsServices) {
            $summary['services'] = $this->formatPatientServices($patientInvoices);
        }

        if ($this->wants($sections, 'admission_invoices') && $admissionId) {
            $summary['admission_invoices'] = $this->formatAdmissionInvoices(
                $patientInvoices,
                $admissionId
            );
        }

        if ($this->wants($sections, 'service_invoices') && $scheduleServiceId) {
            $summary['service_invoices'] = $this->formatServiceInvoices(
                $patientInvoices,
                $scheduleServiceId
            );
        }

        return $summary;
    }

    private function formatAdmissions($patientInvoices)
    {
        $admissions = $patientInvoices
            ->flatMap(
                fn($invoice) =>
                $invoice->invoiceAdmissionLines->map(
                    fn($invoiceAdmissionLines) => [
                        'admission' => $invoiceAdmissionLines->patientAdmission,
                        'invoice' => $invoice,
                        'invoice_admission' => $invoiceAdmissionLines,
                    ]
                )
            )
            ->filter(
                fn($item) => $item['admission']
            )
            ->groupBy(
                fn($item) =>
                $item['admission']->patient_admission_id
            );

        return $admissions
            ->map(function ($items) {
                $admission =
                    $items->first()['admission'];

                // Only the figures the card shows. The invoices themselves are
                // fetched when the stay is opened, so listing a patient's
                // admissions does not format every invoice behind them.
                $invoices = $items
                    ->map(fn($item) => $item['invoice'])
                    ->unique('invoice_id')
                    ->values();

                return [
                    'patient_admission_id' =>
                    $admission->patient_admission_id,

                    'status' =>
                    $admission->status,

                    'admission_date' =>
                    $admission->admitted_at,

                    'discharge_date' =>
                    $admission->end_date,

                    'end_date' =>
                    $admission->end_date,

                    'current_contract' => $this->formatAdmissionContract(
                        $admission->currentPeriod?->branchContract
                    ),

                    'room' => $admission->bed?->room ? [
                        'room_id' =>
                        $admission->bed->room->room_id,

                        'room_no' =>
                        $admission->bed->room->room_no,
                    ] : null,

                    'bed' => $admission->bed ? [
                        'bed_id' =>
                        $admission->bed->bed_id,

                        'bed_no' =>
                        $admission->bed->bed_no,
                    ] : null,

                    'invoice_count' => $invoices->count(),

                    'total_amount' => round(
                        (float) $invoices->sum('adjusted_total'),
                        2
                    ),

                    'balance_due' => round(
                        (float) $invoices->sum('balance_due'),
                        2
                    ),
                ];
            })
            ->sortByDesc('admission_date')
            ->values();
    }

    // The invoices behind one stay, loaded when that stay is opened rather
    // than with every admission on the patient.
    private function formatAdmissionInvoices($patientInvoices, int $admissionId)
    {
        return $patientInvoices
            ->filter(
                fn($invoice) => $invoice->invoiceAdmissionLines->contains(
                    fn($line) => (int) $line->admissionPeriod
                        ?->patient_admission_id === $admissionId
                )
            )
            ->map(function ($invoice) {
                $detail = $this->formatInvoiceDetail($invoice);

                $detail['refundable_amount'] = InvoiceMoney::refundable($invoice);
                $detail['has_pending_refund'] = $this->refundRepository
                    ->pendingForInvoice($invoice)
                    ->isNotEmpty();

                return $detail;
            })
            ->sortByDesc('created_at')
            ->values();
    }

    private function formatServiceInvoices($patientInvoices, int $scheduleServiceId)
    {
        return $patientInvoices
            ->filter(
                fn($invoice) => $invoice->invoiceServices->contains(
                    fn($line) => (int) $line->schedule_services_id === $scheduleServiceId
                )
            )
            ->map(function ($invoice) {
                $detail = $this->formatInvoiceDetail($invoice);

                $detail['refundable_amount'] = InvoiceMoney::refundable($invoice);
                $detail['has_pending_refund'] = $this->refundRepository
                    ->pendingForInvoice($invoice)
                    ->isNotEmpty();

                return $detail;
            })
            ->sortByDesc('created_at')
            ->values();
    }

    private function formatPatientServices($patientInvoices)
    {
        return $patientInvoices
            ->flatMap(
                fn($invoice) =>
                $invoice->invoiceServices->map(
                    fn($invoiceService) => [
                        'invoice' => $invoice,
                        'invoice_service' => $invoiceService,
                    ]
                )
            )
            ->groupBy(function ($item) {
                $scheduleService =
                    $item['invoice_service']->scheduleService;

                return $scheduleService?->schedule_services_id
                    ?? $item['invoice_service']->schedule_services_id;
            })
            ->map(function ($items) {
                $first = $items->first();

                $invoiceService =
                    $first['invoice_service'];

                $scheduleService =
                    $first['invoice_service']
                    ->scheduleService;

                $service =
                    $scheduleService?->service;

                $invoices = $items
                    ->map(
                        fn($item) =>
                        $this->formatInvoiceDetail(
                            $item['invoice']
                        )
                    )
                    ->unique('invoice_id')
                    ->values();

                return [
                    'schedule_services_id' =>
                    $invoiceService
                        ->invoiceServices
                        ?->schedule_services_id
                        ??
                        $invoiceService
                        ->schedule_services_id,

                    'service_id' =>
                    $service?->service_id,

                    'service_name' =>
                    $service?->service_name
                        ??
                        (
                            $scheduleService
                            ?->service_id === null
                            ? 'Activities of Daily Living (ADL)'
                            : null
                        ),

                    'price' => (float) $invoiceService->price,

                    'note' => $invoiceService->note,

                    'type' => $scheduleService?->type,

                    'hours_booked' => $scheduleService?->hours_booked !== null
                        ? (float) $scheduleService->hours_booked
                        : null,

                    'invoices' => $invoices,
                ];
            })
            ->values();
    }

    /**
     * Get the discharge calculation from:
     *
     * Patient
     *   -> Admission
     *      -> Invoice
     *         -> InvoiceAdmission
     *
     * The calculation is based on the latest admission
     * that has an invoice facility.
     */
    private function getPatientDischargeCalculation(
        $patientInvoices
    ): ?array {
        $admissionItems = $patientInvoices
            ->flatMap(
                fn($invoice) =>
                $invoice->invoiceAdmissionLines->map(
                    fn($invoiceAdmissionLines) => [
                        'invoice' => $invoice,
                        'period' => $invoiceAdmissionLines->admissionPeriod,
                        'admission' => $invoiceAdmissionLines->patientAdmission,
                    ]
                )
            )
            ->filter(
                fn($item) =>
                $item['admission'] !== null &&
                    $item['period'] !== null &&
                    strtolower($item['admission']->status) === 'admitted'
            )
            ->sortBy(fn($item) => $item['period']->start_date)
            ->values();

        if ($admissionItems->isEmpty()) {
            return null;
        }

        // Read through the admission so the screen and the discharge itself
        // agree on which period is current. Sorting the lines here instead
        // picked whichever invoice came back first, which could show a prepaid
        // future period in place of the stay being ended.
        $currentPeriodId = $admissionItems->first()['admission']
            ->currentPeriod()
            ->value('admission_period_id');

        $item = $admissionItems->first(
            fn($item) => $item['period']->admission_period_id === $currentPeriodId
        ) ?? $admissionItems->first();

        return DischargeCalculator::getDischargeCalculation(
            $item['invoice'],
            $item['admission'],
            $item['period']
        );
    }

    // Summed from the refund allocations rather than the refunds, so a refund
    // drawn from two payments is counted once, for the amount that actually
    // left this branch.
    private function refundsIssuedBetween(
        mixed $branchId,
        Carbon $from,
        Carbon $to
    ): float {
        return round(
            (float) RefundAllocation::query()
                ->whereHas(
                    'refund',
                    fn($query) => $query
                        ->where('status', Refund::STATUS_COMPLETED)
                        ->whereBetween('refunds.created_at', [$from, $to])
                )
                ->whereHas(
                    'allocation.invoice',
                    fn($query) => $query
                        ->where('branch_id', $branchId)
                        ->where('status', '!=', Invoice::STATUS_VOID)
                )
                ->sum('amount'),
            2
        );
    }

    private function formatAdmissionContract(mixed $contract): ?array
    {
        if (!$contract) {
            return null;
        }

        return [
            'branch_contract_id' => $contract->branch_contract_id,
            'category' => $contract->category,
            'accommodation_type' => $contract->accommodation_type,
            'billing_cycle' => $contract->billing_cycle,
            'price' => $contract->price,
        ];
    }

    private function formatInvoice(object $invoice)
    {
        $patient =
            $invoice->invoiceServices
            ->first()
            ?->scheduleService
            ?->schedule
            ?->patient
            ??
            $invoice->invoiceAdmissionLines
            ->first()
            ?->patientAdmission
            ?->patient;

        $total = (float) $invoice->adjusted_total;
        $paid = $invoice->net_paid_amount;
        $balance = $invoice->balance_due;

        $category = [];

        if ($invoice->invoiceServices->isNotEmpty()) {
            $category[] = 'Homecare';
        }

        if ($invoice->invoiceAdmissionLines->isNotEmpty()) {
            $category[] = 'Facility';
        }

        return [
            'invoice_code' => $invoice->invoice_code,

            'patient' => $patient
                ? $patient->first_name . ' ' .
                $patient->last_name
                : null,

            'schedule' => 'Not Applicable',

            'category' => implode(
                ' + ',
                $category
            ),

            'status' => match (true) {
                $balance <= 0 && $paid > 0 => 'Paid',
                $paid > 0 => 'Partial',
                default => 'Pending',
            },

            'refund_status' =>
            $invoice->refund_status,

            'total' => $total,

            'paid' => (float) $invoice->amount_paid,

            'refunded' => (float) $invoice->refunded_amount,

            'amount' => $balance,

            'created_at' =>
            $invoice->created_at,
        ];
    }

    // Read from the payments themselves: a payment is one record with one
    // amount, and the allocations only say which invoices it settled.
    private function formatPayments(mixed $invoices)
    {
        $paymentIds = collect($invoices)
            ->flatMap(fn($invoice) => $invoice->allocations)
            ->pluck('payment_id')
            ->filter()
            ->unique();

        if ($paymentIds->isEmpty()) {
            return collect();
        }

        return Payment::with('allocations.invoice')
            ->whereIn('payment_id', $paymentIds)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(Payment $payment) => [
                'payment_id' => $payment->payment_id,
                'receipt_no' => $payment->receipt_no,
                'reference_id' => $payment->reference_id,
                'amount' => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
                'masked_card_number' => $payment->masked_card_number,
                'payor_name' => $payment->payor_name,
                'created_at' => $payment->created_at?->toIso8601String(),
                'invoice_codes' => $payment->allocations
                    ->map(fn($allocation) => $allocation->invoice?->invoice_code)
                    ->filter()
                    ->unique()
                    ->values(),
            ])
            ->values();
    }

    // Read from the refunds themselves: a refund is one record with one total,
    // and the allocations only say which invoices it was drawn from.
    private function formatRefunds(mixed $invoices)
    {
        $refundIds = collect($invoices)
            ->flatMap(fn($invoice) => $invoice->allocations)
            ->flatMap(fn($allocation) => $allocation->refundAllocations)
            ->pluck('refund_id')
            ->filter()
            ->unique();

        if ($refundIds->isEmpty()) {
            return collect();
        }

        return Refund::with('allocations.allocation.invoice')
            ->whereIn('refund_id', $refundIds)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(Refund $refund) => [
                'refund_id' => $refund->refund_id,
                'refund_code' => $refund->refund_code,
                'amount' => (float) $refund->amount,
                'refund_method' => $refund->refund_method,
                'masked_card_number' => $refund->masked_card_number,
                'status' => $refund->status,
                'declined_reason' => $refund->declined_reason,
                'created_at' => $refund->created_at?->toIso8601String(),
                'invoice_codes' => $refund->allocations
                    ->map(fn($line) => $line->allocation?->invoice?->invoice_code)
                    ->filter()
                    ->unique()
                    ->values(),
            ])
            ->values();
    }

    private function voidedByName(object $invoice): ?string
    {
        $user = $invoice->voidedBy;

        if (!$user) {
            return null;
        }

        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

        return $name !== '' ? $name : $user->email;
    }

    private function formatInvoiceDetail(
        object $invoice
    ): array {
        $total = (float) $invoice->adjusted_total;

        $paid =
            $invoice->net_paid_amount;

        $refunded =
            $invoice->refunded_completed_amount;

        $refundProcessing =
            $invoice->refunded_requested_amount;

        $balance =
            $invoice->balance_due;

        return [
            'invoice_id' => $invoice->invoice_id,
            'invoice_code' => $invoice->invoice_code,
            'description' => $invoice->paymentDescription(),
            'total' => $total,
            'amount_paid' => $paid,
            'refunded_amount' =>   $refunded,
            'refund_requested_amount' =>   $refundProcessing,
            'balance_due' => $balance - $refundProcessing,
            'status' => match (true) {
                $balance <= 0 && $paid > 0 => 'Paid',
                $paid > 0 => 'Partial',
                default => 'Pending',
            },
            'refund_status' =>  $invoice->refund_status,

            'created_at' =>
            $invoice->created_at,

            'branch' =>
            $invoice->branch
                ? [
                    'branch_id' =>
                    $invoice->branch->branch_id,

                    'name' =>
                    $invoice->branch->name,
                ]
                : null,

            'accommodations' =>
            $invoice->invoiceAdmissionLines
                ->map(fn($accommodation) => [
                    'accommodation_type' =>
                    $accommodation->branchContract?->accommodation_type,

                    'billing_cycle' =>
                    $accommodation->branchContract?->billing_cycle,

                    'accommodation_status' =>
                    $accommodation->status,

                    'room_no' =>
                    $accommodation->patientAdmission?->bed?->room?->room_no,

                    'bed_no' =>
                    $accommodation->patientAdmission?->bed?->bed_no,
                ])
                ->values(),

            'services' =>
            $invoice->invoiceServices
                ->map(fn($invoiceService) => [
                    'invoice_service_id' =>
                    $invoiceService
                        ->invoice_service_id,

                    'schedule_services_id' =>
                    $invoiceService
                        ->schedule_services_id,

                    'price' =>
                    (float) $invoiceService->price,

                    'note' =>
                    $invoiceService->note,

                    'service_name' =>
                    $invoiceService
                        ->scheduleService
                        ?->service_id === null
                        ? 'Activities of Daily Living (ADL)'
                        : (
                            $invoiceService
                            ->scheduleService
                            ?->service
                            ?->service_name
                        ),
                ])
                ->values(),

            'payments' =>
            $invoice->allocations
                ->map(fn($allocation) => [
                    'payment_id' =>
                    $allocation->payment_id,

                    'receipt_no' =>
                    $allocation->payment?->receipt_no,

                    'reference_id' =>
                    $allocation->payment?->reference_id,

                    'amount' =>
                    (float) $allocation->amount,

                    'payment_method' =>
                    $allocation->payment?->payment_method,

                    'created_at' =>
                    $allocation->payment?->created_at,

                    // This allocation's share of each refund; refund_total is
                    // the whole refund, which may span other payments.
                    'refunds' =>
                    $allocation->refundAllocations
                        ->map(fn($line) => [
                            'refund_id' =>
                            $line->refund_id,

                            'refund_code' =>
                            $line->refund?->refund_code,

                            'amount' =>
                            (float) $line->amount,

                            'refund_total' =>
                            (float) ($line->refund?->amount ?? 0),

                            'refund_method' =>
                            $line->refund?->refund_method,

                            'status' =>
                            $line->refund?->status,

                            'declined_reason' =>
                            $line->refund?->declined_reason,

                            'created_at' =>
                            $line->refund?->created_at,
                        ])
                        ->values(),
                ])
                ->values(),
        ];
    }

    public function getUnpaidInvoiceByPatient(
        string $patientUuid,
        string $branchId
    ) {
        $invoices = Invoice::where(function ($q) use ($patientUuid) {
            $q->whereHas(
                'invoiceServices.scheduleService.schedule.patient',
                fn($p) =>
                $p->where('uuid', $patientUuid)
            )->orWhereHas(
                'invoiceAdmissionLines.admissionPeriod.patientAdmission.patient',
                fn($p) =>
                $p->where('uuid', $patientUuid)
            );
        })
            ->where('branch_id', $branchId)
            ->whereIn('status', [
                Invoice::STATUS_PENDING,
                Invoice::STATUS_PARTIAL,
            ])
            ->orderBy('created_at')
            ->get();

        $totalRefunded = (float) $invoices->sum(
            fn($invoice) =>
            $invoice->refunded_amount
        );

        return [
            'invoices' => $invoices,
            'total_refunded' => $totalRefunded,
        ];
    }

    public function overview(array $payload): array
    {
        $branchId =
            $payload['branch_id'] ?? null;

        $month =
            $payload['month'] ?? now()->month;

        $year =
            $payload['year'] ?? now()->year;

        $currentDate = Carbon::create(
            $year,
            $month,
            1
        );

        $currentMonthStart =
            $currentDate->copy()->startOfMonth();

        $currentMonthEnd =
            $currentDate->copy()->endOfMonth();

        $lastMonthStart =
            $currentDate
            ->copy()
            ->subMonth()
            ->startOfMonth();

        $lastMonthEnd =
            $currentDate
            ->copy()
            ->subMonth()
            ->endOfMonth();

        $invoiceQuery = Invoice::query()
            ->where('branch_id', $branchId)
            ->where(
                'status',
                '!=',
                Invoice::STATUS_VOID
            );

        // Credit applications move money that was already received, so counting
        // them again would report the same peso twice.
        $paymentQuery = Payment::query()
            ->where('payment_method', '!=', Payment::METHOD_CREDIT)
            ->whereHas('invoices', function ($query) use ($branchId) {
                $query
                    ->where('branch_id', $branchId)
                    ->where(
                        'status',
                        '!=',
                        Invoice::STATUS_VOID
                    );
            });

        $totalRevenue = (clone $invoiceQuery)
            ->whereBetween('created_at', [
                $currentMonthStart,
                $currentMonthEnd,
            ])
            ->get()
            ->sum('adjusted_total');

        $paymentsReceived = (clone $paymentQuery)
            ->whereBetween('created_at', [
                $currentMonthStart,
                $currentMonthEnd,
            ])
            ->sum('amount');

        $refundsIssued = $this->refundsIssuedBetween(
            $branchId,
            $currentMonthStart,
            $currentMonthEnd
        );

        $outstandingBalance = (clone $invoiceQuery)
            ->whereBetween('created_at', [
                $currentMonthStart,
                $currentMonthEnd,
            ])
            ->get()
            ->sum
            ->balance_due;

        $lastRevenue = (clone $invoiceQuery)
            ->whereBetween('created_at', [
                $lastMonthStart,
                $lastMonthEnd,
            ])
            ->get()
            ->sum('adjusted_total');

        $lastPayments = (clone $paymentQuery)
            ->whereBetween('created_at', [
                $lastMonthStart,
                $lastMonthEnd,
            ])
            ->sum('amount');

        $lastRefunds = $this->refundsIssuedBetween(
            $branchId,
            $lastMonthStart,
            $lastMonthEnd
        );

        $lastOutstanding = (clone $invoiceQuery)
            ->whereBetween('created_at', [
                $lastMonthStart,
                $lastMonthEnd,
            ])
            ->get()
            ->sum
            ->balance_due;

        $upcomingPayments = (clone $invoiceQuery)
            ->whereBetween('created_at', [
                $currentMonthStart,
                $currentMonthEnd,
            ])
            ->get()
            ->filter(
                fn($invoice) =>
                $invoice->balance_due > 0
            )
            ->sum('balance_due');

        $lastUpcoming = (clone $invoiceQuery)
            ->whereBetween('created_at', [
                $lastMonthStart,
                $lastMonthEnd,
            ])
            ->get()
            ->filter(
                fn($invoice) =>
                $invoice->balance_due > 0
            )
            ->sum('balance_due');

        return [
            'total_revenue' => [
                'value' => $totalRevenue,

                'secondary' => $this->formatChange(
                    $this->percentageChange(
                        $totalRevenue,
                        $lastRevenue
                    ),
                    'vs last month'
                ),

                'trend' =>
                $totalRevenue >= $lastRevenue
                    ? 'up'
                    : 'down',
            ],

            'payments_received' => [
                'value' => $paymentsReceived,

                'secondary' => $this->formatChange(
                    $this->percentageChange(
                        $paymentsReceived,
                        $lastPayments
                    ),
                    'vs last month'
                ),

                'trend' =>
                $paymentsReceived >= $lastPayments
                    ? 'up'
                    : 'down',
            ],

            'refunds_issued' => [
                'value' => $refundsIssued,

                'secondary' => $this->formatChange(
                    $this->percentageChange(
                        $refundsIssued,
                        $lastRefunds
                    ),
                    'vs last month'
                ),

                'trend' =>
                $refundsIssued <= $lastRefunds
                    ? 'up'
                    : 'warning',
            ],

            'outstanding_balance' => [
                'value' => $outstandingBalance,

                'secondary' => $this->formatChange(
                    $this->percentageChange(
                        $outstandingBalance,
                        $lastOutstanding
                    ),
                    'vs last month'
                ),

                'trend' =>
                $outstandingBalance <= $lastOutstanding
                    ? 'up'
                    : 'down',
            ],

            'upcoming_payments' => [
                'value' => $upcomingPayments,

                'secondary' => $this->formatChange(
                    $this->percentageChange(
                        $upcomingPayments,
                        $lastUpcoming
                    ),
                    'vs last month'
                ),

                'trend' =>
                $upcomingPayments <= $lastUpcoming
                    ? 'up'
                    : 'down',
            ],
        ];
    }

    private function percentageChange(
        float $current,
        float $previous
    ) {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round(
            (($current - $previous) / $previous) * 100,
            1
        );
    }

    private function formatChange(
        float $change,
        string $suffix
    ) {
        $sign = $change > 0 ? '+' : '';

        return "{$sign}{$change}% {$suffix}";
    }
}
