<?php

namespace App\Models;

use App\Enums\ModuleEnum;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $primaryKey = 'module_id';

    public $timestamps = false;

    protected $fillable = [
        'module_name',
    ];

    public function permissions()
    {
        return $this->hasMany(EmployeePermission::class, 'module_id', 'module_id');
    }

    public function actionColumns(): array
    {
        return ModuleEnum::tryFrom($this->module_name)?->actionColumns() ?? [];
    }
}
