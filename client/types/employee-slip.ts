export interface EmployeeSlipEmployee {
    employee_id: number;
    employee_code: string | null;
    uuid: string | null;
    full_name: string | null;
    birth_date: string | null;
    phone_number: string | null;
    role_name: string | null;
    assignment_type: string | null;
}

export interface EmployeeSlipBranch {
    name: string | null;
}

export interface EmployeeSlipAccess {
    email: string | null;
    default_password: string | null;
    module_count: number;
}

export interface EmployeeSlip {
    employee: EmployeeSlipEmployee;
    branch: EmployeeSlipBranch;
    access: EmployeeSlipAccess;
}
