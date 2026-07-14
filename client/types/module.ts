

export interface Module {
    module_id: number,
    module_name: string
}

export enum Modules {
    Admissions = "Admissions",
    HomecareBookings = "Homecare Bookings",
    Patients = "Patients",
    Schedules = "Schedules",
    FacilityManagement = "Facility Management",
    RoomsAndBeds = "Rooms & Beds",
    Services = "Services",
    EmployeeManagement = "Employee Management",
    BillingAndInvoices = "Billing & Invoices",
    Reports = "Reports",
    BranchSettings = "Branch Settings",
    ManageBranches = "Manage Branches",
}