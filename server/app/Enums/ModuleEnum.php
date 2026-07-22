<?php

namespace App\Enums;

enum ModuleEnum: string
{
    case Homecare = 'Homecare';
    case Admissions = 'Admissions';
    case Bookings = 'Bookings';
    case Patients = 'Patients';
    case Schedules = 'Schedules';
    case Pricing = 'Pricing';
    case RoomsAndBeds = 'Rooms & Beds';
    case Services = 'Services';
    case EmployeeManagement = 'Employee Management';
    case BillingAndInvoices = 'Billing & Invoices';
    case Reports = 'Reports';
    case ManageBranches = 'Manage Branches';
    case BranchSettings = 'Branch Settings';
}
