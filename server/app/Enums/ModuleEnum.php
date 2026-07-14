<?php

namespace App\Enums;

enum ModuleEnum: string
{
    case Admissions = 'Admissions';
    case HomecareBookings = 'Homecare Bookings';
    case Patients = 'Patients';
    case Schedules = 'Schedules';
    case FacilityManagement = 'Facility Management';
    case RoomsAndBeds = 'Rooms & Beds';
    case Services = 'Services';
    case EmployeeManagement = 'Employee Management';
    case BillingAndInvoices = 'Billing & Invoices';
    case Reports = 'Reports';
    case BranchSettings = 'Branch Settings';
    case ManageBranches = 'Manage Branches';
}
