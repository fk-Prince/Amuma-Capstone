<?php

namespace Database\Seeders;

use App\Enums\ModuleEnum;
use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        Module::where('module_name', 'Manage Branches')
            ->update(['module_name' => ModuleEnum::ManageSubscription->value]);

        foreach (ModuleEnum::cases() as $module) {
            Module::updateOrCreate(['module_name' => $module->value]);
        }
    }
}
