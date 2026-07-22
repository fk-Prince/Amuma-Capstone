

export interface Module {
    module_id: number,
    module_name: string,
    has_approve: string,
    has_create: string,
    has_update: string,
    has_read: string,
}

export enum Modules {
    Admissions = "Admissions",
    HomecareBookings = "Homecare Bookings",
    Patients = "Patients",
    Schedules = "Schedules",
    Pricing = "Pricing",
    RoomsAndBeds = "Rooms & Beds",
    Services = "Services",
    EmployeeManagement = "Employee Management",
    BillingAndInvoices = "Billing & Invoices",
    Reports = "Reports",
    BranchSettings = "Branch Settings",
    ManageBranches = "Manage Branches",
}