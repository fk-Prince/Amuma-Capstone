<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CoreSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlatformAdminSeeder::class,
            ModuleSeeder::class,
            PlanSeeder::class,
            ClientSeeder::class,
        ]);
    }
}
