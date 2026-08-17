<?php

namespace App\Repository;

use App\Http\Resources\PatientInvoiceSummaryResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Refund;
use Carbon\Carbon;

class InvoiceRepository
{
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
            'invoiceFacility.patientAdmission.patient',
            'payments.refunds',
        ])->where('invoice_code', $payload['invoice_code'])
            ->where('branch_id', $payload['branch_id'])->first();
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
                'invoiceServices.scheduleService',
                'invoiceFacility.patientAdmission.patient',
            ]);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_code', 'like', "%{$search}%");
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
                'invoiceFacility.patientAdmission.patient',
                fn($p) => $p->where('uuid', $patientUuid)
            );
        })->where('status', '!=', Invoice::STATUS_VOID);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $query->with([
            'branch',
            'payments.refunds',
            'invoiceServices.scheduleService.schedule.patient',
            'invoiceServices.scheduleService.service',
            'invoiceFacility.patientAdmission.patient',
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
        $search   = $payload['search'];
        $branchId = $payload['branch_id'] ?? null;

        $query = Invoice::where(function ($q) use ($search) {
            $q->whereHas(
                'invoiceServices.scheduleService.schedule.patient',
                fn($p) => $p->whereRaw('LOWER(first_name) LIKE ?', ["%" . strtolower($search) . "%"])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', ["%" . strtolower($search) . "%"])
            )->orWhereHas(
                'invoiceFacility.patientAdmission.patient',
                fn($p) => $p->whereRaw('LOWER(first_name) LIKE ?', ["%" . strtolower($search) . "%"])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', ["%" . strtolower($search) . "%"])
            );
        })->where('status', '!=', Invoice::STATUS_VOID);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $query->with([
            'branch',
            'payments.refunds',
            'invoiceServices.scheduleService.schedule.patient',
            'invoiceServices.scheduleService.service',
            'invoiceFacility.patientAdmission.patient',
        ]);

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            return [];
        }

        $grouped = $invoices->groupBy(function ($inv) {
            $patient = $inv->invoiceServices->first()?->scheduleService?->schedule?->patient
                ?? $inv->invoiceFacility->first()?->patientAdmission?->patient;

            return $patient?->patient_id ?? 'unknown';
        });

        $summaries = $grouped->map(
            fn($patientInvoices) => $this->patientInvoice($patientInvoices)
        )->values();

        return PatientInvoiceSummaryResource::collection($summaries);
    }

    private function patientInvoice(mixed $patientInvoices)
    {
        $patientInvoices = $patientInvoices
            ->where('status', '!=', Invoice::STATUS_VOID)
            ->values();

        $patientModel = $patientInvoices
            ->map(fn($inv) => $inv->invoiceServices->first()?->scheduleService?->schedule?->patient
                ?? $inv->invoiceFacility->first()?->patientAdmission?->patient)
            ->filter()
            ->first();

        $overallTotal            = (float) $patientInvoices->sum('total');
        $overallPaid             = (float) $patientInvoices->sum(fn($inv) => $inv->net_paid_amount);
        $overallRefunded         = (float) $patientInvoices->sum(fn($inv) => $inv->refunded_completed_amount);
        $overallRefundProcessing = (float) $patientInvoices->sum(fn($inv) => $inv->refunded_processing_amount);
        $overallBalance          = max($overallTotal - $overallPaid, 0);

        $totalRefundedAny = $overallRefunded + $overallRefundProcessing;

        $refundStatus = match (true) {
            $totalRefundedAny <= 0 => 'none',
            $overallPaid <= 0      => 'full refunded',
            default                => 'partially refunded',
        };

        $formattedInvoices = $patientInvoices
            ->map(fn($inv) => $this->formatInvoiceDetail($inv))
            ->values();

        $latestInvoice = $formattedInvoices->sortByDesc('created_at')->first();

        return [
            'patient' => $patientModel ? [
                'patient_id'    => $patientModel->patient_id,
                'patient_uuid'  => $patientModel->uuid,
                'full_name'     => $patientModel->first_name . ' ' . $patientModel->last_name,
                'first_name'    => $patientModel->first_name,
                'middle_name'   => $patientModel->middle_name,
                'last_name'     => $patientModel->last_name,
                'gender'        => $patientModel->gender,
                'date_of_birth' => $patientModel->date_of_birth,
                'age'           => $patientModel->age,
                'blood_type'    => $patientModel->blood_type,
                'phone_number'  => $patientModel->phone_number,
                'citizenship'   => $patientModel->citizenship,
            ] : null,

            'total_amount'            => $overallTotal,
            'total_paid'              => $overallPaid,
            'total_refunded'          => $overallRefunded,
            'total_refund_processing' => $overallRefundProcessing,
            'refund_status'           => $refundStatus,
            'total_balance'           => $overallBalance,

            'status' => match (true) {
                $overallBalance <= 0 && $overallPaid > 0 => 'Paid',
                $overallPaid > 0     => 'Partial',
                default               => 'Pending',
            },

            'invoice_count'  => $patientInvoices->count(),
            'latest_invoice' => $latestInvoice,
            'invoices'       => $formattedInvoices,
        ];
    }

    private function formatInvoice(object $invoice)
    {
        $patient =
            $invoice->invoiceServices
            ->first()?->scheduleService?->schedule?->patient
            ??
            $invoice->invoiceFacility
            ->first()?->patientAdmission?->patient;

        $total = (float) $invoice->total;
        $paid = $invoice->net_paid_amount;
        $balance = $invoice->balance_due;

        $category = [];

        if ($invoice->invoiceServices->isNotEmpty()) {
            $category[] = 'Homecare';
        }

        if ($invoice->invoiceFacility->isNotEmpty()) {
            $category[] = 'Facility';
        }

        return [
            'invoice_code' => $invoice->invoice_code,
            'patient' => $patient
                ? $patient->first_name . ' ' . $patient->last_name
                : null,
            'schedule' => 'Not Applicable',
            'category' => implode(' + ', $category),
            'status' => match (true) {
                $balance <= 0 && $paid > 0 => 'Paid',
                $paid > 0 => 'Partial',
                default => 'Pending',
            },
            'refund_status' => $invoice->refund_status,
            'total' => $total,
            'amount' => $balance,
            'created_at' => $invoice->created_at,
        ];
    }

    private function formatInvoiceDetail(object $invoice): array
    {
        $total = (float) $invoice->total;
        $paid = $invoice->net_paid_amount;
        $refunded = $invoice->refunded_completed_amount;
        $refundProcessing = $invoice->refunded_processing_amount;
        $balance = $invoice->balance_due;

        return [
            'invoice_id'   => $invoice->invoice_id,
            'invoice_code' => $invoice->invoice_code,
            'total'        => $total,
            'amount_paid'  => $paid,
            'refunded_amount' => $refunded,
            'refund_processing_amount' => $refundProcessing,
            'balance_due'  => $balance -  $refundProcessing,
            'is_collected' => $balance <= 0,
            'status' => match (true) {
                $balance <= 0 && $paid > 0 => 'Paid',
                $paid > 0     => 'Partial',
                default        => 'Pending',
            },
            'refund_status' => $invoice->refund_status,
            'created_at' => $invoice->created_at,

            'branch' => $invoice->branch ? [
                'branch_id' => $invoice->branch->branch_id,
                'name'      => $invoice->branch->name,
            ] : null,

            'services' => $invoice->invoiceServices->map(fn($s) => [
                'schedule_services_id' => $s->schedule_services_id,
                'price'         => (float) $s->price,
                'note'          => $s->note,
                'service_name'  => $s->scheduleService?->service_id === null
                    ? 'Activities of Daily Living (ADL)'
                    : ($s->scheduleService->service->service_name ?? null),
            ])->values(),

            'facilities' => $invoice->invoiceFacility->map(fn($f) => [
                'invoice_facility_id'  => $f->invoice_facility_id,
                'branch_contract_id'   => $f->branch_contract_id,
                'price'                => (float) $f->price,
                'patient_admission_id' => $f->patient_admission_id,
                'patient_name'         => $f->patientAdmission?->patient
                    ? $f->patientAdmission->patient->first_name . ' ' . $f->patientAdmission->patient->last_name
                    : null,
            ])->values(),

            'payments' => $invoice->payments->map(fn($p) => [
                'payment_id'     => $p->payment_id,
                'reference_id'   => $p->reference_id,
                'amount'         => (float) $p->amount,
                'payment_method' => $p->payment_method,
                'created_at'     => $p->created_at,
                'refunds' => $p->refunds->map(fn($r) => [
                    'refund_id'     => $r->refund_id,
                    'reference_id'  => $r->reference_id,
                    'amount'        => (float) $r->amount,
                    'refund_method' => $r->refund_method,
                    'status'        => $r->status,
                    'reason'        => $r->reason,
                ])->values(),
            ])->values(),
        ];
    }

    public function getUnpaidInvoiceByPatient(string $patientUuid, string $branchId)
    {
        $invoices = Invoice::where(function ($q) use ($patientUuid) {
            $q->whereHas(
                'invoiceServices.scheduleService.schedule.patient',
                fn($p) => $p->where('uuid', $patientUuid)
            )->orWhereHas(
                'invoiceFacility.patientAdmission.patient',
                fn($p) => $p->where('uuid', $patientUuid)
            );
        })
            ->where('branch_id', $branchId)
            ->whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_PARTIAL])
            ->orderBy('created_at')
            ->get();

        $totalRefunded = (float) $invoices->sum(fn($invoice) => $invoice->refunded_amount);

        return [
            'invoices'       => $invoices,
            'total_refunded' => $totalRefunded,
        ];
    }

    public function overview(array $payload): array
    {
        $branchId = $payload['branch_id'] ?? null;
        $month = $payload['month'] ?? now()->month;
        $year = $payload['year'] ?? now()->year;

        $currentDate = Carbon::create($year, $month, 1);

        $currentMonthStart = $currentDate->copy()->startOfMonth();
        $currentMonthEnd = $currentDate->copy()->endOfMonth();

        $lastMonthStart = $currentDate->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $currentDate->copy()->subMonth()->endOfMonth();

        $invoiceQuery = Invoice::query()
            ->where('branch_id', $branchId)
            ->where('status', '!=', Invoice::STATUS_VOID);

        $paymentQuery = Payment::query()
            ->whereHas('invoice', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                    ->where('status', '!=', Invoice::STATUS_VOID);
            });

        $totalRevenue = (clone $invoiceQuery)
            ->whereBetween('created_at', [
                $currentMonthStart,
                $currentMonthEnd
            ])
            ->sum('total');

        $paymentsReceived = (clone $paymentQuery)
            ->whereBetween('created_at', [
                $currentMonthStart,
                $currentMonthEnd
            ])
            ->sum('amount');

        $refundsIssued = (clone $paymentQuery)
            ->whereHas('refunds', function ($query) use ($currentMonthStart, $currentMonthEnd) {
                $query->where('status', Refund::STATUS_COMPLETED)
                    ->whereBetween('created_at', [
                        $currentMonthStart,
                        $currentMonthEnd
                    ]);
            })
            ->get()
            ->flatMap->refunds
            ->where('status', Refund::STATUS_COMPLETED)
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->sum('amount');

        $outstandingBalance = (clone $invoiceQuery)
            ->whereBetween('created_at', [
                $currentMonthStart,
                $currentMonthEnd
            ])
            ->get()
            ->sum->balance_due;

        $lastRevenue = (clone $invoiceQuery)
            ->whereBetween('created_at', [
                $lastMonthStart,
                $lastMonthEnd
            ])
            ->sum('total');

        $lastPayments = (clone $paymentQuery)
            ->whereBetween('created_at', [
                $lastMonthStart,
                $lastMonthEnd
            ])
            ->sum('amount');

        $lastRefunds = (clone $paymentQuery)
            ->whereHas('refunds', function ($query) use ($lastMonthStart, $lastMonthEnd) {
                $query->where('status', Refund::STATUS_COMPLETED)
                    ->whereBetween('created_at', [
                        $lastMonthStart,
                        $lastMonthEnd
                    ]);
            })
            ->get()
            ->flatMap->refunds
            ->where('status', Refund::STATUS_COMPLETED)
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('amount');

        $lastOutstanding = (clone $invoiceQuery)
            ->whereBetween('created_at', [
                $lastMonthStart,
                $lastMonthEnd
            ])
            ->get()
            ->sum->balance_due;

        $upcomingPayments = (clone $invoiceQuery)
            ->whereBetween('created_at', [
                $currentMonthStart,
                $currentMonthEnd
            ])
            ->get()
            ->filter(fn($invoice) => $invoice->balance_due > 0)
            ->sum('balance_due');

        $lastUpcoming = (clone $invoiceQuery)
            ->whereBetween('created_at', [
                $lastMonthStart,
                $lastMonthEnd
            ])
            ->get()
            ->filter(fn($invoice) => $invoice->balance_due > 0)
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
                'trend' => $totalRevenue >= $lastRevenue
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
                'trend' => $paymentsReceived >= $lastPayments
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
                'trend' => $refundsIssued <= $lastRefunds
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
                'trend' => $outstandingBalance <= $lastOutstanding
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
                'trend' => $upcomingPayments <= $lastUpcoming
                    ? 'up'
                    : 'down',
            ],
        ];
    }

    private function percentageChange(float $current, float $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function formatChange(float $change, string $suffix)
    {
        $sign = $change > 0 ? '+' : '';

        return "{$sign}{$change}% {$suffix}";
    }
}
