<?php

namespace Database\Seeders;

use App\Enums\ModuleEnum;
use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ModuleEnum::cases() as $module) {
            Module::updateOrCreate(['module_name' => $module->value]);
        }
    }
}
