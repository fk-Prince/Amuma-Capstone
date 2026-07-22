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
    modules?: string[];
    route?: string[];
    icon?: any;
    plan?: string[];
}

export const authMenuList: MenuItems[] = [
    {
        label: "Dashboard",
        icon: LayoutDashboard,
        to: "/app/branches/[uuid]/dashboard",
    },
    {
        label: "Bookings",
        icon: LayoutDashboard,
        to: "/app/branches/[uuid]/bookings",
        modules: ["Bookings"],
    },
    {
        label: "Admissions",
        icon: ClipboardList,
        to: "/app/branches/[uuid]/admissions",
        modules: ["Admissions"],
    },
    {
        label: "Homecare Services",
        icon: Stethoscope,
        to: "/app/branches/[uuid]/bookings",
        modules: ["Homecare"],
    },
    {
        label: "Patients",
        icon: Users,
        to: "/app/branches/[uuid]/patients",
        modules: ["Patients"],
    },
    {
        label: "Schedules",
        icon: CalendarCheck2,
        to: "/app/branches/[uuid]/schedules",
        modules: ["Schedules"],
    },
    {
        label: "Pricing",
        icon: Building2,
        to: "/app/branches/[uuid]/pricing",
        modules: ["Pricing"],
    },
    {
        label: "Rooms & Beds",
        icon: BedDouble,
        to: "/app/branches/[uuid]/rooms-beds",
        modules: ["Rooms & Beds"],
    },
    {
        label: "Services",
        icon: Wrench,
        to: "/app/branches/[uuid]/services",
        modules: ["Services"],
    },
    {
        label: "Employee Management",
        icon: UserCog,
        to: "/app/branches/[uuid]/employees",
        modules: ["Employee Management"],
    },
    {
        label: "Billing & Invoices",
        icon: HandCoins,
        to: "/app/branches/[uuid]/billing",
        modules: ["Billing & Invoices"],
        plan: ["A", "B"],
    },
    {
        label: "Reports",
        icon: ClipboardMinus,
        to: "/app/branches/[uuid]/reports",
        modules: ["Reports"],
    },
    {
        label: "Manage Branches",
        icon: Building2,
        to: "/app/branches/[uuid]/dashboard",
        modules: ["Manage Branches"],
    },
    {
        label: "Branch Settings",
        icon: Settings,
        to: "/app/branches/[uuid]/settings",
        modules: ["Branch Settings"],
    },
];

export const ownerMenuList: MenuItems[] = [
    {
        label: "AMUMA Dashboard",
        icon: LayoutDashboard,
        to: "/app/owner/dashboard",
        modules: ["AMUMA Dashboard"],
    },
    {
        label: "AMUMA Subscription",
        icon: CreditCard,
        to: "/app/owner/subscription",
        modules: ["AMUMA Subscription"],
    },
];

export const branchOwnerMenuLists: MenuItems[] = [
    {
        label: "Branch Owner Dashboard",
        icon: LayoutDashboard,
        to: "/app/branches/dashboard",
        modules: ["Branch Owner Dashboard"],
    },
    {
        label: "Manage Branches",
        icon: Building2,
        to: "/app/branches/manage",
        modules: ["Manage Branches"],
    },
];