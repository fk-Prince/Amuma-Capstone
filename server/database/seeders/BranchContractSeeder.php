<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchContract;
use Database\Seeders\Concerns\PricesPerBranch;
use Illuminate\Database\Seeder;

class BranchContractSeeder extends Seeder
{
    use PricesPerBranch;

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

        foreach ($branches->values() as $position => $branch) {
            $facility = $branch->hasFacilitySubscription();
            $homecare = $branch->hasHomecareSubscription();

            foreach ($contracts as $contract) {
                $isFacility = $contract['category'] === BranchContract::CAREGORY_FACILITY;

                if ($isFacility ? !$facility : !$homecare) {
                    continue;
                }

                BranchContract::updateOrCreate(
                    [
                        'branch_id' => $branch->branch_id,
                        'category' => $contract['category'],
                        'accommodation_type' => $contract['accommodation_type'],
                        'billing_cycle' => $contract['billing_cycle'],
                    ],
                    [
                        'price' => $this->branchPrice($contract['price'], $position),
                        'description' => $contract['description'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
