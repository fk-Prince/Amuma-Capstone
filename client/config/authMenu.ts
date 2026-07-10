import {
    LayoutDashboard,
    ClipboardList,
    Stethoscope,
    Users,
    CalendarCheck2,
    Building2,
    BedDouble,
    Wrench,
    UserCog,
    HandCoins,
    ClipboardMinus,
    Settings,
    CreditCard,
} from 'lucide-vue-next';


export interface MenuItems {
    label: string;
    to: string;
    roles?: string[];
    route?: string[];
    icon?: any;
}

export const authMenuList: MenuItems[] = [
    {
        label: "Branch Dashboard",
        icon: LayoutDashboard,
        to: "/app/branches/[uuid]/dashboard",
        roles: ["branch_owner", "administrator", "admission_staff", "accounting", "caregiver", "nurses"],
    },
    {
        label: "Admissions",
        icon: ClipboardList,
        to: "/app/branches/[uuid]/admissions",
        roles: ["branch_owner", "administrator", "admission_staff"],
    },
    {
        label: "Homecare Bookings",
        icon: Stethoscope,
        to: "/app/branches/[uuid]/bookings",
        roles: ["branch_owner", "administrator", "admission_staff"],
    },
    {
        label: "Patients",
        icon: Users,
        to: "/app/branches/[uuid]/patients",
        roles: ["branch_owner", "administrator", "admission_staff", "caregiver", "nurses"],
    },
    {
        label: "Schedules",
        icon: CalendarCheck2,
        to: "/app/branches/[uuid]/schedules",
        roles: ["branch_owner", "administrator", "admission_staff", "caregiver", "nurses"],
    },
    {
        label: "Facility Management",
        icon: Building2,
        to: "/app/branches/[uuid]/facility",
        roles: ["branch_owner", "administrator"],
    },
    {
        label: "Rooms & Beds",
        icon: BedDouble,
        to: "/app/branches/[uuid]/rooms-beds",
        roles: ["branch_owner", "administrator"],
    },
    {
        label: "Services",
        icon: Wrench,
        to: "/app/branches/[uuid]/services",
        roles: ["branch_owner", "administrator"],
    },
    {
        label: "Staff Management",
        icon: UserCog,
        to: "/app/branches/[uuid]/staffs",
        roles: ["branch_owner", "administrator"],
    },
    {
        label: "Billing & Invoices",
        icon: HandCoins,
        to: "/app/branches/[uuid]/billing",
        roles: ["branch_owner", "administrator", "accounting"],
    },
    {
        label: "Reports",
        icon: ClipboardMinus,
        to: "/app/branches/[uuid]/reports",
        roles: ["branch_owner"],
    },
    {
        label: "Branch Settings",
        icon: Settings,
        to: "/app/branches/[uuid]/settings",
        roles: ["branch_owner"],
    },
];

export const ownerMenuList: MenuItems[] = [
    {
        label: "AMUMA Dashboard",
        icon: LayoutDashboard,
        to: "/app/owner/dashboard",
        roles: ["owner"],
    },
    {
        label: "AMUMA Subscription",
        icon: CreditCard,
        to: "/app/owner/subscription",
        roles: ["owner"],
    },
]

export const branchOwnerMenuLists: MenuItems[] = [
    {
        label: "Branch Owner Dashboard",
        icon: LayoutDashboard,
        to: "/app/branches/dashboard",
        roles: ["branch_owner"],
    },
    {
        label: "Manage Branches",
        icon: Building2,
        to: "/app/branches/manage",
        roles: ["branch_owner"],
    },
]