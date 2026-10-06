<?php

namespace App\Repository;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\PatientAccess;
use App\Models\Schedule;
use App\Models\ScheduleService;
use App\Utils\PortalHelper;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PatientAccessRepository
{
    private const SECTIONS = [
        'all',
        'profile',
        'details',
        'financials',
        'summary',
        'schedule',
        'medication',
        'recent_medication',
        'activity',
        'admissions',
        'caregiver_shifts',
    ];

    private const RECENT_MEDICATION_LIMIT = 5;
    private const ACTIVITY_LIMIT = 200;

    public function __construct(
        private readonly PortalHelper $helper
    ) {}

    public function overview(array $payload)
    {
        $sections = $this->resolveSections($payload);

        if (empty($payload['patient_id']) && empty($payload['selected'])) {
            return $this->getAllPatients($payload, $sections);
        }

        $access = $this->resolveAccess($payload);

        if ($this->wants($sections, 'details')) {
            $access->load('client.user');
        }

        $patient = $access->patient()
            ->with($this->patientRelations($sections, $payload))
            ->firstOrFail();

        $this->loadScheduleDetails(collect([$patient]), $sections);
        $this->loadInvoices(collect([$patient]), $sections);

        return [
            'success' => true,
            'data' => $this->helper->patientPayload($access, $patient, $sections),
        ];
    }

    private function getAllPatients(array $payload, array $sections)
    {
        $patients = $this->accessibleQuery($payload)
            ->with([
                ...($this->wants($sections, 'details') ? ['client.user'] : []),
                'patient' => fn($query) =>
                $query->with($this->patientRelations($sections, $payload)),
            ])
            ->get();

        $this->loadScheduleDetails($patients->pluck('patient'), $sections);
        $this->loadInvoices($patients->pluck('patient'), $sections);

        $data = $patients->map(
            fn($access) =>
            $this->helper->patientPayload(
                $access,
                $access->patient,
                $sections
            )
        );

        return [
            'total' => $data->count(),
            'data' => $data->values(),
        ];
    }


    public function scheduleList(array $payload)
    {
        $access = $this->resolveAccess($payload);
        $patient = $access->patient()->with('location')->firstOrFail();

        $type = $payload['type'] ?? 'adl';

        $schedules = Schedule::query()
            ->where('patient_id', $patient->patient_id)
            ->when(
                $type === 'adl',
                fn($query) => $query->whereHas(
                    'scheduleServices',
                    fn($serviceQuery) =>
                    $serviceQuery->where('type', ScheduleService::TYPE_ADL)
                )
            )
            ->when(
                $type === 'medical',
                fn($query) => $query->whereHas(
                    'scheduleServices',
                    fn($serviceQuery) =>
                    $serviceQuery
                        ->whereNotNull('service_id')
                        ->where(
                            fn($q) => $q
                                ->whereNull('type')
                                ->orWhere('type', '!=', ScheduleService::TYPE_ADL)
                        )
                )
            )
            ->when(
                !empty($payload['month']) && $payload['month'] !== 'all',
                fn($query) => $query->whereRaw(
                    "to_char(scheduled_at, 'YYYY-MM') = ?",
                    [$payload['month']]
                )
            )
            ->when(
                !empty($payload['status']) && $payload['status'] !== 'all',
                fn($query) => $query->whereIn(
                    'status',
                    match ($payload['status']) {
                        'upcoming' => [Schedule::STATUS_PENDING, 'confirmed'],
                        'missed' => [Schedule::STATUS_MISSED, Schedule::STATUS_CANCELLED],
                        default => [$payload['status']],
                    }
                )
            )
            ->with(PortalHelper::scheduleDetailRelations())
            ->orderByDesc('scheduled_at')
            ->paginate((int) ($payload['per_page'] ?? 10));

        return [
            'success' => true,
            'patient_id' => $patient->patient_id,
            'data' => collect($schedules->items())
                ->map(fn($schedule) => $this->helper->schedulePayload($schedule, $patient))
                ->values(),
            'meta' => [
                'current_page' => $schedules->currentPage(),
                'last_page' => $schedules->lastPage(),
                'total' => $schedules->total(),
                'per_page' => $schedules->perPage(),
            ],
        ];
    }


    public function bookings(array $payload)
    {
        if (empty($payload['user_id'])) {
            return [
                'success' => true,
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                    'per_page' => (int) ($payload['per_page'] ?? 10),
                ],
            ];
        }

        $bookings = Booking::where('user_id', $payload['user_id'])
            ->with('branch', 'reviewer')
            ->orderByDesc('booking_id')
            ->paginate((int) ($payload['per_page'] ?? 10));

        return [
            'success' => true,
            'data' => collect($bookings->items())
                ->map(fn($booking) => $this->helper->bookingPayload($booking))
                ->values(),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'total' => $bookings->total(),
                'per_page' => $bookings->perPage(),
            ],
        ];
    }

    public function verifyAccess(array $payload)
    {
        return $this->findAccess($payload);
    }

    private const INVOICE_RELATIONS = [
        'allocations.payment.transaction',
        'allocations.refundAllocations.refund.transaction',
        'allocations.refundAllocations.invoiceAdjustment',
        'invoiceAdjustments',
        'invoiceServices',
        'invoiceAdmissionLines',
        'additionalCharges',
    ];

    public function invoicePage(array $payload)
    {
        $access = $this->findAccess($payload);
        $perPage = min(20, max(1, (int) ($payload['per_page'] ?? 5)));

        $invoices = Invoice::whereIn('invoice_id', Invoice::patientInvoiceIds($access->patient_id))
            ->with(self::INVOICE_RELATIONS)
            ->orderByDesc('invoice_id')
            ->paginate($perPage);

        return response()->json([
            'data' => $invoices->getCollection()
                ->map(fn(Invoice $invoice) => $this->helper->invoiceRow($invoice))
                ->values(),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    public function invoiceDetail(array $payload)
    {
        $access = $this->findAccess($payload);

        $invoice = Invoice::whereIn('invoice_id', Invoice::patientInvoiceIds($access->patient_id))
            ->where('invoice_code', $payload['invoice_code'] ?? '')
            ->with([
                ...self::INVOICE_RELATIONS,
                'invoiceAdmissionLines.admissionPeriod',
                'invoiceServices.scheduleService',
            ])
            ->firstOrFail();

        return response()->json([
            'data' => $this->helper->invoiceDetail($invoice),
        ]);
    }

    private function findAccess(array $payload)
    {
        $access = PatientAccess::query()
            ->where('patient_id', $payload['patient_id'])
            ->where('client_id', $payload['client_id'])
            ->firstOrFail();

        if (!$access->have_access) {
            throw new ModelNotFoundException(
                'Access denied to this patient'
            );
        }
        return $access;
    }

    private function resolveAccess(array $payload)
    {
        if (!empty($payload['patient_id'])) {
            return $this->findAccess($payload);
        }

        $uuid = trim((string) ($payload['patient_uuid'] ?? ''));

        $access = $uuid !== ''
            ? $this->accessibleQuery($payload)
            ->whereHas('patient', fn($query) => $query->where('uuid', $uuid))
            ->first()
            : null;

        return $access ?? $this->accessibleQuery($payload)->firstOrFail();
    }

    private function accessibleQuery(array $payload)
    {
        return PatientAccess::query()
            ->where('client_id', $payload['client_id'])
            ->where('have_access', true)
            ->whereHas('patient')
            ->orderBy('patient_access_id');
    }


    private function resolveSections(array $payload): array
    {
        $requested = $payload['section'] ?? 'all';

        $sections = array_filter(array_map(
            'trim',
            is_array($requested) ? $requested : explode(',', (string) $requested)
        ));

        $sections = array_values(array_intersect(self::SECTIONS, $sections));

        return $sections ?: ['all'];
    }

    private function wants(array $sections, string $section): bool
    {
        return in_array('all', $sections, true) || in_array($section, $sections, true);
    }

    private function patientRelations(array $sections, array $payload): array
    {
        $relations = [];

        if ($this->wants($sections, 'profile')) {
            $relations = array_merge($relations, [
                'branch.location',
                'currentAdmission.bed.room',
                'latestAdmission.bed.room',
            ]);
        }

        if ($this->wants($sections, 'profile') || $this->wants($sections, 'schedule')) {
            $relations['schedules'] = fn($query) => $query
                ->orderByRaw(
                    'CASE WHEN status = ? THEN 0 WHEN status = ? THEN 1 ELSE 2 END',
                    [Schedule::STATUS_ONGOING, Schedule::STATUS_PENDING]
                )
                ->orderByDesc('scheduled_at')
                ->with([
                    'scheduleServices' => fn($serviceQuery) =>
                    $serviceQuery->select('schedule_services_id', 'schedule_id', 'type', 'service_id'),
                ]);
        }

        if ($this->wants($sections, 'details') || $this->wants($sections, 'schedule')) {
            $relations[] = 'location';
        }

        if ($this->wants($sections, 'details')) {
            $relations['assessments'] = fn($query) =>
            $query->orderByDesc('created_at')
                ->orderByDesc('patient_assessment_id');

            $relations['diagnoses'] = fn($query) =>
            $query->orderByDesc('diagnosis_date')
                ->orderByDesc('patient_diagnosis_id');
        }

        if ($this->wants($sections, 'medication')) {
            $relations = array_merge($relations, [
                'medications.schedules',
                'medications.recordedBy',
                'vitals',
                'vitals.recordedBy',
            ]);
        } elseif (in_array('recent_medication', $sections, true)) {
            $relations['medications'] = fn($query) =>
            $query->orderByDesc('recorded_at')
                ->limit(self::RECENT_MEDICATION_LIMIT)
                ->with(['schedules', 'recordedBy']);

            $relations['vitals'] = fn($query) =>
            $query->orderByDesc('recorded_date')
                ->orderByDesc('recorded_time')
                ->limit(self::RECENT_MEDICATION_LIMIT)
                ->with('recordedBy');
        }

        if ($this->wants($sections, 'activity')) {
            $limit = min(
                max((int) ($payload['activity_limit'] ?? self::ACTIVITY_LIMIT), 1),
                self::ACTIVITY_LIMIT
            );

            $relations['activities'] = fn($query) =>
            $query->orderByDesc('occurred_at')->limit($limit)->with('recordedBy');
        }

        if ($this->wants($sections, 'admissions')) {
            $relations = array_merge($relations, [
                'currentAdmission.currentPeriod.branchContract',
                'currentAdmission.latestPeriod.branchContract',
                'currentAdmission.invoiceAdmission.admissionPeriod.branchContract',
            ]);
        }

        if ($this->wants($sections, 'caregiver_shifts')) {
            $relations['currentAdmission.caregiverShifts'] = fn($query) =>
            $query->where('is_active', true)
                ->orderBy('start_time')
                ->with('caregiver');
        }

        return $relations;
    }

    private function loadInvoices(Collection $patients, array $sections): void
    {
        $full = $this->wants($sections, 'financials');

        if (!$full && !in_array('summary', $sections, true)) {
            return;
        }

        $patientIds = $patients->pluck('patient_id')->all();

        $pairs = DB::table('invoice_admission')
            ->join('admission_periods', 'admission_periods.admission_period_id', '=', 'invoice_admission.admission_period_id')
            ->join('patient_admissions', 'patient_admissions.patient_admission_id', '=', 'admission_periods.patient_admission_id')
            ->whereIn('patient_admissions.patient_id', $patientIds)
            ->select('invoice_admission.invoice_id', 'patient_admissions.patient_id')
            ->union(
                DB::table('invoice_services')
                    ->join('schedule_services', 'schedule_services.schedule_services_id', '=', 'invoice_services.schedule_services_id')
                    ->join('schedules', 'schedules.schedule_id', '=', 'schedule_services.schedule_id')
                    ->whereIn('schedules.patient_id', $patientIds)
                    ->select('invoice_services.invoice_id', 'schedules.patient_id')
            )
            ->union(
                DB::table('additional_charges')
                    ->join('patient_admissions', 'patient_admissions.patient_admission_id', '=', 'additional_charges.patient_admission_id')
                    ->whereIn('patient_admissions.patient_id', $patientIds)
                    ->select('additional_charges.invoice_id', 'patient_admissions.patient_id')
            )
            ->get();

        $invoices = $pairs->isEmpty()
            ? new EloquentCollection()
            : Invoice::whereIn('invoice_id', $pairs->pluck('invoice_id')->unique()->values())
            ->with([
                'allocations.payment.transaction',
                'allocations.refundAllocations.refund.transaction',
                'allocations.refundAllocations.invoiceAdjustment',
                'invoiceAdjustments',
                ...($full ? ['invoiceServices', 'invoiceAdmissionLines', 'additionalCharges'] : []),
            ])
            ->orderByDesc('invoice_id')
            ->get();

        $idsByPatient = $pairs->groupBy('patient_id')
            ->map(fn($rows) => $rows->pluck('invoice_id')->all());

        foreach ($patients as $patient) {
            $ids = $idsByPatient->get($patient->patient_id, []);

            $patient->setRelation(
                'invoices',
                $invoices->filter(fn($invoice) => in_array($invoice->invoice_id, $ids))->values()
            );
        }
    }

    private function loadScheduleDetails(Collection $patients, array $sections): void
    {
        if (!$this->wants($sections, 'schedule')) {
            return;
        }

        $picked = $patients
            ->flatMap(fn($patient) => array_values(array_filter(
                $this->helper->pickSchedules($patient->schedules)
            )))
            ->unique('schedule_id')
            ->values();

        (new EloquentCollection($picked->all()))
            ->load(PortalHelper::scheduleDetailRelations());
    }
}
