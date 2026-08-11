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
}
