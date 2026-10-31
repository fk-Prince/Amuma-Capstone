<?php

namespace App\Enums;

enum ModuleEnum: string
{
    case Bookings = 'Bookings';
    case Schedules = 'Schedules';
    case Medications = 'Medications';
    case Admissions = 'Admissions';
    case Patients = 'Patients';
    case Contracts = 'Contracts';
    case RoomsAndBeds = 'Rooms & Beds';
    case Services = 'Services';
    case EmployeeManagement = 'Employee Management';
    case BillingAndInvoices = 'Billing & Invoices';
    case BranchSettings = 'Branch Settings';
    case ManageSubscription = 'Manage Subscription';
    case Inquiries = 'Inquiries';
    case AuditLogs = 'Audit Logs';
    case Reports = 'Reports';

    public function actions(): array
    {
        return match ($this) {
            self::Bookings => [
                PermissionAction::Read,
                PermissionAction::Create,
                PermissionAction::Review,
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
                PermissionAction::Export,
            ],
            self::Admissions, self::Medications => [
                PermissionAction::Read,
                PermissionAction::Create,
                PermissionAction::Update,
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
            ],
            self::Inquiries => [
                PermissionAction::Read,
                PermissionAction::Update,
            ],
            self::AuditLogs => [
                PermissionAction::Read,
            ],
            self::Reports => [
                PermissionAction::Read,
                PermissionAction::Export,
            ],
        };
    }

    public function actionColumns(): array
    {
        return array_map(fn(PermissionAction $action) => $action->value, $this->actions());
    }
}
