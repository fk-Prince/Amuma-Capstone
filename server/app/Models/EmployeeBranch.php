<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeBranch extends Model
{
    protected $primaryKey = 'employee_branch_id';
    public $timestamps = false;

    protected $fillable = [
        'branch_id',
        'employee_id',
        'role_name',
        'assignment_type'
    ];

    public function branches()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function employees()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function employeeServices()
    {
        return $this->hasMany(EmployeeService::class, 'employee_branch_id', 'employee_branch_id');
    }


    public function scheduleAssignments()
    {
        return $this->hasMany(
            ScheduleAssigned::class,
            'employee_id',
            'employee_id'
        );
    }
}
