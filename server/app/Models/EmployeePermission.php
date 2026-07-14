<?php

namespace App\Models;

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
        'can_update',
        'can_create'
    ];

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
