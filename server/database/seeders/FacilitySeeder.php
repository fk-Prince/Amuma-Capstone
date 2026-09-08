<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{

    public function run(): void
    {
        $this->call([
            BedSeeder::class,
            BranchContractSeeder::class,
            ServiceSeeder::class,
            EmployeeSeeder::class,
        ]);
    }
}
