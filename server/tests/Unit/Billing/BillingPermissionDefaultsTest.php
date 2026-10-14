<?php

namespace Tests\Unit\Billing;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Enums\RoleEnum;
use PHPUnit\Framework\TestCase;

class BillingPermissionDefaultsTest extends TestCase
{
    private function billing(RoleEnum $role): array
    {
        return $role->permissions()[ModuleEnum::BillingAndInvoices->value] ?? [];
    }

    public function test_the_cashier_gets_every_billing_action(): void
    {
        $this->assertEqualsCanonicalizing(
            [
                PermissionAction::Read->value,
                PermissionAction::Create->value,
                PermissionAction::Update->value,
                PermissionAction::Export->value,
            ],
            $this->billing(RoleEnum::Cashier)
        );
    }

    public function test_the_branch_manager_can_only_read_and_export_billing(): void
    {
        $this->assertEqualsCanonicalizing(
            [PermissionAction::Read->value, PermissionAction::Export->value],
            $this->billing(RoleEnum::BranchManager)
        );
    }

    public function test_the_agency_owner_gets_every_billing_action(): void
    {
        $granted = $this->billing(RoleEnum::AgencyOwner);

        foreach (ModuleEnum::BillingAndInvoices->actionColumns() as $action) {
            $this->assertContains($action, $granted);
        }
    }

    public function test_admission_staff_nurses_and_caregivers_get_no_billing_access(): void
    {
        $this->assertSame([], $this->billing(RoleEnum::Admission));
        $this->assertSame([], $this->billing(RoleEnum::Nurse));
        $this->assertSame([], $this->billing(RoleEnum::Caregiver));
    }
}
