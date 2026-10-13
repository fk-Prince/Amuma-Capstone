<?php

namespace App\Http\Resources;

use App\Models\Booking;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogDetailResource extends AuditLogResource
{
    private const FOREIGN_CODES = [
        'patient_id' => [Patient::class, 'patient_code', 'patient_code'],
        'employee_id' => [Employee::class, 'employee_code', 'employee_code'],
        'invoice_id' => [Invoice::class, 'invoice_code', 'invoice_code'],
        'schedule_id' => [Schedule::class, 'schedule_code', 'schedule_code'],
        'transaction_id' => [Transaction::class, 'transaction_code', 'transaction_code'],
        'booking_id' => [Booking::class, 'reference_id', 'booking_reference'],
    ];

    private const RECORD_CODES = [
        'reference_id',
        'patient_code',
        'employee_code',
        'invoice_code',
        'schedule_code',
        'transaction_code',
    ];

    public function toArray(Request $request): array
    {
        $type = $this->auditable_type;
        $isModel = $type && is_subclass_of($type, Model::class);
        $primaryKey = $isModel ? (new $type)->getKeyName() : null;
        $subject = $isModel && $this->auditable_id ? $type::query()->find($this->auditable_id) : null;

        return parent::toArray($request) + [
            'record_code' => $this->recordCode($subject),
            'old_values' => $this->present($this->old_values, $primaryKey),
            'new_values' => $this->present($this->new_values, $primaryKey),
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
        ];
    }

    private function recordCode(?Model $subject): ?string
    {
        foreach (self::RECORD_CODES as $column) {
            $code = $subject?->getAttribute($column);

            if (filled($code)) {
                return (string) $code;
            }
        }

        return null;
    }

    private function present(?array $values, ?string $primaryKey): ?array
    {
        if ($values === null) {
            return null;
        }

        $shown = [];

        foreach ($values as $key => $value) {
            if ($key === $primaryKey) {
                continue;
            }

            if (isset(self::FOREIGN_CODES[$key]) && $this->isRawId($value)) {
                [$model, $column, $label] = self::FOREIGN_CODES[$key];
                $code = $model::query()->whereKey($value)->value($column);

                if (filled($code)) {
                    $shown[$label] = $code;
                }

                continue;
            }

            if (str_ends_with($key, '_id') && ($value === null || $this->isRawId($value))) {
                continue;
            }

            $shown[$key] = $value;
        }

        return $shown;
    }

    private function isRawId(mixed $value): bool
    {
        return is_int($value) || (is_string($value) && ctype_digit($value));
    }
}
