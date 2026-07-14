<?php

namespace App\Repository;

use App\Models\Module;

class ModuleRepository
{
    public function getAllModules()
    {
        return Module::all();
    }
}
