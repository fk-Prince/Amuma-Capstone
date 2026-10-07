<?php

namespace App\Repository;

use App\Models\Transaction;
use App\Http\Resources\PatientInvoiceSummaryResource;
use App\Http\Resources\RefundResource;
use App\Models\AdmissionPeriod;
use App\Models\Invoice;
use App\Models\InvoiceAdmission;
use App\Models\InvoiceAdjustment;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Refund;
use App\Utils\DischargeCalculator;
use App\Utils\InvoiceMoney;
use App\Utils\OutstandingBalance;
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
            'additionalCharges.patientAdmission.patient',
            'allocations.refundAllocations.refund.transaction',
            'allocations.payment.transaction',
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
                'allocations.refundAllocations.refund.transaction',
                'allocations.payment.transaction',
                'payments',
                'invoiceServices.scheduleService.schedule.patient',
                'invoiceServices.scheduleService.service',
                'invoiceAdmissionLines.admissionPeriod.patientAdmission.patient',
                'invoiceAdmissionLines.admissionPeriod.patientAdmission.bed.room',
                'invoiceAdmissionLines.admissionPeriod.branchContract',
                'additionalCharges.patientAdmission.patient',
            ]);

        if (!empty($search)) {
            $term = '%' . strtolower(trim($search)) . '%';

            $patientName = fn($patient) => $patient->where(
                fn($name) => $name
                    ->whereRaw('LOWER(first_name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', [$term])
                    ->orWhereRaw("LOWER(first_name || ' ' || last_name) LIKE ?", [$term])
            );

            $query->where(function ($q) use ($search, $patientName) {
                $q->where(
                    'invoice_code',
                    'like',
                    "%{$search}%"
                )
                    ->orWhereHas('invoiceServices.scheduleService.schedule.patient', $patientName)
                    ->orWhereHas('invoiceAdmissionLines.admissionPeriod.patientAdmission.patient', $patientName)
                    ->orWhereHas('additionalCharges.patientAdmission.patient', $patientName);
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

    public function upgradesOf(Invoice $invoice)
    {
        $periodIds = $invoice->invoiceAdmissionLines()
            ->pluck('admission_period_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $covered = $periodIds;

        while ($periodIds) {
            $periodIds = AdmissionPeriod::whereIn('parent_admission_period_id', $periodIds)
                ->whereNotIn('admission_period_id', $covered)
                ->pluck('admission_period_id')
                ->all();

            $covered = array_merge($covered, $periodIds);
        }

        if (!$covered) {
            return collect();
        }

        return Invoice::whereIn(
            'invoice_id',
            InvoiceAdmission::whereIn('admission_period_id', $covered)->pluck('invoice_id')
        )
            ->where('invoice_id', '>', $invoice->invoice_id)
            ->whereNotIn('status', Invoice::CLOSED_STATUSES)
            ->get();
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
            )->orWhereHas(
                'additionalCharges.patientAdmission.patient',
                fn($p) => $p->where('uuid', $patientUuid)
            );
        })
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with([
                'branch',
                'allocations.refundAllocations.refund.transaction',
                'allocations.payment.transaction',
                'payments',
                'invoiceServices.scheduleService.schedule.patient',
                'invoiceServices.scheduleService.service',
                'invoiceAdmissionLines.admissionPeriod.patientAdmission.patient',
                'invoiceAdmissionLines.admissionPeriod.patientAdmission.bed.room',
                'invoiceAdmissionLines.admissionPeriod.branchContract',
                'additionalCharges.patientAdmission.patient',
                'additionalCharges.patientAdmission.bed.room',
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
        $branchId = $payload['branch_id'];

        $patients = Patient::query()
            ->where('branch_id', $branchId)
            ->when($search !== '', function ($q) use ($search) {
                $term = '%' . strtolower($search) . '%';

                $q->where(function ($name) use ($term, $search) {
                    // Matched from the start, so PT-0001 finds the codes it opens
                    // but a fragment out of the middle does not.
                    $name->whereRaw('LOWER(patient_code) LIKE ?', [strtolower($search) . '%'])
                        ->orWhereRaw('LOWER(first_name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$term])
                        ->orWhereRaw(
                            "LOWER(first_name || ' ' || last_name) LIKE ?",
                            [$term]
                        );
                });
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate($payload['per_page'] ?? 10);

        if ($patients->isEmpty()) {
            return PatientInvoiceSummaryResource::collection($patients);
        }

        // Only the invoices belonging to the patients on this page: pulling the
        // whole branch's invoice history here is what pushed this past 30s.
        $patientIds = $patients->pluck('patient_id')->all();

        $invoices = Invoice::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', '!=', Invoice::STATUS_VOID)
            ->where(function ($q) use ($patientIds) {
                $q->whereHas(
                    'invoiceServices.scheduleService.schedule',
                    fn($s) => $s->whereIn('patient_id', $patientIds)
                )->orWhereHas(
                    'invoiceAdmissionLines.admissionPeriod.patientAdmission',
                    fn($a) => $a->whereIn('patient_id', $patientIds)
                )->orWhereHas(
                    'additionalCharges.patientAdmission',
                    fn($a) => $a->whereIn('patient_id', $patientIds)
                );
            })
            ->with([
                'branch',
                'allocations.refundAllocations.refund.transaction',
                'allocations.payment.transaction',
                'payments',
                'invoiceServices.scheduleService.schedule.patient',
                'invoiceServices.scheduleService.service',
                'invoiceAdmissionLines.admissionPeriod.patientAdmission.patient',
                'invoiceAdmissionLines.admissionPeriod.patientAdmission.bed.room',
                'invoiceAdmissionLines.admissionPeriod.branchContract',
                'additionalCharges.patientAdmission.patient',
            ])
            ->get();

        $grouped = $invoices->groupBy(
            fn($invoice) => $this->invoicePatient($invoice)?->patient_id ?? 'unknown'
        );

        // The list renders totals only, so none of the per-invoice panels are
        // built here. Opening a patient loads those through their own section.
        $summaries = $patients
            ->getCollection()
            ->map(fn($patient) => $this->patientInvoice(
                $grouped->get($patient->patient_id, collect()),
                null,
                [],
                null,
                null,
                $patient
            ))
            ->values();

        return PatientInvoiceSummaryResource::collection(
            $patients->setCollection($summaries)
        );
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


        $patientModel = $patient ?? $patientInvoices
            ->map(fn($invoice) => $this->invoicePatient($invoice))
            ->filter()
            ->first();

        $settledInvoices = $patientInvoices->concat($voidedInvoices);

        $overallTotal = (float) $patientInvoices->sum('adjusted_total');

        $activePaid = (float) $patientInvoices->sum(
            fn($invoice) => $invoice->net_paid_amount
        );

        $overallPaid = (float) $settledInvoices->sum(
            fn($invoice) => $invoice->net_paid_amount
        );

        $overallRefunded = $this->refundRepository->refundedCreditFor(
            $patientModel?->patient_id
        );

        $overallWithdrawn = $this->refundRepository->withdrawnCreditFor(
            $patientModel?->patient_id
        );

        $overallRefundProcessing = $this->refundRepository->pendingCreditFor(
            $patientModel?->patient_id
        );

        $overallRefundable = $this->refundRepository->creditFor(
            $patientModel?->patient_id
        );


        $overallBalance = round(
            (float) $patientInvoices->sum('balance_due'),
            2
        );

        $overallWrittenOff = round(
            (float) $patientInvoices
                ->where('status', Invoice::STATUS_WRITTEN_OFF)
                ->sum(fn($invoice) => max(0, $invoice->adjusted_total - $invoice->net_paid_amount)),
            2
        );

        $refundStatus = match (true) {
            $overallRefunded <= 0 => 'none',
            $overallPaid <= 0 => 'full refunded',
            default => 'partially refunded',
        };

        $formattedInvoices = $patientInvoices
            ->map(function ($invoice) {
                $detail = $this->formatInvoiceDetail($invoice);

                $detail['refundable_amount'] = InvoiceMoney::refundable($invoice);
                $detail['has_pending_refund'] = InvoiceMoney::hasPendingWithdrawal($invoice);

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
                'patient_code' => $patientModel->patient_code,
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
            'total_withdrawn' => $overallWithdrawn,
            'total_refund_requested' => $overallRefundProcessing,
            'total_refundable' => $overallRefundable,
            'refund_status' => $refundStatus,
            'total_balance' => $overallBalance,
            'total_written_off' => $overallWrittenOff,

            'status' => match (true) {
                $overallBalance <= 0 && $overallPaid > 0 => 'Paid',
                $overallPaid > 0 => 'Partial',
                default => 'Unpaid',
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
                        InvoiceMoney::hasPendingWithdrawal($invoice);

                    return $detail;
                })
                ->sortByDesc('created_at')
                ->values();

            $summary['payments'] = $this->formatPayments($settledInvoices);
        }

        if ($this->wants($sections, 'refunds')) {
            $summary['refunds'] = $this->formatRefunds($patientModel?->patient_id);
        }

        if ($wantsAdmissions) {
            $summary['admissions'] = $this->formatAdmissions($patientInvoices);
            $summary['discharge_calculation'] = $this->getPatientDischargeCalculation($patientInvoices);
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

    private function formatAdmissions(mixed $patientInvoices)
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
                )->concat(
                    $invoice->additionalCharges->map(fn($charge) => [
                        'admission' => $charge->patientAdmission,
                        'invoice' => $invoice,
                        'invoice_admission' => null,
                    ])
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

    private function formatAdmissionInvoices(mixed $patientInvoices, int $admissionId)
    {
        return $patientInvoices
            ->filter(
                fn($invoice) => $invoice->invoiceAdmissionLines->contains(
                    fn($line) => (int) $line->admissionPeriod
                        ?->patient_admission_id === $admissionId
                ) || $invoice->additionalCharges->contains(
                    fn($charge) => (int) $charge->patient_admission_id === $admissionId
                )
            )
            ->map(function ($invoice) {
                $detail = $this->formatInvoiceDetail($invoice);

                $detail['refundable_amount'] = InvoiceMoney::refundable($invoice);
                $detail['has_pending_refund'] = InvoiceMoney::hasPendingWithdrawal($invoice);

                return $detail;
            })
            ->sortByDesc('created_at')
            ->values();
    }

    private function formatServiceInvoices(mixed $patientInvoices, int $scheduleServiceId)
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
                $detail['has_pending_refund'] = InvoiceMoney::hasPendingWithdrawal($invoice);

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


    private function getPatientDischargeCalculation(
        mixed $patientInvoices
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

        $calculation = DischargeCalculator::getDischargeCalculation(
            $item['invoice'],
            $item['admission'],
            $item['period']
        );

        $futurePeriodIds = $admissionItems
            ->filter(
                fn($row) =>
                $row['admission']->patient_admission_id === $item['admission']->patient_admission_id
                    && $row['period']->admission_period_id !== $currentPeriodId
                    && !in_array($row['period']->status, \App\Models\AdmissionPeriod::CLOSED_STATUSES, true)
                    && Carbon::parse($row['period']->start_date)
                    ->gte(Carbon::parse($item['period']->end_date))
            )
            ->map(fn($row) => $row['period']->admission_period_id)
            ->unique()
            ->values()
            ->all();

        $calculation['outstanding'] = OutstandingBalance::forInvoices(
            $patientInvoices,
            $item['invoice'],
            $futurePeriodIds,
            $item['admission']->patient_admission_id
        );

        return $calculation;
    }

    private function refundsIssuedBetween(
        mixed $branchId,
        ?Carbon $from,
        ?Carbon $to
    ): float {
        return round(
            (float) Transaction::query()
                ->where('branch_id', $branchId)
                ->where('type', Transaction::TYPE_WITHDRAW)
                ->where('status', Transaction::STATUS_COMPLETED)
                ->when($from && $to, fn($query) => $query->whereBetween('created_at', [$from, $to]))
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

    private function invoicePatient(object $invoice)
    {
        return $invoice->invoiceServices
            ->first()
            ?->scheduleService
            ?->schedule
            ?->patient
            ?? $invoice->chargedAdmission()?->patient;
    }

    private function formatInvoice(object $invoice)
    {
        $patient = $this->invoicePatient($invoice);

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

        if ($invoice->additionalCharges->isNotEmpty()) {
            $category[] = 'Charges';
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
                $invoice->status === Invoice::STATUS_WRITTEN_OFF => 'Written Off',
                $balance <= 0 => 'Paid',
                $paid > 0 => 'Partial',
                default => 'Unpaid',
            },

            'write_off_reason' => $invoice->write_off_reason,
            'written_off_amount' => $invoice->status === Invoice::STATUS_WRITTEN_OFF
                ? round(max(0, $invoice->adjusted_total - $invoice->net_paid_amount), 2)
                : 0.0,
            'written_off_at' => $invoice->written_off_at?->toIso8601String(),
            'written_off_by' => $this->writtenOffByName($invoice),

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

        return Payment::with('allocations.invoice', 'transaction')
            ->whereIn('payment_id', $paymentIds)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(Payment $payment) => [
                'payment_id' => $payment->payment_id,
                'payment_code' => $payment->payment_code,
                'transaction_code' => $payment->transaction?->transaction_code,
                'reference_id' => $payment->reference_id,
                'amount' => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
                'masked_account_detail' => $payment->masked_account_detail,
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

    private function formatRefunds(mixed $patientId)
    {
        if (!$patientId) {
            return collect();
        }

        return $this->refundRepository
            ->withdrawalsFor($patientId)
            ->map(fn(Transaction $withdrawal) => RefundResource::format($withdrawal))
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

    private function writtenOffByName(object $invoice): ?string
    {
        $user = $invoice->writtenOffBy;

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
            (float) $invoice->refunded_amount;

        $refundProcessing =
            InvoiceMoney::pendingWithdrawal($invoice);

        $balance =
            $invoice->balance_due;

        return [
            'invoice_id' => $invoice->invoice_id,
            'invoice_code' => $invoice->invoice_code,
            'description' => $invoice->paymentDescription(),
            'total' => $total,

            'original_total' => (float) $invoice->total_amount,

            'adjustments' => $invoice->invoiceAdjustments
                ->map(fn($adjustment) => [
                    'invoice_adjustment_id' => $adjustment->invoice_adjustment_id,
                    'type' => $adjustment->type,
                    'amount' => (float) $adjustment->amount,
                    'reason' => $adjustment->reason,
                    'created_at' => $adjustment->created_at?->toIso8601String(),
                ])
                ->values(),

            'amount_paid' => $paid,
            'refunded_amount' =>   $refunded,
            'refund_requested_amount' =>   $refundProcessing,
            'balance_due' => $balance,
            'status' => match (true) {
                $invoice->status === Invoice::STATUS_WRITTEN_OFF => 'Written Off',
                $balance <= 0 => 'Paid',
                $paid > 0 => 'Partial',
                default => 'Unpaid',
            },
            'write_off_reason' => $invoice->write_off_reason,
            'written_off_amount' => $invoice->status === Invoice::STATUS_WRITTEN_OFF
                ? round(max(0, $invoice->adjusted_total - $invoice->net_paid_amount), 2)
                : 0.0,
            'written_off_at' => $invoice->written_off_at?->toIso8601String(),
            'written_off_by' => $this->writtenOffByName($invoice),
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

                    'type' =>
                    $invoiceService->scheduleService?->type,

                    'hours_booked' =>
                    $invoiceService->scheduleService?->hours_booked !== null
                        ? (float) $invoiceService->scheduleService->hours_booked
                        : null,
                ])
                ->values(),

            'charges' =>
            $invoice->additionalCharges
                ->map(fn($charge) => [
                    'additional_charge_id' => $charge->additional_charge_id,
                    'type' => $charge->type,
                    'type_label' => $charge->type_label,
                    'description' => $charge->description,
                    'amount' => (float) $charge->amount,
                    'created_at' => $charge->created_at?->toIso8601String(),
                ])
                ->values(),

            'payments' =>
            $invoice->allocations
                ->map(fn($allocation) => [
                    'payment_id' =>
                    $allocation->payment_id,

                    'payment_code' =>
                    $allocation->payment?->payment_code,

                    'reference_id' =>
                    $allocation->payment?->reference_id,

                    'amount' =>
                    (float) $allocation->amount,

                    'payment_method' =>
                    $allocation->payment?->payment_method,

                    'created_at' =>
                    $allocation->payment?->created_at,

                    'refunds' =>
                    $allocation->refundAllocations
                        ->map(fn($line) => [
                            'refund_id' =>
                            $line->refund_id,

                            'refund_code' =>
                            $line->refund?->transaction?->transaction_code,

                            'amount' =>
                            (float) $line->amount,

                            'refund_total' =>
                            (float) ($line->refund?->amount ?? 0),

                            'refund_method' =>
                            $line->refund?->transaction?->method,

                            'reason' =>
                            $line->invoiceAdjustment?->reason,

                            'status' =>
                            $line->refund?->transaction?->status,

                            'declined_reason' =>
                            $line->refund?->transaction?->declined_reason,

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
            )->orWhereHas(
                'additionalCharges.patientAdmission.patient',
                fn($p) =>
                $p->where('uuid', $patientUuid)
            );
        })
            ->where('branch_id', $branchId)
            ->whereIn('status', [
                Invoice::STATUS_PENDING,
                Invoice::STATUS_PARTIAL,
            ])
            ->get()
            ->sortBy(fn($invoice) => $invoice->paymentOrder())
            ->values();

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
        $branchId = $payload['branch_id'] ?? null;
        $period = $this->overviewPeriod($payload);
        $compare = $period['from'] !== null;

        $invoices = $this->invoicesIssued($branchId, $period['from'], $period['to']);
        $billed = $this->billedTotal($invoices);
        $paid = $this->paidTotal($invoices);
        $refunds = $this->refundsIssuedBetween($branchId, $period['from'], $period['to']);

        $unpaid = $invoices->filter(
            fn(Invoice $invoice) => in_array($invoice->status, [Invoice::STATUS_PENDING, Invoice::STATUS_PARTIAL], true)
                && $invoice->balance_due > 0
        );

        $receivable = round((float) $unpaid->sum('balance_due'), 2);

        $previousInvoices = $compare
            ? $this->invoicesIssued($branchId, $period['previous_from'], $period['previous_to'])
            : null;

        $previousBilled = $previousInvoices ? $this->billedTotal($previousInvoices) : null;
        $previousPaid = $previousInvoices ? $this->paidTotal($previousInvoices) : null;

        $previousRefunds = $compare
            ? $this->refundsIssuedBetween($branchId, $period['previous_from'], $period['previous_to'])
            : null;

        return [
            'period' => $period['label'],
            'chart' => $this->overviewChart($branchId, $period, $invoices),

            'total_revenue' => [
                'value' => $billed,
                'secondary' => $compare
                    ? $this->changeLabel($billed, $previousBilled, $period['previous'])
                    : $this->countLabel($invoices->count(), 'invoice'),
                'trend' => $compare && $billed < $previousBilled ? 'down' : 'up',
            ],

            'payments_received' => [
                'value' => $paid,
                'secondary' => $compare
                    ? $this->changeLabel($paid, $previousPaid, $period['previous'])
                    : $this->countLabel(
                        $invoices->filter(fn(Invoice $invoice) => $invoice->net_paid_amount > 0)->count(),
                        'paid invoice'
                    ),
                'trend' => $compare && $paid < $previousPaid ? 'down' : 'up',
            ],

            'refunds_issued' => [
                'value' => $refunds,
                'secondary' => $compare
                    ? $this->changeLabel($refunds, $previousRefunds, $period['previous'])
                    : null,
                'trend' => $compare && $refunds > $previousRefunds ? 'warning' : 'up',
            ],

            'outstanding_balance' => [
                'value' => $receivable,
                'secondary' => $unpaid->isEmpty()
                    ? 'Nothing owed'
                    : $this->countLabel($unpaid->count(), 'unpaid invoice'),
                'trend' => $receivable > 0 ? 'down' : 'up',
            ],
        ];
    }

    private function overviewPeriod(array $payload): array
    {
        $mode = $payload['period'] ?? 'month';

        if ($mode === 'all') {
            return [
                'mode' => 'all',
                'from' => null,
                'to' => null,
                'previous_from' => null,
                'previous_to' => null,
                'previous' => null,
                'label' => 'All time',
            ];
        }

        if ($mode === 'date') {
            $from = Carbon::parse($payload['from'] ?? now()->toDateString())->startOfDay();
            $to = Carbon::parse($payload['to'] ?? $from->toDateString())->endOfDay();

            if ($to->lt($from)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            $days = (int) $from->diffInDays($to->copy()->startOfDay()) + 1;

            return [
                'mode' => 'date',
                'from' => $from,
                'to' => $to,
                'previous_from' => $from->copy()->subDays($days),
                'previous_to' => $from->copy()->subDay()->endOfDay(),
                'previous' => $days === 1 ? 'the previous day' : "the previous {$days} days",
                'label' => $days === 1
                    ? $from->format('M j, Y')
                    : $from->format('M j, Y') . ' – ' . $to->format('M j, Y'),
            ];
        }

        $from = Carbon::create(
            $payload['year'] ?? now()->year,
            $payload['month'] ?? now()->month,
            1
        )->startOfMonth();

        return [
            'mode' => 'month',
            'from' => $from,
            'to' => $from->copy()->endOfMonth(),
            'previous_from' => $from->copy()->subMonth()->startOfMonth(),
            'previous_to' => $from->copy()->subMonth()->endOfMonth(),
            'previous' => 'last month',
            'label' => $from->format('F Y'),
        ];
    }

    private function overviewChart(mixed $branchId, array $period, $invoices): array
    {
        if ($period['mode'] === 'all') {
            $first = $invoices->min('created_at');
            $start = Carbon::parse($first ?? now())->startOfMonth();
            $end = now()->startOfMonth();

            if ($start->diffInMonths($end) < 24) {
                $byMonth = $invoices->groupBy(fn(Invoice $invoice) => Carbon::parse($invoice->created_at)->format('Y-m'));
                $months = collect(Carbon::parse($start)->monthsUntil($end))->map(fn(Carbon $month) => $month->copy());

                return [
                    'title' => 'Revenue over time',
                    'labels' => $months->map(fn(Carbon $month) => $month->format('M Y'))->all(),
                    'revenue' => $months->map(fn(Carbon $month) => $this->billedTotal($byMonth->get($month->format('Y-m'), collect())))->all(),
                    'highlight' => [],
                ];
            }

            $byYear = $invoices->groupBy(fn(Invoice $invoice) => (int) Carbon::parse($invoice->created_at)->year);
            $years = range($start->year, $end->year);

            return [
                'title' => 'Revenue by year',
                'labels' => array_map('strval', $years),
                'revenue' => array_map(fn($year) => $this->billedTotal($byYear->get($year, collect())), $years),
                'highlight' => [],
            ];
        }

        $year = $period['from']->year;

        $byMonth = $this->invoicesIssued(
            $branchId,
            Carbon::create($year, 1, 1)->startOfYear(),
            Carbon::create($year, 12, 31)->endOfYear()
        )->groupBy(fn(Invoice $invoice) => (int) Carbon::parse($invoice->created_at)->month);

        $months = range(1, 12);

        $highlight = array_values(array_filter(
            $months,
            fn($month) => Carbon::create($year, $month, 1)->startOfMonth()->lte($period['to'])
                && Carbon::create($year, $month, 1)->endOfMonth()->gte($period['from'])
        ));

        return [
            'title' => "Revenue in {$year}",
            'labels' => array_map(fn($month) => Carbon::create($year, $month, 1)->format('M'), $months),
            'revenue' => array_map(fn($month) => $this->billedTotal($byMonth->get($month, collect())), $months),
            'highlight' => array_map(fn($month) => $month - 1, $highlight),
        ];
    }

    private function invoicesIssued(mixed $branchId, ?Carbon $from, ?Carbon $to)
    {
        return Invoice::query()
            ->with('invoiceAdjustments', 'allocations.refundAllocations')
            ->where('branch_id', $branchId)
            ->where('status', '!=', Invoice::STATUS_VOID)
            ->when($from && $to, fn($query) => $query->whereBetween('created_at', [$from, $to]))
            ->get();
    }

    private function billedTotal($invoices): float
    {
        return round(
            (float) $invoices->sum(
                fn(Invoice $invoice) => $invoice->status === Invoice::STATUS_WRITTEN_OFF
                    ? min($invoice->net_paid_amount, $invoice->adjusted_total)
                    : $invoice->adjusted_total
            ),
            2
        );
    }

    private function paidTotal($invoices): float
    {
        return round(
            (float) $invoices->sum(
                fn(Invoice $invoice) => min($invoice->net_paid_amount, $invoice->adjusted_total)
            ),
            2
        );
    }

    private function countLabel(int $count, string $noun): string
    {
        return $count . ' ' . $noun . ($count === 1 ? '' : 's');
    }

    private function changeLabel(float $current, float $previous, string $previousLabel): string
    {
        if ($previous == 0) {
            return $current == 0 ? "0% vs {$previousLabel}" : "Up from ₱0 {$previousLabel}";
        }

        $change = round((($current - $previous) / $previous) * 100, 1);

        return ($change > 0 ? '+' : '') . $change . "% vs {$previousLabel}";
    }
}
