<?php

namespace App\Repository;

use App\Http\Resources\PatientInvoiceSummaryResource;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\PatientBooking;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
            // 'booking' => $this->getBookingInvoice($payload),
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
            'payments',
        ])->where('invoice_code', $payload['invoice_code'])
            ->where('branch_id', $payload['branch_id'])->first();
    }


    // GET TABLE DISPLAY INVOICE
    public function getBookingInvoice(array $payload)
    {
        $perPage = $payload['per_page'] ?? 10;

        $bookings = Booking::with([
            'invoices',
        ])
            ->when(
                !empty($payload['branch_uuid']),
                function ($query) use ($payload) {
                    $query->whereHas('branch', function ($q) use ($payload) {
                        $q->where('uuid', $payload['branch_uuid']);
                    });
                }
            )
            ->when(
                !empty($payload['search']) &&
                    ($payload['search_type'] ?? null) === 'booking',
                function ($query) use ($payload) {
                    $query->where(
                        'reference_id',
                        'ilike',
                        '%' . $payload['search'] . '%'
                    );
                }
            )
            ->orderByDesc('created_at')
            ->paginate($perPage);


        $bookings->getCollection()->transform(function ($booking) {

            $invoice = $booking->invoices->first();

            $bookingData = $booking->booking_data ?? [];

            $bookingTotal = data_get(
                $bookingData,
                'payment.total_amount',
                0
            );

            $bookingPaid = data_get(
                $bookingData,
                'payment.paid',
                false
            );

            return [
                'reference_id' => $booking->reference_id,

                'invoice_code' => $invoice?->invoice_code,

                'status' => $invoice?->status
                    ?? ($bookingPaid ? 'paid' : 'pending'),

                'patient' => trim(
                    data_get($bookingData, 'patient.first_name', '') .
                        ' ' .
                        data_get($bookingData, 'patient.last_name', '')
                ),

                'category' => $booking->category,

                'booking_status' => $booking->status,

                'total' => $invoice
                    ? $invoice->total
                    : $bookingTotal,

                'amount_paid' => $invoice
                    ? $invoice->amount_paid
                    : ($bookingPaid ? $bookingTotal : 0),

                'balance_due' => $invoice
                    ? $invoice->balance_due
                    : ($bookingPaid ? 0 : $bookingTotal),

                'created_at' => $booking->created_at,
            ];
        });

        return $bookings;
    }

    // GET SPECIFIC BOOKING REF ID
    public function getBookingDetail(array $payload)
    {
        $branchId = $payload['branch_id'] ?? null;

        $query = Booking::where('reference_id', $payload['reference_id']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $booking = $query->first();

        if (!$booking) {
            return null;
        }

        // booking_data is already cast to array in Booking model
        $data = $booking->booking_data ?? [];

        $service = $data['service'] ?? [];
        $patient = $data['patient'] ?? [];
        $payment = $data['payment'] ?? [];
        $reserved = $data['reserved'] ?? null;

        // Correct boolean handling
        $isPaid = filter_var(
            $payment['paid'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $status = strtolower($booking->status ?? '');
        $isPending = $status === 'pending';

        $total = (float) ($payment['total_amount'] ?? 0);

        // A pending booking hasn't been approved yet, so there is nothing
        // to collect on it until it's approved — balance due shows as 0
        // and the payment form stays hidden regardless of the raw total.
        $amountPaid = $isPaid ? $total : 0;
        $balanceDue = ($isPaid || $isPending) ? 0 : $total;

        return [
            'reference_id' => $booking->reference_id,
            'category' => $booking->category,
            'status' => $booking->status,
            'valid_until' => $booking->valid_until,

            'total' => $total,
            'amount_paid' => $amountPaid,
            'balance_due' => $balanceDue,

            'created_at' => $booking->created_at,

            'service' => [
                'type' => $service['type'] ?? null,
                'date' => $service['date'] ?? null,
                'preferred_time' => $service['prefered_time'] ?? null,
                'address' => $service['address'] ?? null,
                'time_span' => $service['time_span'] ?? null,
                'plan' => $service['plan'] ?? null,
                'billing_cycle' => $service['billing_cycle'] ?? null,
                'admission_date' => $service['admission_date'] ?? null,

                'services' => collect($service['services'] ?? [])
                    ->map(fn($s) => [
                        'service_id' => $s['service_id'] ?? null,
                        'service_name' => $s['service_name'] ?? null,
                        'price' => (float) ($s['price'] ?? 0),
                    ])
                    ->values(),
            ],

            'patient' => [
                'full_name' => trim(
                    ($patient['first_name'] ?? '') . ' ' .
                        ($patient['middle_name'] ?? '') . ' ' .
                        ($patient['last_name'] ?? '')
                ) ?: null,

                'first_name' => $patient['first_name'] ?? null,
                'middle_name' => $patient['middle_name'] ?? null,
                'last_name' => $patient['last_name'] ?? null,
                'gender' => $patient['gender'] ?? null,
                'citizenship' => $patient['citizenship'] ?? null,
                'date_of_birth' => $patient['date_of_birth'] ?? null,
                'phone_number' => $patient['phone_number'] ?? null,
                'blood_type' => $patient['blood_type'] ?? null,
            ],

            'reserved' => $reserved ? [
                'room' => [
                    'room_id' => data_get($reserved, 'room.room_id'),
                    'room_no' => data_get($reserved, 'room.room_no'),
                    'room_type' => data_get($reserved, 'room.room_type'),
                    'floor' => data_get($reserved, 'room.floor'),
                ],

                'bed' => [
                    'bed_id' => data_get($reserved, 'bed.bed_id'),
                    'bed_no' => data_get($reserved, 'bed.bed_no'),
                    'status' => data_get($reserved, 'bed.status'),
                ],

                'billing_cycle' => $reserved['billing_cycle'] ?? null,
                'price' => (float) ($reserved['price'] ?? 0),
                'accommodation_type' => $reserved['accommodation_type'] ?? null,
            ] : null,

            'payment' => [
                'paid' => $isPaid,
                'total_amount' => $total,
            ],
        ];
    }


    // SEARCH INVOICE CODCE
    public function getInvoiceSearch(array $payload)
    {
        $perPage = $payload['per_page'] ?? 10;
        $branchId = $payload['branch_id'];
        $search = $payload['search'];



        $query = Invoice::where('branch_id', $branchId)
            ->with([
                'payments',
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

    // GET SPECIIFCI PATIENT INVOICE
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
        });

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $query->with([
            'branch',
            'payments',
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

    // GET ALL PATIENT INVOCIE P1
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
        });

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $query->with([
            'branch',
            'payments',
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
    // GET ALL PATIENT INVOCIE P2
    private function patientInvoice(mixed $patientInvoices)
    {
        $patientModel = $patientInvoices
            ->map(fn($inv) => $inv->invoiceServices->first()?->scheduleService?->schedule?->patient
                ?? $inv->invoiceFacility->first()?->patientAdmission?->patient)
            ->filter()
            ->first();

        $overallTotal   = (float) $patientInvoices->sum('total');
        $overallPaid    = (float) $patientInvoices->sum(fn($inv) => $inv->payments->sum('amount'));
        $overallBalance = max($overallTotal - $overallPaid, 0);

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

            'total_amount'  => $overallTotal,
            'total_paid'    => $overallPaid,
            'total_balance' => $overallBalance,

            'status' => match (true) {
                $overallBalance <= 0 => 'Paid',
                $overallPaid > 0     => 'Partial',
                default               => 'Pending',
            },

            'invoice_count'  => $patientInvoices->count(),
            'latest_invoice' => $latestInvoice,
            'invoices'       => $formattedInvoices,
        ];
    }

    // FORMAT TABLE DISPLAY
    private function formatInvoice(object $invoice)
    {
        $patient =
            $invoice->invoiceServices
            ->first()?->scheduleService?->schedule?->patient
            ??
            $invoice->invoiceFacility
            ->first()?->patientAdmission?->patient;

        $total = (float) $invoice->total;
        $paid = (float) $invoice->payments->sum('amount');
        $balance = max($total - $paid, 0);

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
                $balance <= 0 => 'Paid',
                $paid > 0 => 'Partial',
                default => 'Pending',
            },
            'total' => $total,
            'amount' => $balance,
            'created_at' => $invoice->created_at,
        ];
    }

    // FORMAT CARD DISPLAY
    private function formatInvoiceDetail(object $invoice): array
    {
        $total = (float) $invoice->total;
        $paid = (float) $invoice->payments->sum('amount');
        $balance = max($total - $paid, 0);

        return [
            'invoice_id'   => $invoice->invoice_id,
            'invoice_code' => $invoice->invoice_code,
            'total'        => $total,
            'amount_paid'  => $paid,
            'balance_due'  => $balance,
            'is_collected' => $balance <= 0,
            'status' => match (true) {
                $balance <= 0 => 'Paid',
                $paid > 0     => 'Partial',
                default        => 'Pending',
            },
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
            ])->values(),
        ];
    }

    public function getUnpaidInvoiceByPatient(string $patientUuid, string $branchId)
    {
        return  Invoice::where(function ($q) use ($patientUuid) {
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
            ->where('branch_id', $branchId);


        $paymentQuery = Payment::query()
            ->whereHas('invoice', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            });



        /*
    |--------------------------------------------------------------------------
    | Current Month Data
    |--------------------------------------------------------------------------
    */

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


        $outstandingBalance = (clone $invoiceQuery)
            ->whereBetween('created_at', [
                $currentMonthStart,
                $currentMonthEnd
            ])
            ->get()
            ->sum->balance_due;



        /*
    |--------------------------------------------------------------------------
    | Last Month Data For Percentage
    |--------------------------------------------------------------------------
    */

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


        $lastOutstanding = (clone $invoiceQuery)
            ->whereBetween('created_at', [
                $lastMonthStart,
                $lastMonthEnd
            ])
            ->get()
            ->sum->balance_due;



        /*
    |--------------------------------------------------------------------------
    | No due_date available
    |--------------------------------------------------------------------------
    |
    | Overdue and upcoming payments cannot use dates.
    | Using unpaid balances instead.
    |
    */


        // $overdueInvoices = (clone $invoiceQuery)
        //     ->whereNotNull('due_date')
        //     ->whereDate('due_date', '<', now())
        //     ->get()
        //     ->filter(fn($invoice) => $invoice->balance_due > 0)
        //     ->count();


        // $lastOverdue = (clone $invoiceQuery)
        //     ->whereNotNull('due_date')
        //     ->whereDate('due_date', '<', $lastMonthEnd)
        //     ->whereBetween('created_at', [
        //         $lastMonthStart,
        //         $lastMonthEnd
        //     ])
        //     ->get()
        //     ->filter(fn($invoice) => $invoice->balance_due > 0)
        //     ->count();



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


            // 'overdue_invoices' => [
            //     'value' => $overdueInvoices,
            //     'secondary' => $this->formatChange(
            //         $this->percentageChange(
            //             $overdueInvoices,
            //             $lastOverdue
            //         ),
            //         'vs last month'
            //     ),
            //     'trend' => $overdueInvoices <= $lastOverdue
            //         ? 'up'
            //         : 'warning',
            // ],


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
