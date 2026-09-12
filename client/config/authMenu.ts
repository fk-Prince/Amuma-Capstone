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
    group?: string;
}

export const authMenuList: MenuItems[] = [
    {
        label: "Dashboard",
        icon: LayoutDashboard,
        to: "/app/branches/[uuid]/dashboard",
        group: "Overview",
    },
    {
        label: "Bookings",
        icon: BookOpen,
        to: "/app/branches/[uuid]/bookings",
        modules: ["Bookings"],
        group: "Operations",
    },
    {
        label: "Schedules",
        icon: CalendarCheck2,
        to: "/app/branches/[uuid]/schedules",
        modules: ["Schedules"],
        group: "Operations",
    },
    {
        label: "Admissions",
        icon: ClipboardList,
        to: "/app/branches/[uuid]/admissions",
        modules: ["Admissions"],
        group: "Operations",
    },
    {
        label: "Patients",
        icon: Users,
        to: "/app/branches/[uuid]/patients",
        modules: ["Patients"],
        group: "Operations",
    },
    {
        label: "Rooms & Beds",
        icon: BedDouble,
        to: "/app/branches/[uuid]/rooms-beds",
        modules: ["Rooms & Beds"],
        group: "Operations",
    },
    {
        label: "Services",
        icon: Wrench,
        to: "/app/branches/[uuid]/services",
        modules: ["Services"],
        group: "Operations",
    },
    {
        label: "Messages",
        icon: MessageSquare,
        to: "/app/branches/[uuid]/messages",
        group: "Communication",
    },
    {
        label: "Contracts",
        icon: Building2,
        to: "/app/branches/[uuid]/contracts",
        modules: ["Contracts"],
        group: "Business",
    },
    {
        label: "Employee Management",
        icon: UserCog,
        to: "/app/branches/[uuid]/employees",
        modules: ["Employee Management"],
        group: "Business",
    },
    {
        label: "Billing & Invoices",
        icon: HandCoins,
        to: "/app/branches/[uuid]/invoices",
        modules: ["Billing & Invoices"],
        plan: ["A", "B", "C"],
        group: "Business",
    },
    // {
    //     label: "Reports",
    //     icon: ClipboardMinus,
    //     to: "/app/branches/[uuid]/reports",
    //     modules: ["Reports"],
    //     group: "Business",
    // },
    {
        label: "Manage Branches",
        icon: Building2,
        to: "/app/branches/[uuid]/manage-branches",
        modules: ["Manage Branches"],
        group: "Business",
    },
    {
        label: "Branch Settings",
        icon: Settings,
        to: "/app/branches/[uuid]/settings",
        modules: ["Branch Settings"],
        group: "Business",
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
        label: "AMUMA Verification",
        icon: CreditCard,
        to: "/app/owner/verification",
        modules: ["AMUMA Subscription"],
    },
    {
        label: "AMUMA Branches",
        icon: Building2,
        to: "/app/owner/branches",
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