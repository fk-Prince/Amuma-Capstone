<?php

namespace App\Repository;

use App\Http\Resources\PatientInvoiceSummaryResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Refund;
use App\Service\RefundService;
use Carbon\Carbon;

class InvoiceRepository
{
    public function __construct(
        private RefundService $refundService
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
            'invoiceAccommodation.patientAdmission.patient',
            'invoiceAccommodation.patientAdmission.bed.room',
            'invoiceAccommodation.branchContract',
            'payments.refunds',
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
                'payments.refunds',
                'invoiceServices.scheduleService.schedule.patient',
                'invoiceServices.scheduleService.service',
                'invoiceAccommodation.patientAdmission.patient',
                'invoiceAccommodation.patientAdmission.bed.room',
                'invoiceAccommodation.branchContract',
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

        $query = Invoice::where(function ($q) use ($patientUuid) {
            $q->whereHas(
                'invoiceServices.scheduleService.schedule.patient',
                fn($p) => $p->where('uuid', $patientUuid)
            )->orWhereHas(
                'invoiceAccommodation.patientAdmission.patient',
                fn($p) => $p->where('uuid', $patientUuid)
            );
        })
            ->where('status', '!=', Invoice::STATUS_VOID);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $query->with([
            'branch',
            'payments.refunds',
            'invoiceServices.scheduleService.schedule.patient',
            'invoiceServices.scheduleService.service',
            'invoiceAccommodation.patientAdmission.patient',
            'invoiceAccommodation.patientAdmission.bed.room',
            'invoiceAccommodation.branchContract',
        ]);

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            return null;
        }

        $summary = $this->patientInvoice($invoices);

        return new PatientInvoiceSummaryResource($summary);
    }

    public function getPatientInvoiceSummary(array $payload)
    {
        $search = $payload['search'];
        $branchId = $payload['branch_id'] ?? null;

        $query = Invoice::where(function ($q) use ($search) {
            $q->whereHas(
                'invoiceServices.scheduleService.schedule.patient',
                fn($p) => $p
                    ->whereRaw(
                        'LOWER(first_name) LIKE ?',
                        ["%" . strtolower($search) . "%"]
                    )
                    ->orWhereRaw(
                        'LOWER(last_name) LIKE ?',
                        ["%" . strtolower($search) . "%"]
                    )
            )->orWhereHas(
                'invoiceAccommodation.patientAdmission.patient',
                fn($p) => $p
                    ->whereRaw(
                        'LOWER(first_name) LIKE ?',
                        ["%" . strtolower($search) . "%"]
                    )
                    ->orWhereRaw(
                        'LOWER(last_name) LIKE ?',
                        ["%" . strtolower($search) . "%"]
                    )
            );
        })
            ->where('status', '!=', Invoice::STATUS_VOID);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $query->with([
            'branch',
            'payments.refunds',
            'invoiceServices.scheduleService.schedule.patient',
            'invoiceServices.scheduleService.service',
            'invoiceAccommodation.patientAdmission.patient',
            'invoiceAccommodation.patientAdmission.bed.room',
            'invoiceAccommodation.branchContract',
        ]);

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            return [];
        }

        $grouped = $invoices->groupBy(function ($invoice) {
            $patient =
                $invoice->invoiceServices
                ->first()
                ?->scheduleService
                ?->schedule
                ?->patient
                ??
                $invoice->invoiceAccommodation
                ->first()
                ?->patientAdmission
                ?->patient;

            return $patient?->patient_id ?? 'unknown';
        });

        $summaries = $grouped->map(
            fn($patientInvoices) => $this->patientInvoice(
                $patientInvoices
            )
        )->values();

        return PatientInvoiceSummaryResource::collection(
            $summaries
        );
    }

    private function patientInvoice(mixed $patientInvoices)
    {
        $patientInvoices = $patientInvoices
            ->where('status', '!=', Invoice::STATUS_VOID)
            ->values();

        $patientModel = $patientInvoices
            ->map(
                fn($invoice) =>
                $invoice->invoiceServices
                    ->first()
                    ?->scheduleService
                    ?->schedule
                    ?->patient
                    ??
                    $invoice->invoiceAccommodation
                    ->first()
                    ?->patientAdmission
                    ?->patient
            )
            ->filter()
            ->first();

        $overallTotal = (float) $patientInvoices->sum('total');

        $overallPaid = (float) $patientInvoices->sum(
            fn($invoice) => $invoice->net_paid_amount
        );

        $overallRefunded = (float) $patientInvoices->sum(
            fn($invoice) => $invoice->refunded_completed_amount
        );

        $overallRefundProcessing = (float) $patientInvoices->sum(
            fn($invoice) => $invoice->refunded_processing_amount
        );

        $overallBalance = max(
            $overallTotal - $overallPaid,
            0
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
            ->map(fn($invoice) => $this->formatInvoiceDetail($invoice))
            ->values();

        $latestInvoice = $formattedInvoices
            ->sortByDesc('created_at')
            ->first();

        $admissions = $this->formatAdmissions(
            $patientInvoices
        );

        $services = $this->formatPatientServices(
            $patientInvoices
        );

        $dischargeCalculation =
            $this->getPatientDischargeCalculation(
                $patientInvoices
            );

        return [
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
            'total_refund_processing' => $overallRefundProcessing,
            'refund_status' => $refundStatus,
            'total_balance' => $overallBalance,

            'status' => match (true) {
                $overallBalance <= 0 && $overallPaid > 0 => 'Paid',
                $overallPaid > 0 => 'Partial',
                default => 'Pending',
            },

            'invoice_count' => $patientInvoices->count(),

            'latest_invoice' => $latestInvoice,

            'admissions' => $admissions,

            'services' => $services,

            'discharge_calculation' => $dischargeCalculation,
        ];
    }

    private function formatAdmissions($patientInvoices)
    {
        $admissions = $patientInvoices
            ->flatMap(
                fn($invoice) =>
                $invoice->invoiceAccommodation->map(
                    fn($invoiceAccommodation) => [
                        'admission' => $invoiceAccommodation->patientAdmission,
                        'invoice' => $invoice,
                        'invoice_accommodation' => $invoiceAccommodation,
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
                    'patient_admission_id' =>
                    $admission->patient_admission_id,

                    'status' =>
                    $admission->status,

                    'admission_date' =>
                    $admission->admitted_at,

                    'discharge_date' =>
                    $admission->end_date,


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

                    'invoices' => $invoices,
                ];
            })
            ->sortByDesc('admission_date')
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
     *         -> InvoiceAccommodation
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
                $invoice->invoiceAccommodation->map(
                    fn($invoiceAccommodation) => [
                        'invoice' => $invoice,
                        'invoice_accommodation' => $invoiceAccommodation,
                        'admission' => $invoiceAccommodation->patientAdmission,
                    ]
                )
            )
            ->filter(
                fn($item) =>
                $item['admission'] !== null &&
                    strtolower($item['admission']->status) === 'admitted'
            )
            ->sortByDesc(
                fn($item) => $item['admission']->admitted_at
            )
            ->values();

        if ($admissionItems->isEmpty()) {
            return null;
        }


        $item = $admissionItems->first();

        return $this->refundService->getDischargeCalculation(
            $item['invoice'],
            $item['admission'],
            $item['invoice_accommodation']
        );
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
            $invoice->invoiceAccommodation
            ->first()
            ?->patientAdmission
            ?->patient;

        $total = (float) $invoice->total;
        $paid = $invoice->net_paid_amount;
        $balance = $invoice->balance_due;

        $category = [];

        if ($invoice->invoiceServices->isNotEmpty()) {
            $category[] = 'Homecare';
        }

        if ($invoice->invoiceAccommodation->isNotEmpty()) {
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

    private function formatInvoiceDetail(
        object $invoice
    ): array {
        $total = (float) $invoice->total;

        $paid =
            $invoice->net_paid_amount;

        $refunded =
            $invoice->refunded_completed_amount;

        $refundProcessing =
            $invoice->refunded_processing_amount;

        $balance =
            $invoice->balance_due;

        return [
            'invoice_id' => $invoice->invoice_id,
            'invoice_code' => $invoice->invoice_code,
            'total' => $total,
            'amount_paid' => $paid,
            'refunded_amount' =>   $refunded,
            'refund_processing_amount' =>   $refundProcessing,
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
            $invoice->payments
                ->map(fn($payment) => [
                    'payment_id' =>
                    $payment->payment_id,

                    'reference_id' =>
                    $payment->reference_id,

                    'amount' =>
                    (float) $payment->amount,

                    'payment_method' =>
                    $payment->payment_method,

                    'created_at' =>
                    $payment->created_at,

                    'refunds' =>
                    $payment->refunds
                        ->map(fn($refund) => [
                            'refund_id' =>
                            $refund->refund_id,

                            'reference_id' =>
                            $refund->reference_id,

                            'amount' =>
                            (float) $refund->amount,

                            'refund_method' =>
                            $refund->refund_method,

                            'status' =>
                            $refund->status,

                            'reason' =>
                            $refund->reason,
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
                'invoiceAccommodation.patientAdmission.patient',
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

        $paymentQuery = Payment::query()
            ->whereHas('invoice', function ($query) use ($branchId) {
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
            ->sum('total');

        $paymentsReceived = (clone $paymentQuery)
            ->whereBetween('created_at', [
                $currentMonthStart,
                $currentMonthEnd,
            ])
            ->sum('amount');

        $refundsIssued = (clone $paymentQuery)
            ->whereHas('refunds', function ($query) use (
                $currentMonthStart,
                $currentMonthEnd
            ) {
                $query
                    ->where(
                        'status',
                        Refund::STATUS_COMPLETED
                    )
                    ->whereBetween('created_at', [
                        $currentMonthStart,
                        $currentMonthEnd,
                    ]);
            })
            ->get()
            ->flatMap
            ->refunds
            ->where(
                'status',
                Refund::STATUS_COMPLETED
            )
            ->whereBetween(
                'created_at',
                [
                    $currentMonthStart,
                    $currentMonthEnd,
                ]
            )
            ->sum('amount');

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
            ->sum('total');

        $lastPayments = (clone $paymentQuery)
            ->whereBetween('created_at', [
                $lastMonthStart,
                $lastMonthEnd,
            ])
            ->sum('amount');

        $lastRefunds = (clone $paymentQuery)
            ->whereHas('refunds', function ($query) use (
                $lastMonthStart,
                $lastMonthEnd
            ) {
                $query
                    ->where(
                        'status',
                        Refund::STATUS_COMPLETED
                    )
                    ->whereBetween('created_at', [
                        $lastMonthStart,
                        $lastMonthEnd,
                    ]);
            })
            ->get()
            ->flatMap
            ->refunds
            ->where(
                'status',
                Refund::STATUS_COMPLETED
            )
            ->whereBetween(
                'created_at',
                [
                    $lastMonthStart,
                    $lastMonthEnd,
                ]
            )
            ->sum('amount');

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
