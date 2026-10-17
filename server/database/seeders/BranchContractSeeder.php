<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchContract;
use Illuminate\Database\Seeder;

class BranchContractSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::orderBy('branch_id')->get();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. Seed branches first.');
            return;
        }

        $contracts = [
            [
                'category'           => BranchContract::CAREGORY_FACILITY,
                'accommodation_type' => BranchContract::ACCOMMODATION_TYPE_COMMON,
                'price'              => 1500.00,
                'billing_cycle'      => BranchContract::BILLING_CYCLE_MONTHLY,
                'description'        => 'Shared accommodation providing essential medical care and routine nursing monitoring. Features standard hospital beds separated by privacy curtains, shared restroom facilities, basic bedside storage, and standard medical connectivity. Ideal for cost-effective patient stays during routine recovery or observation.',
            ],
            [
                'category'           => BranchContract::CAREGORY_FACILITY,
                'accommodation_type' => BranchContract::ACCOMMODATION_TYPE_COMMON,
                'price'              => 16000.00,
                'billing_cycle'      => BranchContract::BILLING_CYCLE_YEARLY,
                'description'        => 'Shared accommodation providing essential medical care and routine nursing monitoring. Features standard hospital beds separated by privacy curtains, shared restroom facilities, basic bedside storage, and standard medical connectivity. Ideal for cost-effective patient stays during routine recovery or observation.',
            ],
            [
                'category'           => BranchContract::CAREGORY_FACILITY,
                'accommodation_type' => BranchContract::ACCOMMODATION_TYPE_VIP,
                'price'              => 3000.00,
                'billing_cycle'      => BranchContract::BILLING_CYCLE_MONTHLY,
                'description'        => 'This VIP accommodation provides an isolated, infection-controlled clinical environment tailored for patients requiring maximum privacy, comfort, and dedicated medical attention.',
            ],
            [
                'category'           => BranchContract::CAREGORY_FACILITY,
                'accommodation_type' => BranchContract::ACCOMMODATION_TYPE_VIP,
                'price'              => 35000.00,
                'billing_cycle'      => BranchContract::BILLING_CYCLE_YEARLY,
                'description'        => 'This VIP accommodation provides an isolated, infection-controlled clinical environment tailored for patients requiring maximum privacy, comfort, and dedicated medical attention.',
            ],
            [
                'category'           => BranchContract::CAREGORY_HOMECARE,
                'accommodation_type' => BranchContract::ACCOMMODATION_TYPE_ADL,
                'price'              => 350.00,
                'billing_cycle'      => BranchContract::BILLING_CYCLE_HOURLY,
                'description'        => 'This package provides dedicated, hourly support for essential personal care and fundamental physical needs. Services include hands-on assistance with dressing, bathing, personal hygiene, and grooming. Caregivers also support safe mobility, transfers (e.g., bed to chair), mealtime feeding assistance, and sensitive toileting or continence care to maintain client comfort and dignity.',
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
