import type { LucideIcon } from "lucide-vue-next";
import {
    LayoutDashboard,
    CalendarCheck,
    ClipboardCheck,
    BedDouble,
    HeartPulse,
    CalendarClock,
    ClipboardList,
    Users,
    FileText,
    Wallet,
    MessageSquare,
    BarChart3,
    Building2,
    Settings,
} from "lucide-vue-next";

export interface BranchMenuItem {
    label: string;
    to: string;
    icon: LucideIcon;
    group?: string;
}


const items: { label: string; path: string; icon: LucideIcon; group?: string }[] = [
    { label: "Dashboard", path: "dashboard", icon: LayoutDashboard },

    { label: "Bookings", path: "bookings", icon: CalendarCheck, group: "Care Operations" },
    { label: "Admissions", path: "admissions", icon: ClipboardCheck, group: "Care Operations" },
    { label: "Rooms & Beds", path: "rooms-beds", icon: BedDouble, group: "Care Operations" },
    { label: "Patients", path: "patients", icon: HeartPulse, group: "Care Operations" },
    { label: "Schedules", path: "schedules", icon: CalendarClock, group: "Care Operations" },
    { label: "Services", path: "services", icon: ClipboardList, group: "Care Operations" },

    { label: "Employees", path: "employees", icon: Users, group: "Business" },
    { label: "Contracts", path: "contracts", icon: FileText, group: "Business" },
    { label: "Invoices", path: "invoices", icon: Wallet, group: "Business" },
    { label: "Reports", path: "reports", icon: BarChart3, group: "Business" },
    { label: "Manage Branches", path: "manage-branches", icon: Building2, group: "Business" },

    { label: "Messages", path: "messages", icon: MessageSquare, group: "Communication" },

    { label: "Settings", path: "settings", icon: Settings, group: "Settings" },
];

export function buildBranchMenu(uuid: string | null | undefined): BranchMenuItem[] {
    const base = uuid ? `/app/branches/${uuid}` : "/app/branches";

    return items.map((item) => ({
        label: item.label,
        to: `${base}/${item.path}`,
        icon: item.icon,
        group: item.group,
    }));
}
