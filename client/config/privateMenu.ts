import type { LucideIcon } from 'lucide-vue-next';
import { LayoutDashboard, ShieldCheck, Building2, BookOpen } from 'lucide-vue-next';

export interface MenuItem {
    label: string;
    to: string;
    route?: string[];
    icon?: LucideIcon;
}

export const privateMenu: MenuItem[] = [
    {
        label: "Dashboard",
        to: "/app/owner/dashboard",
        icon: LayoutDashboard,
    },
    {
        label: "Verification",
        to: "/app/owner/verification",
        icon: ShieldCheck,
    },
    {
        label: "Branches",
        to: "/app/owner/branches",
        icon: Building2,
    },
    {
        label: "Plans",
        to: "/app/owner/plans",
        icon: BookOpen,
    },
];