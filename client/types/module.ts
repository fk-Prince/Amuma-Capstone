

export interface Module {
    module_id: number,
    module_name: string,
    has_approve: string,
    has_create: string,
    has_update: string,
    has_read: string,
    has_assign: string
    description: string,
}

export enum Modules {
    Bookings = "Bookings",
    Schedules = "Schedules",
    Admissions = "Admissions",
    Patients = "Patients",
    Contracts = "Contracts",
    RoomsAndBeds = "Rooms & Beds",
    Services = "Services",
    EmployeeManagement = "Employee Management",
    BillingAndInvoices = "Billing & Invoices",
    BranchSettings = "Branch Settings",
    ManageBranches = "Manage Branches",
}