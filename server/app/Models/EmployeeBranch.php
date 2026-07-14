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
        return $this->belongsToMany(Branch::class, 'branch_id', 'branch_id');
    }
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_id', 'employee_id');
    }
}
