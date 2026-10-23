<?php

namespace App\Enums;

enum RoleEnum: string
{
    case AgencyOwner = 'agency_owner';
    case BranchManager = 'branch_manager';
    case Admission = 'admission';
    case Cashier = 'cashier';
    case Nurse = 'nurse';
    case Caregiver = 'caregiver';

    public function permissions(): array
    {
        return match ($this) {
            self::AgencyOwner => collect(ModuleEnum::cases())
                ->mapWithKeys(fn(ModuleEnum $module) => [
                    $module->value => PermissionAction::columns(),
                ])
                ->all(),

            self::BranchManager => collect(ModuleEnum::cases())
                ->mapWithKeys(fn(ModuleEnum $module) => [
                    $module->value => match ($module) {
                        ModuleEnum::Patients, ModuleEnum::Schedules => [
                            PermissionAction::Read->value,
                            PermissionAction::Export->value,
                        ],
                        ModuleEnum::BillingAndInvoices => [
                            PermissionAction::Read->value,
                            PermissionAction::Export->value,
                        ],
                        ModuleEnum::Contracts,
                        ModuleEnum::RoomsAndBeds,
                        ModuleEnum::Services,
                        ModuleEnum::EmployeeManagement,
                        ModuleEnum::BranchSettings,
                        ModuleEnum::AuditLogs => $module->actionColumns(),
                        default => [PermissionAction::Read->value],
                    },
                ])
                ->all(),

            self::Admission => [
                ModuleEnum::Bookings->value => ModuleEnum::Bookings->actionColumns(),
                ModuleEnum::Patients->value => [
                    PermissionAction::Read->value,
                    PermissionAction::Create->value,
                    PermissionAction::Update->value,
                    PermissionAction::Export->value,
                ],
                ModuleEnum::Medications->value => ModuleEnum::Medications->actionColumns(),
                ModuleEnum::Schedules->value => ModuleEnum::Schedules->actionColumns(),
                ModuleEnum::Admissions->value => ModuleEnum::Admissions->actionColumns(),
                ModuleEnum::RoomsAndBeds->value => [PermissionAction::Read->value],
                ModuleEnum::Services->value => [PermissionAction::Read->value],
                ModuleEnum::Contracts->value => [PermissionAction::Read->value],
                ModuleEnum::EmployeeManagement->value => [PermissionAction::Read->value],
                ModuleEnum::Inquiries->value => ModuleEnum::Inquiries->actionColumns(),
            ],

            self::Cashier => [
                ModuleEnum::BillingAndInvoices->value => ModuleEnum::BillingAndInvoices->actionColumns(),
            ],

            self::Nurse, self::Caregiver => [
                ModuleEnum::Patients->value => array_values(array_diff(
                    ModuleEnum::Patients->actionColumns(),
                    [PermissionAction::Export->value]
                )),
                ModuleEnum::Medications->value => ModuleEnum::Medications->actionColumns(),
                ModuleEnum::Schedules->value => [
                    PermissionAction::Read->value,
                    PermissionAction::Update->value,
                ],
            ],
        };
    }

    public static function normalize(?string $role): ?self
    {
        return self::tryFrom(
            str_replace([' ', '-'], '_', strtolower(trim((string) $role)))
        );
    }

    public static function slug(?string $role): string
    {
        return self::normalize($role)?->value
            ?? str_replace([' ', '-'], '_', strtolower(trim((string) $role)));
    }

    public static function permissionsFor(?string $role): array
    {
        return self::normalize($role)?->permissions() ?? [];
    }
}
