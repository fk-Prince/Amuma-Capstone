import {
    LayoutDashboard,
    ClipboardList,
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
    BookOpen,
    MessageSquare
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
        icon: BookOpen,
        to: "/app/branches/[uuid]/bookings",
        modules: ["Bookings"],
    },
    {
        label: "Schedules",
        icon: CalendarCheck2,
        to: "/app/branches/[uuid]/schedules",
        modules: ["Schedules"],
    },
    {
        label: "Admissions",
        icon: ClipboardList,
        to: "/app/branches/[uuid]/admissions",
        modules: ["Admissions"],
    },
    {
        label: "Patients",
        icon: Users,
        to: "/app/branches/[uuid]/patients",
        modules: ["Patients"],
    },
    {
        label: "Messages",
        icon: MessageSquare,
        to: "/app/branches/[uuid]/messages",
    },
    {
        label: "Contracts",
        icon: Building2,
        to: "/app/branches/[uuid]/contracts",
        modules: ["Contracts"],
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
        to: "/app/branches/[uuid]/invoices",
        modules: ["Billing & Invoices"],
        plan: ["A", "B", "C"],
    },
    // {
    //     label: "Reports",
    //     icon: ClipboardMinus,
    //     to: "/app/branches/[uuid]/reports",
    //     modules: ["Reports"],
    // },
    {
        label: "Manage Branches",
        icon: Building2,
        to: "/app/branches/[uuid]/manage-branches",
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