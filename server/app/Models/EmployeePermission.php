<?php

namespace App\Models;

use App\Enums\PermissionAction;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;

class EmployeePermission extends Model
{
    protected $primaryKey = 'employee_permission_id';
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'branch_id',
        'module_id',
        'can_read',
        'can_create',
        'can_update',
        'can_export',
        'can_approve',
        'can_reject',
        'can_assign',
        'can_admit',
        'can_discharge',
        'can_force_discharge',
        'can_approve_withdrawal',
        'can_renew',
    ];

    protected $casts = [
        'can_read' => 'boolean',
        'can_create' => 'boolean',
        'can_update' => 'boolean',
        'can_export' => 'boolean',
        'can_approve' => 'boolean',
        'can_reject' => 'boolean',
        'can_assign' => 'boolean',
        'can_admit' => 'boolean',
        'can_discharge' => 'boolean',
        'can_force_discharge' => 'boolean',
        'can_approve_withdrawal' => 'boolean',
        'can_renew' => 'boolean',
    ];

    public static function grantColumns(array $actions): array
    {
        return collect(PermissionAction::columns())
            ->mapWithKeys(fn(string $column) => [
                $column => in_array($column, $actions, true),
            ])
            ->all();
    }

    public function grantedActions(): array
    {
        return array_values(array_filter(
            PermissionAction::columns(),
            fn(string $column) => (bool) $this->{$column}
        ));
    }

    public function modules()
    {
        return $this->belongsTo(Module::class, 'module_id', 'module_id');
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_id', 'branch_id');
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_id', 'employee_id');
    }
}
