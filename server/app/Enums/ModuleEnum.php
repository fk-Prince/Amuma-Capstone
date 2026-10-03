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
    case ManageSubscription = 'Manage Subscription';
    case BranchSettings = 'Branch Settings';

    public function actions(): array
    {
        return match ($this) {
            self::Bookings => [
                PermissionAction::Read,
                PermissionAction::Update,
            ],
            self::Patients => [
                PermissionAction::Read,
                PermissionAction::Create,
                PermissionAction::Update,
                PermissionAction::Export,
            ],
            self::Schedules => [
                PermissionAction::Read,
                PermissionAction::Update,
                PermissionAction::Assign,
            ],
            self::Admissions => [
                PermissionAction::Read,
                PermissionAction::Create,
                PermissionAction::Update,
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
                PermissionAction::Export,
            ],
            self::ManageSubscription => [
                PermissionAction::Read,
                PermissionAction::Create,
                PermissionAction::Update,
            ],
            self::BranchSettings => [
                PermissionAction::Read,
                PermissionAction::Create,
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
