<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlatformAdminSeeder::class,
            ModuleSeeder::class,
            PlanSeeder::class,
            BranchSubscriptionSeeder::class,
            RoomSeeder::class,
            BedSeeder::class,
            BranchContractSeeder::class,
            ServiceSeeder::class,
            EmployeeSeeder::class,
            NurseSeeder::class,
            ClientSeeder::class,
            // PatientSeeder::class,
        ]);
    }
}
