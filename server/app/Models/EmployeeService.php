<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeService extends Model
{

    protected $table = 'employee_assigned_service';
    protected $primaryKey = 'employee_assigned_service_id';
    public $timestamps = false;
    protected $fillable = [
        'service_id',
        'employee_branch_id',
        'is_active',
    ];

    public function services()
    {
        return $this->belongsTo(Service::class, 'service_id', 'service_id');
    }

    public function employeeBranch()
    {
        return $this->belongsTo(EmployeeBranch::class, 'employee_branch_id', 'employee_branch_id');
    }
}
