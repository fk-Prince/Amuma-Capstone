<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchContract;
use Illuminate\Database\Seeder;

class BranchContractSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. Seed branches first.');
            return;
        }

        $contracts = [
            [
                'category'           => BranchContract::CAREGORY_FACILITY,
                'accommodation_type' => BranchContract::ACCOMMODATION_TYPE_COMMON,
                'price'              => 10000.00,
                'billing_cycle'      => BranchContract::BILLING_CYCLE_MONTHLY,
                'description'        => 'Standard shared room accommodation',
            ],
            [
                'category'           => BranchContract::CAREGORY_FACILITY,
                'accommodation_type' => BranchContract::ACCOMMODATION_TYPE_COMMON,
                'price'              => 80000.00,
                'billing_cycle'      => BranchContract::BILLING_CYCLE_YEARLY,
                'description'        => 'Standard shared room accommodation',
            ],
            [
                'category'           => BranchContract::CAREGORY_FACILITY,
                'accommodation_type' => BranchContract::ACCOMMODATION_TYPE_VIP,
                'price'              => 15000.00,
                'billing_cycle'      => BranchContract::BILLING_CYCLE_MONTHLY,
                'description'        => 'Private VIP room accommodation',
            ],
            [
                'category'           => BranchContract::CAREGORY_FACILITY,
                'accommodation_type' => BranchContract::ACCOMMODATION_TYPE_VIP,
                'price'              => 130000.00,
                'billing_cycle'      => BranchContract::BILLING_CYCLE_YEARLY,
                'description'        => 'Private VIP room accommodation (annual plan)',
            ],
            [
                'category'           => BranchContract::CAREGORY_HOMECARE,
                'accommodation_type' => BranchContract::ACCOMMODATION_TYPE_ADL,
                'price'              => 350.00,
                'billing_cycle'      => BranchContract::BILLING_CYCLE_HOURLY,
                'description'        => 'Homecare ADL support (hourly)',
            ],
        ];

        foreach ($branches as $branch) {
            foreach ($contracts as $contract) {
                BranchContract::create(array_merge($contract, [
                    'branch_id' => $branch->branch_id,
                    'is_active' => true,
                ]));
            }
        }
    }
}
