import { Modules } from "~/types/module";

export const PermissionAction = {
    Read: "can_read",
    Create: "can_create",
    Update: "can_update",
    Export: "can_export",
    Assign: "can_assign",
    ForceDischarge: "can_force_discharge",
    Renew: "can_renew",
} as const;

export type PermissionActionKey =
    (typeof PermissionAction)[keyof typeof PermissionAction];

export const MODULE_ACTIONS: Record<Modules, PermissionActionKey[]> = {
    [Modules.Bookings]: [PermissionAction.Read, PermissionAction.Update],
    [Modules.Patients]: [
        PermissionAction.Read,
        PermissionAction.Create,
        PermissionAction.Update,
        PermissionAction.Export,
    ],
    [Modules.Schedules]: [
        PermissionAction.Read,
        PermissionAction.Update,
        PermissionAction.Assign,
    ],
    [Modules.Admissions]: [
        PermissionAction.Read,
        PermissionAction.Create,
        PermissionAction.Update,
        PermissionAction.ForceDischarge,
    ],
    [Modules.RoomsAndBeds]: [
        PermissionAction.Read,
        PermissionAction.Create,
        PermissionAction.Update,
    ],
    [Modules.Services]: [
        PermissionAction.Read,
        PermissionAction.Create,
        PermissionAction.Update,
        PermissionAction.Assign,
    ],
    [Modules.Contracts]: [
        PermissionAction.Read,
        PermissionAction.Create,
        PermissionAction.Update,
    ],
    [Modules.EmployeeManagement]: [
        PermissionAction.Read,
        PermissionAction.Create,
        PermissionAction.Update,
    ],
    [Modules.BillingAndInvoices]: [
        PermissionAction.Read,
        PermissionAction.Create,
        PermissionAction.Update,
        PermissionAction.Export,
    ],
    [Modules.ManageSubscription]: [
        PermissionAction.Read,
        PermissionAction.Create,
        PermissionAction.Update,
    ],
    [Modules.BranchSettings]: [
        PermissionAction.Read,
        PermissionAction.Create,
        PermissionAction.Update,
        PermissionAction.Renew,
    ],
};

export const MODULE_DESCRIPTIONS: Record<Modules, string> = {
    [Modules.Bookings]:
        "Review booking requests from families and accept or decline them, turning an approved request into a visit or admission.",
    [Modules.Schedules]:
        "View the visit calendar, assign nurses and caregivers to visits, and move, edit or cancel them.",
    [Modules.Admissions]:
        "Set up admissions, place residents in their beds to start billing, and discharge them once settled or, when permitted, with a balance outstanding.",
    [Modules.Patients]:
        "Open resident profiles and record diagnoses, assessments, activities, ADL and medication entries, correct what is on file, and export records.",
    [Modules.Contracts]:
        "Draw up and maintain accommodation contracts, including their rates and billing cycles.",
    [Modules.RoomsAndBeds]:
        "Maintain the branch's rooms and beds, their types and availability, and see what is occupied or free.",
    [Modules.Services]:
        "Maintain the branch's service catalogue and prices, and set which staff are qualified to carry out each service.",
    [Modules.EmployeeManagement]:
        "Add staff to the branch, issue their accounts, and manage their details, roles, permissions and status.",
    [Modules.BillingAndInvoices]:
        "Issue invoices, record payments and receipts, adjust or void invoices, decide on families' requests to withdraw credit, and print balance statements.",
    [Modules.ManageSubscription]:
        "See the subscription status of the branches on this account, subscribe new branches, and resubmit rejected ones.",
    [Modules.BranchSettings]:
        "View and change branch details such as address, hours, currency and policies, and upload branch photos.",
};

export const ACTION_LABELS: Record<PermissionActionKey, string> = {
    [PermissionAction.Read]: "Read",
    [PermissionAction.Create]: "Create",
    [PermissionAction.Update]: "Update",
    [PermissionAction.Export]: "Export",
    [PermissionAction.Assign]: "Assign",
    [PermissionAction.ForceDischarge]: "Force discharge",
    [PermissionAction.Renew]: "Renew",
};

type ActionCopy = Partial<Record<PermissionActionKey, string>>;

const ACTION_DESCRIPTIONS: Record<Modules, ActionCopy> = {
    [Modules.Bookings]: {
        [PermissionAction.Read]:
            "See booking requests from families, including the service, dates and who sent them.",
        [PermissionAction.Update]:
            "Accept a request, which turns it into a schedule or admission and bills the family, or decline it so it never becomes one.",
    },
    [Modules.Patients]: {
        [PermissionAction.Read]:
            "Open a resident's profile and their records: diagnoses, assessments, medications, ADL and care history.",
        [PermissionAction.Create]:
            "Add new records for a resident already at the branch, such as a diagnosis, an assessment, an activity, an ADL entry or a medication record.",
        [PermissionAction.Update]:
            "Correct records already on file and edit the resident's own profile details.",
        [PermissionAction.Export]:
            "Download a resident's records as a file to share outside the system.",
    },
    [Modules.Schedules]: {
        [PermissionAction.Read]:
            "View the visit calendar and the details of each visit.",
        [PermissionAction.Update]:
            "Move, edit or cancel visits and the services attached to them.",
        [PermissionAction.Assign]:
            "Choose which nurse or caregiver handles a visit, and reassign it later.",
    },
    [Modules.Admissions]: {
        [PermissionAction.Read]:
            "View admissions, billing periods, room history and the admission timeline.",
        [PermissionAction.Create]:
            "Start an admission for a resident: contract, accommodation and dates.",
        [PermissionAction.Update]:
            "Change an admission's details such as its rate, room or billing period, admit the resident into their bed to start billing, and discharge them once everything owed is settled.",
        [PermissionAction.ForceDischarge]:
            "Discharge despite an unpaid balance or an unfinished billing period.",
    },
    [Modules.RoomsAndBeds]: {
        [PermissionAction.Read]:
            "See rooms, beds and what is occupied or free.",
        [PermissionAction.Create]: "Add rooms and beds to the branch.",
        [PermissionAction.Update]:
            "Edit a room or bed: type and availability.",
    },
    [Modules.Services]: {
        [PermissionAction.Read]:
            "See the services this branch offers and their prices.",
        [PermissionAction.Create]: "Add a service to the branch catalogue.",
        [PermissionAction.Update]:
            "Edit a service's details, price or availability.",
        [PermissionAction.Assign]:
            "Decide which staff are qualified to carry out each service.",
    },
    [Modules.Contracts]: {
        [PermissionAction.Read]:
            "View accommodation contracts and their rates and billing cycles.",
        [PermissionAction.Create]:
            "Draw up a new contract for a resident or accommodation type.",
        [PermissionAction.Update]:
            "Change a contract's terms, rate or billing cycle.",
    },
    [Modules.EmployeeManagement]: {
        [PermissionAction.Read]:
            "View the staff directory and each person's role and schedule.",
        [PermissionAction.Create]:
            "Add a staff member and issue their account.",
        [PermissionAction.Update]:
            "Edit a staff member's details, role, permissions and status.",
    },
    [Modules.BillingAndInvoices]: {
        [PermissionAction.Read]:
            "Open invoices, receipts and payment history, and see a resident's outstanding balance and credit on account.",
        [PermissionAction.Create]:
            "Record payments taken at the branch (which produces the receipt), take payment for a stay extension, pay out a resident's credit, and approve or decline a family's request to withdraw it.",
        [PermissionAction.Update]:
            "Adjust an invoice or issue a refund as credit on the resident's account, void an invoice, or write off what can't be collected.",
        [PermissionAction.Export]:
            "Print a patient's statement of the balances still to be paid.",
    },
    [Modules.ManageSubscription]: {
        [PermissionAction.Read]:
            "See the branches under this account and their status.",
        [PermissionAction.Create]:
            "Open a new branch, which starts a subscription for it.",
        [PermissionAction.Update]:
            "Fix and resubmit a rejected branch or agency for review, paying again when its subscription was refunded or is full.",
    },
    [Modules.BranchSettings]: {
        [PermissionAction.Read]:
            "View branch details: address, opening hours, currency and policies.",
        [PermissionAction.Create]: "Upload photos of the branch and its rooms.",
        [PermissionAction.Update]: "Change those branch details.",
        [PermissionAction.Renew]:
            "Renew the branch's subscription and pay for it.",
    },
};

export const ROLE_DEFAULT_PERMISSIONS: Record<
    string,
    Partial<Record<Modules, PermissionActionKey[]>>
> = {
    agency_owner: MODULE_ACTIONS,
    branch_manager: MODULE_ACTIONS,
    admission: {
        [Modules.Bookings]: MODULE_ACTIONS[Modules.Bookings],
        [Modules.Patients]: [
            PermissionAction.Read,
            PermissionAction.Create,
            PermissionAction.Update,
            PermissionAction.Export,
        ],
        [Modules.Schedules]: MODULE_ACTIONS[Modules.Schedules],
        [Modules.Admissions]: MODULE_ACTIONS[Modules.Admissions].filter(
            (action) => action !== PermissionAction.ForceDischarge,
        ),
        [Modules.RoomsAndBeds]: [PermissionAction.Read],
        [Modules.Services]: [PermissionAction.Read],
        [Modules.Contracts]: [PermissionAction.Read],
        [Modules.EmployeeManagement]: [PermissionAction.Read],
    },
    cashier: {
        [Modules.BillingAndInvoices]:
            MODULE_ACTIONS[Modules.BillingAndInvoices],
    },
    nurse: {
        [Modules.Patients]: MODULE_ACTIONS[Modules.Patients].filter(
            (action) => action !== PermissionAction.Export,
        ),
        [Modules.Schedules]: [PermissionAction.Read, PermissionAction.Update],
    },
    caregiver: {
        [Modules.Patients]: MODULE_ACTIONS[Modules.Patients].filter(
            (action) => action !== PermissionAction.Export,
        ),
        [Modules.Schedules]: [PermissionAction.Read, PermissionAction.Update],
    },
};

export function moduleActions(moduleName: string): PermissionActionKey[] {
    return MODULE_ACTIONS[moduleName as Modules] ?? [];
}

export function moduleDescription(moduleName: string): string {
    return (
        MODULE_DESCRIPTIONS[moduleName as Modules] ??
        "Manage access to this module."
    );
}

export function actionLabel(action: PermissionActionKey): string {
    return ACTION_LABELS[action] ?? action;
}

export function actionDescription(
    moduleName: string,
    action: PermissionActionKey,
): string {
    return ACTION_DESCRIPTIONS[moduleName as Modules]?.[action] ?? "";
}
