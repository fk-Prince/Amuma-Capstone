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
                    $module->value => $module->actionColumns(),
                ])
                ->all(),

            self::BranchManager => collect(ModuleEnum::cases())
                ->mapWithKeys(fn(ModuleEnum $module) => [
                    $module->value => match ($module) {
                        ModuleEnum::Admissions => [
                            PermissionAction::Read->value,
                            PermissionAction::ForceDischarge->value,
                        ],
                        ModuleEnum::Patients => [
                            PermissionAction::Read->value,
                            PermissionAction::Export->value,
                        ],
                        ModuleEnum::Contracts,
                        ModuleEnum::RoomsAndBeds,
                        ModuleEnum::Services,
                        ModuleEnum::EmployeeManagement,
                        ModuleEnum::BillingAndInvoices,
                        ModuleEnum::BranchSettings => $module->actionColumns(),
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
                ModuleEnum::Schedules->value => ModuleEnum::Schedules->actionColumns(),
                ModuleEnum::Admissions->value => array_values(array_diff(
                    ModuleEnum::Admissions->actionColumns(),
                    [PermissionAction::ForceDischarge->value]
                )),
                ModuleEnum::RoomsAndBeds->value => [PermissionAction::Read->value],
                ModuleEnum::Services->value => [PermissionAction::Read->value],
                ModuleEnum::Contracts->value => [PermissionAction::Read->value],
                ModuleEnum::EmployeeManagement->value => [PermissionAction::Read->value],
            ],

            self::Cashier => [
                ModuleEnum::BillingAndInvoices->value => ModuleEnum::BillingAndInvoices->actionColumns(),
            ],

            self::Nurse, self::Caregiver => [
                ModuleEnum::Patients->value => array_values(array_diff(
                    ModuleEnum::Patients->actionColumns(),
                    [PermissionAction::Export->value]
                )),
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
