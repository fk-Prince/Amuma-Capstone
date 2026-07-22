<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $primaryKey = 'module_id';

    public $timestamps = false;
    protected $fillable = [
        'module_name',
        'has_read',
        'has_create',
        'has_approve',
        'has_update',
    ];


    public function permissions()
    {
        return $this->hasMany(EmployeePermission::class, 'module_id', 'module_id');
    }
}
