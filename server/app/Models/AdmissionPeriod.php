<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;


class AdmissionPeriod extends Model
{
    protected $table = 'admission_periods';

    protected $primaryKey = 'admission_period_id';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_CANCELLED = 'cancelled';

    public const CLOSED_STATUSES = [
        self::STATUS_INACTIVE,
        self::STATUS_CANCELLED,
    ];

    public const REASON_ADMITTED = 'admitted';
    public const REASON_EXTENDED = 'extended';
    public const REASON_ACCOMMODATION_CHANGE = 'accommodation_change';

    protected $fillable = [
        'patient_admission_id',
        'branch_contract_id',
        'start_date',
        'end_date',
        'status',
        'reason',
        'note',
        'parent_admission_period_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function patientAdmission()
    {
        return $this->belongsTo(PatientAdmission::class,   'patient_admission_id',   'patient_admission_id');
    }

    public static function codeFor(mixed $periodId): ?string
    {
        return $periodId
            ? str_pad((string) $periodId, 2, '0', STR_PAD_LEFT)
            : null;
    }

    public function branchContract()
    {
        return $this->belongsTo(BranchContract::class, 'branch_contract_id', 'branch_contract_id');
    }

    public function parentPeriod()
    {
        return $this->belongsTo(self::class, 'parent_admission_period_id', 'admission_period_id');
    }

    public function childPeriods()
    {
        return $this->hasMany(self::class, 'parent_admission_period_id', 'admission_period_id');
    }

    public function invoiceAdmissionLines()
    {
        return $this->hasMany(InvoiceAdmission::class, 'admission_period_id',  'admission_period_id');
    }

    public function totalDays()
    {
        return max(1, (int) Carbon::parse($this->start_date)
            ->startOfDay()
            ->diffInDays(Carbon::parse($this->end_date)->startOfDay()));
    }


    public function consumedDays(?Carbon $asOf = null): int
    {
        $start = Carbon::parse($this->start_date)->startOfDay();
        $today = ($asOf ?? Carbon::now())->copy()->startOfDay();

        if ($today->lessThan($start)) {
            return 0;
        }

        return (int) min($this->totalDays(), $start->diffInDays($today) + 1);
    }

    public function remainingDays(?Carbon $asOf = null): int
    {
        return $this->totalDays() - $this->consumedDays($asOf);
    }

    public function chargedAmount(): float
    {
        return round((float) $this->invoiceAdmissionLines()->sum('price'), 2);
    }

    public function dailyRate(): float
    {
        return $this->chargedAmount() / $this->totalDays();
    }
}
