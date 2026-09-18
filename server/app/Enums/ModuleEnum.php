<?php

namespace App\Enums;

enum ModuleEnum: string
{
    case Bookings = 'Bookings';
    case Schedules = 'Schedules';
    case Admissions = 'Admissions';
    case Patients = 'Patients';
    case Contracts = 'Contracts';
    case RoomsAndBeds = 'Rooms & Beds';
    case Services = 'Services';
    case EmployeeManagement = 'Employee Management';
    case BillingAndInvoices = 'Billing & Invoices';
    case ManageBranches = 'Manage Branches';
    case BranchSettings = 'Branch Settings';

    public function actions(): array
    {
        return match ($this) {
            self::Bookings => [
                PermissionAction::Read,
                PermissionAction::Approve,
                PermissionAction::Reject,
            ],
            self::Patients => [
                PermissionAction::Read,
                PermissionAction::Create,
                PermissionAction::Update,
                PermissionAction::Export,
            ],
            self::Schedules => [
                PermissionAction::Read,
                PermissionAction::Assign,
                PermissionAction::Update,
            ],
            self::Admissions => [
                PermissionAction::Read,
                PermissionAction::Create,
                PermissionAction::Update,
                PermissionAction::Admit,
                PermissionAction::Discharge,
                PermissionAction::ForceDischarge,
            ],
            self::RoomsAndBeds, self::Contracts, self::EmployeeManagement => [
                PermissionAction::Read,
                PermissionAction::Create,
                PermissionAction::Update,
            ],
            self::Services => [
                PermissionAction::Read,
                PermissionAction::Create,
                PermissionAction::Update,
                PermissionAction::Assign,
            ],
            self::BillingAndInvoices => [
                PermissionAction::Read,
                PermissionAction::Create,
                PermissionAction::Update,
                PermissionAction::ApproveWithdrawal,
            ],
            self::ManageBranches => [
                PermissionAction::Read,
                PermissionAction::Create,
            ],
            self::BranchSettings => [
                PermissionAction::Read,
                PermissionAction::Update,
                PermissionAction::Renew,
            ],
        };
    }

    public function actionColumns(): array
    {
        return array_map(fn(PermissionAction $action) => $action->value, $this->actions());
    }
}
